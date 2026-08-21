<?php
/**
 * Unit tests for vp_product_image() (the 6-step resolution chain that maps a
 * product row -> an image URL). The function ships in
 * application/helpers/app_helper.php and is hit on EVERY marketplace
 * listing/detail render, so a quiet break here is a marketplace-wide
 * pixel outage.
 *
 * No DB, no HTTP, no installer. We define the minimum required constants
 * here so the helpers can load in isolation.
 */
require_once __DIR__ . '/_runner.php';

if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
    if (!defined('FCPATH'))     define('FCPATH', dirname(__DIR__, 2) . '/');
    if (!defined('ASSETS_URL')) define('ASSETS_URL', '/assets/');
    if (!defined('IMG_URL'))    define('IMG_URL', ASSETS_URL . 'img/');
}
require_once dirname(__DIR__, 2) . '/application/helpers/app_helper.php';

$IMG = IMG_URL;

section('Resolution order: explicitly uploaded imageUrl wins');
$p = ['slug' => 'main-landing-gear-wheel-2612201-2', 'imageUrl' => 'https://cdn.example.com/wheel.jpg'];
assert_eq('https://cdn.example.com/wheel.jpg', vp_product_image($p), 'imageUrl wins over curated artwork');

section('Resolution order: legacy "image" column wins after imageUrl');
$p = ['slug' => 'main-landing-gear-wheel-2612201-2', 'image' => '/uploads/legacy.jpg'];
assert_eq('/uploads/legacy.jpg',                 vp_product_image($p), 'legacy image wins over curated artwork');

section('Seeded catalog products map to their category artwork');
$seeded = [
    'main-landing-gear-wheel-2612201-2' => 'wheels-brakes.jpg',
    'nose-landing-gear-9001252-3'       => 'landing-gear.jpg',
    'vhf-4000-comm-radio'               => 'avionics.jpg',
    'gtcp36-150-apu'                    => 'engines-apus.jpg',
    'rudder-servo-523-0771-517'         => 'flight-controls.jpg',
    'edp-hydraulic-pump'                => 'hydraulics.jpg',
    'bleed-air-regulating-valve'        => 'pneumatics.jpg',
    'starter-generator'                 => 'electrical-lighting.jpg',
    'fuel-quantity-indicator'           => 'fuel-systems.jpg',
    'emergency-escape-slide'            => 'interior-cabin.jpg',
    'solenoid-shutoff-valve'            => 'actuators-valves.jpg',
    'engine-cowling-rh'                 => 'airframe.jpg',
];
foreach ($seeded as $slug => $expectedFilename) {
    $p = ['slug' => $slug, 'name' => 'Generic text without keywords'];
    assert_eq($IMG . 'products/' . $expectedFilename, vp_product_image($p),
        "seed '$slug' -> $expectedFilename");
}

section('Category artwork ships for the 12 marketplaces');
$known = [
    'wheels-brakes','landing-gear','avionics','engines-apus',
    'flight-controls','hydraulics','pneumatics','electrical-lighting',
    'interior-cabin','actuators-valves','fuel-systems','airframe',
];
foreach ($known as $cat) {
    $p = ['slug' => 'mystery-unknown-slug', 'categorySlug' => $cat, 'name' => ''];
    assert_eq($IMG . 'products/' . $cat . '.jpg', vp_product_image($p), "category $cat artwork");
}

section('Keyword-gussed category wins over default for known phrases');
$p = ['slug' => 'mystery-slug', 'name' => 'Main Wheel And Brake Assembly'];
assert_eq($IMG . 'products/wheels-brakes.jpg',     vp_product_image($p), 'wheel/brake -> wheels-brakes');
$p = ['slug' => 'mystery-slug', 'name' => 'Turbofan Engine Core'];
assert_eq($IMG . 'products/engines-apus.jpg',     vp_product_image($p), 'turbofan/engine -> engines-apus');
$p = ['slug' => 'mystery-slug', 'name' => 'Hydraulic Pump Replacement'];
assert_eq($IMG . 'products/hydraulics.jpg',       vp_product_image($p), 'hydraulic pump -> hydraulics');
$p = ['slug' => 'mystery-slug', 'name' => 'Rudder Servo Actuator'];
assert_eq($IMG . 'products/flight-controls.jpg',  vp_product_image($p), 'rudder/servo -> flight-controls');
$p = ['slug' => 'mystery-slug', 'name' => 'Bleed Air Pressure Controller'];
assert_eq($IMG . 'products/pneumatics.jpg',       vp_product_image($p), 'bleed air/pneumatic -> pneumatics');
$p = ['slug' => 'mystery-slug', 'name' => 'Starter Generator'];
assert_eq($IMG . 'products/electrical-lighting.jpg', vp_product_image($p), 'starter/generator -> electrical-lighting');
$p = ['slug' => 'mystery-slug', 'name' => 'Emergency Escape Slide'];
assert_eq($IMG . 'products/interior-cabin.jpg',   vp_product_image($p), 'escape slide -> interior-cabin');
$p = ['slug' => 'mystery-slug', 'name' => 'Fuel Boost Pump'];
assert_eq($IMG . 'products/fuel-systems.jpg',     vp_product_image($p), 'fuel pump/boost -> fuel-systems');
$p = ['slug' => 'mystery-slug', 'name' => 'APU Auxiliary Power'];
assert_eq($IMG . 'products/engines-apus.jpg',     vp_product_image($p), 'apu/auxiliary power -> engines-apus');
$p = ['slug' => 'mystery-slug', 'name' => 'Engine Cowling Panel'];
assert_eq($IMG . 'products/airframe.jpg',         vp_product_image($p), 'cowling/airframe -> airframe');
$p = ['slug' => 'mystery-slug', 'name' => 'Solenoid Shutoff Valve'];
assert_eq($IMG . 'products/actuators-valves.jpg', vp_product_image($p), 'solenoid/valve/shutoff -> actuators-valves');

section('Fallback to default');
$p = ['slug' => 'unrecognised-thing-99', 'name' => 'Sprocket widget for a tea kettle'];
assert_eq($IMG . 'products/default.jpg', vp_product_image($p), 'unrecognised -> default');
$p = ['slug' => 'unrecognised-thing-99'];
assert_eq($IMG . 'products/default.jpg', vp_product_image($p), 'empty name + no category -> default');

section('Empty / malformed inputs do not throw');
assert_eq($IMG . 'products/default.jpg', vp_product_image([]), 'empty row -> default');
assert_eq($IMG . 'products/default.jpg', vp_product_image(null), 'null row -> default');
assert_eq($IMG . 'products/wheels-brakes.jpg', vp_product_image(['slug' => 'main-wheel'], 'wheels-brakes'),
    'explicit categorySlug still wins on bare row');

summary();
exit($failures === 0 ? 0 : 1);
