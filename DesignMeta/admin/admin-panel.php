<?php

if (! defined('ABSPATH')) {
    exit;
}

class DM_Admin_Panel
{
    const NONCE_ACTION = 'dm_admin_panel_save';
    const NONCE_NAME = 'dm_admin_panel_nonce';

    public static function init()
    {
        add_action('add_meta_boxes', array(__CLASS__, 'register_meta_box'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
    }

    public static function register_meta_box()
    {
        add_meta_box(
            'dm-meta-panel',
            esc_html__('DesignMeta Fields', 'designmeta'),
            array(__CLASS__, 'render_meta_box'),
            'post',
            'normal',
            'default'
        );
    }

    public static function enqueue_assets($hook_suffix)
    {
        if ($hook_suffix !== 'post.php' && $hook_suffix !== 'post-new.php') {
            return;
        }

        wp_enqueue_media();
    }

    public static function render_meta_box($post)
    {
        $data = DM_Repository::get_data((int) $post->ID);
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);

        $designer_preview = $data['designer_url'] !== '' ? home_url('/' . $data['designer_url']) : '';
        $pattern_preview = $data['pattern_url'] !== '' ? home_url('/' . $data['pattern_url']) : '';
        ?>
        <table class="form-table" role="presentation">
            <tbody>
            <?php foreach (DM_Helper::fields() as $field) : ?>
                <tr>
                    <th scope="row"><label for="<?php echo esc_attr('dm_' . $field); ?>"><?php echo esc_html($field); ?></label></th>
                    <td>
                        <?php if ($field === 'pin_info' || $field === 'meta_description') : ?>
                            <textarea class="large-text" rows="3" id="<?php echo esc_attr('dm_' . $field); ?>" name="<?php echo esc_attr('dm_' . $field); ?>"><?php echo esc_textarea($data[$field]); ?></textarea>
                        <?php else : ?>
                            <input type="text" class="regular-text" id="<?php echo esc_attr('dm_' . $field); ?>" name="<?php echo esc_attr('dm_' . $field); ?>" value="<?php echo esc_attr($data[$field]); ?>" />
                        <?php endif; ?>

                        <?php if ($field === 'pin_url') : ?>
                            <button type="button" class="button dm-select-media" data-target="dm_pin_url"><?php echo esc_html__('Select Image', 'designmeta'); ?></button>
                            <div style="margin-top:8px;">
                                <img id="dm-pin-preview" src="<?php echo esc_url($data['pin_url']); ?>" style="max-width:240px;<?php echo $data['pin_url'] === '' ? 'display:none;' : ''; ?>" alt="<?php echo esc_attr__('Pin Preview', 'designmeta'); ?>" />
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p>
            <?php echo esc_html__('Designer preview:', 'designmeta'); ?>
            <a href="<?php echo esc_url($designer_preview); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($designer_preview); ?></a>
            <br />
            <?php echo esc_html__('Pattern preview:', 'designmeta'); ?>
            <a href="<?php echo esc_url($pattern_preview); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($pattern_preview); ?></a>
        </p>
        <script>
            (function($){
                $(document).on('click', '.dm-select-media', function(e){
                    e.preventDefault();
                    var targetId = $(this).data('target');
                    var frame = wp.media({
                        title: 'Select image',
                        button: { text: 'Use this image' },
                        multiple: false
                    });
                    frame.on('select', function(){
                        var attachment = frame.state().get('selection').first().toJSON();
                        $('#' + targetId).val(attachment.url);
                        $('#dm-pin-preview').attr('src', attachment.url).show();
                    });
                    frame.open();
                });

                $('#dm_pin_url').on('input', function(){
                    var val = $(this).val();
                    if (val) {
                        $('#dm-pin-preview').attr('src', val).show();
                    } else {
                        $('#dm-pin-preview').hide();
                    }
                });
            })(jQuery);
        </script>
        <?php
    }

    public static function handle_save_post($post_id, $post)
    {
        if (! isset($_POST[self::NONCE_NAME])) {
            return;
        }

        $nonce = sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME]));
        if (! wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (! isset($post->post_type) || $post->post_type !== 'post') {
            return;
        }

        if (! current_user_can('edit_post', $post_id)) {
            return;
        }

        $input = array();
        foreach (DM_Helper::fields() as $field) {
            $key = 'dm_' . $field;
            if (! isset($_POST[$key])) {
                continue;
            }
            $input[$field] = wp_unslash($_POST[$key]);
        }

        DM_Repository::save_all_data((int) $post_id, $input);
    }
}
