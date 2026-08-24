<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Inventory presentation and validation helpers. */

if (!function_exists('vp_inventory_lot_status_label')) {
    function vp_inventory_lot_status_label($status)
    {
        $map = [
            'ACTIVE'     => ['Active', 'bg-emerald-100 text-emerald-800'],
            'QUARANTINE' => ['Quarantine', 'bg-amber-100 text-amber-800'],
            'EXPIRED'    => ['Expired', 'bg-red-100 text-red-800'],
            'DEPLETED'   => ['Depleted', 'bg-gray-200 text-gray-700'],
        ];
        return $map[strtoupper((string) $status)] ?? [strtoupper((string) $status), 'bg-gray-100 text-gray-700'];
    }
}

if (!function_exists('vp_inventory_expiry_label')) {
    /** @return array [label, CSS classes] */
    function vp_inventory_expiry_label($date, $today = null)
    {
        $date = trim((string) $date);
        if ($date === '') return ['No expiry', 'text-gray-500'];
        $expiry = strtotime($date . ' 23:59:59');
        if (!$expiry) return ['Unknown', 'text-gray-500'];
        $today = $today === null ? strtotime(date('Y-m-d') . ' 00:00:00') : (int) $today;
        $days = (int) floor(($expiry - $today) / 86400);
        if ($days < 0) return ['Expired', 'text-red-700 font-semibold'];
        if ($days <= 30) return ['Expires in ' . $days . 'd', 'text-amber-700 font-semibold'];
        return [date('M j, Y', $expiry), 'text-gray-600'];
    }
}

if (!function_exists('vp_inventory_lot_number')) {
    /** Normalize a human-entered lot/batch identifier. */
    function vp_inventory_lot_number($lot)
    {
        $lot = strtoupper(trim((string) $lot));
        $lot = preg_replace('/\s+/', '-', $lot);
        return preg_replace('/[^A-Z0-9._\/-]/', '', $lot);
    }
}
