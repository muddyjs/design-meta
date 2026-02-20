<?php
/**
 * REST integration layer for exposing and persisting DesignMeta fields.
 */
class DM_REST
{
    /**
     * Register REST hooks and field declarations.
     *
     * @return void
     */
    public static function init(): void
    {
        // REST hook registrations will be implemented in a later phase.
    }

    /**
     * Register post fields for REST read/write.
     *
     * @return void
     */
    public static function register_fields(): void
    {
        // REST field registration will be implemented in a later phase.
    }

    /**
     * Callback for reading a specific field from repository output.
     *
     * @param array<string, mixed> $object REST object context.
     * @param string $field_name Requested field.
     * @param mixed $request Request context.
     * @return string
     */
    public static function get_field(array $object, string $field_name, $request): string
    {
        return '';
    }

    /**
     * Callback for updating one field using merge semantics.
     *
     * @param mixed $value Incoming field value.
     * @param mixed $post WP_Post-like object.
     * @param string $field_name Field being updated.
     * @return bool True on accepted update.
     */
    public static function update_field($value, $post, string $field_name): bool
    {
        return false;
    }
}
