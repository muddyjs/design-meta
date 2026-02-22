<?php

if (!defined('ABSPATH')) {
    exit;
}

final class DM_Admin_Panel
{
    public static function register_meta_box(): void
    {
        add_meta_box(
            'dm_meta_box',
            'DesignMeta',
            [self::class, 'render_meta_box'],
            'post',
            'normal',
            'default'
        );
    }

    public static function render_meta_box(WP_Post $post): void
    {
        wp_nonce_field('dm_save_meta', 'dm_meta_nonce');
        $data = DM_Repository::get_data((int) $post->ID);

        foreach (DM_Helper::fields() as $field) {
            $label = esc_html($field);
            $value = esc_attr((string) ($data[$field] ?? ''));
            echo '<p><label for="dm_' . $label . '"><strong>' . $label . '</strong></label><br/>';
            if (in_array($field, ['pin_info', 'meta_description'], true)) {
                echo '<textarea id="dm_' . $label . '" name="dm[' . $label . ']" rows="3" style="width:100%;">' . esc_textarea((string) ($data[$field] ?? '')) . '</textarea>';
            } else {
                echo '<input id="dm_' . $label . '" name="dm[' . $label . ']" value="' . $value . '" style="width:100%;" />';
            }
            echo '</p>';
        }
    }

    public static function save_post(int $post_id, WP_Post $post): void
    {
        if ($post->post_type !== 'post') {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!isset($_POST['dm_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['dm_meta_nonce'])), 'dm_save_meta')) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $payload = isset($_POST['dm']) && is_array($_POST['dm']) ? wp_unslash($_POST['dm']) : [];
        DM_Repository::save_all_data($post_id, $payload);
    }
}
