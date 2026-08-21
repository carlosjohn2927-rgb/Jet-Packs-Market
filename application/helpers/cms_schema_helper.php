<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Small, idempotent schema repair helper for CMS screens that were added after
 * the original installer. Older deployments can be missing these tables/columns,
 * which used to make /admin/homepage, /admin/pages and /admin/menus fail while
 * loading. The helper creates only the CMS tables those screens need.
 */
if (!function_exists('vp_ensure_cms_tables')) {
    function vp_ensure_cms_tables()
    {
        $CI =& get_instance();
        if (!isset($CI->db)) $CI->load->database();

        try {
            if (!$CI->db->table_exists('pages')) {
                $CI->db->query("CREATE TABLE IF NOT EXISTS `pages` (
                  `id` CHAR(36) NOT NULL,
                  `title` VARCHAR(190) NOT NULL,
                  `slug` VARCHAR(190) NOT NULL,
                  `excerpt` TEXT DEFAULT NULL,
                  `content` LONGTEXT DEFAULT NULL,
                  `featuredImage` VARCHAR(500) DEFAULT NULL,
                  `template` VARCHAR(40) NOT NULL DEFAULT 'default',
                  `metaTitle` VARCHAR(255) DEFAULT NULL,
                  `metaDescription` VARCHAR(500) DEFAULT NULL,
                  `status` VARCHAR(20) NOT NULL DEFAULT 'DRAFT',
                  `visibility` VARCHAR(20) NOT NULL DEFAULT 'PUBLIC',
                  `publishedAt` DATETIME DEFAULT NULL,
                  `showInMenu` TINYINT(1) NOT NULL DEFAULT 0,
                  `sortOrder` INT NOT NULL DEFAULT 0,
                  `isSystem` TINYINT(1) NOT NULL DEFAULT 0,
                  `authorId` CHAR(36) DEFAULT NULL,
                  `createdAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `updatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `uk_pages_slug` (`slug`),
                  KEY `idx_pages_status` (`status`,`publishedAt`),
                  KEY `idx_pages_menu` (`showInMenu`,`sortOrder`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            }

            if (!$CI->db->table_exists('page_sections')) {
                $CI->db->query("CREATE TABLE IF NOT EXISTS `page_sections` (
                  `id` CHAR(36) NOT NULL,
                  `pageKey` VARCHAR(60) NOT NULL DEFAULT 'home',
                  `type` VARCHAR(40) NOT NULL DEFAULT 'richtext',
                  `name` VARCHAR(190) NOT NULL DEFAULT '',
                  `title` VARCHAR(255) DEFAULT NULL,
                  `subtitle` VARCHAR(500) DEFAULT NULL,
                  `body` LONGTEXT DEFAULT NULL,
                  `image` VARCHAR(500) DEFAULT NULL,
                  `buttonText` VARCHAR(120) DEFAULT NULL,
                  `buttonUrl` VARCHAR(500) DEFAULT NULL,
                  `buttonText2` VARCHAR(120) DEFAULT NULL,
                  `buttonUrl2` VARCHAR(500) DEFAULT NULL,
                  `settings` LONGTEXT DEFAULT NULL,
                  `sortOrder` INT NOT NULL DEFAULT 0,
                  `isActive` TINYINT(1) NOT NULL DEFAULT 1,
                  `isSystem` TINYINT(1) NOT NULL DEFAULT 0,
                  `createdAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `updatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  KEY `idx_sections_page_order` (`pageKey`,`sortOrder`),
                  KEY `idx_sections_active` (`isActive`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            }

            if (!$CI->db->table_exists('menu_items')) {
                $CI->db->query("CREATE TABLE IF NOT EXISTS `menu_items` (
                  `id` CHAR(36) NOT NULL,
                  `menu` VARCHAR(40) NOT NULL DEFAULT 'header',
                  `label` VARCHAR(120) NOT NULL,
                  `type` VARCHAR(20) NOT NULL DEFAULT 'INTERNAL',
                  `url` VARCHAR(500) DEFAULT NULL,
                  `pageId` CHAR(36) DEFAULT NULL,
                  `target` VARCHAR(10) NOT NULL DEFAULT '_self',
                  `icon` VARCHAR(60) DEFAULT NULL,
                  `parentId` CHAR(36) DEFAULT NULL,
                  `sortOrder` INT NOT NULL DEFAULT 0,
                  `isActive` TINYINT(1) NOT NULL DEFAULT 1,
                  `createdAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `updatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  KEY `idx_menu_items_menu` (`menu`,`sortOrder`),
                  KEY `idx_menu_items_active` (`isActive`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            }
        } catch (Throwable $e) {
            log_message('error', 'CMS schema repair failed: ' . $e->getMessage());
        }
    }
}

if (!function_exists('vp_ensure_user_avatar_column')) {
    function vp_ensure_user_avatar_column()
    {
        $CI =& get_instance();
        if (!isset($CI->db)) $CI->load->database();
        try {
            if ($CI->db->table_exists('users') && !$CI->db->field_exists('avatar', 'users')) {
                $CI->db->query("ALTER TABLE `users` ADD `avatar` VARCHAR(255) DEFAULT NULL AFTER `company`");
            }
        } catch (Throwable $e) {
            log_message('error', 'User avatar schema repair failed: ' . $e->getMessage());
        }
    }
}
