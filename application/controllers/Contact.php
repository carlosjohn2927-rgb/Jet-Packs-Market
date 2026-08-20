<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Contact extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Contact_model');
        $this->load->library(['form_validation', 'mailer', 'rate_limiter']);
        $this->load->helper(['form', 'url', 'security_helper']);
    }

    public function index()
    {
        $this->page_title = 'Contact us';
        $this->page_description = 'Get in touch with Vortex Precision - sales, service, careers and general enquiries.';
        $this->render('contact/index');
    }

    public function submit()
    {
        $ip = vp_get_client_ip();
        $rlKey = 'contact:' . $ip;
        if ($this->rate_limiter->too_many($rlKey, 5, 600)) {
            $this->flash('error', 'Too many submissions. Please try again later.');
            redirect('contact');
        }

        $this->form_validation->set_rules('name',       'Name',       'required|max_length[190]');
        $this->form_validation->set_rules('email',      'Email',      'required|valid_email|max_length[190]');
        $this->form_validation->set_rules('phone',      'Phone',      'max_length[50]');
        $this->form_validation->set_rules('company',    'Company',    'max_length[190]');
        $this->form_validation->set_rules('department', 'Department', 'max_length[100]');
        $this->form_validation->set_rules('subject',    'Subject',    'required|max_length[255]');
        $this->form_validation->set_rules('message',    'Message',    'required|min_length[10]|max_length[10000]');

        if ($this->form_validation->run() === false) {
            $this->flash('error', 'Please correct the errors and try again.');
            return $this->index();
        }

        $id = $this->Contact_model->insert([
            'name'    => $this->input->post('name'),
            'email'   => strtolower(trim($this->input->post('email'))),
            'phone'   => $this->input->post('phone'),
            'company' => $this->input->post('company'),
            'subject' => $this->input->post('subject'),
            'message' => $this->input->post('message'),
            'department' => $this->input->post('department'),
            'status'  => 'NEW',
        ]);
        $this->audit->log(AUDIT_CREATE, 'contact', $id);

        $subject = $this->input->post('subject');
        $name    = $this->input->post('name');

        // In-app notification to all staff
        $this->notify(
            'contact_new',
            'New contact: ' . $subject,
            $name . ' sent a new message. Department: ' . ($this->input->post('department') ?: 'General') . '.',
            ['contactId' => $id, 'name' => $name, 'subject' => $subject],
            null
        );

        // Notify admin. The dedupe key includes the contact id so every
        // message emails once (a plain template+recipient key would suppress
        // every message after the first).
        $this->mailer->send(
            $this->config->item('contact_email'),
            '[Vortex] New contact: ' . $subject,
            '<p><strong>' . vp_safe_html($name) . '</strong> sent a new contact message:</p><p>' . nl2br(vp_safe_html($this->input->post('message'))) . '</p>',
            'contact_received',
            'contact_received:' . $id,
            []
        );

        $this->flash('success', 'Thank you - we will be in touch within 1 business day.');
        redirect('contact');
    }
}
