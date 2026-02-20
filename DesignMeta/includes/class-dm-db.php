<?php

if (! defined('ABSPATH')) {
    exit;
}

class DM_DB
{
    public static function table_name()
    {
        global $wpdb;

        return $wpdb->prefix . 'dm_meta';
    }

    public static function activate()
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        global $wpdb;
        $table = self::table_name();
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            post_id BIGINT UNSIGNED NOT NULL,
            designer VARCHAR(191) NOT NULL DEFAULT '',
            src_designer_url TEXT NULL,
            designer_slug VARCHAR(191) NULL,
            src_pattern_url TEXT NULL,
            pattern_slug VARCHAR(191) NULL,
            pin_path VARCHAR(512) NOT NULL DEFAULT '',
            pin_info MEDIUMTEXT NULL,
            meta_description TEXT NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (post_id),
            UNIQUE KEY uniq_designer_slug (designer_slug),
            UNIQUE KEY uniq_pattern_slug (pattern_slug),
            KEY idx_updated_at (updated_at)
        ) {$charset};";

        dbDelta($sql);
    }

    public static function get_row($post_id)
    {
        global $wpdb;
        $table = self::table_name();
        $sql = $wpdb->prepare("SELECT * FROM {$table} WHERE post_id = %d", (int) $post_id);

        return $wpdb->get_row($sql, ARRAY_A);
    }

    public static function delete_row($post_id)
    {
        global $wpdb;
        $table = self::table_name();
        $sql = $wpdb->prepare("DELETE FROM {$table} WHERE post_id = %d", (int) $post_id);

        return $wpdb->query($sql);
    }

    public static function upsert_row(array $row)
    {
        global $wpdb;
        $table = self::table_name();

        $sql = $wpdb->prepare(
            "INSERT INTO {$table}
            (post_id, designer, src_designer_url, designer_slug, src_pattern_url, pattern_slug, pin_path, pin_info, meta_description)
            VALUES (%d, %s, %s, %s, %s, %s, %s, %s, %s)
            ON DUPLICATE KEY UPDATE
            designer = VALUES(designer),
            src_designer_url = VALUES(src_designer_url),
            designer_slug = VALUES(designer_slug),
            src_pattern_url = VALUES(src_pattern_url),
            pattern_slug = VALUES(pattern_slug),
            pin_path = VALUES(pin_path),
            pin_info = VALUES(pin_info),
            meta_description = VALUES(meta_description)",
            (int) $row['post_id'],
            (string) $row['designer'],
            (string) $row['src_designer_url'],
            (string) $row['designer_slug'],
            (string) $row['src_pattern_url'],
            (string) $row['pattern_slug'],
            (string) $row['pin_path'],
            (string) $row['pin_info'],
            (string) $row['meta_description']
        );

        return $wpdb->query($sql);
    }
}
