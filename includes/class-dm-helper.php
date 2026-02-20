<?php
/**
 * Helper utilities for field definitions, sanitization, and URL/path transforms.
 */
class DM_Helper
{
    /**
     * Return whitelist of public fields accepted by admin/REST inputs.
     *
     * @return array<int, string>
     */
    public static function get_fields(): array
    {
        return array_keys(self::get_sanitize_map());
    }

    /**
     * Return per-field sanitize callback map.
     *
     * @return array<string, callable>
     */
    public static function get_sanitize_map(): array
    {
        return [
            'designer' => 'sanitize_text_field',
            'src_designer_url' => 'esc_url_raw',
            'designer_url' => 'sanitize_title',
            'src_pattern_url' => 'esc_url_raw',
            'pattern_url' => 'sanitize_title',
            'pin_url' => 'sanitize_text_field',
            'pin_info' => 'sanitize_textarea_field',
            'meta_description' => 'sanitize_textarea_field',
        ];
    }

    /**
     * Sanitize incoming payload according to per-field rules.
     *
     * @param array<string, mixed> $input Raw input payload.
     * @return array<string, string> Sanitized values.
     */
    public static function sanitize_input(array $input): array
    {
        $result = [];
        $sanitize_map = self::get_sanitize_map();

        foreach ($sanitize_map as $field => $sanitizer) {
            if (! array_key_exists($field, $input)) {
                continue;
            }

            $value = self::stringify($input[$field]);
            $result[$field] = (string) call_user_func($sanitizer, $value);
        }

        return $result;
    }

    /**
     * Convert a public uploads URL into a database relative path.
     *
     * @param string $pin_url Public pin URL.
     * @param string $uploads_base_url Uploads base URL (for pure-function usage).
     * @return string Relative path for persistence.
     */
    public static function to_relative_pin_path(string $pin_url, string $uploads_base_url = ''): string
    {
        $pin_url = trim($pin_url);
        if ($pin_url === '') {
            return '';
        }

        $uploads_base_url = self::normalize_base_url($uploads_base_url);
        if ($uploads_base_url === '' || strpos($pin_url, $uploads_base_url) !== 0) {
            return $pin_url;
        }

        return ltrim(substr($pin_url, strlen($uploads_base_url)), '/');
    }

    /**
     * Convert a database relative path to a public uploads URL.
     *
     * @param string $pin_path Relative path from DB.
     * @param string $uploads_base_url Uploads base URL (for pure-function usage).
     * @return string Public URL for output.
     */
    public static function to_public_pin_url(string $pin_path, string $uploads_base_url = ''): string
    {
        $pin_path = trim($pin_path);
        if ($pin_path === '') {
            return '';
        }

        $uploads_base_url = self::normalize_base_url($uploads_base_url);
        if ($uploads_base_url === '' || preg_match('#^https?://#i', $pin_path) === 1) {
            return $pin_path;
        }

        return $uploads_base_url . '/' . ltrim($pin_path, '/');
    }

    /**
     * Build a stable slug candidate from base value and optional source URL.
     *
     * @param string $slug Raw slug-like value.
     * @param string $source_url Optional source URL for hash suffix.
     * @return string Generated slug candidate.
     */
    public static function build_slug_candidate(string $slug, string $source_url = ''): string
    {
        $base_slug = sanitize_title($slug);
        if ($base_slug === '') {
            return '';
        }

        $source_url = trim($source_url);
        if ($source_url === '') {
            return $base_slug;
        }

        return $base_slug . '-' . substr(md5($source_url), 0, 4);
    }

    /**
     * Cast any scalar-like input into string.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    private static function stringify($value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * Normalize uploads base URL by trimming spaces and trailing slash.
     *
     * @param string $base_url Raw base URL.
     * @return string
     */
    private static function normalize_base_url(string $base_url): string
    {
        return rtrim(trim($base_url), '/');
    }
}
