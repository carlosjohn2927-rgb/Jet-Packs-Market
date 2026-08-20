<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Services extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $sections = vp_sections('services');
        if (!empty($sections)) {
            $this->page_title       = vp_cms_setting('services_title', 'Services');
            $this->page_description = vp_cms_setting('services_description', vp_site('description', ''));
            return $this->render('home/index', [
                'sections' => $sections,
                'blocks'   => vp_section_blocks($sections),
            ]);
        }

        $this->page_title = 'Services';
        $this->page_description = 'Custom engineering, manufacturing, installation, commissioning and aftermarket service from Vortex Precision.';

        $services = [
            ['icon' => 'ri-tools-line', 'title' => 'Custom Engineering', 'desc' => 'In-house mechanical, process and controls engineers for custom skidded systems, pressure vessels and heat exchangers.'],
            ['icon' => 'ri-stack-line', 'title' => 'Manufacturing', 'desc' => 'ASME-coded fabrication with full QA, hydrostatic testing, NDE and traceability.'],
            ['icon' => 'ri-shield-check-line', 'title' => 'Quality &amp; Certification', 'desc' => 'ISO 9001:2015, ASME U/U2/S/NB, PED, CRN, ATEX, EHEDG, 3-A.'],
            ['icon' => 'ri-earth-line', 'title' => 'Installation &amp; Commissioning', 'desc' => 'Global field service for installation supervision, commissioning and operator training.'],
            ['icon' => 'ri-customer-service-2-line', 'title' => 'Aftermarket &amp; Spares', 'desc' => '24/7 spares support, maintenance contracts and plant lifecycle services.'],
            ['icon' => 'ri-file-chart-line', 'title' => 'Engineering Studies', 'desc' => 'Feasibility, fitness-for-service, FEA and remaining-life assessments.'],
        ];

        $this->render('services/index', ['services' => $services]);
    }
}
