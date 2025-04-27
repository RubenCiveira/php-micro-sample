<?php

declare(strict_types=1);

namespace Civi\Micro\Telemetry;

use OpenTelemetry\API\Trace\SpanInterface;

class SpanHolder
{
    /**
     * @internal
     */
    public function __construct(private readonly ?SpanInterface $span)
    {
    }

    public function end()
    {
        if ($this->span) {
            $this->span->end();
        }
    }
}
