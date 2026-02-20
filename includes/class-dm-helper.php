<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Utility helper class for common plugin operations.
 */
class DM_Helper
{
    /**
     * Build a standardized response payload.
     *
     * @param bool  $success Indicates whether the operation succeeded.
     * @param mixed $data    Optional data payload.
     * @param array $meta    Optional metadata.
     *
     * @return array
     */
    public static function build_response($success, $data = null, array $meta = array())
    {
        return array(
            'success' => (bool) $success,
            'data'    => $data,
            'meta'    => $meta,
        );
    }

    /**
     * Sanitize plugin text field.
     *
     * @param string $value Raw text input.
     *
     * @return string
     */
    public static function sanitize_text($value)
    {
        return sanitize_text_field($value);
    }
}
