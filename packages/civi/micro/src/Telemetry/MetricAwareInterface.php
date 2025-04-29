<?php

declare(strict_types=1);

namespace Civi\Micro\Telemetry;

use Prometheus\CollectorRegistry;

/**
 * @api
 */
interface MetricAwareInterface
{
    public function setCollector(CollectorRegistry $collector);
}
