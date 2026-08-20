<?php
$this->load->view('layouts/public', array_merge(get_defined_vars(), [
    'content' => '<section class="container mx-auto px-4 py-16 text-center">'
        . '<img src="'.IMG_URL.'system-route.jpg" alt="Industrial system route awaiting reconnection" class="w-full max-w-2xl mx-auto aspect-[16/7] object-cover rounded-2xl shadow-sm">'
        . '<div class="text-6xl font-extrabold text-red-600 mt-8">500</div>'
        . '<h1 class="text-3xl font-bold mt-3">Something went wrong</h1>'
        . '<p class="text-ink-800 mt-3">Our team has been notified. Please try again in a moment.</p>'
        . '<a href="'.base_url().'" class="vp-btn vp-btn-primary mt-6 inline-flex">Back to home</a>'
        . '</section>',
    'page_title' => 'Something went wrong',
]));
