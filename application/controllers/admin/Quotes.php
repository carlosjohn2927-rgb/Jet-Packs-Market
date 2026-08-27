<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halyk Petroleum - admin Quotes controller.
 *
 * - List with filters + search + CSV export
 * - View single quote (items + history + activity)
 * - Update status (state machine + optimistic lock)
 * - Assign
 * - Internal note
 * - Generate HTML->PDF (lightweight, no Composer)
 */
class Quotes extends Admin_Controller
{
    /** Baseline permission for the controller. Method overrides below grant
     *  least-privilege access to the individual RFQ actions. */
    protected $required_permission = 'quotes.view';

    /** Per-action permission map (enforced server-side by Admin_Controller). */
    protected $method_permissions = [
        'index'             => 'quotes.view',
        'view'              => 'quotes.view',
        'status'            => 'quotes.update_status',
        'assign'            => 'quotes.assign',
        'note'              => 'quotes.manage',
        'pdf'               => 'quotes.generate_pdf',
        'send'              => 'quotes.generate_pdf',
        'export_csv'        => 'quotes.export',
        'delete'            => 'quotes.manage',
        'attachment_delete' => 'quotes.manage_attachments',
        'payment_request'   => 'quotes.manage',
        'payment_cancel'    => 'quotes.manage',
        'note'              => 'quotes.manage',
        'delete'            => 'quotes.manage',
        'add_item'          => 'quotes.manage',
        'update_item'       => 'quotes.manage',
        'delete_item'       => 'quotes.manage',
        'pricing'           => 'quotes.manage',
    ];


    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Quote_model', 'User_model', 'Payment_model']);
        $this->load->library(['mailer', 'stripe_gateway']);
        $this->load->helper(['form', 'url', 'security_helper', 'payment_helper']);
    }

    public function index()
    {
        $this->page_title = 'Quotes / RFQs';

        $status = $this->input->get('status');
        $assignee = $this->input->get('assignedTo');
        $search = trim((string) $this->input->get('q'));
        $page = max(1, (int) $this->input->get('page'));
        $per = 25;

        $where = [];
        if ($status)    $where['status'] = $status;
        if ($assignee)  $where['assignedTo'] = $assignee;

        // Full-text search across quote headers AND line items:
        // RFQ number, company, contact, email, phone, notes + part number /
        // part name / specification on requested parts.
        if ($search !== '') {
            $headerIds = $this->db->distinct()->select('id')
                ->group_start()
                    ->like('quoteNumber', $search)
                    ->or_like('companyName', $search)
                    ->or_like('contactPerson', $search)
                    ->or_like('email', $search)
                    ->or_like('phone', $search)
                    ->or_like('notes', $search)
                    ->or_like('internalNotes', $search)
                ->group_end()
                ->get('quotes')->result_array();
            $itemIds = $this->db->distinct()->select('quoteId')
                ->group_start()
                    ->like('productName', $search)
                    ->or_like('partNumber', $search)
                    ->or_like('specifications', $search)
                ->group_end()
                ->get('quote_items')->result_array();
            $idList = array_values(array_unique(array_merge(
                array_column($headerIds, 'id'),
                array_column($itemIds, 'quoteId')
            )));
            // Restrict the list (and its count) to the matched ids. The
            // where_in is applied by the model through the shared query
            // builder; an impossible sentinel id yields an empty result.
            if (empty($idList)) $idList = ['__no_match__'];
        }

        // Search filter is injected via a closure: Quote_model::list_rfqs()
        // builds its own query builder twice (count + list), so the where_in
        // must be (re)applied for each.
        $idList = isset($idList) ? $idList : [];
        $applySearch = $search !== '' ? function () use ($idList) {
            $this->db->where_in('id', $idList);
        } : null;

        $result = $this->Quote_model->list_rfqs(
            $where, $per, $page, $applySearch,
            ['createdAt' => 'DESC']
        );

        $queryString = http_build_query(array_filter(['status' => $status, 'assignedTo' => $assignee, 'q' => $search]));
        $this->render('admin/quotes/index', [
            'rows' => $result['rows'],
            'total' => $result['total'],
            'total_pages' => $result['total_pages'],
            'page' => $result['page'],
            'search' => $search,
            'status' => $status,
            'assignee' => $assignee,
            'staff' => $this->User_model->staff(),
            'base_url' => base_url('admin/quotes') . '?' . ($queryString ? $queryString . '&' : '') . 'page={page}',
            'export_url' => base_url('admin/quotes/export/csv') . ($queryString ? '?' . $queryString : ''),
        ]);
    }

    public function view($id = null)
    {
        if (!$id) show_404();
        $q = $this->Quote_model->find($id);
        if (!$q) show_404();
        $this->page_title = 'Quote ' . $q['quoteNumber'];

        $items       = $this->Quote_model->get_items($id);
        $history     = $this->Quote_model->get_status_history($id);
        $activity    = $this->Quote_model->get_activities($id);
        $attachments = $this->Quote_model->get_attachments($id);
        $payments    = $this->Payment_model->for_quote($id);
        $assignee    = $q['assignedTo'] ? $this->User_model->find($q['assignedTo']) : null;
        $customer    = $q['userId'] ? $this->User_model->find($q['userId']) : null;
        $staff       = $this->User_model->staff();

        $this->render('admin/quotes/view', [
            'quote' => $q,
            'items' => $items,
            'history' => $history,
            'activity' => $activity,
            'attachments' => $attachments,
            'payments' => $payments,
            'stripe' => $this->stripe_gateway->status(),
            'payment_currencies' => vp_payment_supported_currencies(),
            'assignee' => $assignee,
            'customer' => $customer,
            'staff' => $staff,
        ]);
    }

    /**
     * Delete a single attachment on a quote.
     */
    public function attachment_delete($quoteId = null, $attachmentId = null)
    {
        if (!$quoteId || !$attachmentId) show_404();
        $row = $this->db->get_where('quote_attachments', ['id' => $attachmentId, 'quoteId' => $quoteId])->row_array();
        if (!$row) show_404();
        $abs = FCPATH . $row['url'];
        if ($row['url'] && is_file($abs)) @unlink($abs);
        $this->db->delete('quote_attachments', ['id' => $attachmentId]);
        $this->audit->log(AUDIT_DELETE, 'quote_attachment', $attachmentId, ['quoteId' => $quoteId, 'filename' => $row['filename']]);
        $this->flash('success', 'Attachment removed.');
        redirect('admin/quotes/' . $quoteId);
    }

    /**
     * Create a one-time card payment request for an approved quote. The amount
     * is stored in integer minor units before any call to Stripe, and the email
     * carries an opaque local link rather than a reusable direct Checkout URL.
     */
    public function payment_request($id = null)
    {
        if (!$id || $this->input->method() !== 'post') show_404();
        $quote = $this->Quote_model->find($id);
        if (!$quote) show_404();

        $stripe = $this->stripe_gateway->status();
        if (empty($stripe['configured'])) {
            $this->flash('error', 'Stripe card payments are not configured. ' . ($stripe['message'] ?? ''));
            return redirect('admin/quotes/' . $id);
        }
        if ($quote['status'] !== QUOTE_APPROVED) {
            $this->flash('error', 'Approve this quote before requesting a card payment.');
            return redirect('admin/quotes/' . $id);
        }

        $amountMinor = vp_payment_minor_units($this->input->post('amount'));
        $currencyPosted = strtoupper(trim((string) $this->input->post('currency')));
        $currencies = vp_payment_supported_currencies();
        if (!isset($currencies[$currencyPosted])) {
            $this->flash('error', 'Select one of the supported checkout currencies.');
            return redirect('admin/quotes/' . $id);
        }
        $currency = $currencyPosted;
        $hours = (int) $this->input->post('expires_hours');
        $hours = max(1, min(24, $hours ?: (int) $stripe['ttl_hours']));
        if ($amountMinor === null) {
            $this->flash('error', 'Enter a positive amount with no more than two decimal places.');
            return redirect('admin/quotes/' . $id);
        }

        $expiresAt = date('Y-m-d H:i:s', time() + ($hours * 3600));
        $created = $this->Payment_model->create_request(
            $id,
            $amountMinor,
            $currency,
            $this->jet_auth->id(),
            (int) $this->input->post('version'),
            $expiresAt
        );
        if (empty($created['ok'])) {
            $this->flash('error', $created['error'] ?? 'Could not create the card payment request.');
            return redirect('admin/quotes/' . $id);
        }

        $payment = $created['payment'];
        $payment['customerToken'] = $created['customerToken'];
        $paymentUrl = base_url('pay/' . rawurlencode($payment['customerToken']));
        $tpl = $this->mailer->template_card_payment_request($quote, $payment, $paymentUrl);
        $email = $this->mailer->send(
            $quote['email'],
            $tpl['subject'],
            $tpl['html'],
            'card_payment_request',
            'card_payment_request:' . $payment['id'],
            ['quoteId' => $id]
        );

        $this->audit->log(AUDIT_PAYMENT_REQUESTED, 'payment', $payment['id'], [
            'quoteId' => $id,
            'quoteNumber' => $quote['quoteNumber'],
            'amountMinor' => $amountMinor,
            'currency' => $currency,
            'expiresAt' => $expiresAt,
            'emailStatus' => $email['status'] ?? null,
        ]);
        $this->notify(
            'payment_requested',
            'Card payment requested: ' . $quote['quoteNumber'],
            $quote['companyName'] . ' was sent a secure card-payment link for ' . vp_payment_format_minor($amountMinor, $currency) . '.',
            ['quoteId' => $id, 'paymentId' => $payment['id'], 'quoteNumber' => $quote['quoteNumber']],
            !empty($quote['assignedTo']) ? $quote['assignedTo'] : null
        );

        $message = 'Secure card-payment link created and emailed to ' . $quote['email'] . '.';
        if (($email['status'] ?? '') !== EMAIL_SENT && ($email['status'] ?? '') !== 'DUPLICATE') {
            $message .= ' The request was saved, but the email could not be sent; fix email delivery, then cancel and create a new request to issue a fresh secure link.';
        }
        $this->flash('success', $message);
        redirect('admin/quotes/' . $id);
    }

    /** Cancel a local payment request and expire its remote Checkout Session first. */
    public function payment_cancel($quoteId = null, $paymentId = null)
    {
        if (!$quoteId || !$paymentId || $this->input->method() !== 'post') show_404();
        $payment = $this->Payment_model->find($paymentId);
        if (!$payment || $payment['quoteId'] !== $quoteId) show_404();
        if ($payment['status'] === PAYMENT_PAID) {
            $this->flash('error', 'A paid card payment cannot be canceled.');
            return redirect('admin/quotes/' . $quoteId);
        }

        if ($payment['status'] === PAYMENT_OPEN && !empty($payment['stripeCheckoutSessionId'])) {
            $expired = $this->stripe_gateway->expire_checkout_session($payment['stripeCheckoutSessionId']);
            if (empty($expired['ok'])) {
                $this->flash('error', 'Stripe could not expire the open checkout. It was not canceled locally to avoid an accidental charge.');
                return redirect('admin/quotes/' . $quoteId);
            }
        }

        $result = $this->Payment_model->cancel($paymentId, $this->jet_auth->id());
        if (empty($result['ok'])) {
            $this->flash('error', $result['error'] ?? 'Could not cancel the payment request.');
            return redirect('admin/quotes/' . $quoteId);
        }
        $this->audit->log(AUDIT_UPDATE, 'payment', $paymentId, ['quoteId' => $quoteId, 'status' => PAYMENT_CANCELED]);
        $this->flash('success', 'Card-payment request canceled.');
        redirect('admin/quotes/' . $quoteId);
    }

    public function status($id = null)
    {
        if (!$id || $this->input->method() !== 'post') show_404();
        $q = $this->Quote_model->find($id);
        if (!$q) show_404();

        $toStatus = $this->input->post('status');
        $expectedVersion = (int) $this->input->post('version');
        $notes = $this->input->post('notes');
        $assignedTo = $this->input->post('assignedTo') ?: null;

        $res = $this->Quote_model->transition_status(
            $id, $toStatus, $this->jet_auth->id(), $assignedTo, $notes, $expectedVersion
        );

        if (!$res['ok']) {
            $this->flash('error', $res['error']);
            return redirect('admin/quotes/' . $id);
        }

        $this->audit->log(AUDIT_STATUS, 'quote', $id, ['from' => $res['from'], 'to' => $res['to']]);

        // Notify the assignee + all staff
        $recipients = [];
        if (!empty($res['quote']['assignedTo'])) $recipients[] = $res['quote']['assignedTo'];
        $recipientSet = array_unique(array_merge($recipients, [])); // staff broadcast happens via notify() with $userId=null
        $this->notify(
            'rfq_status',
            'Quote ' . $res['quote']['quoteNumber'] . ' -> ' . $res['to'],
            $res['quote']['companyName'] . ' was moved from ' . $res['from'] . ' to ' . $res['to'] . '.',
            ['quoteId' => $id, 'from' => $res['from'], 'to' => $res['to']],
            !empty($res['quote']['assignedTo']) ? $res['quote']['assignedTo'] : null
        );

        // Send status update email (idempotent per transition: retrying the
        // same change never double-sends, but each new transition does email).
        $tpl = $this->mailer->template_quote_status_update($res['quote'], $res['from'], $res['to'], $notes);
        $dedupeKey = 'quote_status_update:' . $res['quote']['email'] . ':' . $id . ':' . $res['from'] . '->' . $res['to'];
        $this->mailer->send($res['quote']['email'], $tpl['subject'], $tpl['html'], 'quote_status_update', $dedupeKey, ['quoteId' => $id]);
        $this->db->update('quotes', ['lastNotifiedAt' => date('Y-m-d H:i:s')], ['id' => $id]);

        $this->flash('success', 'Status updated to ' . $res['to'] . '.');
        redirect('admin/quotes/' . $id);
    }

    public function assign($id = null)
    {
        if (!$id || $this->input->method() !== 'post') show_404();
        $q = $this->Quote_model->find($id);
        if (!$q) show_404();

        $assignedTo = $this->input->post('assignedTo');
        $expectedVersion = (int) $this->input->post('version');
        $res = $this->Quote_model->assign($id, $assignedTo, $this->jet_auth->id(), $expectedVersion);
        if (!$res['ok']) {
            $this->flash('error', $res['error']);
        } else {
            $this->audit->log(AUDIT_ASSIGN, 'quote', $id, ['assignedTo' => $assignedTo]);

            // Notify the new assignee
            $q = $this->Quote_model->find($id);
            $assignee = $this->User_model->find($assignedTo);
            if ($assignee) {
                $this->notify(
                    'rfq_assigned',
                    'Quote ' . ($q['quoteNumber'] ?? '') . ' assigned to you',
                    $q['companyName'] . ' (' . $q['contactPerson'] . ') - ' . count($this->Quote_model->get_items($id)) . ' line item(s).',
                    ['quoteId' => $id, 'quoteNumber' => $q['quoteNumber'] ?? ''],
                    $assignee['id']
                );
            }
            $this->flash('success', 'Quote assigned.');
        }
        redirect('admin/quotes/' . $id);
    }

    public function note($id = null)
    {
        if (!$id || $this->input->method() !== 'post') show_404();
        $q = $this->Quote_model->find($id);
        if (!$q) show_404();
        $note = trim((string) $this->input->post('note'));
        if ($note === '') {
            $this->flash('error', 'Note cannot be empty.');
            return redirect('admin/quotes/' . $id);
        }
        $res = $this->Quote_model->add_internal_note($id, $note, $this->jet_auth->id(), (int) $this->input->post('version'));
        $this->flash($res['ok'] ? 'success' : 'error', $res['ok'] ? 'Internal note added.' : $res['error']);
        redirect('admin/quotes/' . $id);
    }

    public function delete($id = null)
    {
        if (!$this->jet_auth->has_role(ROLE_SUPER_ADMIN)) {
            show_error('Only Super Admin can delete quotes.', 403);
        }
        if (!$id) show_404();
        $q = $this->Quote_model->find($id);
        if (!$q) show_404();
        $this->Quote_model->delete($id);
        $this->audit->log(AUDIT_DELETE, 'quote', $id, ['quoteNumber' => $q['quoteNumber']]);
        $this->flash('success', 'Quote deleted.');
        redirect('admin/quotes');
    }

    public function pdf($id = null)
    {
        if (!$id) show_404();
        $this->require_permission('quotes.generate_pdf');
        $q = $this->Quote_model->find($id);
        if (!$q) show_404();
        $items = $this->Quote_model->get_items($id);
        $customer = $q['userId'] ? $this->User_model->find($q['userId']) : null;

        $site = vp_site('name', 'Halyk Petroleum');
        $tagline = vp_site('tagline', 'Aircraft Parts & Components Supply');
        $contact = [
            'email'   => vp_site('email', $this->config->item('contact_email')),
            'phone'   => vp_site('phone', $this->config->item('phone')),
            'address' => vp_site('address', $this->config->item('address')),
        ];
        $currency = $q['currency'] ?? 'USD';

        $this->load->library('pdf');

        // Columns: requested part, qty, condition/spec, unit price, line total.
        $columns = [
            ['label' => 'Requested Part',                 'width' => 4.6, 'align' => 'L'],
            ['label' => 'Qty',                             'width' => 0.8, 'align' => 'C'],
            ['label' => 'Condition / Specification',       'width' => 2.8, 'align' => 'L'],
            ['label' => 'Unit Price',                      'width' => 1.4, 'align' => 'R'],
            ['label' => 'Amount',                          'width' => 1.4, 'align' => 'R'],
        ];
        $rows = [];
        $computedTotal = 0.0;
        foreach ($items as $it) {
            $qty = (int) ($it['quantity'] ?? 1);
            $unit = $it['unitPrice'] !== null && $it['unitPrice'] !== '' ? (float) $it['unitPrice'] : null;
            $line = $it['total'] !== null && $it['total'] !== '' ? (float) $it['total'] : ($unit !== null ? $unit * $qty : null);
            if ($line !== null) $computedTotal += $line;

            $desc = $it['productName'] ?? 'Part';
            $pn = $it['partNumber'] ?? null;
            if ($pn) $desc = $pn . ' — ' . $desc;

            $spec = trim(($it['condition'] ?? '') . ' ' . ($it['specifications'] ?? ''));
            if ($spec === '') $spec = $it['manufacturer'] ?? '';

            $rows[] = [
                $desc,
                (string) $qty,
                $spec,
                $unit !== null ? vp_money($unit, $currency) : 'On quote',
                $line !== null ? vp_money($line, $currency) : '—',
            ];
        }

        $total = ($q['totalAmount'] !== null && $q['totalAmount'] !== '' && (float) $q['totalAmount'] > 0)
            ? (float) $q['totalAmount'] : $computedTotal;

        $st = vp_quote_status_label($q['status']);
        $metaR = [
            'QUOTATION  ' . $q['quoteNumber'],
            'Date: ' . date('Y-m-d', strtotime($q['createdAt'])),
            'Status: ' . $st['label'],
        ];
        if (!empty($q['deadline'])) {
            $metaR[] = 'Valid until: ' . date('Y-m-d', strtotime($q['deadline']));
        }
        $metaL = [
            $site . ' — ' . $tagline,
            $contact['address'],
            $contact['phone'] . '  ·  ' . $contact['email'],
        ];

        $billLines = array_filter([
            $q['companyName'],
            $q['contactPerson'],
            $q['email'],
            $q['phone'],
            $q['country'],
            $q['address'],
        ]);

        $notesBlocks = [];
        if (!empty($q['notes'])) {
            $notesBlocks[] = ['heading' => 'Customer notes', 'text' => $q['notes']];
        }
        $notesBlocks[] = ['heading' => 'Terms & notes', 'text' =>
            "Prices are in {$currency}, EXW Halyk Petroleum unless otherwise stated, and valid until the date shown. "
            . "Parts ship with FAA Form 8130-3 and/or EASA Form 1 release documentation and full traceability where applicable. "
            . "Lead times are confirmed on order placement; AOG requests are prioritised 24/7. "
            . "This quotation is for the aircraft parts and components listed above — Halyk Petroleum supplies parts, not complete aircraft."];

        $doc = [
            'company'      => $site,
            'tagline'      => $tagline,
            'company_info' => [$contact['address'], $contact['phone'], $contact['email']],
            'title'        => 'Quotation / RFQ',
            'meta_left'    => $metaL,
            'meta_right'   => $metaR,
            'bill_to'      => implode("\n", $billLines),
            'columns'      => $columns,
            'rows'         => $rows,
            'totals'       => [
                ['label' => 'Subtotal',          'value' => vp_money($total, $currency), 'bold' => false],
                ['label' => 'Total (' . $currency . ')', 'value' => vp_money($total, $currency), 'bold' => true],
            ],
            'notes_blocks' => $notesBlocks,
            'footer'       => $site . '  ·  ' . $contact['address'] . '  ·  ' . $contact['phone'] . '  ·  ' . $contact['email'],
        ];

        $binary = $this->pdf->build($doc);

        // Also persist the printable HTML alongside, for browser preview / fallback
        $html = $this->load->view('admin/quotes/pdf', [
            'quote' => $q, 'items' => $items, 'customer' => $customer,
            'site' => $site, 'contact' => $contact,
        ], TRUE);

        $dir = VP_UPLOAD_PATH . 'quotes/';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $pdfPath = $dir . $q['quoteNumber'] . '.pdf';
        $htmlPath = $dir . $q['quoteNumber'] . '.html';
        @file_put_contents($pdfPath, $binary);
        @file_put_contents($htmlPath, $html);

        $url = VP_UPLOAD_URL . 'quotes/' . $q['quoteNumber'] . '.pdf';
        $this->Quote_model->set_pdf_url($id, $url, $this->jet_auth->id());
        $this->audit->log(AUDIT_PDF, 'quote', $id, ['url' => $url, 'format' => 'pdf']);

        // Stream the PDF to the browser
        $this->output
            ->set_status_header(200)
            ->set_content_type('application/pdf')
            ->set_header('Content-Disposition: inline; filename="' . $q['quoteNumber'] . '.pdf"')
            ->set_header('Content-Length: ' . strlen($binary))
            ->set_output($binary);
    }

    public function export_csv()
    {
        // Honor the current list filters (status, assignee, search) so admins
        // export exactly what they see.
        $where = [];
        if ($this->input->get('status'))   $where['status'] = $this->input->get('status');
        if ($this->input->get('assignedTo')) $where['assignedTo'] = $this->input->get('assignedTo');
        $search = trim((string) $this->input->get('q'));

        $scope = null;
        if ($search !== '') {
            $headerIds = $this->db->distinct()->select('id')
                ->group_start()
                    ->like('quoteNumber', $search)->or_like('companyName', $search)
                    ->or_like('contactPerson', $search)->or_like('email', $search)
                    ->or_like('phone', $search)->or_like('notes', $search)
                    ->or_like('internalNotes', $search)
                ->group_end()->get('quotes')->result_array();
            $itemIds = $this->db->distinct()->select('quoteId')
                ->group_start()
                    ->like('productName', $search)->or_like('partNumber', $search)->or_like('specifications', $search)
                ->group_end()->get('quote_items')->result_array();
            $idList = array_values(array_unique(array_merge(
                array_column($headerIds, 'id'), array_column($itemIds, 'quoteId'))));
            if (empty($idList)) $idList = ['__no_match__'];
            $scope = function () use ($idList) { $this->db->where_in('id', $idList); };
        }

        $result = $this->Quote_model->list_rfqs($where, 10000, 1, $scope, ['createdAt' => 'DESC']);
        $rows = $result['rows'];

        $filename = 'halyk-rfqs-' . date('Y-m-d') . '.csv';
        $this->load->helper('download');
        $fh = fopen('php://temp', 'r+');
        fputcsv($fh, ['Quote #', 'Company', 'Contact', 'Email', 'Phone', 'Country',
                      'Industry', 'Status', 'Line Items', 'Part Numbers', 'Total', 'Currency',
                      'Assigned To', 'Valid Until', 'Created', 'Updated']);
        $assignees = [];
        foreach ($rows as $r) {
            $assigned = $r['assignedTo'];
            if ($assigned && !isset($assignees[$assigned])) {
                $u = $this->User_model->find($assigned);
                $assignees[$assigned] = $u ? trim($u['firstName'] . ' ' . $u['lastName']) : $assigned;
            }
            fputcsv($fh, [
                $r['quoteNumber'], $r['companyName'], $r['contactPerson'], $r['email'], $r['phone'],
                $r['country'], $r['industry'], $r['status'],
                $r['item_count'] ?? '', $r['part_numbers'] ?? '',
                $r['totalAmount'], $r['currency'] ?? 'USD',
                $assigned ? $assignees[$assigned] : '',
                $r['validUntil'] ?? '', $r['createdAt'], $r['updatedAt'],
            ]);
        }
        rewind($fh);
        $data = stream_get_contents($fh);
        fclose($fh);
        $this->audit->log(AUDIT_EXPORT, 'quote', null, ['count' => count($rows), 'filters' => $where, 'q' => $search]);
        force_download($filename, $data, 'text/csv');
    }

    /* ------------------------------------------------------------------ */
    /* Line items + pricing (admin)                                        */
    /* ------------------------------------------------------------------ */

    /** Add a requested part line to the RFQ. */
    public function add_item($id = null)
    {
        if (!$id || $this->input->method() !== 'post') show_404();
        $q = $this->Quote_model->find($id);
        if (!$q) show_404();
        $expectedVersion = (int) $this->input->post('version');
        if ((int) $q['version'] !== $expectedVersion) {
            $this->flash('error', 'This RFQ was modified by someone else. Please refresh and try again.');
            return redirect('admin/quotes/' . $id);
        }
        $data = [
            'productName'    => $this->input->post('productName'),
            'partNumber'     => $this->input->post('partNumber'),
            'description'    => $this->input->post('description'),
            'manufacturer'   => $this->input->post('manufacturer'),
            'condition'      => $this->input->post('condition'),
            'quantity'       => (int) $this->input->post('quantity') ?: 1,
            'specifications' => $this->input->post('specifications'),
            'leadTime'       => $this->input->post('leadTime'),
            'availability'   => $this->input->post('availability'),
            'notes'          => $this->input->post('item_notes'),
            'unitPrice'      => $this->input->post('unitPrice') !== '' ? (float) $this->input->post('unitPrice') : null,
            'currency'       => $q['currency'] ?? 'USD',
        ];
        $res = $this->Quote_model->add_item($id, $data, $this->jet_auth->id());
        $this->flash($res['ok'] ? 'success' : 'error', $res['ok'] ? 'Part added to the RFQ.' : $res['error']);
        redirect('admin/quotes/' . $id);
    }

    /** Update pricing / details of one line item. */
    public function update_item($id = null, $itemId = null)
    {
        if (!$id || !$itemId || $this->input->method() !== 'post') show_404();
        if (!$this->Quote_model->find($id)) show_404();
        $data = array_filter([
            'productName'    => $this->input->post('productName'),
            'partNumber'     => $this->input->post('partNumber'),
            'manufacturer'   => $this->input->post('manufacturer'),
            'condition'      => $this->input->post('condition'),
            'quantity'       => $this->input->post('quantity') ? (int) $this->input->post('quantity') : null,
            'specifications' => $this->input->post('specifications'),
            'leadTime'       => $this->input->post('leadTime'),
            'availability'   => $this->input->post('availability'),
            'unitPrice'      => ($this->input->post('unitPrice') !== null && $this->input->post('unitPrice') !== '') ? (float) $this->input->post('unitPrice') : null,
        ], function ($v) { return $v !== null; });
        $res = $this->Quote_model->update_item($id, $itemId, $data, $this->jet_auth->id());
        $this->flash($res['ok'] ? 'success' : 'error', $res['ok'] ? 'Line item updated.' : $res['error']);
        redirect('admin/quotes/' . $id);
    }

    public function delete_item($id = null, $itemId = null)
    {
        if (!$id || !$itemId || $this->input->method() !== 'post') show_404();
        if (!$this->Quote_model->find($id)) show_404();
        $res = $this->Quote_model->delete_item($id, $itemId, $this->jet_auth->id());
        $this->flash($res['ok'] ? 'success' : 'error', $res['ok'] ? 'Line item removed.' : $res['error']);
        redirect('admin/quotes/' . $id);
    }

    /** Update quote header details: validity, currency, total. */
    public function pricing($id = null)
    {
        if (!$id || $this->input->method() !== 'post') show_404();
        $q = $this->Quote_model->find($id);
        if (!$q) show_404();
        $expectedVersion = (int) $this->input->post('version');
        if ((int) $q['version'] !== $expectedVersion) {
            $this->flash('error', 'This RFQ was modified by someone else. Please refresh and try again.');
            return redirect('admin/quotes/' . $id);
        }
        $res = $this->Quote_model->update_details($id, [
            'validUntil'  => $this->input->post('validUntil'),
            'deadline'    => $this->input->post('deadline'),
            'currency'    => strtoupper((string) $this->input->post('currency')) ?: 'USD',
            'totalAmount' => $this->input->post('totalAmount') !== '' ? (float) $this->input->post('totalAmount') : null,
            'internalNotes' => $this->input->post('internalNotes'),
        ], $this->jet_auth->id());
        $this->flash($res['ok'] ? 'success' : 'error', $res['ok'] ? 'Quote details updated.' : $res['error']);
        redirect('admin/quotes/' . $id);
    }

    /**
     * Send the quotation to the customer: generate the PDF, store it, log the
     * activity and email the customer (PDF attached / linked). Also moves the
     * RFQ to QUOTED when it is still NEW/REVIEWING.
     */
    public function send($id = null)
    {
        if (!$id) show_404();
        $this->require_permission('quotes.generate_pdf');
        $q = $this->Quote_model->find($id);
        if (!$q) show_404();

        // Generate (or regenerate) the PDF first so the email can reference it.
        $items    = $this->Quote_model->get_items($id);
        $currency = $q['currency'] ?? 'USD';
        $site     = vp_site('name', 'Halyk Petroleum');

        // Advance the state machine toward QUOTED, one valid step at a time
        // (NEW → REVIEWING → QUOTED). The machine forbids arbitrary jumps.
        $path = ['NEW' => QUOTE_REVIEWING, QUOTE_REVIEWING => QUOTE_QUOTED];
        while (in_array($q['status'], [QUOTE_NEW, QUOTE_REVIEWING], true)) {
            $next = $path[$q['status']];
            $assignee = $q['assignedTo'] ?: $this->jet_auth->id();
            $res = $this->Quote_model->transition_status(
                $id, $next, $this->jet_auth->id(), $assignee,
                $next === QUOTE_QUOTED ? 'Quotation issued to customer' : 'Under review (auto via Send quote)',
                (int) $q['version']
            );
            if (!$res['ok']) {
                $this->flash('error', 'Could not move RFQ to ' . $next . ': ' . $res['error']);
                return redirect('admin/quotes/' . $id);
            }
            $this->audit->log(AUDIT_STATUS, 'quote', $id, ['from' => $res['from'], 'to' => $res['to']]);
            $q = $this->Quote_model->find($id);
        }

        // Build the PDF (mirror the pdf() action via a shared method).
        $binary = $this->_build_pdf_binary($q, $items);

        $dir = VP_UPLOAD_PATH . 'quotes/';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $pdfPath = $dir . $q['quoteNumber'] . '.pdf';
        @file_put_contents($pdfPath, $binary);
        $url = VP_UPLOAD_URL . 'quotes/' . $q['quoteNumber'] . '.pdf';
        $this->Quote_model->set_pdf_url($id, $url, $this->jet_auth->id());

        // Email the customer.
        $customerLink = base_url('account/quotes/' . $id);
        $tpl = $this->mailer->template_quote_sent_customer($q, $items, $url, $customerLink);
        $dedupeKey = 'quote_sent:' . $id . ':' . md5($url);
        $email = $this->mailer->send($q['email'], $tpl['subject'], $tpl['html'], 'quote_sent', $dedupeKey, ['quoteId' => $id]);

        $this->Quote_model->log_email_sent($id, $this->jet_auth->id(), $q['email'], $email['status'] ?? 'FAILED');
        $this->audit->log(AUDIT_PDF, 'quote', $id, ['url' => $url, 'sent_to' => $q['email'], 'email_status' => $email['status'] ?? null]);

        $this->notify(
            'rfq_sent',
            'Quote ' . $q['quoteNumber'] . ' sent to customer',
            $q['companyName'] . ' was emailed quotation ' . $q['quoteNumber'] . ' (PDF attached).',
            ['quoteId' => $id, 'quoteNumber' => $q['quoteNumber']],
            $q['assignedTo'] ?: null
        );

        $msg = 'Quotation ' . $q['quoteNumber'] . ' generated and sent to ' . $q['email'] . '.';
        if (($email['status'] ?? '') !== EMAIL_SENT && ($email['status'] ?? '') !== 'DUPLICATE') {
            $msg .= ' The PDF was saved, but the email could not be delivered; check mail settings and resend.';
        }
        $this->flash($email['status'] === EMAIL_SENT || $email['status'] === 'DUPLICATE' ? 'success' : 'error', $msg);
        redirect('admin/quotes/' . $id);
    }

    /** Shared: build the branded quote PDF binary for a quote + items. */
    private function _build_pdf_binary(array $q, array $items)
    {
        $site    = vp_site('name', 'Halyk Petroleum');
        $tagline = vp_site('tagline', 'Aircraft Parts & Components Supply');
        $contact = [
            'email'   => vp_site('email'),
            'phone'   => vp_site('phone'),
            'address' => vp_site('address'),
        ];
        $currency = $q['currency'] ?? 'USD';
        $this->load->library('pdf');

        $columns = [
            ['label' => 'Requested Part',           'width' => 4.6, 'align' => 'L'],
            ['label' => 'Qty',                       'width' => 0.8, 'align' => 'C'],
            ['label' => 'Condition / Specification', 'width' => 2.8, 'align' => 'L'],
            ['label' => 'Unit Price',                'width' => 1.4, 'align' => 'R'],
            ['label' => 'Amount',                    'width' => 1.4, 'align' => 'R'],
        ];
        $rows = [];
        $computed = 0.0;
        foreach ($items as $it) {
            $qty  = (int) ($it['quantity'] ?? 1);
            $unit = ($it['unitPrice'] !== null && $it['unitPrice'] !== '') ? (float) $it['unitPrice'] : null;
            $line = ($it['total'] !== null && $it['total'] !== '') ? (float) $it['total'] : ($unit !== null ? $unit * $qty : null);
            if ($line !== null) $computed += $line;
            $desc = $it['productName'] ?? 'Part';
            if (!empty($it['partNumber'])) $desc = $it['partNumber'] . ' — ' . $desc;
            $spec = trim(($it['condition'] ?? '') . ' ' . ($it['specifications'] ?? ''));
            if ($spec === '') $spec = $it['manufacturer'] ?? '';
            $rows[] = [
                $desc,
                (string) $qty,
                $spec,
                $unit !== null ? vp_money($unit, $currency) : 'On quote',
                $line !== null ? vp_money($line, $currency) : '—',
            ];
        }
        $total = ($q['totalAmount'] !== null && $q['totalAmount'] !== '' && (float) $q['totalAmount'] > 0)
            ? (float) $q['totalAmount'] : $computed;

        $st = vp_quote_status_label($q['status']);
        $metaR = [
            'QUOTATION  ' . $q['quoteNumber'],
            'Date: ' . date('Y-m-d', strtotime($q['createdAt'])),
            'Status: ' . $st['label'],
        ];
        if (!empty($q['validUntil'])) $metaR[] = 'Valid until: ' . date('Y-m-d', strtotime($q['validUntil']));
        elseif (!empty($q['deadline'])) $metaR[] = 'Required by: ' . date('Y-m-d', strtotime($q['deadline']));

        $billLines = array_filter([$q['companyName'], $q['contactPerson'], $q['email'], $q['phone'], $q['country'], $q['address']]);
        $notesBlocks = [];
        if (!empty($q['notes'])) $notesBlocks[] = ['heading' => 'Customer notes', 'text' => $q['notes']];
        $notesBlocks[] = ['heading' => 'Terms & notes', 'text' =>
            "Prices are in {$currency}, EXW Halyk Petroleum unless otherwise stated, and valid until the date shown. "
            . "Parts ship with FAA Form 8130-3 and/or EASA Form 1 release documentation and full traceability where applicable. "
            . "Lead times are confirmed on order placement; AOG requests are prioritised 24/7. "
            . "This quotation covers the aircraft parts and components listed above — Halyk Petroleum supplies parts, not complete aircraft."];

        return $this->pdf->build([
            'company'      => $site,
            'tagline'      => $tagline,
            'company_info' => [$contact['address'], $contact['phone'], $contact['email']],
            'title'        => 'Quotation / RFQ',
            'meta_left'    => [$site . ' — ' . $tagline, $contact['address'], $contact['phone'] . '  ·  ' . $contact['email']],
            'meta_right'   => $metaR,
            'bill_to'      => implode("\n", $billLines),
            'columns'      => $columns,
            'rows'         => $rows,
            'totals'       => [
                ['label' => 'Subtotal', 'value' => vp_money($total, $currency), 'bold' => false],
                ['label' => 'Total (' . $currency . ')', 'value' => vp_money($total, $currency), 'bold' => true],
            ],
            'notes_blocks' => $notesBlocks,
            'footer'       => $site . '  ·  ' . $contact['address'] . '  ·  ' . $contact['phone'] . '  ·  ' . $contact['email'],
        ]);
    }
}
