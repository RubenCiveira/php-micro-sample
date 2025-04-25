<?php

declare(strict_types=1);

namespace Civi\Micro\Telemetry;

use Prometheus\CollectorRegistry;

trait MetricAwareTrait
{
    private ?CollectorRegistry $metricRegistry = null;

    public function setMetricRegistry(CollectorRegistry $registry): void
    {
        $this->metricRegistry = $registry;
    }

    public function incrementCounter(string $name, array $labels = [], float $value = 1.0): void
    {
        if ($this->metricRegistry !== null) {
            $counter = $this->metricRegistry->getOrRegisterCounter('app', $name, 'Auto-generated counter', array_keys($labels));
            $counter->incBy($value, array_values($labels));
        } else {
            $this->writeMetricFallback('counter', $name, $value, $labels);
        }
    }

    public function observeHistogram(string $name, float $observation, array $labels = []): void
    {
        if ($this->metricRegistry !== null) {
            $histogram = $this->metricRegistry->getOrRegisterHistogram('app', $name, 'Auto-generated histogram', array_keys($labels));
            $histogram->observe($observation, array_values($labels));
        } else {
            $this->writeMetricFallback('histogram', $name, $observation, $labels);
        }
    }

    public function setGauge(string $name, float $value, array $labels = []): void
    {
        if ($this->metricRegistry !== null) {
            $gauge = $this->metricRegistry->getOrRegisterGauge('app', $name, 'Auto-generated gauge', array_keys($labels));
            $gauge->set($value, array_values($labels));
        } else {
            $this->writeMetricFallback('gauge', $name, $value, $labels);
        }
    }

    private function writeMetricFallback(string $type, string $name, $value = null, array $labels = []): void
    {
        $labelStr = !empty($labels) ? json_encode($labels) : '';
        $valueStr = $value !== null ? " $value" : "";
        error_log("[METRIC][$type] $name$valueStr $labelStr");
    }
}
