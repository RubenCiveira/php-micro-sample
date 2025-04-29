<?php

declare(strict_types=1);

namespace Civi\Micro;

/**
 * Class AppConfig
 *
 * Application configuration holder.
 *
 * This class defines configuration options related to the internal application management endpoint.
 * It is designed to be immutable after construction.
 */
class AppConfig
{
    /**
    * @var string The base endpoint path used for internal management operations (e.g., health checks, metrics).
    */
    public readonly string $managementEndpoint;

    /**
     * AppConfig constructor.
     *
     * @api
     *
     * @param string|null $managementEndpoint Optional custom endpoint path. Defaults to "/management" if not provided.
     */
    public function __construct(?string $managementEndpoint)
    {
        $this->managementEndpoint = $managementEndpoint ?? "/management";
    }
}
