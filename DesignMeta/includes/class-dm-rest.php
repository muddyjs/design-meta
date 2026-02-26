<?php

if (! defined('ABSPATH')) {
    exit;
}

class DM_REST
{
    public static function init()
    {
        add_action('rest_api_init', array(__CLASS__, 'register_fields'));
    }

    public static function register_fields()
    {
        foreach (DM_Helper::fields() as $field) {
            register_rest_field(
                'post',
                $field,
                array(
                    'get_callback' => array(__CLASS__, 'get_rest_field'),
                    'update_callback' => array(__CLASS__, 'update_rest_field'),
                    'schema' => array(
                        'type' => 'string',
                        'context' => array('view', 'edit'),
                    ),
                )
            );
        }
    }

    public static function get_rest_field($object, $field_name)
    {
        $post_id = isset($object['id']) ? (int) $object['id'] : 0;
        $data = DM_Repository::get_data($post_id);

        return isset($data[$field_name]) ? $data[$field_name] : '';
    }

    public static function update_rest_field($value, $post, $field_name)
    {
        $payload = array($field_name => $value);
        DM_Repository::save_all_data((int) $post->ID, $payload);

        return true;
    }
}
