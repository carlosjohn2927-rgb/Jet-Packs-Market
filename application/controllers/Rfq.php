<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Vortex Precision - public RFQ (Request for Quote) controller.
 */
class Rfq extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Quote_model', 'Product_model', 'User_model']);
        $this->load->library(['form_validation', 'mailer', 'rate_limiter']);
        $this->load->helper(['form', 'url', 'security_helper']);
    }

    public function index()
    {
        $this->page_title = 'Request a quote';
        $this->page_description = 'Submit a Request for Quote (RFQ) to Vortex Precision and our engineering team will respond within 2 business days.';

        $productSlug = $this->input->get('product');
        $prefill = null;
        if ($productSlug) {
            $prefill = $this->Product_model->find_by_slug($productSlug);
        }

        $this->render('rfq/index', ['prefill' => $prefill]);
    }

    public function submit()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        // Rate limit: 5 per hour keyed by IP+email
        $ip = vp_get_client_ip();
        $email = strtolower(trim((string) $this->input->post('email')));
        $rlKey = 'rfq:' . $ip . ':' . $email;
        $limit = (int) (vp_setting('rfq_rate_limit_per_hour', 5));
        if ($this->rate_limiter->too_many($rlKey, $limit, 3600)) {
            $this->flash('error', 'Too many quote submissions from this device. Please try again in an hour.');
            redirect('rfq');
        }
        // Global rate limit
        $globalKey = 'global:' . $ip;
        $globalLimit = (int) $this->config->item('global_rate_limit') ?: 100;
        if ($this->rate_limiter->too_many($globalKey, $globalLimit, 900)) {
            show_error('Rate limit exceeded. Please try again later.', 429);
        }

        $this->form_validation->set_rules('companyName',   'Company name',  'required|max_length[255]');
        $this->form_validation->set_rules('contactPerson', 'Contact person','required|max_length[255]');
        $this->form_validation->set_rules('email',         'Email',         'required|valid_email|max_length[190]');
        $this->form_validation->set_rules('country',       'Country',       'required|max_length[100]');
        $this->form_validation->set_rules('phone',         'Phone',         'max_length[50]');
        $this->form_validation->set_rules('industry',      'Industry',      'max_length[190]');
        $this->form_validation->set_rules('address',       'Address',       'max_length[500]');
        $this->form_validation->set_rules('notes',         'Notes',         'max_length[5000]');

        if ($this->form_validation->run() === false) {
            $this->flash('error', 'Please correct the errors below.');
            return $this->index();
        }

        $items = $this->_collect_items();
        if (empty($items)) {
            $this->flash('error', 'Please add at least one line item.');
            return $this->index();
        }

        $userId = $this->vp_auth->id() ?: null;
        $quoteData = [
            'userId'         => $userId,
            'companyName'    => $this->input->post('companyName'),
            'contactPerson'  => $this->input->post('contactPerson'),
            'email'          => $email,
            'phone'          => $this->input->post('phone'),
            'country'        => $this->input->post('country'),
            'address'        => $this->input->post('address'),
            'industry'       => $this->input->post('industry'),
            'notes'          => $this->input->post('notes'),
            'deadline'       => $this->input->post('deadline') ?: null,
        ];
        $itemsDb = [];
        foreach ($items as $it) {
            $itemsDb[] = [
                'id'             => MY_Model::uuid(),
                'productId'      => $it['productId'] ?: null,
                'productName'    => $it['productName'],
                'quantity'       => max(1, (int) $it['quantity']),
                'specifications' => $it['specifications'],
            ];
        }

        $res = $this->Quote_model->create_quote($quoteData, $itemsDb);
        if (!$res['ok']) {
            $this->flash('error', $res['error'] ?? 'Failed to submit. Please try again.');
            return $this->index();
        }

        // Handle attachments
        $attachmentCount = $this->_save_attachments($res['id']);
        if ($attachmentCount === false) {
            // The quote was created, but the attachments failed. Don't fail the whole flow.
            $this->audit->log(AUDIT_CREATE, 'quote', $res['id'], ['attachments_failed' => true]);
        }

        $this->audit->log(AUDIT_CREATE, 'quote', $res['id'], ['quoteNumber' => $res['quoteNumber'], 'attachments' => $attachmentCount ?: 0]);

        // In-app notification to all staff
        $this->notify(
            'rfq_new',
            'New RFQ: ' . $res['quoteNumber'],
            $quoteData['companyName'] . ' (' . $quoteData['contactPerson'] . ') submitted a new RFQ with ' . count($itemsDb) . ' line item(s).',
            ['quoteId' => $res['id'], 'quoteNumber' => $res['quoteNumber'], 'company' => $quoteData['companyName']],
            null
        );

        // Queue emails (idempotent). Admin notification, customer confirmation.
        $this->_dispatch_rfq_emails($res['id']);

        redirect('rfq/thanks/' . $res['quoteNumber']);
    }

    private function _collect_items()
    {
        $names = (array) $this->input->post('item_name');
        $qtys  = (array) $this->input->post('item_qty');
        $specs = (array) $this->input->post('item_spec');
        $pids  = (array) $this->input->post('item_productId');
        $out = [];
        foreach ($names as $i => $n) {
            $n = trim((string) $n);
            if ($n === '') continue;
            $out[] = [
                'productName'    => $n,
                'productId'      => $pids[$i] ?? null,
                'quantity'       => (int) ($qtys[$i] ?? 1),
                'specifications' => trim((string) ($specs[$i] ?? '')) ?: null,
            ];
        }
        return $out;
    }

    private function _dispatch_rfq_emails($quoteId)
    {
        $q = $this->db->get_where('quotes', ['id' => $quoteId])->row_array();
        if (!$q) return;

        $admin = $this->User_model->find_by_email(vp_setting('rfq_admin_email', 'admin@vortexprecision.com'));

        // 1) Admin notification
        $tpl = $this->mailer->template_quote_submitted_admin($q);
        $this->mailer->send(
            $admin['email'] ?? 'admin@vortexprecision.com',
            $tpl['subject'],
            $tpl['html'],
            'quote_submitted_admin',
            null,
            ['quoteId' => $quoteId]
        );

        // 2) Customer confirmation
        $tpl = $this->mailer->template_quote_confirmation_customer($q);
        $this->mailer->send(
            $q['email'],
            $tpl['subject'],
            $tpl['html'],
            'quote_confirmation_customer',
            null,
            ['quoteId' => $quoteId]
        );

        $this->db->update('quotes', ['lastNotifiedAt' => date('Y-m-d H:i:s')], ['id' => $quoteId]);
    }

    public function thanks($quoteNumber = null)
    {
        $this->page_title = 'Thank you';
        $this->render('rfq/thanks', ['quoteNumber' => $quoteNumber]);
    }

    /**
     * Save any uploaded attachments to disk and write quote_attachments rows.
     * Returns the count of saved files, or false on hard failure.
     */
    private function _save_attachments($quoteId)
    {
        if (empty($_FILES['attachments']) || !isset($_FILES['attachments']['name'])) return 0;
        $this->load->library('vp_upload');
        $names = (array) $_FILES['attachments']['name'];
        $count = count($names);
        $saved = 0;
        $errors = [];
        for ($i = 0; $i < $count; $i++) {
            if (empty($names[$i])) continue;
            if (($_FILES['attachments']['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
            $entry = [
                'name'     => $_FILES['attachments']['name'][$i],
                'type'     => $_FILES['attachments']['type'][$i],
                'tmp_name' => $_FILES['attachments']['tmp_name'][$i],
                'error'    => $_FILES['attachments']['error'][$i],
                'size'     => $_FILES['attachments']['size'][$i],
            ];
            $original = $_FILES;
            $_FILES = ['attachments' => $entry];
            $r = $this->vp_upload->handle('attachments', 'quotes', 'pdf|doc|docx|xls|xlsx|txt|jpg|jpeg|png|gif|dwg|dxf|step|stp|iges|igs|zip', 16384);
            $_FILES = $original;
            if (is_array($r) && empty($r['error'])) {
                $this->db->insert('quote_attachments', [
                    'id'        => MY_Model::uuid(),
                    'quoteId'   => $quoteId,
                    'filename'  => $r['filename'],
                    'url'       => $r['url'],
                    'size'      => $r['size'],
                    'mimeType'  => $r['mime'],
                    'createdAt' => date('Y-m-d H:i:s'),
                ]);
                $saved++;
            } else {
                $errors[] = is_array($r) ? ($r['error'] ?? 'unknown') : 'failed';
            }
        }
        if (!empty($errors)) {
            log_message('error', 'RFQ attachment upload errors: ' . implode('; ', $errors));
        }
        return $saved;
    }
}
