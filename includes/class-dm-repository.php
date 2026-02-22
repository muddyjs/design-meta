<?php

if (!defined('ABSPATH')) {
    exit;
}

final class DM_Repository
{
    public static function save_all_data(int $post_id, array $input): void
    {
        if ($post_id <= 0) {
            return;
        }

        $existing = self::get_data($post_id);
        $incoming = DM_Helper::sanitize(DM_Helper::whitelist($input));
        $merged = array_merge($existing, $incoming);

        $designer_base = $merged['designer_url'] ?: $merged['designer'];
        $pattern_base = $merged['pattern_url'];

        $designer_slug = DM_Helper::build_designer_slug($post_id, $designer_base);
        $pattern_slug = DM_Helper::build_pattern_slug($pattern_base, $merged['src_pattern_url']);

        $row = [
            'post_id' => $post_id,
            'designer' => $merged['designer'],
            'src_designer_url' => $merged['src_designer_url'],
            'designer_slug' => $designer_slug !== '' ? $designer_slug : null,
            'src_pattern_url' => $merged['src_pattern_url'],
            'pattern_slug' => $pattern_slug !== '' ? $pattern_slug : null,
            'pin_path' => DM_Helper::to_pin_path($merged['pin_url']),
            'pin_info' => $merged['pin_info'],
            'meta_description' => $merged['meta_description'],
        ];

        self::save_with_retry($row, $designer_slug, $pattern_slug);

        wp_cache_delete(DM_Helper::cache_key($post_id), DM_Helper::CACHE_GROUP);
    }

    public static function get_data(int $post_id): array
    {
        $cache_key = DM_Helper::cache_key($post_id);
        $cached = wp_cache_get($cache_key, DM_Helper::CACHE_GROUP);
        if (is_array($cached)) {
            return $cached;
        }

        $defaults = self::defaults();
        $row = DM_DB::get_row($post_id);
        if (!$row) {
            wp_cache_set($cache_key, $defaults, DM_Helper::CACHE_GROUP);

            return $defaults;
        }

        $data = [
            'designer' => (string) ($row['designer'] ?? ''),
            'src_designer_url' => (string) ($row['src_designer_url'] ?? ''),
            'designer_url' => (string) ($row['designer_slug'] ?? ''),
            'src_pattern_url' => (string) ($row['src_pattern_url'] ?? ''),
            'pattern_url' => (string) ($row['pattern_slug'] ?? ''),
            'pin_url' => DM_Helper::to_pin_url((string) ($row['pin_path'] ?? '')),
            'pin_info' => (string) ($row['pin_info'] ?? ''),
            'meta_description' => (string) ($row['meta_description'] ?? ''),
        ];

        wp_cache_set($cache_key, $data, DM_Helper::CACHE_GROUP);

        return $data;
    }

    public static function delete_for_post(int $post_id): void
    {
        DM_DB::delete_row($post_id);
        wp_cache_delete(DM_Helper::cache_key($post_id), DM_Helper::CACHE_GROUP);
    }

    private static function save_with_retry(array $row, string $designer_slug, string $pattern_slug): void
    {
        for ($i = 1; $i <= 20; $i++) {
            if ($i > 1) {
                $suffix = '-' . $i;
                $row['designer_slug'] = $designer_slug ? $designer_slug . $suffix : null;
                $row['pattern_slug'] = $pattern_slug ? $pattern_slug . $suffix : null;
            }

            $saved = DM_DB::upsert($row);
            if ($saved) {
                return;
            }

            $error = DM_DB::last_error();
            if (strpos($error, 'uniq_designer_slug') === false && strpos($error, 'uniq_pattern_slug') === false) {
                error_log('DesignMeta save failed: ' . $error);

                return;
            }
        }

        error_log('DesignMeta save failed after slug retry limit for post_id=' . (int) $row['post_id']);
    }

    private static function defaults(): array
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
}
