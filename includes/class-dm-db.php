<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Database layer for Design Meta plugin.
 */
class DM_DB
{
    /**
     * Register database related hooks.
     *
     * @return void
     */
    public function register_hooks()
    {
        // Placeholder: register activation or migration hooks.
    }

    /**
     * Create or update plugin database tables.
     *
     * @return void
     */
    public function migrate()
    {
        // Placeholder: implement schema creation and migrations.
    }

    /**
     * Get plugin table name by logical key.
     *
     * @param string $key Logical table key.
     *
     * @return string
     */
    public function get_table_name($key)
    {
        global $wpdb;

        return $wpdb->prefix . 'dm_' . sanitize_key($key);
    }
}
