<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * REST API controller for Design Meta plugin.
 */
class DM_REST
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
     * Register plugin REST routes.
     *
     * @return void
     */
    public function register_routes()
    {
        add_action('rest_api_init', array($this, 'register_designmeta_routes'));
    }

    /**
     * Register route definitions under rest_api_init.
     *
     * @return void
     */
    public function register_designmeta_routes()
    {
        // Placeholder: define register_rest_route() mappings.
    }

    /**
     * Validate REST request payload.
     *
     * @param array $payload Request payload.
     *
     * @return bool
     */
    public function validate_payload(array $payload)
    {
        // Placeholder: add payload validation logic.
        return true;
    }
}
