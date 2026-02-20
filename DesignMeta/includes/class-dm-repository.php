<?php

if (! defined('ABSPATH')) {
    exit;
}

class DM_Repository
{
    public static function get_data($post_id)
    {
        $post_id = (int) $post_id;
        if ($post_id <= 0) {
            return DM_Helper::default_output();
        }

        $cache_key = DM_Helper::cache_key($post_id);
        $cached = wp_cache_get($cache_key, DM_Helper::CACHE_GROUP);
        if (is_array($cached)) {
            return $cached;
        }

        $row = DM_DB::get_row($post_id);
        if (! is_array($row)) {
            $output = DM_Helper::default_output();
            wp_cache_set($cache_key, $output, DM_Helper::CACHE_GROUP);

            return $output;
        }

        $output = DM_Helper::db_row_to_output($row);
        wp_cache_set($cache_key, $output, DM_Helper::CACHE_GROUP);

        return $output;
    }

    public static function save_all_data($post_id, array $input)
    {
        $post_id = (int) $post_id;
        if ($post_id <= 0) {
            return;
        }

        $existing = self::get_data($post_id);
        $allowed = array_intersect_key($input, array_flip(DM_Helper::fields()));
        $sanitized = DM_Helper::sanitize_input($allowed);
        $merged = array_merge($existing, $sanitized);

        $merged['designer_url'] = DM_Helper::generate_slug(
            $merged['designer_url'],
            $merged['src_designer_url']
        );
        $merged['pattern_url'] = DM_Helper::generate_slug(
            $merged['pattern_url'],
            $merged['src_pattern_url']
        );

        $max_retry = 20;
        $attempt = 1;

        while ($attempt <= $max_retry) {
            $row = DM_Helper::to_db_row($post_id, $merged);
            $result = DM_DB::upsert_row($row);
            if ($result !== false) {
                wp_cache_delete(DM_Helper::cache_key($post_id), DM_Helper::CACHE_GROUP);
                return;
            }

            global $wpdb;
            $error = (string) $wpdb->last_error;
            if (strpos($error, 'uniq_designer_slug') !== false) {
                $merged['designer_url'] = self::append_increment_suffix($merged['designer_url'], $attempt + 1);
            } elseif (strpos($error, 'uniq_pattern_slug') !== false) {
                $merged['pattern_url'] = self::append_increment_suffix($merged['pattern_url'], $attempt + 1);
            } else {
                error_log('DesignMeta save failed: ' . $error);
                return;
            }

            $attempt++;
        }

        error_log('DesignMeta slug retry exceeded for post_id=' . $post_id);
    }

    public static function delete_post_data($post_id)
    {
        $post_id = (int) $post_id;
        if ($post_id <= 0) {
            return;
        }

        DM_DB::delete_row($post_id);
        wp_cache_delete(DM_Helper::cache_key($post_id), DM_Helper::CACHE_GROUP);
    }

    private static function append_increment_suffix($slug, $index)
    {
        if ($slug === '') {
            return '';
        }

        $slug = preg_replace('/-\d+$/', '', $slug);

        return $slug . '-' . (int) $index;
    }
}
