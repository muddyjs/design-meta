<?php

if (!defined('ABSPATH')) {
    exit;
}

final class DM_REST
{
    public static function register(): void
    {
        register_rest_field(
            'post',
            'designmeta',
            [
                'get_callback' => [self::class, 'get_field'],
                'update_callback' => [self::class, 'update_field'],
                'schema' => [
                    'description' => 'DesignMeta custom fields',
                    'type' => 'object',
                    'context' => ['view', 'edit'],
                ],
            ]
        );
    }

    public static function get_field(array $post_arr): array
    {
        return DM_Repository::get_data((int) $post_arr['id']);
    }

    public static function update_field($value, WP_Post $post): bool
    {
        if (!is_array($value)) {
            return false;
        }

        DM_Repository::save_all_data((int) $post->ID, $value);

        return true;
    }
}
