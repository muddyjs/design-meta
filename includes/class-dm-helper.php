<?php
/**
 * Helper utilities for field definitions, sanitization, and URL/path transforms.
 */
class DM_Helper
{
    /**
     * Return whitelist of public fields accepted by admin/REST inputs.
     *
     * @return array<int, string>
     */
    public static function get_fields(): array
    {
        return [
            'designer',
            'src_designer_url',
            'designer_url',
            'src_pattern_url',
            'pattern_url',
            'pin_url',
            'pin_info',
            'meta_description',
        ];
    }

    /**
     * Sanitize incoming payload according to per-field rules.
     *
     * @param array<string, mixed> $input Raw input payload.
     * @return array<string, string> Sanitized values.
     */
    public static function sanitize_input(array $input): array
    {
        return [];
    }

    /**
     * Convert a public uploads URL into a database relative path.
     *
     * @param string $pin_url Public pin URL.
     * @return string Relative path for persistence.
     */
    public static function to_relative_pin_path(string $pin_url): string
    {
        return '';
    }

    /**
     * Convert a database relative path to a public uploads URL.
     *
     * @param string $pin_path Relative path from DB.
     * @return string Public URL for output.
     */
    public static function to_public_pin_url(string $pin_path): string
    {
        return '';
    }

    /**
     * Build a stable slug candidate from base value and optional source URL.
     *
     * @param string $slug Raw slug-like value.
     * @param string $source_url Optional source URL for hash suffix.
     * @return string Generated slug candidate.
     */
    public static function build_slug_candidate(string $slug, string $source_url = ''): string
    {
        return '';
    }
}
