<?php
/**
 * Plugin Name: Design Meta
 * Description: Design Meta plugin bootstrap file.
 * Version: 0.1.0
 * Author: Design Meta Team
 */

if (! defined('ABSPATH')) {
    exit;
}

define('DM_PLUGIN_FILE', __FILE__);
define('DM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DM_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once DM_PLUGIN_DIR . 'includes/class-dm-helper.php';
require_once DM_PLUGIN_DIR . 'includes/class-dm-db.php';
require_once DM_PLUGIN_DIR . 'includes/class-dm-repository.php';
require_once DM_PLUGIN_DIR . 'includes/class-dm-rest.php';
require_once DM_PLUGIN_DIR . 'includes/class-dm-seo.php';
require_once DM_PLUGIN_DIR . 'admin/admin-panel.php';

/**
 * Main plugin bootstrap class.
 */
class DesignMeta
{
    /** @var DM_DB */
    private $db;

    /** @var DM_Repository */
    private $repository;

    /** @var DM_REST */
    private $rest;

    /** @var DM_SEO */
    private $seo;

    /** @var DM_Admin_Panel */
    private $admin_panel;

    /**
     * Register plugin hooks and initialize component classes.
     *
     * @return void
     */
    public function init()
    {
        $this->db = new DM_DB();
        $this->repository = new DM_Repository($this->db);
        $this->rest = new DM_REST($this->repository);
        $this->seo = new DM_SEO($this->repository);
        $this->admin_panel = new DM_Admin_Panel($this->repository);

        $this->db->register_hooks();
        $this->rest->register_routes();
        $this->seo->register_hooks();
        $this->admin_panel->register_hooks();
    }
}

add_action('plugins_loaded', function () {
    $plugin = new DesignMeta();
    $plugin->init();
});
