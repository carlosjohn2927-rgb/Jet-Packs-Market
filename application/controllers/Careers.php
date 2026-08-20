<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Careers extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Career_model', 'Application_model']);
        $this->load->library(['form_validation', 'vp_upload', 'rate_limiter']);
        $this->load->helper(['form', 'url', 'security_helper']);
    }

    public function index()
    {
        $this->page_title = 'Careers';
        $this->page_description = 'Join the Vortex Precision team - engineering, manufacturing, quality and sales roles.';
        $rows = $this->Career_model->find_all(['isActive' => 1], ['postedAt' => 'DESC'], 50);
        $this->render('careers/index', ['rows' => $rows]);
    }

    public function view($slug = null)
    {
        if (!$slug) show_404();
        $job = $this->Career_model->find_one(['slug' => $slug, 'isActive' => 1]);
        if (!$job) show_404();
        $this->page_title = $job['title'];
        $this->page_description = vp_truncate(strip_tags($job['description']), 160);
        $this->render('careers/view', ['job' => $job]);
    }

    public function apply($slug = null)
    {
        if (!$slug || $this->input->method() !== 'post') show_404();
        $job = $this->Career_model->find_one(['slug' => $slug, 'isActive' => 1]);
        if (!$job) show_404();

        $ip = vp_get_client_ip();
        if ($this->rate_limiter->too_many('apply:' . $ip, 5, 3600)) {
            $this->flash('error', 'Too many applications. Please try again later.');
            redirect('careers/' . $slug);
        }

        $this->form_validation->set_rules('name',  'Name',  'required|max_length[190]');
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email|max_length[190]');
        $this->form_validation->set_rules('phone', 'Phone', 'max_length[50]');
        $this->form_validation->set_rules('linkedin', 'LinkedIn', 'max_length[255]');
        $this->form_validation->set_rules('coverLetter', 'Cover letter', 'max_length[5000]');

        if ($this->form_validation->run() === false) {
            $this->flash('error', 'Please complete the required fields.');
            redirect('careers/' . $slug);
        }

        $resume = $this->vp_upload->handle('resume', 'careers', 'pdf|doc|docx', 8192);
        if (!is_array($resume) || !empty($resume['error'])) {
            $this->flash('error', 'Resume upload failed: ' . ($resume['error'] ?? 'unknown'));
            redirect('careers/' . $slug);
        }

        $id = $this->Application_model->insert([
            'careerId'    => $job['id'],
            'name'        => $this->input->post('name'),
            'email'       => strtolower(trim($this->input->post('email'))),
            'phone'       => $this->input->post('phone'),
            'coverLetter' => $this->input->post('coverLetter'),
            'resumeUrl'   => $resume['url'],
            'linkedin'    => $this->input->post('linkedin'),
            'status'      => 'RECEIVED',
        ]);
        $this->audit->log(AUDIT_CREATE, 'application', $id, ['job' => $job['title']]);

        // In-app notification to all staff
        $this->notify(
            'application_new',
            'New application: ' . $job['title'],
            $this->input->post('name') . ' applied for the ' . $job['title'] . ' role.',
            ['applicationId' => $id, 'careerId' => $job['id'], 'jobTitle' => $job['title']],
            null
        );

        // Notify admin. Dedupe key includes the application id so every
        // application emails once.
        $this->load->library('mailer');
        $this->mailer->send(
            $this->config->item('contact_email'),
            '[Vortex] New application: ' . $job['title'],
            '<p><strong>' . vp_safe_html($this->input->post('name')) . '</strong> applied for <strong>' . vp_safe_html($job['title']) . '</strong>.</p><p>Resume: <a href="' . base_url($resume['url']) . '">View</a></p>',
            'career_application',
            'career_application:' . $id,
            []
        );

        $this->flash('success', 'Application submitted. We will be in touch.');
        redirect('careers/' . $slug);
    }
}
