<?php
/**
 * Plugin Name: DesignMeta
 * Description: Provides structured meta fields, REST integration, and SEO output for posts.
 * Version: 0.1.0
 * Author: DesignMeta Team
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/class-dm-helper.php';
require_once __DIR__ . '/includes/class-dm-db.php';
require_once __DIR__ . '/includes/class-dm-repository.php';
require_once __DIR__ . '/includes/class-dm-rest.php';
require_once __DIR__ . '/includes/class-dm-seo.php';
require_once __DIR__ . '/admin/admin-panel.php';

/**
 * Bootstrap DesignMeta plugin services.
 *
 * @return void
 */
function dm_bootstrap(): void
{
    DM_DB::init();
    DM_Repository::init();
    DM_REST::init();
    DM_SEO::init();
    DM_Admin_Panel::init();
}

add_action('plugins_loaded', 'dm_bootstrap');


/**
 * Public save API wrapper.
 *
 * @param int $post_id Post identifier.
 * @param array<string, mixed> $input Incoming payload.
 * @return void
 */
function dm_save_all_data(int $post_id, array $input): void
{
    DM_Repository::save_all_data($post_id, $input);
}

/**
 * Public read API wrapper.
 *
 * @param int $post_id Post identifier.
 * @return array<string, string>
 */
function dm_get_data(int $post_id): array
{
    return DM_Repository::get_data($post_id);
}

register_activation_hook(__FILE__, ['DM_DB', 'activate']);
