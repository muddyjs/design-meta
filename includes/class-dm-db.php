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
        // Reserved for runtime DB related hooks.
    }

    /**
     * Plugin activation callback.
     *
     * @return void
     */
    public static function activate()
    {
        $db = new self();
        $db->migrate();
    }

    /**
     * Create or update plugin database tables.
     *
     * @return void
     */
    public function migrate()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'dm_meta';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = $this->get_create_table_sql($table_name, $charset_collate);

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Requirement: execute dbDelta on activation path.
        $result = dbDelta($sql);
        if (empty($result)) {
            error_log('[DesignMeta] dbDelta returned no statements for table: ' . $table_name);
        }

        $this->maybe_create_table($table_name, $sql);
    }

    /**
     * Return CREATE TABLE SQL for dm_meta.
     *
     * @param string $table_name      Full table name.
     * @param string $charset_collate Charset and collation clause.
     *
     * @return string
     */
    public function get_create_table_sql($table_name, $charset_collate)
    {
        return "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            object_id bigint(20) unsigned NOT NULL,
            meta_key varchar(191) NOT NULL,
            meta_value longtext NULL,
            source varchar(50) NOT NULL DEFAULT '',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY object_id (object_id),
            KEY meta_key (meta_key)
        ) {$charset_collate};";
    }

    /**
     * Attempt to create table if it does not exist.
     *
     * @param string $table_name Full table name.
     * @param string $sql        CREATE TABLE SQL.
     *
     * @return bool
     */
    public function maybe_create_table($table_name, $sql)
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $created = maybe_create_table($table_name, $sql);
        if (! $created && $wpdb->last_error) {
            error_log('[DesignMeta] maybe_create_table failed for ' . $table_name . ': ' . $wpdb->last_error);
        }

        return (bool) $created;
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
