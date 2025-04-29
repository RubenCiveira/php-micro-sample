<?php

declare(strict_types=1);

namespace Civi\Micro\Telemetry;

use OpenTelemetry\API\Trace\SpanInterface;

/**
 * Holds a reference to a span and provides a safe method to end it.
 *
 * @api
 */
class SpanHolder
{
    /**
     * Constructs a new SpanHolder.
     *
     * @internal This class is intended for internal use. It is not part of the public API and may change without notice.
     *
     * @param SpanInterface|null $span The span instance to hold. Can be null if no span is active.
     */
    public function __construct(private readonly ?SpanInterface $span)
    {
    }

    /**
     * Safely ends the held span if it exists.
     *
     * This method will call `end()` on the span only if the span is not null.
     * If no span is set, the method does nothing.
     *
     * This is useful to ensure spans are properly finished without requiring null checks in user code.
     *
     * @return void
     */
    public function end()
    {
        if ($this->span) {
            $this->span->end();
        }
    }
}
