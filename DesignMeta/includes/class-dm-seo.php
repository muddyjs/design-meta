<?php

if (! defined('ABSPATH')) {
    exit;
}

class DM_SEO
{
    public static function init()
    {
        add_action('wp_head', array(__CLASS__, 'render_meta_description'), 1);
    }

    public static function render_meta_description()
    {
        if (! is_single()) {
            return;
        }

        $post_id = get_the_ID();
        if (! $post_id) {
            return;
        }

        $data = DM_Repository::get_data((int) $post_id);
        if (empty($data['meta_description'])) {
            return;
        }

        echo '<meta name="description" content="' . esc_attr($data['meta_description']) . '" />' . "\n";
    }
}
