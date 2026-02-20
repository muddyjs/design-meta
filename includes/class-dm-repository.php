<?php
/**
 * Repository layer that orchestrates sanitization, caching, and persistence.
 */
class DM_Repository
{
    /**
     * Register repository-level hooks.
     *
     * @return void
     */
    public static function init(): void
    {
        add_action('before_delete_post', [__CLASS__, 'delete_post_data']);
    }

    /**
     * Save all DesignMeta fields for one post.
     *
     * @param int $post_id Post identifier.
     * @param array<string, mixed> $input Incoming payload.
     * @return void
     */
    public static function save_all_data(int $post_id, array $input): void
    {
        if ($post_id <= 0) {
            return;
        }

        $sanitized = DM_Helper::sanitize_input($input);
        $resolved = self::resolve_slugs($post_id, $sanitized);

        $uploads = wp_upload_dir();
        $uploads_base_url = isset($uploads['baseurl']) ? (string) $uploads['baseurl'] : '';

        $mapped = [
            'designer' => isset($resolved['designer']) ? $resolved['designer'] : '',
            'src_designer_url' => isset($resolved['src_designer_url']) ? $resolved['src_designer_url'] : '',
            'designer_slug' => isset($resolved['designer_url']) ? $resolved['designer_url'] : '',
            'src_pattern_url' => isset($resolved['src_pattern_url']) ? $resolved['src_pattern_url'] : '',
            'pattern_slug' => isset($resolved['pattern_url']) ? $resolved['pattern_url'] : '',
            'pin_path' => DM_Helper::to_relative_pin_path(isset($resolved['pin_url']) ? $resolved['pin_url'] : '', $uploads_base_url),
            'pin_info' => isset($resolved['pin_info']) ? $resolved['pin_info'] : '',
            'meta_description' => isset($resolved['meta_description']) ? $resolved['meta_description'] : '',
        ];

        $attempt = 0;
        while ($attempt < 20) {
            $ok = DM_DB::upsert_meta($post_id, $mapped);
            if ($ok) {
                self::flush_cache($post_id);
                return;
            }

            global $wpdb;
            $last_error = isset($wpdb->last_error) ? (string) $wpdb->last_error : '';
            if (stripos($last_error, 'Duplicate entry') === false) {
                error_log('[DesignMeta] Failed to save metadata for post_id=' . $post_id . ': ' . $last_error);
                return;
            }

            $attempt++;
            $resolved = self::resolve_slugs($post_id, $resolved, $attempt + 1);
            $mapped['designer_slug'] = isset($resolved['designer_url']) ? $resolved['designer_url'] : '';
            $mapped['pattern_slug'] = isset($resolved['pattern_url']) ? $resolved['pattern_url'] : '';
        }

        error_log('[DesignMeta] Slug conflict retry exceeded for post_id=' . $post_id);
    }

    /**
     * Read all DesignMeta fields for one post.
     *
     * @param int $post_id Post identifier.
     * @return array<string, string> Normalized output fields.
     */
    public static function get_data(int $post_id): array
    {
        $defaults = self::get_default_data();
        if ($post_id <= 0) {
            return $defaults;
        }

        $cache_key = self::get_cache_key($post_id);
        $cached = wp_cache_get($cache_key, 'designmeta');
        if (is_array($cached)) {
            return array_merge($defaults, $cached);
        }

        $row = DM_DB::get_meta_row($post_id);
        if ($row === null) {
            wp_cache_set($cache_key, $defaults, 'designmeta');
            return $defaults;
        }

        $uploads = wp_upload_dir();
        $uploads_base_url = isset($uploads['baseurl']) ? (string) $uploads['baseurl'] : '';

        $data = [
            'designer' => isset($row['designer']) ? (string) $row['designer'] : '',
            'src_designer_url' => isset($row['src_designer_url']) ? (string) $row['src_designer_url'] : '',
            'designer_url' => isset($row['designer_slug']) ? (string) $row['designer_slug'] : '',
            'src_pattern_url' => isset($row['src_pattern_url']) ? (string) $row['src_pattern_url'] : '',
            'pattern_url' => isset($row['pattern_slug']) ? (string) $row['pattern_slug'] : '',
            'pin_url' => DM_Helper::to_public_pin_url(isset($row['pin_path']) ? (string) $row['pin_path'] : '', $uploads_base_url),
            'pin_info' => isset($row['pin_info']) ? (string) $row['pin_info'] : '',
            'meta_description' => isset($row['meta_description']) ? (string) $row['meta_description'] : '',
        ];

        $merged = array_merge($defaults, $data);
        wp_cache_set($cache_key, $merged, 'designmeta');

        return $merged;
    }

