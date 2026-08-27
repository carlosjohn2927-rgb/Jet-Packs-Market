<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halyk Petroleum — customer account area.
 *
 * Signed-in CUSTOMER accounts land here to manage their parts-order history:
 *   • re-order past quotes (clones the line items into a fresh RFQ)
 *   • download prior invoices (minted from PAID card payments)
 *   • track AOG / emergency dispatches
 *   • update their profile
 *
 * Staff accounts are redirected to the admin dashboard; anonymous visitors are
 * sent to login (preserving the intended return path).
 */
class Account extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Quote_model', 'Payment_model', 'Invoice_model', 'Aog_dispatch_model', 'User_model']);
        $this->load->library(['form_validation']);
        $this->load->helper(['form', 'url', 'security_helper']);
        $this->_require_customer();
    }

    /** Gate: must be a signed-in customer (not staff). */
    private function _require_customer()
    {
        if (!$this->jet_auth->check()) {
            return redirect('login?next=' . urlencode(current_url()));
        }
        if ($this->jet_auth->is_staff()) {
            return redirect('admin');
        }
    }

    /** Account overview. */
    public function index()
    {
        $uid = $this->jet_auth->id();
        $this->data['account_section'] = 'dashboard';
        $this->page_title = 'My account';
        $this->page_description = 'Your orders, invoices and AOG dispatches at ' . ($this->config->item('site_name') ?: 'Halyk Petroleum') . '.';

        $quotesTotal = (int) $this->db->where('userId', $uid)->count_all_results('quotes');
        $invoices     = $this->Invoice_model->list_for_user($uid);
        $activeAog    = (int) $this->db->where('userId', $uid)
            ->where_in('status', ['REQUESTED', 'CONFIRMED', 'IN_TRANSIT'])
            ->count_all_results('aog_dispatches');

        $recentQuotes = $this->Quote_model->list_with_filters(['userId' => $uid], 5, 1);

        $this->render('account/dashboard', [
            'quotes_total'  => $quotesTotal,
            'invoices'      => $invoices,
            'invoices_count'=> count($invoices),
            'active_aog'    => $activeAog,
            'recent_quotes' => $recentQuotes['rows'] ?? [],
        ]);
    }

    /** Parts-order history (all of the customer's quotes). */
    public function quotes()
    {
        $uid = $this->jet_auth->id();
        $this->data['account_section'] = 'quotes';
        $this->page_title = 'My orders & quotes';

        $result = $this->Quote_model->list_with_filters(['userId' => $uid], 50, 1);
        $this->render('account/quotes', ['quotes' => $result['rows'] ?? []]);
    }

    /** Read-only view of a single quote the customer placed. */
    public function quotes_view($id = null)
    {
        if (!$id) show_404();
        $uid = $this->jet_auth->id();
        $quote = $this->Quote_model->find($id);
        if (!$quote || $quote['userId'] !== $uid) show_404();

        $this->data['account_section'] = 'quotes';
        $this->page_title = 'Quote ' . $quote['quoteNumber'];

        $items   = $this->Quote_model->get_items($id);
        $paid    = $this->db->get_where('payments', ['quoteId' => $id, 'status' => PAYMENT_PAID])->row_array();
        $invoice = $paid ? $this->Invoice_model->find_or_create_for_payment($paid) : null;
        $pdfUrl  = !empty($quote['pdfUrl']) ? $quote['pdfUrl'] : null;

        $this->render('account/quote_view', [
            'quote'   => $quote,
            'items'   => $items,
            'invoice' => $invoice,
            'pdf_url' => $pdfUrl,
            'status'  => vp_quote_status_label($quote['status']),
        ]);
    }

    /** Customer approves a quoted RFQ (QUOTED -> APPROVED). */
    public function quotes_approve($id = null)
    {
        $this->_customer_transition($id, QUOTE_APPROVED, 'Customer approved the quotation online.');
    }

    /** Customer declines a quoted RFQ (QUOTED -> REJECTED). */
    public function quotes_reject($id = null)
    {
        $note = trim((string) $this->input->post('note'));
        $this->_customer_transition($id, QUOTE_REJECTED, 'Customer declined the quotation online.' . ($note !== '' ? ' Note: ' . $note : ''));
    }

    /**
     * Apply a customer-driven status transition for a quote they own.
     * Uses the same optimistic-locked, forward-only state machine as the
     * admin side, so customers cannot reach states that are not allowed.
     */
    private function _customer_transition($id, $toStatus, $note)
    {
        if (!$id || $this->input->method() !== 'post') show_404();
        $uid = $this->jet_auth->id();
        $quote = $this->Quote_model->find($id);
        if (!$quote || $quote['userId'] !== $uid) show_404();

        $res = $this->Quote_model->transition_status(
            $id, $toStatus, $uid, $quote['assignedTo'], $note,
            (int) $this->input->post('version')
        );

        if (!$res['ok']) {
            $this->flash('error', $res['error'] ?? 'This quotation cannot be updated in its current state.');
            return redirect('account/quotes/' . $id);
        }

        // Notify staff.
        $this->load->library('mailer');
        $this->load->model('User_model');
        if (method_exists($this, 'notify')) {
            $this->notify(
                'rfq_customer_' . strtolower($toStatus),
                'Quote ' . $res['quote']['quoteNumber'] . ' ' . strtolower($toStatus) . ' by customer',
                $res['quote']['companyName'] . ' has ' . strtolower($toStatus) . ' quotation ' . $res['quote']['quoteNumber'] . '.',
                ['quoteId' => $id, 'quoteNumber' => $res['quote']['quoteNumber']],
                !empty($res['quote']['assignedTo']) ? $res['quote']['assignedTo'] : null
            );
        }
        $this->flash('success', $toStatus === QUOTE_APPROVED
            ? 'Quotation ' . $res['quote']['quoteNumber'] . ' approved — our sales desk will confirm the order shortly.'
            : 'Quotation ' . $res['quote']['quoteNumber'] . ' declined. We will follow up shortly.');
        redirect('account/quotes/' . $id);
    }

    /** Download the generated PDF quotation for a quote the customer owns. */
    public function quotes_pdf($id = null)
    {
        if (!$id) show_404();
        $uid = $this->jet_auth->id();
        $quote = $this->Quote_model->find($id);
        if (!$quote || $quote['userId'] !== $uid) show_404();
        if (empty($quote['pdfUrl'])) {
            $this->flash('error', 'The PDF quotation has not been generated yet. Our sales team will issue it shortly.');
            return redirect('account/quotes/' . $id);
        }
        $path = FCPATH . ltrim($quote['pdfUrl'], '/');
        if (!is_file($path)) {
            $this->flash('error', 'The PDF file is not available.');
            return redirect('account/quotes/' . $id);
        }
        $this->output
            ->set_status_header(200)
            ->set_content_type('application/pdf')
            ->set_header('Content-Disposition: inline; filename="' . $quote['quoteNumber'] . '.pdf"')
            ->set_output(file_get_contents($path));
    }

    /** Prior invoices, with download links. */
    public function invoices()
    {
        $uid = $this->jet_auth->id();
        $this->data['account_section'] = 'invoices';
        $this->page_title = 'My invoices';

        $invoices = $this->Invoice_model->list_for_user($uid);
        $this->render('account/invoices', ['invoices' => $invoices]);
    }

    /** Stream (force-download) an invoice PDF the customer owns. */
    public function invoice_download($id = null)
    {
        if (!$id) show_404();
        $uid = $this->jet_auth->id();
        $invoice = $this->Invoice_model->find($id);
        if (!$invoice || $invoice['userId'] !== $uid) show_404();

        $path = null;
        if (!empty($invoice['pdfUrl'])) {
            $candidate = FCPATH . ltrim($invoice['pdfUrl'], '/');
            if (is_file($candidate)) $path = $candidate;
        }
        if (!$path) {
            $url = $this->Invoice_model->build_pdf($invoice);
            if ($url) {
                $this->db->where('id', $invoice['id'])->update('invoices', ['pdfUrl' => $url]);
                $candidate = FCPATH . ltrim($url, '/');
                if (is_file($candidate)) $path = $candidate;
            }
        }
        if (!$path) {
            $this->flash('error', 'Invoice PDF is not available right now. Please try again later.');
            redirect('account/invoices');
        }

        $binary = file_get_contents($path);
        $this->output
            ->set_status_header(200)
            ->set_content_type('application/pdf')
            ->set_header('Content-Disposition: attachment; filename="' . $invoice['invoiceNumber'] . '.pdf"')
            ->set_header('Content-Length: ' . strlen($binary))
            ->set_output($binary);
    }

    /** Track AOG / emergency dispatches. */
    public function dispatches()
    {
        $uid = $this->jet_auth->id();
        $this->data['account_section'] = 'dispatches';
        $this->page_title = 'AOG dispatches';

        $dispatches = $this->Aog_dispatch_model->list_for_user($uid);
        $this->render('account/dispatches', ['dispatches' => $dispatches]);
    }

    /** Detail of a single dispatch the customer is tracking. */
    public function dispatches_view($id = null)
    {
        if (!$id) show_404();
        $uid = $this->jet_auth->id();
        $dispatch = $this->Aog_dispatch_model->find($id);
        if (!$dispatch || $dispatch['userId'] !== $uid) show_404();

        $this->data['account_section'] = 'dispatches';
        $this->page_title = 'Dispatch ' . $dispatch['reference'];

        $quote = !empty($dispatch['quoteId']) ? $this->Quote_model->find($dispatch['quoteId']) : null;
        $this->render('account/dispatch_view', ['dispatch' => $dispatch, 'quote' => $quote]);
    }

    /** Update profile (name, company, phone) and optional password change. */
    public function profile()
    {
        $uid = $this->jet_auth->id();
        $this->data['account_section'] = 'profile';
        $this->page_title = 'My profile';

        $user = $this->User_model->find($uid);

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('firstName', 'First name', 'required|max_length[100]');
            $this->form_validation->set_rules('lastName',  'Last name',  'required|max_length[100]');
            $this->form_validation->set_rules('company',   'Company',    'max_length[190]');
            $this->form_validation->set_rules('phone',     'Phone',      'max_length[50]');

            // Optional password change.
            $cur = (string) $this->input->post('current_password');
            $new = (string) $this->input->post('new_password');
            if ($new !== '') {
                $this->form_validation->set_rules('new_password', 'New password', 'min_length[8]');
                $this->form_validation->set_rules('new_password_confirm', 'Confirm', 'matches[new_password]');
                if (!password_verify($cur, $user['password'])) {
                    $this->flash('error', 'Your current password was not correct.');
                    return $this->render('account/profile', ['user' => $user]);
                }
            }

            if ($this->form_validation->run()) {
                $update = [
                    'firstName' => $this->input->post('firstName'),
                    'lastName'  => $this->input->post('lastName'),
                    'company'   => $this->input->post('company') ?: null,
                    'phone'     => $this->input->post('phone') ?: null,
                ];
                if ($new !== '') {
                    $update['password'] = password_hash($new, PASSWORD_BCRYPT);
                    $update['mustChangePassword'] = 0;
                }
                $this->User_model->update($uid, $update);
                $this->audit->log(AUDIT_UPDATE, 'user', $uid, ['action' => 'profile_self']);
                $this->flash('success', 'Profile updated.');
                redirect('account/profile');
            } elseif ($this->input->method() === 'post') {
                $this->flash('error', 'Please correct the errors below.');
            }
        }

        $this->render('account/profile', ['user' => $user]);
    }
}
