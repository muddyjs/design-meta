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
        // Hook registrations will be implemented in a later phase.
    }

    /**
     * Handle plugin activation tasks (e.g., schema creation).
     *
     * @return void
     */
    public static function activate(): void
    {
        // Activation behavior will be implemented in a later phase.
    }

    /**
     * Create or update plugin table schema.
     *
     * @return void
     */
    public static function create_table(): void
    {
        // Table creation SQL will be implemented in a later phase.
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
        return false;
    }

    /**
     * Fetch a mapped data row by post ID.
     *
     * @param int $post_id Post identifier.
     * @return array<string, mixed>|null Row data or null when missing.
     */
    public static function get_meta_row(int $post_id): ?array
    {
        return null;
    }

    /**
     * Delete a row for the given post ID.
     *
     * @param int $post_id Post identifier.
     * @return bool True when a delete query succeeds.
     */
    public static function delete_meta_row(int $post_id): bool
    {
        return false;
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
        return false;
    }
}
