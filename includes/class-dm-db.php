<?php

if (!defined('ABSPATH')) {
    exit;
}

final class DM_DB
{
    public static function init(): void
    {
        // Placeholder for future migrations.
    }

    public static function table(): string
    {
        global $wpdb;

        return $wpdb->prefix . 'dm_meta';
    }

    public static function create_table(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table = self::table();
        $charset_collate = $wpdb->get_charset_collate();

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
        ) {$charset_collate};";

        dbDelta($sql);
    }

    public static function get_row(int $post_id): ?array
    {
        global $wpdb;

        $table = self::table();
        $sql = $wpdb->prepare("SELECT * FROM {$table} WHERE post_id = %d", $post_id);
        $row = $wpdb->get_row($sql, ARRAY_A);

        return $row ?: null;
    }

    public static function upsert(array $row): bool
    {
        global $wpdb;

        $table = self::table();
        $sql = "INSERT INTO {$table}
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
            meta_description = VALUES(meta_description)";

        $prepared = $wpdb->prepare(
            $sql,
            $row['post_id'],
            $row['designer'],
            $row['src_designer_url'],
            $row['designer_slug'],
            $row['src_pattern_url'],
            $row['pattern_slug'],
            $row['pin_path'],
            $row['pin_info'],
            $row['meta_description']
        );

        $result = $wpdb->query($prepared);

        return $result !== false;
    }

    public static function delete_row(int $post_id): void
    {
        global $wpdb;
        $wpdb->delete(self::table(), ['post_id' => $post_id], ['%d']);
    }

    public static function last_error(): string
    {
        global $wpdb;

        return (string) $wpdb->last_error;
    }
}
