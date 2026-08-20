<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Vortex Precision - admin Quotes controller.
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
    /** Permission enforced server-side for every action (see Admin_Controller). */
    protected $required_permission = 'quotes.manage';


    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Quote_model', 'User_model']);
        $this->load->library(['mailer']);
        $this->load->helper(['form', 'url', 'security_helper']);
    }

    public function index()
    {
        $this->page_title = 'Quotes';

        $status = $this->input->get('status');
        $assignee = $this->input->get('assignedTo');
        $search = $this->input->get('q');
        $page = max(1, (int) $this->input->get('page'));
        $per = 25;

        $where = [];
        if ($status)    $where['status'] = $status;
        if ($assignee)  $where['assignedTo'] = $assignee;

        $result = $this->Quote_model->list_with_filters(
            $where, $per, $page, $search,
            ['quoteNumber','companyName','contactPerson','email','phone','notes'],
            ['createdAt' => 'DESC']
        );

        $this->render('admin/quotes/index', [
            'rows' => $result['rows'],
            'total' => $result['total'],
            'total_pages' => $result['total_pages'],
            'page' => $result['page'],
            'search' => $search,
            'status' => $status,
            'assignee' => $assignee,
            'staff' => $this->User_model->staff(),
            'base_url' => base_url('admin/quotes') . '?' . http_build_query(array_filter(['status' => $status, 'assignedTo' => $assignee, 'q' => $search])) . '&page={page}',
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
        $assignee    = $q['assignedTo'] ? $this->User_model->find($q['assignedTo']) : null;
        $customer    = $q['userId'] ? $this->User_model->find($q['userId']) : null;
        $staff       = $this->User_model->staff();

        $this->render('admin/quotes/view', [
            'quote' => $q,
            'items' => $items,
            'history' => $history,
            'activity' => $activity,
            'attachments' => $attachments,
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
            $id, $toStatus, $this->vp_auth->id(), $assignedTo, $notes, $expectedVersion
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
        $res = $this->Quote_model->assign($id, $assignedTo, $this->vp_auth->id(), $expectedVersion);
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
        $res = $this->Quote_model->add_internal_note($id, $note, $this->vp_auth->id(), (int) $this->input->post('version'));
        $this->flash($res['ok'] ? 'success' : 'error', $res['ok'] ? 'Internal note added.' : $res['error']);
        redirect('admin/quotes/' . $id);
    }

    public function delete($id = null)
    {
        if (!$this->vp_auth->has_role(ROLE_SUPER_ADMIN)) {
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
        $q = $this->Quote_model->find($id);
        if (!$q) show_404();
        $items = $this->Quote_model->get_items($id);
        $customer = $q['userId'] ? $this->User_model->find($q['userId']) : null;

        $site = $this->config->item('site_name');
        $contact = [
            'email'   => $this->config->item('contact_email'),
            'phone'   => $this->config->item('phone'),
            'address' => $this->config->item('address'),
        ];

        $this->load->library('pdf');

        // Build columns and rows for the PDF
        $columns = [
            ['label' => 'Item',           'width' => 5, 'align' => 'L'],
            ['label' => 'Qty',            'width' => 1, 'align' => 'C'],
            ['label' => 'Specifications', 'width' => 6, 'align' => 'L'],
        ];
        $rows = [];
        foreach ($items as $it) {
            $rows[] = [
                $it['productName'],
                (string) (int) $it['quantity'],
                $it['specifications'] ?? '',
            ];
        }

        $st = vp_quote_status_label($q['status']);
        $metaL = [
            $site,
            'Industrial manufacturing',
        ];
        $metaR = [
            'QUOTATION  ' . $q['quoteNumber'],
            'Date: ' . date('Y-m-d', strtotime($q['createdAt'])),
            'Status: ' . $st['label'],
        ];

        $bill = 'Bill to:  ' . $q['companyName']
              . '  /  ' . $q['contactPerson']
              . '  /  ' . $q['email']
              . ($q['phone'] ? '  /  ' . $q['phone'] : '')
              . '  /  ' . $q['country']
              . ($q['address'] ? "\n         " . str_replace("\n", "\n         ", $q['address']) : '');

        $doc = [
            'title'      => 'Quotation',
            'subtitle'   => $site,
            'meta_left'  => $metaL,
            'meta_right' => $metaR,
            'columns'    => $columns,
            'rows'       => $rows,
            'notes'      => !empty($q['notes']) ? "Customer notes:\n" . $q['notes'] : '',
            'footer'     => $site . '  ·  ' . $contact['address'] . '  ·  ' . $contact['phone'] . '  ·  ' . $contact['email'],
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
        $this->Quote_model->set_pdf_url($id, $url, $this->vp_auth->id());
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
        $rows = $this->Quote_model->find_all([], ['createdAt' => 'DESC'], 5000);
        $filename = 'vortex-quotes-' . date('Y-m-d') . '.csv';
        $this->load->helper('download');
        $fh = fopen('php://temp', 'r+');
        fputcsv($fh, ['Quote #', 'Company', 'Contact', 'Email', 'Phone', 'Country', 'Industry', 'Status', 'Assigned To', 'Total', 'Created', 'Updated']);
        $assignees = [];
        foreach ($rows as $r) {
            $assigned = $r['assignedTo'];
            if ($assigned && !isset($assignees[$assigned])) {
                $u = $this->User_model->find($assigned);
                $assignees[$assigned] = $u ? trim($u['firstName'] . ' ' . $u['lastName']) : $assigned;
            }
            fputcsv($fh, [
                $r['quoteNumber'], $r['companyName'], $r['contactPerson'], $r['email'], $r['phone'],
                $r['country'], $r['industry'], $r['status'], $assigned ? $assignees[$assigned] : '',
                $r['totalAmount'], $r['createdAt'], $r['updatedAt'],
            ]);
        }
        rewind($fh);
        $data = stream_get_contents($fh);
        fclose($fh);
        $this->audit->log(AUDIT_EXPORT, 'quote', null, ['count' => count($rows)]);
        force_download($filename, $data, 'text/csv');
    }
}
