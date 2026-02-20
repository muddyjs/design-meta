<?php
/**
 * Plugin Name: DesignMeta
 * Description: High-performance metadata extension plugin for post content sites.
 * Version: 1.0.0
 * Author: DesignMeta
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/class-dm-helper.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-dm-db.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-dm-repository.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-dm-rest.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-dm-seo.php';
require_once plugin_dir_path(__FILE__) . 'admin/admin-panel.php';

register_activation_hook(__FILE__, array('DM_DB', 'activate'));

function dm_save_all_data($post_id, $input)
{
    DM_Repository::save_all_data((int) $post_id, (array) $input);
}

function dm_get_data($post_id)
{
    return DM_Repository::get_data((int) $post_id);
}

add_action('plugins_loaded', function () {
    DM_REST::init();
    DM_SEO::init();
    DM_Admin_Panel::init();
});

add_action('save_post', array('DM_Admin_Panel', 'handle_save_post'), 10, 2);
add_action('before_delete_post', array('DM_Repository', 'delete_post_data'));
add_filter('upload_dir', array('DM_Helper', 'filter_upload_dir_for_rest_media'));
