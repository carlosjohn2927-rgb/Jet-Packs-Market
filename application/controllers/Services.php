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
        $this->page_description = 'Aircraft parts supply, AOG dispatch, parts sourcing, exchange programs and certification support from Halyk Petroleum.';

        $services = [
            ['icon' => 'ri-flight-takeoff-line', 'title' => 'Parts Supply', 'desc' => 'New, OHC, USED and SERVICEABLE parts for business and commercial jets — wheels, brakes, avionics, hydraulics, engines and more.'],
            ['icon' => 'ri-alarm-line', 'title' => '24/7 AOG Dispatch', 'desc' => 'Aircraft on the ground? Our round-the-clock AOG desk sources and dispatches urgent parts within hours.'],
            ['icon' => 'ri-global-line', 'title' => 'Parts Sourcing', 'desc' => 'Hard-to-find part? Our sourcing desk searches 2,000+ vetted aviation suppliers and OEMs — most requests answered within 48 hours.'],
            ['icon' => 'ri-refund-2-line', 'title' => 'Exchange &amp; Repair Programs', 'desc' => 'Ship-first exchange programs for rotables: wheels, brakes, actuators, avionics and starters — cores accepted in return.'],
            ['icon' => 'ri-shield-check-line', 'title' => 'Certification &amp; Traceability', 'desc' => 'Every part ships with FAA 8130-3 / EASA Form 1, full traceability and our own inspection certificate.'],
            ['icon' => 'ri-hand-coin-line', 'title' => 'Buy &amp; Consignment', 'desc' => 'We buy surplus and end-of-life inventories outright, or manage consignment sales of your rotables and engines.'],
        ];

        $this->render('services/index', ['services' => $services]);
    }
}
