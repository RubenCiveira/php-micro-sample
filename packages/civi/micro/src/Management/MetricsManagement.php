<?php

declare(strict_types=1);

namespace Civi\Micro\Management;

use Closure;
use Override;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;

/**
 * Management class for exposing Prometheus metrics in the Civi Micro framework.
 *
 * Provides an endpoint to retrieve application metrics in the Prometheus text format.
 *
 * @api
 */
class MetricsManagement implements ManagementInterface
{
    /**
     * Creates a new MetricsManagement instance.
     *
     * @param CollectorRegistry $collector The Prometheus collector registry instance used to gather metrics.
     */
    public function __construct(private readonly CollectorRegistry $collector)
    {
    }

    /**
     * Returns the name of this management endpoint.
     *
     * @return string The management name, which is always "metrics".
     */
    #[Override]
    public function name(): string
    {
        return "metrics";
    }

    /**
     * Provides a Closure that renders the current Prometheus metrics.
     *
     * When executed, the Closure returns the metrics in Prometheus' text-based exposition format.
     *
     * @return Closure|null The closure to retrieve metrics, or null if not applicable.
     */
    #[Override]
    public function get(): ?Closure
    {
        return function () {
            $renderer = new RenderTextFormat();
            $metrics = $this->collector->getMetricFamilySamples();
            return $renderer->render($metrics);
        };
    }

    /**
     * Returns the setter Closure for the management interface.
     *
     * This management endpoint does not support modifying data, so it always returns null.
     *
     * @return Closure|null Always null, as metrics are read-only.
     */
    #[Override]
    public function set(): ?Closure
    {
        return null;
    }
}
