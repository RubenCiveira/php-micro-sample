<?php

declare(strict_types=1);

namespace Civi\Micro\Telemetry\Tests;

use Civi\Micro\Telemetry\MetricAwareTrait;
use PHPUnit\Framework\TestCase;
use Prometheus\CollectorRegistry;
use Prometheus\Counter;
use Prometheus\Histogram;
use Prometheus\Gauge;

class MetricAwareTraitUnitTest extends TestCase
{
    private object $instance;

    protected function setUp(): void
    {
        // Crear una clase anónima que use el trait
        $this->instance = new class {
            use MetricAwareTrait;

            public function callIncrementCounter(string $name, array $labels = [], float $value = 1.0): void
            {
                $this->incrementCounter($name, $labels, $value);
            }

            public function callObserveHistogram(string $name, float $observation, array $labels = []): void
            {
                $this->observeHistogram($name, $observation, $labels);
            }

            public function callSetGauge(string $name, float $value, array $labels = []): void
            {
                $this->setGauge($name, $value, $labels);
            }

            public function injectMetricRegistry(CollectorRegistry $registry): void
            {
                $this->setMetricRegistry($registry);
            }
        };
    }

    public function testIncrementCounterWithRegistry(): void
    {
        $counterMock = $this->createMock(Counter::class);
        $counterMock->expects($this->once())
            ->method('incBy')
            ->with(5.0, ['value1']);

        $registryMock = $this->createMock(CollectorRegistry::class);
        $registryMock->expects($this->once())
            ->method('getOrRegisterCounter')
            ->with('app', 'test_counter', 'Auto-generated counter', ['label1'])
            ->willReturn($counterMock);

        $this->instance->injectMetricRegistry($registryMock);
        $this->instance->callIncrementCounter('test_counter', ['label1' => 'value1'], 5.0);
    }

    public function testObserveHistogramWithRegistry(): void
    {
        $histogramMock = $this->createMock(Histogram::class);
        $histogramMock->expects($this->once())
            ->method('observe')
            ->with(3.5, ['value2']);

        $registryMock = $this->createMock(CollectorRegistry::class);
        $registryMock->expects($this->once())
            ->method('getOrRegisterHistogram')
            ->with('app', 'test_histogram', 'Auto-generated histogram', ['label2'])
            ->willReturn($histogramMock);

        $this->instance->injectMetricRegistry($registryMock);
        $this->instance->callObserveHistogram('test_histogram', 3.5, ['label2' => 'value2']);
    }

    public function testSetGaugeWithRegistry(): void
    {
        $gaugeMock = $this->createMock(Gauge::class);
        $gaugeMock->expects($this->once())
            ->method('set')
            ->with(42.0, ['value3']);

        $registryMock = $this->createMock(CollectorRegistry::class);
        $registryMock->expects($this->once())
            ->method('getOrRegisterGauge')
            ->with('app', 'test_gauge', 'Auto-generated gauge', ['label3'])
            ->willReturn($gaugeMock);

        $this->instance->injectMetricRegistry($registryMock);
        $this->instance->callSetGauge('test_gauge', 42.0, ['label3' => 'value3']);
    }

    public function testIncrementCounterWithoutRegistryFallback(): void
    {
        // $this->expectOutputRegex('/\[METRIC\]\[counter\] fallback_counter 1 {}|{"}/');
        $this->expectOutputRegex('/fallback_counter 1(\s|\{|\{.*\})?/i');
        ob_start();
        $this->instance->callIncrementCounter('fallback_counter');
        ob_end_flush();
    }

    public function testObserveHistogramWithoutRegistryFallback(): void
    {
        $this->expectOutputRegex('/fallback_histogram 2\.5(\s|\{|\{.*\})?/i');
        ob_start();
        $this->instance->callObserveHistogram('fallback_histogram', 2.5);    
        ob_end_flush();
    }

    public function testSetGaugeWithoutRegistryFallback(): void
    {
        // $this->expectOutputRegex('/\[METRIC\]\[gauge\] fallback_gauge 99 {}|{"}/');
        $this->expectOutputRegex('/fallback_gauge 99(\s|\{|\{.*\})?/i');
        ob_start();
        $this->instance->callSetGauge('fallback_gauge', 99.0);
        ob_end_flush();
    }
}
