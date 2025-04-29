<?php

declare(strict_types=1);

namespace Civi\Micro\Telemetry;

use OpenTelemetry\API\Trace\TracerInterface;

/**
 * Trait TracerAwareTrait
 *
 * Provides integration with OpenTelemetry's TracerInterface.
 * It allows starting and ending spans for distributed tracing,
 * and falls back to simple error logging if no tracer is configured.
 *
 * @api
 */
trait TracerAwareTrait
{
    /**
     * Holds the current tracer instance, or null if none is set.
     */
    protected ?TracerInterface $tracer = null;


    /**
     * Sets the tracer instance used for starting and managing spans.
     *
     * @param TracerInterface $tracer The tracer instance to be used.
     */
    public function setTracer(TracerInterface $tracer): void
    {
        $this->tracer = $tracer;
    }

    /**
     * Starts a new span for a given operation.
     * If a tracer is configured, it creates a real span with optional attributes.
     * If no tracer is available, it logs the operation to the error log instead.
     *
     * @param string $operationName The name of the operation being traced.
     * @param array<string, mixed> $attributes Optional attributes to attach to the span.
     * @return SpanHolder A holder for the active span, or a dummy holder if no tracer is set.
     */
    public function startSpan(string $operationName, array $attributes = []): SpanHolder
    {
        if ($this->tracer !== null) {
            $spanBuilder = $this->tracer->spanBuilder($operationName);
            foreach ($attributes as $key => $value) {
                $spanBuilder->setAttribute($key, $value);
            }
            $span = $spanBuilder->startSpan();
            $span->activate();
            return new SpanHolder($span);
        } else {
            $this->writeTraceFallback($operationName, $attributes);
            return new SpanHolder(null); // No podemos devolver un Span real si no hay tracer
        }
    }

    /**
     * Fallback method to log trace operations when no tracer is configured.
     * It writes a formatted message to the PHP error log.
     *
     * @param string $operationName The name of the operation.
     * @param array<string, mixed> $attributes Optional attributes to include in the log.
     */
    private function writeTraceFallback(string $operationName, array $attributes = []): void
    {
        $attributesStr = !empty($attributes) ? json_encode($attributes) : '';
        error_log('[TRACE] ' . $operationName . ($attributesStr ? ' ' . $attributesStr : ''));
    }
}
