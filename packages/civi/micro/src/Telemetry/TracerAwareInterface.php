<?php

declare(strict_types=1);

namespace Civi\Micro\Telemetry;

use OpenTelemetry\API\Trace\TracerInterface;

/**
 * Interface for components that are aware of an OpenTelemetry Tracer instance.
 *
 * Classes implementing this interface can receive a Tracer to create and manage spans
 * for distributed tracing purposes.
 *
 * @api
 */
interface TracerAwareInterface
{
    /**
     * Injects an OpenTelemetry Tracer into the implementing class.
     *
     * @param TracerInterface $tracer The tracer instance used to create and manage spans.
     * @return void
     */
    public function setTracer(TracerInterface $tracer): void;
}
