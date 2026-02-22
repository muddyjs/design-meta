<?php

if (!defined('ABSPATH')) {
    exit;
}

final class DM_SEO
{
    public static function output_meta_description(): void
    {
        if (!is_singular('post')) {
            return;
        }

        $post_id = get_queried_object_id();
        if (!$post_id) {
            return;
        }

        $data = DM_Repository::get_data((int) $post_id);
        $description = trim((string) ($data['meta_description'] ?? ''));
        if ($description === '') {
            return;
        }

        echo '<meta name="description" content="' . esc_attr($description) . '" />' . "\n";
    }
}
