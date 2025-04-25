<?php

declare(strict_types=1);

namespace Civi\Micro\Telemetry;

use OpenTelemetry\API\Trace\TracerInterface;

trait TracerAwareTrait
{
    protected ?TracerInterface $tracer = null;

    public function setTracer(TracerInterface $tracer): void
    {
        $this->tracer = $tracer;
    }

    /**
     * Inicia un span de trazado. 
     * Si no hay tracer configurado, escribe en error_log.
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
     * Finaliza un span iniciado previamente.
     */
    public function endSpan(SpanHolder $span): void
    {
        $span->end();
    }

    private function writeTraceFallback(string $operationName, array $attributes = []): void
    {
        $attributesStr = !empty($attributes) ? json_encode($attributes) : '';
        error_log('[TRACE] ' . $operationName . ($attributesStr ? ' ' . $attributesStr : ''));
    }
}
