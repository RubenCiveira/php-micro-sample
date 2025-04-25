<?php

declare(strict_types=1);

namespace Civi\Micro\Telemetry;

use OpenTelemetry\API\Trace\TracerInterface;

interface TracerAwareInterface
{
    public function setTracer(TracerInterface $tracer): void;
}