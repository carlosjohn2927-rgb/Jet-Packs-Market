<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Downloads extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Download_model');
    }

    public function index()
    {
        $this->page_title = 'Downloads';
        $this->page_description = 'Brochures, selection guides, datasheets and engineering tools from Vortex Precision.';
        $rows = $this->db->where('isActive', 1)->order_by('category', 'ASC')->order_by('createdAt', 'DESC')->get('downloads')->result_array();
        $grouped = [];
        foreach ($rows as $r) $grouped[$r['category'] ?: 'General'][] = $r;
        $this->render('downloads/index', ['grouped' => $grouped]);
    }

    public function file($id = null)
    {
        $d = $this->Download_model->find($id);
        if (!$d || !$d['isActive']) show_404();
        $this->db->set('downloads', 'downloads+1', false)->where('id', $id)->update('downloads');
        $this->audit->log(AUDIT_EXPORT, 'download', $id, ['title' => $d['title']]);
        redirect($d['fileUrl']);
    }
}
