<?php

if (! defined('ABSPATH')) {
    exit;
}

class DM_Helper
{
    const CACHE_GROUP = 'designmeta';

    public static function fields()
    {
        return array(
            'designer',
            'src_designer_url',
            'designer_url',
            'src_pattern_url',
            'pattern_url',
            'pin_url',
            'pin_info',
            'meta_description',
        );
    }

    public static function sanitize_input(array $input)
    {
        $sanitized = array();
        foreach (self::fields() as $field) {
            if (! array_key_exists($field, $input)) {
                continue;
            }
            switch ($field) {
                case 'designer':
                    $sanitized[$field] = sanitize_text_field($input[$field]);
                    break;
                case 'src_designer_url':
                    $sanitized[$field] = esc_url_raw($input[$field]);
                    break;
                case 'designer_url':
                    $sanitized[$field] = sanitize_title($input[$field]);
                    break;
                case 'src_pattern_url':
                    $sanitized[$field] = esc_url_raw($input[$field]);
                    break;
                case 'pattern_url':
                    $sanitized[$field] = sanitize_title($input[$field]);
                    break;
                case 'pin_url':
                    $sanitized[$field] = sanitize_text_field($input[$field]);
                    break;
                case 'pin_info':
                    $sanitized[$field] = sanitize_textarea_field($input[$field]);
                    break;
                case 'meta_description':
                    $sanitized[$field] = sanitize_textarea_field($input[$field]);
                    break;
            }
        }

        return $sanitized;
    }

    public static function to_db_row($post_id, array $data)
    {
        $uploads = wp_get_upload_dir();
        $pin_path = '';
        if (! empty($data['pin_url'])) {
            $pin_path = self::absolute_to_relative_upload_path($data['pin_url'], $uploads['baseurl']);
        }

        return array(
            'post_id' => (int) $post_id,
            'designer' => isset($data['designer']) ? $data['designer'] : '',
            'src_designer_url' => isset($data['src_designer_url']) ? $data['src_designer_url'] : '',
            'designer_slug' => isset($data['designer_url']) ? $data['designer_url'] : '',
            'src_pattern_url' => isset($data['src_pattern_url']) ? $data['src_pattern_url'] : '',
            'pattern_slug' => isset($data['pattern_url']) ? $data['pattern_url'] : '',
            'pin_path' => $pin_path,
            'pin_info' => isset($data['pin_info']) ? $data['pin_info'] : '',
            'meta_description' => isset($data['meta_description']) ? $data['meta_description'] : '',
        );
    }

    public static function db_row_to_output(array $row)
    {
        $uploads = wp_get_upload_dir();
        $pin_url = '';
        if (! empty($row['pin_path'])) {
            $pin_url = self::relative_to_absolute_upload_url($row['pin_path'], $uploads['baseurl']);
        }

        return array(
            'designer' => isset($row['designer']) ? (string) $row['designer'] : '',
            'src_designer_url' => isset($row['src_designer_url']) ? (string) $row['src_designer_url'] : '',
            'designer_url' => isset($row['designer_slug']) ? (string) $row['designer_slug'] : '',
            'src_pattern_url' => isset($row['src_pattern_url']) ? (string) $row['src_pattern_url'] : '',
            'pattern_url' => isset($row['pattern_slug']) ? (string) $row['pattern_slug'] : '',
            'pin_url' => $pin_url,
            'pin_info' => isset($row['pin_info']) ? (string) $row['pin_info'] : '',
            'meta_description' => isset($row['meta_description']) ? (string) $row['meta_description'] : '',
        );
    }

    public static function default_output()
    {
        $output = array();
        foreach (self::fields() as $field) {
            $output[$field] = '';
        }

        return $output;
    }

    public static function absolute_to_relative_upload_path($url, $baseurl)
    {
        $clean_url = untrailingslashit((string) $url);
        $clean_base = untrailingslashit((string) $baseurl);

        if ($clean_base !== '' && strpos($clean_url, $clean_base) === 0) {
            return ltrim(substr($clean_url, strlen($clean_base)), '/');
        }

        return ltrim((string) $url, '/');
    }

    public static function relative_to_absolute_upload_url($path, $baseurl)
    {
        return trailingslashit((string) $baseurl) . ltrim((string) $path, '/');
    }

    public static function cache_key($post_id)
    {
        return 'dm:post:' . (int) $post_id;
    }

    public static function generate_slug($raw_slug, $source_url)
    {
        $slug = sanitize_title($raw_slug);
        if ($slug === '') {
            return '';
        }

        if (! empty($source_url)) {
            $hash = substr(md5((string) $source_url), 0, 4);
            $slug .= '-' . $hash;
        }

        return $slug;
    }

    public static function is_rest_media_upload_request()
    {
        if (! defined('REST_REQUEST') || REST_REQUEST !== true) {
            return false;
        }

        $method = isset($_SERVER['REQUEST_METHOD']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'])) : '';
        if (strtoupper($method) !== 'POST') {
            return false;
        }

        $uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';

        return strpos($uri, '/wp/v2/media') !== false;
    }

    public static function filter_upload_dir_for_rest_media($dirs)
    {
        if (! self::is_rest_media_upload_request()) {
            return $dirs;
        }

        $target_date = '';
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (is_array($headers) && isset($headers['X-Target-Date'])) {
                $target_date = sanitize_text_field($headers['X-Target-Date']);
            }
        }

        if ($target_date === '' && isset($_SERVER['HTTP_X_TARGET_DATE'])) {
            $target_date = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_TARGET_DATE']));
        }

        if (! preg_match('/^(\d{4})\/(\d{2})$/', $target_date, $matches)) {
            return $dirs;
        }

        $subdir = '/' . $matches[1] . '/' . $matches[2];
        $dirs['subdir'] = $subdir;
        $dirs['path'] = $dirs['basedir'] . $subdir;
        $dirs['url'] = $dirs['baseurl'] . $subdir;

        return $dirs;
    }
}