    /**
     * Handle post delete events and cleanup related row data.
     *
     * @param int $post_id Post identifier.
     * @return void
     */
    public static function delete_post_data(int $post_id): void
    {
        if ($post_id <= 0) {
            return;
        }

        DM_DB::delete_meta_row($post_id);
        self::flush_cache($post_id);
    }

    /**
     * Resolve and enforce unique slug values for mapped slug fields.
     *
     * @param int $post_id Post identifier.
     * @param array<string, string> $sanitized Sanitized public fields.
     * @param int $suffix_index Numeric suffix index start (2 => -2).
     * @return array<string, string> Updated fields including resolved slugs.
     */
    public static function resolve_slugs(int $post_id, array $sanitized, int $suffix_index = 1): array
    {
        $resolved = $sanitized;

        $slug_specs = [
            'designer_url' => ['source' => 'src_designer_url', 'column' => 'designer_slug'],
            'pattern_url' => ['source' => 'src_pattern_url', 'column' => 'pattern_slug'],
        ];

        foreach ($slug_specs as $field => $spec) {
            if (! isset($sanitized[$field])) {
                continue;
            }

            $source = isset($sanitized[$spec['source']]) ? $sanitized[$spec['source']] : '';
            $candidate = DM_Helper::build_slug_candidate($sanitized[$field], $source);
            if ($candidate === '') {
                $resolved[$field] = '';
                continue;
            }

            $resolved[$field] = self::find_unique_slug($post_id, $spec['column'], $candidate, $suffix_index);
        }

        return $resolved;
    }

    /**
     * Invalidate cache for a post.
     *
     * @param int $post_id Post identifier.
     * @return void
     */
    public static function flush_cache(int $post_id): void
    {
        wp_cache_delete(self::get_cache_key($post_id), 'designmeta');
    }

    /**
     * Find unique slug with retry suffixing.
     *
     * @param int $post_id Post identifier.
     * @param string $column DB column for uniqueness checks.
     * @param string $base_candidate Base candidate slug.
     * @param int $start_index Retry index start.
     * @return string
     */
    private static function find_unique_slug(int $post_id, string $column, string $base_candidate, int $start_index = 1): string
    {
        $candidate = $base_candidate;

        if ($start_index > 1) {
            $candidate = $base_candidate . '-' . $start_index;
        }

        for ($i = $start_index; $i <= 20; $i++) {
            if ($i > $start_index) {
                $candidate = $base_candidate . '-' . $i;
            }

            if (! DM_DB::slug_exists($column, $candidate, $post_id)) {
                return $candidate;
            }
        }

        return $base_candidate;
    }

    /**
     * Build defaults for all 8 public fields.
     *
     * @return array<string, string>
     */
    private static function get_default_data(): array
    {
        return [
            'designer' => '',
            'src_designer_url' => '',
            'designer_url' => '',
            'src_pattern_url' => '',
            'pattern_url' => '',
            'pin_url' => '',
            'pin_info' => '',
            'meta_description' => '',
        ];
    }

    /**
     * Get object cache key for a post.
     *
     * @param int $post_id Post identifier.
     * @return string
     */
    private static function get_cache_key(int $post_id): string
    {
        return 'dm:post:' . $post_id;
    }
}
