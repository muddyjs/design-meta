<?php

if (!defined('ABSPATH')) {
    exit;
}

final class DM_Helper
{
    public const CACHE_GROUP = 'designmeta';

    public static function fields(): array
    {
        return [
            'designer',
            'src_designer_url',
            'designer_url',
            'src_pattern_url',
            'pattern_url',
            'pin_url',
            'pin_info',
            'meta_description',
        ];
    }

    public static function cache_key(int $post_id): string
    {
        return 'dm:post:' . $post_id;
    }

    public static function whitelist(array $input): array
    {
        return array_intersect_key($input, array_flip(self::fields()));
    }

    public static function sanitize(array $input): array
    {
        $output = [];
        foreach (self::fields() as $field) {
            if (!array_key_exists($field, $input)) {
                continue;
            }

            $value = $input[$field];
            switch ($field) {
                case 'designer':
                    $output[$field] = sanitize_text_field((string) $value);
                    break;
                case 'src_designer_url':
                case 'src_pattern_url':
                    $output[$field] = esc_url_raw((string) $value);
                    break;
                case 'designer_url':
                case 'pattern_url':
                    $output[$field] = sanitize_title((string) $value);
                    break;
                case 'pin_url':
                    $output[$field] = sanitize_text_field((string) $value);
                    break;
                case 'pin_info':
                case 'meta_description':
                    $output[$field] = sanitize_textarea_field((string) $value);
                    break;
            }
        }

        return $output;
    }

    public static function build_designer_slug(int $post_id, string $designer_url): string
    {
        $base = sanitize_title($designer_url);
        if ($base === '') {
            return '';
        }

        $hash_input = $post_id . '|' . gmdate('Y-m-d H:i:s');
        $suffix = substr(md5($hash_input), 0, 4);

        return $base . '-' . $suffix;
    }

    public static function build_pattern_slug(string $pattern_url, string $src_pattern_url): string
    {
        $base = sanitize_title($pattern_url);
        if ($base === '') {
            return '';
        }

        if ($src_pattern_url !== '') {
            $base .= '-' . substr(md5($src_pattern_url), 0, 4);
        }

        return $base;
    }

    public static function to_pin_path(string $pin_url): string
    {
        if ($pin_url === '') {
            return '';
        }

        $uploads = wp_upload_dir();
        $baseurl = rtrim($uploads['baseurl'], '/');
        if (strpos($pin_url, $baseurl) === 0) {
            return ltrim(substr($pin_url, strlen($baseurl)), '/');
        }

        return $pin_url;
    }

    public static function to_pin_url(string $pin_path): string
    {
        if ($pin_path === '') {
            return '';
        }

        if (preg_match('#^https?://#', $pin_path)) {
            return $pin_path;
        }

        $uploads = wp_upload_dir();

        return trailingslashit($uploads['baseurl']) . ltrim($pin_path, '/');
    }
}
