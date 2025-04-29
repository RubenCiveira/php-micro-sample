<?php

declare(strict_types=1);

namespace Civi\Micro\Management;

use Closure;
use Override;

/**
 * Management class responsible for reporting the health status of different application components.
 * 
 * It aggregates health details from multiple providers and computes an overall health status.
 * Implements the {@see ManagementInterface}.
 *
 * @api
 */
class HealthManagement implements ManagementInterface
{
    /**
     * @var array<int, HealthProviderInterface> List of health check providers. Each must implement a method `check(): HealthDetail`.
     */
    private readonly array $providers;

    /**
     * Constructs a new HealthManagement instance.
     *
     * @param array<int, HealthProviderInterface> $providers List of providers responsible for health checking.
     */
    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    /**
     * Returns the name that identifies this management component.
     *
     * @return string Always returns 'health'.
     */
    #[Override]
    public function name(): string
    {
        return 'health';
    }

    /**
     * Returns a Closure that, when executed, returns the current health status.
     *
     * The returned Closure checks each provider and composes a final status:
     * - 'DOWN' if any provider reports 'DOWN'.
     * - 'UNKWOWN' if no provider reports 'DOWN', but at least one reports 'UNKWOWN'.
     * - 'UP' if all providers are 'UP' or no providers are present.
     *
     * The Closure also returns individual components' statuses and optional details.
     *
     * @return Closure|null A Closure that computes the health check when called, or null if not available.
     */
    #[Override]
    public function get(): ?Closure
    {
        return function (): array {
            $status = 'UP';
            $details = [];
            foreach ($this->providers as $provider) {
                $detail = $provider->check();
                if ($detail->status === 'DOWN') {
                    $status = 'DOWN';
                } elseif ($detail->status === 'UNKWOWN') {
                    $status = $status === 'DOWN' ? 'DOWN' : 'UNKWOWN';
                }
                $details[$detail->name] = ['status' => $detail->status];
                if ($detail->details) {
                    $details[$detail->name]['details'] = $detail->details;
                }
            }
            return $details ? ['status' => $status, 'components' => $details] : ['status' => $status];
        };
    }

    /**
     * Returns a Closure to update or set health status.
     *
     * Health status management is read-only in this implementation, 
     * so this method always returns null.
     *
     * @return Closure|null Always returns null.
     */
    #[Override]
    public function set(): ?Closure
    {
        return null;
    }
}
