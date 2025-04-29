<?php

declare(strict_types=1);

namespace Civi\Micro\Management;

/**
 * Represents the health status of a component or subsystem in a service.
 *
 * Provides factory methods to create "up", "down", and "unknown" health details.
 *
 * @api
 */
class HealthDetail
{
    /**
     * Creates a HealthDetail instance with status "UP".
     *
     * @param string $name The name of the component or subsystem.
     * @param array<string, mixed>|null $details Optional additional details about the health status.
     * @return HealthDetail
     */
    public static function up(string $name, ?array $details = null): HealthDetail
    {
        return new HealthDetail(name: $name, status: 'UP', details: $details);
    }

    /**
     * Creates a HealthDetail instance with status "DOWN".
     *
     * @param string $name The name of the component or subsystem.
     * @param array<string, mixed>|null $details Optional additional details about the failure.
     * @return HealthDetail
     */
    public static function down(string $name, ?array $details = null): HealthDetail
    {
        return new HealthDetail(name: $name, status: 'DOWN', details: $details);
    }

    /**
     * Creates a HealthDetail instance with status "UNKNOWN".
     *
     * @param string $name The name of the component or subsystem.
     * @param array<string, mixed>|null $details Optional additional information.
     * @return HealthDetail
     *
     * @note There is a typo in the returned status: "UNKWOWN" instead of "UNKNOWN".
     */
    public static function unknown(string $name, ?array $details = null): HealthDetail
    {
        return new HealthDetail(name: $name, status: 'UNKWOWN', details: $details);
    }

    /**
     * Constructs a new immutable HealthDetail.
     *
     * @param string $status The health status (e.g., "UP", "DOWN", "UNKNOWN").
     * @param string $name The name of the component or subsystem.
     * @param array<string, mixed>|null $details Optional additional details.
     */
    private function __construct(
        public readonly string $status,
        public readonly string $name,
        public readonly ?array $details
    ) {
    }
}
