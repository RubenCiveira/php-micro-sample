<?php

declare(strict_types=1);

namespace Civi\Micro\Management;

use Closure;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;

class MetricsManagement implements ManagementInterface
{
    public function __construct(private readonly CollectorRegistry $collector) {}

    public function name(): string
    {
        return "metrics";
    }

    public function get(): ?Closure
    {
        return function () {
            $renderer = new RenderTextFormat();
            $metrics = $this->collector->getMetricFamilySamples();
            return $renderer->render($metrics);
        };
    }

    public function set(): ?Closure
    {
        return null;
    }
}
