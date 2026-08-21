<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Vortex Precision - migration config.
 * Migrations are SQL files installed manually (see install/install.sql).
 * This config is kept for CI3's migrate CLI to function if invoked.
 */
$config['migration_enabled'] = FALSE;
$config['migration_type'] = 'sequential';
$config['migration_table'] = 'migrations';
$config['migration_auto_latest'] = FALSE;
$config['migration_version'] = 0;
$config['migration_path'] = APPPATH . 'migrations/';
