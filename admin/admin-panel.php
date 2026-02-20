<?php
/**
 * Admin panel integration for DesignMeta edit screen UI.
 */
class DM_Admin_Panel
{
    /**
     * Nonce action string.
     */
    private const NONCE_ACTION = 'dm_save_meta';

    /**
     * Nonce field name.
     */
    private const NONCE_NAME = 'dm_meta_nonce';

    /**
     * Register admin hooks.
     *
     * @return void
     */
    public static function init(): void
    {
        add_action('add_meta_boxes', [__CLASS__, 'register_metabox']);
        add_action('save_post', [__CLASS__, 'save_metabox']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }

    /**
     * Register post edit metabox.
     *
     * @return void
     */
    public static function register_metabox(): void
    {
        add_meta_box(
            'dm-meta-box',
            'DesignMeta',
            [__CLASS__, 'render_metabox'],
            'post',
            'normal',
            'default'
        );
    }

    /**
     * Enqueue media scripts for image selection.
     *
     * @param string $hook_suffix Current admin page hook.
     * @return void
     */
    public static function enqueue_assets(string $hook_suffix): void
    {
        if ($hook_suffix !== 'post.php' && $hook_suffix !== 'post-new.php') {
            return;
        }

        wp_enqueue_media();
    }

    /**
     * Render metabox HTML.
     *
     * @param mixed $post Current post object.
     * @return void
     */
    public static function render_metabox($post): void
    {
        if (! is_object($post) || ! isset($post->ID)) {
            return;
        }

        $data = dm_get_data((int) $post->ID);
        $fields = DM_Helper::get_fields();

        wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);

        echo '<table class="form-table" role="presentation">';

        foreach ($fields as $field) {
            $value = isset($data[$field]) ? (string) $data[$field] : '';
            echo '<tr>';
            echo '<th scope="row"><label for="dm_' . esc_attr($field) . '">' . esc_html($field) . '</label></th>';
            echo '<td>';

            if ($field === 'pin_info' || $field === 'meta_description') {
                echo '<textarea class="large-text" rows="4" id="dm_' . esc_attr($field) . '" name="dm_fields[' . esc_attr($field) . ']">' . esc_textarea($value) . '</textarea>';
            } elseif ($field === 'pin_url') {
                self::render_pin_field($value);
            } else {
                echo '<input class="regular-text" type="text" id="dm_' . esc_attr($field) . '" name="dm_fields[' . esc_attr($field) . ']" value="' . esc_attr($value) . '" />';
            }

            if ($field === 'designer_url' && $value !== '') {
                echo '<p class="description">Slug: <code>' . esc_html($value) . '</code></p>';
            }
            if ($field === 'pattern_url' && $value !== '') {
                echo '<p class="description">Slug: <code>' . esc_html($value) . '</code></p>';
            }

            echo '</td>';
            echo '</tr>';
        }

        echo '</table>';
        self::render_pin_script();
    }

    /**
     * Handle metabox save from post editor.
     *
     * @param int $post_id Post identifier.
     * @return void
     */
    public static function save_metabox(int $post_id): void
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }

        if (! isset($_POST[self::NONCE_NAME])) {
            return;
        }

        $nonce = sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME]));
        if (! wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            return;
        }

        if (! current_user_can('edit_post', $post_id)) {
            return;
        }

        $raw_fields = [];
        if (isset($_POST['dm_fields']) && is_array($_POST['dm_fields'])) {
            $raw_fields = wp_unslash($_POST['dm_fields']);
        }

        dm_save_all_data($post_id, $raw_fields);
    }

    /**
     * Render pin URL field with media select button and preview.
     *
     * @param string $pin_url Current pin URL.
     * @return void
     */
    private static function render_pin_field(string $pin_url): void
    {
        echo '<input class="regular-text" type="text" id="dm_pin_url" name="dm_fields[pin_url]" value="' . esc_attr($pin_url) . '" /> ';
        echo '<button type="button" class="button" id="dm_pin_select">' . esc_html__('Select Image', 'designmeta') . '</button>';

        $preview_style = $pin_url === '' ? 'display:none;max-width:240px;height:auto;margin-top:8px;' : 'display:block;max-width:240px;height:auto;margin-top:8px;';
        echo '<div><img id="dm_pin_preview" src="' . esc_url($pin_url) . '" alt="" style="' . esc_attr($preview_style) . '" /></div>';
    }

    /**
     * Render lightweight inline script for media picker and preview update.
     *
     * @return void
     */
    private static function render_pin_script(): void
    {
        echo '<script>';
        echo '(function($){';
        echo 'var frame;';
        echo 'var $input = $("#dm_pin_url");';
        echo 'var $preview = $("#dm_pin_preview");';
        echo '$("#dm_pin_select").on("click", function(e){';
        echo 'e.preventDefault();';
        echo 'if (frame) { frame.open(); return; }';
        echo 'frame = wp.media({title: "Select Image", button: {text: "Use this image"}, multiple: false});';
        echo 'frame.on("select", function(){';
        echo 'var attachment = frame.state().get("selection").first().toJSON();';
        echo '$input.val(attachment.url).trigger("change");';
        echo '});';
        echo 'frame.open();';
        echo '});';
        echo '$input.on("input change", function(){';
        echo 'var url = $(this).val();';
        echo 'if (!url) { $preview.hide().attr("src", ""); return; }';
        echo '$preview.attr("src", url).show();';
        echo '});';
        echo '})(jQuery);';
        echo '</script>';
    }
}
