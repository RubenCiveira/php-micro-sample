<?php

declare(strict_types=1);

namespace Civi\Micro\Telemetry;

use Prometheus\CollectorRegistry;

/**
 * Interface for components that are aware of a Prometheus CollectorRegistry instance.
 *
 * Classes implementing this interface can receive a CollectorRegistry to
 * register and manage custom metrics such as counters, gauges, histograms, and summaries.
 *
 * @api
 */
interface MetricAwareInterface
{
    /**
     * Injects a Prometheus CollectorRegistry into the implementing class.
     *
     * @param CollectorRegistry $collector The registry instance for metric collection and management.
     * @return void
     */
    public function setCollector(CollectorRegistry $collector);
}
