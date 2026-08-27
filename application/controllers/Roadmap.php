<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halyk Petroleum — public Roadmap page.
 *
 * One structured source of truth (`vp_roadmap_data()`) drives this page, the
 * admin dashboard widget, and the footer status block. The page itself is
 * fully cache-friendly: it does not load any model or hit the database, so
 * it serves instantly from anywhere.
 */
class Roadmap extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->page_title = 'Roadmap';
        $this->page_description = 'Halyk Petroleum public roadmap — what we have shipped, what is being built, and what is planned next.';

        $phases   = vp_roadmap_data();
        $progress = vp_roadmap_progress();

        $this->render('pages/roadmap', [
            'phases'   => $phases,
            'progress' => $progress,
        ]);
    }
}
