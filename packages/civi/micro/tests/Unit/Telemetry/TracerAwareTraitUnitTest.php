<?php

declare(strict_types=1);

namespace Civi\Micro\Telemetry\Tests;

use Civi\Micro\Telemetry\SpanHolder;
use Civi\Micro\Telemetry\TracerAwareTrait;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\API\Trace\SpanBuilderInterface;
use PHPUnit\Framework\TestCase;

class TracerAwareTraitUnitTest extends TestCase
{
    private TracerAwareTraitTestClass $instance;

    protected function setUp(): void
    {
        $this->instance = new TracerAwareTraitTestClass();
    }

    public function testSetTracerAndStartSpan(): void
    {
        $span = $this->createMock(\OpenTelemetry\API\Trace\SpanInterface::class);
        $span->expects($this->once())->method('activate');

        $spanBuilder = $this->createMock(\OpenTelemetry\API\Trace\SpanBuilderInterface::class);
        $spanBuilder->method('setAttribute')
            ->willReturnSelf();
        $spanBuilder->expects($this->once())
            ->method('startSpan')
            ->willReturn($span);

        $tracer = $this->createMock(\OpenTelemetry\API\Trace\TracerInterface::class);
        $tracer->expects($this->once())
            ->method('spanBuilder')
            ->with('operation')
            ->willReturn($spanBuilder);

        $this->instance->setTracer($tracer);

        $spanHolder = $this->instance->callStartSpan('operation', ['attr1' => 'value1', 'attr2' => 'value2']);
        $this->assertInstanceOf(SpanHolder::class, $spanHolder);
    }

    public function testStartSpanWithoutTracer(): void
    {
        $this->expectOutputRegex('/fallback_operation\s+\{"key":"value"\}\s*/i');
        ob_start();
        $spanHolder = $this->instance->callStartSpan('fallback_operation', ['key' => 'value']);
        ob_end_flush();
        $this->assertInstanceOf(SpanHolder::class, $spanHolder);
    }
}

class TracerAwareTraitTestClass
{
    use TracerAwareTrait {
        startSpan as callStartSpan;
    }
}
