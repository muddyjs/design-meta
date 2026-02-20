<?php
/**
 * Front-end SEO output integration.
 */
class DM_SEO
{
    /**
     * Register front-end hooks.
     *
     * @return void
     */
    public static function init(): void
    {
        add_action('wp_head', [__CLASS__, 'render_meta_description']);
        add_filter('upload_dir', [__CLASS__, 'filter_upload_dir']);
    }

    /**
     * Output meta description tag on single post pages when available.
     *
     * @return void
     */
    public static function render_meta_description(): void
    {
        if (! is_single()) {
            return;
        }

        $post_id = get_queried_object_id();
        if ($post_id <= 0) {
            return;
        }

        $data = dm_get_data((int) $post_id);
        $meta_description = isset($data['meta_description']) ? trim((string) $data['meta_description']) : '';
        if ($meta_description === '') {
            return;
        }

        echo '<meta name="description" content="' . esc_attr($meta_description) . '" />' . "\n";
    }

    /**
     * Override uploads subdir for REST media uploads using X-Target-Date.
     *
     * @param array<string, string> $dirs Upload directories data.
     * @return array<string, string>
     */
    public static function filter_upload_dir(array $dirs): array
    {
        if (! self::is_rest_request()) {
            return $dirs;
        }

        $target = self::get_target_subdir_from_header();
        if ($target === '') {
            return $dirs;
        }

        $dirs['subdir'] = '/' . $target;
        $dirs['path'] = rtrim($dirs['basedir'], '/') . $dirs['subdir'];
        $dirs['url'] = rtrim($dirs['baseurl'], '/') . $dirs['subdir'];

        return $dirs;
    }

    /**
     * Determine whether current request is a REST request.
     *
     * @return bool
     */
    private static function is_rest_request(): bool
    {
        return defined('REST_REQUEST') && REST_REQUEST;
    }

    /**
     * Parse X-Target-Date request header into YYYY/MM.
     *
     * @return string
     */
    private static function get_target_subdir_from_header(): string
    {
        $raw = '';
        if (isset($_SERVER['HTTP_X_TARGET_DATE'])) {
            $raw = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_TARGET_DATE']));
        }

        if ($raw === '') {
            return '';
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $matches) !== 1) {
            return '';
        }

        $year = (int) $matches[1];
        $month = (int) $matches[2];
        $day = (int) $matches[3];

        if (! checkdate($month, $day, $year)) {
            return '';
        }

        return sprintf('%04d/%02d', $year, $month);
    }
}
