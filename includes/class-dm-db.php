<?php
/**
 * Database layer for schema lifecycle and low-level SQL operations.
 */
class DM_DB
{
    /**
     * Register DB-related hooks.
     *
     * @return void
     */
    public static function init(): void
    {
        // Runtime DB hooks will be implemented in a later phase.
    }

    /**
     * Handle plugin activation tasks (e.g., schema creation).
     *
     * @return void
     */
    public static function activate(): void
    {
        self::maybe_create_table();
    }

    /**
     * Ensure plugin table exists and matches current schema.
     *
     * @return void
     */
    public static function maybe_create_table(): void
    {
        global $wpdb;

        $table_name = self::get_table_name();
        $table_like = $wpdb->esc_like($table_name);
        $existing_table = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_like));

        self::create_table();

        if ($existing_table !== $table_name) {
            $verified = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_like));
            if ($verified !== $table_name) {
                error_log('[DesignMeta] Failed to create database table: ' . $table_name);
            }
        }
    }

    /**
     * Create or update plugin table schema.
     *
     * @return void
     */
    public static function create_table(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = self::get_create_table_sql();
        dbDelta($sql);

        if (! empty($wpdb->last_error)) {
            error_log('[DesignMeta] dbDelta error: ' . $wpdb->last_error);
        }
    }

    /**
     * Get fully-qualified table name.
     *
     * @return string
     */
    public static function get_table_name(): string
    {
        global $wpdb;

        return $wpdb->prefix . 'dm_meta';
    }

    /**
     * Build CREATE TABLE SQL for dbDelta.
     *
     * @return string
     */
    public static function get_create_table_sql(): string
    {
        global $wpdb;

        $table_name = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        return "CREATE TABLE {$table_name} (
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
    }

    /**
     * Upsert mapped row data by post ID.
     *
     * @param int $post_id Post identifier.
     * @param array<string, mixed> $mapped_data DB-mapped column values.
     * @return bool True on successful write.
     */
    public static function upsert_meta(int $post_id, array $mapped_data): bool
    {
        global $wpdb;

        $columns = [
            'designer',
            'src_designer_url',
            'designer_slug',
            'src_pattern_url',
            'pattern_slug',
            'pin_path',
            'pin_info',
            'meta_description',
        ];

        $table_name = self::get_table_name();
        $insert_columns = array_merge(['post_id'], $columns);

        $values = [$post_id];
        foreach ($columns as $column) {
            $values[] = isset($mapped_data[$column]) ? (string) $mapped_data[$column] : '';
        }

        $placeholders = array_fill(0, count($insert_columns), '%s');
        $placeholders[0] = '%d';

        $update_assignments = [];
        foreach ($columns as $column) {
            $update_assignments[] = "{$column} = VALUES({$column})";
        }

        $sql = "INSERT INTO {$table_name} (" . implode(', ', $insert_columns) . ") VALUES (" . implode(', ', $placeholders) . ") ON DUPLICATE KEY UPDATE " . implode(', ', $update_assignments);
        $prepared = $wpdb->prepare($sql, $values);

        $result = $wpdb->query($prepared);

        return $result !== false;
    }

    /**
     * Fetch a mapped data row by post ID.
     *
     * @param int $post_id Post identifier.
     * @return array<string, mixed>|null Row data or null when missing.
     */
    public static function get_meta_row(int $post_id): ?array
    {
        global $wpdb;

        $table_name = self::get_table_name();
        $sql = "SELECT post_id, designer, src_designer_url, designer_slug, src_pattern_url, pattern_slug, pin_path, pin_info, meta_description FROM {$table_name} WHERE post_id = %d LIMIT 1";
        $prepared = $wpdb->prepare($sql, $post_id);
        $row = $wpdb->get_row($prepared, ARRAY_A);

        if (! is_array($row)) {
            return null;
        }

        return $row;
    }

    /**
     * Delete a row for the given post ID.
     *
     * @param int $post_id Post identifier.
     * @return bool True when a delete query succeeds.
     */
    public static function delete_meta_row(int $post_id): bool
    {
        global $wpdb;

        $table_name = self::get_table_name();
        $sql = "DELETE FROM {$table_name} WHERE post_id = %d";
        $prepared = $wpdb->prepare($sql, $post_id);

        return $wpdb->query($prepared) !== false;
    }

    /**
     * Check whether a slug is already used in a target column.
     *
     * @param string $column Slug column name.
     * @param string $slug Slug candidate.
     * @param int $exclude_post_id Optional post ID to exclude from uniqueness checks.
     * @return bool True if the slug exists.
     */
    public static function slug_exists(string $column, string $slug, int $exclude_post_id = 0): bool
    {
        global $wpdb;

        $allowed_columns = ['designer_slug', 'pattern_slug'];
        if (! in_array($column, $allowed_columns, true) || $slug === '') {
            return false;
        }

        $table_name = self::get_table_name();
        $sql = "SELECT post_id FROM {$table_name} WHERE {$column} = %s LIMIT 1";
        $prepared = $wpdb->prepare($sql, $slug);
        $row = $wpdb->get_row($prepared, ARRAY_A);

        if (! is_array($row)) {
            return false;
        }

        if ($exclude_post_id > 0 && isset($row['post_id']) && (int) $row['post_id'] === $exclude_post_id) {
            return false;
        }

        return true;
    }
}
