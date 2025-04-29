<?php

declare(strict_types=1);

namespace Civi\Micro\Management\Tests;

use Civi\Micro\Management\MetricsManagement;
use PHPUnit\Framework\TestCase;
use Prometheus\CollectorRegistry;
use Prometheus\MetricFamilySamples;

class MetricsManagementUnitTest extends TestCase
{
    public function testNameReturnsMetrics(): void
    {
        $collector = $this->createMock(CollectorRegistry::class);
        $metricsManagement = new MetricsManagement($collector);

        $this->assertSame('metrics', $metricsManagement->name());
    }

    public function testGetReturnsClosure(): void
    {
        $collector = $this->createMock(CollectorRegistry::class);
        $metricsManagement = new MetricsManagement($collector);

        $getClosure = $metricsManagement->get();
        $this->assertInstanceOf(\Closure::class, $getClosure);
    }

    public function testGetClosureRendersMetrics(): void
    {
        $sample = $this->createMock(MetricFamilySamples::class);

        $collector = $this->createMock(CollectorRegistry::class);
        $collector->expects($this->once())
            ->method('getMetricFamilySamples')
            ->willReturn([$sample]);

        $metricsManagement = new MetricsManagement($collector);

        $getClosure = $metricsManagement->get();
        $this->assertInstanceOf(\Closure::class, $getClosure);

        // Execute the closure and validate that it returns a string
        $output = $getClosure();
        $this->assertIsString($output);
        $this->assertStringContainsString('#', $output); // Metrics output normally contains comments starting with "#"
    }

    public function testSetReturnsNull(): void
    {
        $collector = $this->createMock(CollectorRegistry::class);
        $metricsManagement = new MetricsManagement($collector);

        $this->assertNull($metricsManagement->set());
    }
}
