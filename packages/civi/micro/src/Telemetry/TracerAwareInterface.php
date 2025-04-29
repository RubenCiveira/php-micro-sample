<?php

declare(strict_types=1);

namespace Civi\Micro\Telemetry;

use OpenTelemetry\API\Trace\TracerInterface;

/**
 * @api
 */
interface TracerAwareInterface
{
    public function setTracer(TracerInterface $tracer): void;
}
