<?php

declare(strict_types=1);

namespace Civi\Micro\Management;

use Closure;
use Override;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;

/**
 * @api
 */
class MetricsManagement implements ManagementInterface
{
    public function __construct(private readonly CollectorRegistry $collector)
    {
    }

    #[Override]
    public function name(): string
    {
        return "metrics";
    }

    #[Override]
    public function get(): ?Closure
    {
        return function () {
            $renderer = new RenderTextFormat();
            $metrics = $this->collector->getMetricFamilySamples();
            return $renderer->render($metrics);
        };
    }

    #[Override]
    public function set(): ?Closure
    {
        return null;
    }
}
