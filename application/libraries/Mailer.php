<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Vortex Precision - Mailer.
 *
 * Sends transactional email with idempotency via email_logs.dedupe_key.
 * Transport order: dashboard/.env SMTP, Resend HTTP API, then PHP mail().
 */
class Mailer
{
    protected $CI;
    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
        
        $this->CI->load->helper('security_helper');
    }

    /**
     * Send a templated email with deduplication.
     *
     * @param string $to
     * @param string $subject
     * @param string $html
     * @param string $template  Logical template name, used in dedupe key.
     * @param string|null $dedupeKey  Explicit dedupe key, otherwise auto from template+to+quoteId.
     * @param array  $context  Metadata stored in email_logs (e.g. relatedQuoteId).
     * @return array ['status' => 'SENT'|'FAILED'|'DUPLICATE', 'log_id' => ...]
     */
    public function send($to, $subject, $html, $template = 'generic', $dedupeKey = null, array $context = [])
    {
        $to = strtolower(trim((string) $to));
        $quoteId = $context['quoteId'] ?? ($context['relatedQuoteId'] ?? null);
        $dedupeKey = $dedupeKey ?: ($template . ':' . $to . ($quoteId ? ':' . $quoteId : ''));

        // Check existing
        $existing = $this->CI->db->get_where('email_logs', ['dedupeKey' => $dedupeKey])->row_array();
        if ($existing) {
            if ($existing['status'] === EMAIL_SENT) {
                return ['status' => 'DUPLICATE', 'log_id' => $existing['id']];
            }
            if ($existing['status'] === EMAIL_PENDING) {
                // try again
            }
            if ($existing['status'] === EMAIL_FAILED) {
                $this->CI->db->update('email_logs', [
                    'status'     => EMAIL_RETRYING,
                    'retryCount' => $existing['retryCount'] + 1,
                ], ['id' => $existing['id']]);
            }
        }

        // Insert/update log row
        $now = date('Y-m-d H:i:s');
        $logRow = [
            'to'             => $to,
            'subject'        => $subject,
            'template'       => $template,
            'status'         => EMAIL_PENDING,
            'dedupeKey'      => $dedupeKey,
            'retryCount'     => $existing ? ($existing['retryCount'] + 1) : 0,
            'relatedQuoteId' => $quoteId,
            'createdAt'      => $existing['createdAt'] ?? $now,
        ];

        if ($existing) {
            $this->CI->db->update('email_logs', $logRow, ['id' => $existing['id']]);
            $logId = $existing['id'];
        } else {
            $logRow['id'] = MY_Model::uuid();
            $this->CI->db->insert('email_logs', $logRow);
            $logId = $logRow['id'];
        }

        try {
            $ok = $this->dispatch($to, $subject, $html, $providerId, $errorMessage);
        } catch (Exception $e) {
            $ok = false;
            $providerId = null;
            $errorMessage = get_class($e) . ': ' . $e->getMessage();
            log_message('error', 'Mailer dispatch exception: ' . $errorMessage);
        }
        $status = $ok ? EMAIL_SENT : EMAIL_FAILED;
        $this->CI->db->update('email_logs', [
            'status'       => $status,
            'providerId'   => $providerId,
            'sentAt'       => $ok ? $now : null,
            'errorMessage' => $ok ? null : $errorMessage,
        ], ['id' => $logId]);

        $this->CI->audit->log($ok ? AUDIT_EMAIL : AUDIT_EMAIL_FAILED, 'email', $logId, [
            'to' => $to, 'template' => $template, 'status' => $status,
            'error' => $ok ? null : $errorMessage,
        ]);

        if (!$ok) {
            $this->notify_staff_email_failed($to, $subject, $errorMessage);
        }

        return ['status' => $status, 'log_id' => $logId];
    }

    /**
     * Surface failed outgoing mail to staff. Without this, a broken transport
     * is invisible: the page says "thank you", the database says FAILED, and
     * nobody ever finds out.
     * Rate-guarded: at most one unread warning per hour for the whole site.
     */
    private function notify_staff_email_failed($to, $subject, $errorMessage)
    {
        try {
            if (!$this->CI->db->table_exists('notifications') || !$this->CI->db->table_exists('users')) return;

            $recentUnread = $this->CI->db
                ->where('type', 'email_failed')
                ->where('read', 0)
                ->where('createdAt >=', date('Y-m-d H:i:s', time() - 3600))
                ->count_all_results('notifications');
            if ($recentUnread > 0) return;

            $staff = $this->CI->db
                ->where_in('role', [ROLE_SUPER_ADMIN, ROLE_ADMIN])
                ->where('isActive', 1)
                ->get('users')->result_array();

            $transport = $this->describe_transport();
            $now = date('Y-m-d H:i:s');
            foreach ($staff as $u) {
                $this->CI->db->insert('notifications', [
                    'id'        => MY_Model::uuid(),
                    'userId'    => $u['id'],
                    'type'      => 'email_failed',
                    'title'     => 'Outgoing email is failing',
                    'message'   => 'Could not send "' . substr((string) $subject, 0, 80) . '" to ' . $to
                        . ' via ' . $transport['transport'] . '. '
                        . substr((string) $errorMessage, 0, 200)
                        . ' Check Admin -> Dashboard for email health, or run: php install/test-mail.php',
                    'data'      => json_encode(['to' => $to, 'error' => substr((string) $errorMessage, 0, 300)]),
                    'read'      => 0,
                    'createdAt' => $now,
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Mailer: could not create email_failed notification - ' . $e->getMessage());
        }
    }

    /**
     * Email health snapshot for the admin dashboard.
     * @return array{transport:array, sent_7d:int, failed_7d:int, last_error:?string, last_error_at:?string}
     */
    /**
     * Sender identity: dashboard-managed (Settings → System → Email) with the
     * environment/config value as the fallback.
     */
    public function sender($key)
    {
        static $map = [
            'from_email' => 'mail_from_email',
            'from_name'  => 'mail_from_name',
            'reply_to'   => 'mail_reply_to',
        ];
        if (isset($map[$key]) && isset($this->CI->settings)) {
            $v = trim((string) $this->CI->settings->get($map[$key], ''));
            if ($v !== '') return $v;
        }
        return $this->CI->config->item($key);
    }

    /** SMTP configuration: dashboard-managed settings win, .env/config remains the fallback. */
    private function smtp_config($key)
    {
        static $map = [
            'host'   => ['setting' => 'smtp_host',   'config' => 'smtp_host',   'default' => ''],
            'port'   => ['setting' => 'smtp_port',   'config' => 'smtp_port',   'default' => '465'],
            'user'   => ['setting' => 'smtp_user',   'config' => 'smtp_user',   'default' => ''],
            'pass'   => ['setting' => 'smtp_pass',   'config' => 'smtp_pass',   'default' => ''],
            'crypto' => ['setting' => 'smtp_crypto', 'config' => 'smtp_crypto', 'default' => 'ssl'],
        ];
        if (!isset($map[$key])) return '';
        $def = $map[$key];
        $value = '';
        if (isset($this->CI->settings)) {
            $value = trim((string) $this->CI->settings->get($def['setting'], ''));
        }
        if ($value === '') {
            $value = trim((string) $this->CI->config->item($def['config']));
        }
        if ($value === '') $value = $def['default'];
        if ($key === 'crypto' && !in_array($value, ['ssl', 'tls'], true)) $value = '';
        return $value;
    }

    public function health()
    {
        $out = [
            'transport'    => $this->describe_transport(),
            'sent_7d'      => 0,
            'failed_7d'    => 0,
            'last_error'   => null,
            'last_error_at'=> null,
        ];
        try {
            if (!$this->CI->db->table_exists('email_logs')) return $out;
            $since = date('Y-m-d H:i:s', time() - 7 * 86400);
            $rows = $this->CI->db->select('status, COUNT(*) AS c')
                ->where('createdAt >=', $since)
                ->group_by('status')
                ->get('email_logs')->result_array();
            foreach ($rows as $r) {
                if ($r['status'] === EMAIL_SENT)   $out['sent_7d']   = (int) $r['c'];
                if ($r['status'] === EMAIL_FAILED) $out['failed_7d'] = (int) $r['c'];
            }
            $last = $this->CI->db->where('status', EMAIL_FAILED)
                ->order_by('createdAt', 'DESC')->limit(1)
                ->get('email_logs')->row_array();
            if ($last) {
                $out['last_error']    = $last['errorMessage'];
                $out['last_error_at'] = $last['createdAt'];
            }
        } catch (\Throwable $e) {
            log_message('error', 'Mailer::health failed - ' . $e->getMessage());
        }
        return $out;
    }

    /**
     * Which transport WOULD handle the next send. Mirrors dispatch().
     * @return array{transport:string, reason:string, misconfigured:bool}
     */
    public function describe_transport()
    {
        $smtpHost = $this->smtp_config('host');
        $smtpPass = $this->smtp_config('pass');
        if (!empty($smtpHost) && !empty($smtpPass)) {
            return ['transport' => 'smtp', 'reason' => 'SMTP via ' . $smtpHost, 'misconfigured' => false];
        }
        if (!empty($this->CI->config->item('resend_api_key'))) {
            $r = ['transport' => 'resend', 'reason' => 'Resend HTTP API', 'misconfigured' => false];
            if (!empty($smtpHost)) {
                $r['reason'] .= ' (SMTP host is set but SMTP password is empty — SMTP is skipped. Add the mailbox password in Admin → Settings → System or clear the SMTP host.)';
            }
            return $r;
        }
        $r = ['transport' => 'mail', 'reason' => 'PHP mail() fallback (shared hosts often drop this mail as spam)', 'misconfigured' => true];
        if (!empty($smtpHost)) {
            $r['reason'] = 'SMTP host is set but SMTP password is empty, so mail falls back to PHP mail(). '
                . 'Add the mailbox password in Admin → Settings → System or run: php install/test-mail.php';
        }
        return $r;
    }

    private function dispatch($to, $subject, $html, &$providerId = null, &$errorMessage = '')
    {
        $providerId = null;
        $errorMessage = '';
        // 1) SMTP (cPanel email account) when both host and password are set.
        $smtpHost = $this->smtp_config('host');
        $smtpPass = $this->smtp_config('pass');
        if (!empty($smtpHost) && !empty($smtpPass)) {
            return $this->send_via_smtp($to, $subject, $html, $providerId, $errorMessage);
        }
        // Partially configured SMTP used to fail silently; make it loud in the log.
        if (!empty($smtpHost) && empty($smtpPass)) {
            log_message('error', 'Mailer: SMTP host is set but SMTP password is EMPTY - '
                . 'SMTP skipped. Add the mailbox password in Admin -> Settings -> System -> SMTP or clear the SMTP host. Run: php install/test-mail.php');
        }
        // 2) Resend HTTP API when an API key is present.
        $apiKey = $this->CI->config->item('resend_api_key');
        if (!empty($apiKey)) {
            return $this->send_via_resend($to, $subject, $html, $providerId, $errorMessage);
        }
        // 3) Fallback to PHP mail().
        return $this->send_via_mail($to, $subject, $html, $errorMessage);
    }

    private function send_via_mail($to, $subject, $html, &$errorMessage = '')
    {
        $fromEmail = $this->sender('from_email');
        $fromName  = $this->sender('from_name');
        $replyTo   = $this->sender('reply_to');
        $headers   = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=utf-8';
        $headers[] = 'From: ' . sprintf('%s <%s>', $fromName, $fromEmail);
        if ($replyTo) $headers[] = 'Reply-To: ' . $replyTo;
        $headers[] = 'X-Mailer: Vortex-Precision/CI3';
        // Set the envelope sender (-f): without it the MTA uses the hosting
        // account address (e.g. cpaneluser@server.host), which fails SPF for
        // our domain and is commonly discarded by recipients' servers.
        $fromEmail = trim((string) $fromEmail);
        $envelope = (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN' && $fromEmail !== '' && strpos($fromEmail, ' ') === false)
            ? '-f' . $fromEmail
            : null;
        $ok = $envelope !== null
            ? @mail($to, $subject, $html, implode("\r\n", $headers), $envelope)
            : @mail($to, $subject, $html, implode("\r\n", $headers));
        if (!$ok) {
            $errorMessage = 'PHP mail() returned false (sendmail_path may be unset/disabled)';
            log_message('error', 'Mailer: ' . $errorMessage . ' - to=' . $to);
        }
        return $ok;
    }

    private function send_via_resend($to, $subject, $html, &$providerId = null, &$errorMessage = '')
    {
        $apiKey = $this->CI->config->item('resend_api_key');
        $apiUrl = $this->CI->config->item('resend_api_url') ?: 'https://api.resend.com/emails';
        $fromEmail = $this->sender('from_email');
        $fromName  = $this->sender('from_name');
        $replyTo   = $this->sender('reply_to');
        $payload = [
            'from'    => ($fromName ? $fromName . ' <' . $fromEmail . '>' : $fromEmail),
            'to'      => [$to],
            'subject' => $subject,
            'html'    => $html,
        ];
        if ($replyTo) $payload['reply_to'] = $replyTo;
        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT        => 15,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($resp === false || $code < 200 || $code >= 300) {
            $errorMessage = 'Resend API error: ' . ($err ?: ('HTTP ' . $code))
                . ($resp !== false ? ' body=' . substr((string) $resp, 0, 300) : '');
            log_message('error', 'Mailer: ' . $errorMessage . ' - to=' . $to);
            return false;
        }
        $data = json_decode($resp, true);
        $providerId = $data['id'] ?? null;
        return true;
    }

    /**
     * Send via SMTP (cPanel / shared-hosting email accounts).
     * Configuration comes from Admin → Settings → System, falling back to .env.
     */
    private function send_via_smtp($to, $subject, $html, &$providerId = null, &$errorMessage = '')
    {
        $providerId = null;
        $errorMessage = '';
        $host   = $this->smtp_config('host');
        $port   = (int) $this->smtp_config('port');
        $user   = $this->smtp_config('user');
        $pass   = $this->smtp_config('pass');
        $crypto = $this->smtp_config('crypto') ?: 'ssl';

        $fromEmail = $this->sender('from_email');
        $fromName  = $this->sender('from_name');
        $replyTo   = $this->sender('reply_to');

        $this->CI->load->library('email');
        $this->CI->email->clear(TRUE);
        $this->CI->email->initialize([
            'protocol'     => 'smtp',
            'smtp_host'    => $host,
            'smtp_port'    => $port,
            'smtp_user'    => $user,
            'smtp_pass'    => $pass,
            'smtp_crypto'  => $crypto,
            'smtp_timeout' => 15,
            'mailtype'     => 'html',
            'charset'      => 'utf-8',
            'wordwrap'     => FALSE,
            'crlf'         => "\r\n",
            'newline'      => "\r\n",
        ]);
        $this->CI->email->from($fromEmail, $fromName);
        if ($replyTo) $this->CI->email->reply_to($replyTo);
        $this->CI->email->to($to);
        $this->CI->email->subject($subject);
        $this->CI->email->message($html);

        if (!$this->CI->email->send(FALSE)) {
            $errorMessage = 'SMTP send failed: ' . $this->CI->email->print_debugger(['headers']);
            log_message('error', 'Mailer: ' . $errorMessage . ' - to=' . $to);
            return FALSE;
        }
        $providerId = 'smtp:' . $host;
        return TRUE;
    }

    /* ---------- Templates ---------- */

    public function template_quote_submitted_admin($quote)
    {
        $vars = [
            'quoteNumber' => $quote['quoteNumber'] ?? $quote['quote_number'] ?? '',
            'companyName' => $quote['companyName'] ?? $quote['company_name'] ?? '',
            'contactPerson' => $quote['contactPerson'] ?? $quote['contact_person'] ?? '',
            'email' => $quote['email'] ?? '',
            'adminUrl' => base_url('admin/quotes/' . ($quote['id'] ?? '')),
        ];
        $html = $this->render_email('quote_submitted_admin', $vars);
        return ['subject' => '[Vortex] New RFQ ' . $vars['quoteNumber'] . ' from ' . $vars['companyName'], 'html' => $html];
    }

    public function template_quote_confirmation_customer($quote)
    {
        $vars = [
            'quoteNumber' => $quote['quoteNumber'] ?? $quote['quote_number'] ?? '',
            'firstName'   => $quote['firstName'] ?? '',
            'companyName' => $quote['companyName'] ?? '',
        ];
        $html = $this->render_email('quote_confirmation_customer', $vars);
        return ['subject' => 'We received your RFQ ' . $vars['quoteNumber'], 'html' => $html];
    }

    public function template_quote_status_update($quote, $oldStatus, $newStatus, $note = null)
    {
        $vars = [
            'quoteNumber' => $quote['quoteNumber'] ?? $quote['quote_number'] ?? '',
            'firstName'   => $quote['firstName'] ?? '',
            'companyName' => $quote['companyName'] ?? '',
            'oldStatus'   => $oldStatus,
            'newStatus'   => $newStatus,
            'note'        => $note,
        ];
        $html = $this->render_email('quote_status_update', $vars);
        return ['subject' => 'Your RFQ ' . $vars['quoteNumber'] . ' is now ' . $newStatus, 'html' => $html];
    }

    private function render_email($template, $vars)
    {
        $vars['siteName'] = $this->CI->config->item('site_name');
        $vars['siteUrl']  = base_url();
        $vars['year']     = date('Y');
        extract($vars, EXTR_SKIP);
        ob_start();
        include APPPATH . 'views/emails/' . $template . '.php';
        return ob_get_clean();
    }
}
