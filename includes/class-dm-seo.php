<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * SEO integration class for Design Meta plugin.
 */
class DM_SEO
{
    /** @var DM_Repository */
    private $repository;

    /**
     * Constructor.
     *
     * @param DM_Repository $repository Data repository.
     */
    public function __construct(DM_Repository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Register SEO related WordPress hooks.
     *
     * @return void
     */
    public function register_hooks()
    {
        // Placeholder: add_filter / add_action registrations.
    }

    /**
     * Build SEO metadata for a given object.
     *
     * @param int $object_id WordPress object ID.
     *
     * @return array
     */
    public function build_meta($object_id)
    {
        // Placeholder: construct meta from repository data.
        return array();
    }
}
