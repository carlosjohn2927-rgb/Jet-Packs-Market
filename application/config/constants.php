<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Vortex Precision - global constants.
 */

// Roles (mirrors Prisma enum Role)
define('ROLE_SUPER_ADMIN', 'SUPER_ADMIN');
define('ROLE_ADMIN',       'ADMIN');
define('ROLE_SALES',       'SALES');
define('ROLE_ENGINEER',    'ENGINEER');
define('ROLE_EDITOR',      'EDITOR');
define('ROLE_CUSTOMER',    'CUSTOMER');

// Quote statuses (mirrors QuoteStatus enum + lifecycle)
define('QUOTE_NEW',       'NEW');
define('QUOTE_REVIEWING', 'REVIEWING');
define('QUOTE_QUOTED',    'QUOTED');
define('QUOTE_APPROVED',  'APPROVED');
define('QUOTE_REJECTED',  'REJECTED');
define('QUOTE_COMPLETED', 'COMPLETED');

// Allowed forward transitions for the quote state machine
define('QUOTE_TRANSITIONS', [
    QUOTE_NEW       => [QUOTE_REVIEWING],
    QUOTE_REVIEWING => [QUOTE_QUOTED, QUOTE_REJECTED],
    QUOTE_QUOTED    => [QUOTE_APPROVED, QUOTE_REJECTED],
    QUOTE_APPROVED  => [QUOTE_COMPLETED],
    QUOTE_REJECTED  => [],
    QUOTE_COMPLETED => [],
]);

// Email status (mirrors EmailStatus)
define('EMAIL_PENDING',  'PENDING');
define('EMAIL_SENT',     'SENT');
define('EMAIL_FAILED',   'FAILED');
define('EMAIL_RETRYING', 'RETRYING');

// Audit log actions
define('AUDIT_CREATE',  'CREATE');
define('AUDIT_UPDATE',  'UPDATE');
define('AUDIT_DELETE',  'DELETE');
define('AUDIT_LOGIN',   'LOGIN');
define('AUDIT_LOGOUT',  'LOGOUT');
define('AUDIT_VIEW',    'VIEW');
define('AUDIT_EXPORT',  'EXPORT');
define('AUDIT_STATUS',  'STATUS_CHANGE');
define('AUDIT_ASSIGN',  'ASSIGN');
define('AUDIT_EMAIL',   'EMAIL_SENT');
define('AUDIT_EMAIL_FAILED', 'EMAIL_FAILED');
define('AUDIT_PDF',     'PDF_GENERATED');
define('AUDIT_LOGIN_FAILED', 'LOGIN_FAILED');

// Asset / path constants
define('ASSETS_URL', '/assets/');
define('CSS_URL',    ASSETS_URL . 'css/');
define('JS_URL',     ASSETS_URL . 'js/');
define('IMG_URL',    ASSETS_URL . 'img/');
define('UPLOAD_URL', ASSETS_URL . 'uploads/');

// Bump when changing files under assets/js|css so browsers/Cloudflare fetch
// the fresh build instead of serving a stale cached copy.
define('VP_ASSET_VERSION', '10');

// Quote activity actions (mirrors QuoteActivityAction)
define('QA_CREATED',     'QUOTE_CREATED');
define('QA_ASSIGNED',    'ASSIGNED');
define('QA_STATUS',      'STATUS_CHANGED');
define('QA_NOTE',        'INTERNAL_NOTE_ADDED');
define('QA_UPDATED',     'QUOTE_UPDATED');
define('QA_PDF',         'PDF_GENERATED');
define('QA_EMAIL_QUEUED','EMAIL_QUEUED');
define('QA_EMAIL_SENT',  'EMAIL_SENT');
