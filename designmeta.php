<?php
/**
 * Plugin Name: DesignMeta
 * Description: Structured design metadata management for posts.
 * Version: 0.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/class-dm-helper.php';
require_once __DIR__ . '/includes/class-dm-db.php';
require_once __DIR__ . '/includes/class-dm-repository.php';
require_once __DIR__ . '/includes/class-dm-rest.php';
require_once __DIR__ . '/includes/class-dm-seo.php';
require_once __DIR__ . '/admin/admin-panel.php';

final class DesignMeta_Plugin
{
    public static function init(): void
    {
        add_action('init', [DM_DB::class, 'init']);
        add_action('rest_api_init', [DM_REST::class, 'register']);
        add_action('add_meta_boxes', [DM_Admin_Panel::class, 'register_meta_box']);
        add_action('save_post', [DM_Admin_Panel::class, 'save_post'], 10, 2);
        add_action('wp_head', [DM_SEO::class, 'output_meta_description']);
        add_action('delete_post', [DM_Repository::class, 'delete_for_post']);
        add_filter('upload_dir', [self::class, 'filter_upload_dir']);
    }

    public static function activate(): void
    {
        DM_DB::create_table();
    }

    public static function filter_upload_dir(array $uploads): array
    {
        if (!defined('REST_REQUEST') || !REST_REQUEST) {
            return $uploads;
        }

        $target = isset($_SERVER['HTTP_X_TARGET_DATE']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_X_TARGET_DATE'])) : '';
        if (!$target || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $target, $m)) {
            return $uploads;
        }

        $subdir = '/' . $m[1] . '/' . $m[2];
        $uploads['subdir'] = $subdir;
        $uploads['path'] = $uploads['basedir'] . $subdir;
        $uploads['url'] = $uploads['baseurl'] . $subdir;

        return $uploads;
    }
}

register_activation_hook(__FILE__, [DesignMeta_Plugin::class, 'activate']);
DesignMeta_Plugin::init();
