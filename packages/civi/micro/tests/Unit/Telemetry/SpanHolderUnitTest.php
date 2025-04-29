<?php

declare(strict_types=1);

namespace Civi\Micro\Telemetry\Tests;

use Civi\Micro\Telemetry\SpanHolder;
use OpenTelemetry\API\Trace\SpanInterface;
use PHPUnit\Framework\TestCase;

class SpanHolderUnitTest extends TestCase
{
    public function testEndWithSpan(): void
    {
        $span = $this->createMock(SpanInterface::class);
        $span->expects($this->once())
             ->method('end');

        $holder = new SpanHolder($span);
        $holder->end();
    }

    public function testEndWithoutSpan(): void
    {
        $holder = new SpanHolder(null);

        // Nothing should happen, but we can still call it without errors
        $holder->end();

        // No assertions needed: we are testing that no exception is thrown
        $this->assertTrue(true);
    }
}
