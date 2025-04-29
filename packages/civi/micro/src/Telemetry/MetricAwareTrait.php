<?php

declare(strict_types=1);

namespace Civi\Micro\Telemetry;

use Prometheus\CollectorRegistry;

/**
 * Provides metric recording capabilities for classes.
 *
 * This trait allows a class to easily interact with a Prometheus CollectorRegistry
 * to register and update metrics such as counters, histograms, and gauges.
 * If no registry is available, it falls back to logging the metric operation.
 *
 * @api
 */
trait MetricAwareTrait
{
    /**
     * The Prometheus CollectorRegistry instance used to record metrics.
     */
    private ?CollectorRegistry $metricRegistry = null;

    /**
     * Sets the CollectorRegistry to be used for metric recording.
     *
     * @param CollectorRegistry $registry The Prometheus CollectorRegistry instance.
     */
    public function setMetricRegistry(CollectorRegistry $registry): void
    {
        $this->metricRegistry = $registry;
    }

    /**
     * Increments a counter metric.
     *
     * Use this method to record discrete events or occurrences (e.g., number of requests, number of errors).
     * If a registry is not set, the metric operation will be logged instead.
     *
     * @param string $name The metric name.
     * @param array $labels Optional labels for the metric.
     * @param float $value The value to increment by (default is 1.0).
     */
    public function incrementCounter(string $name, array $labels = [], float $value = 1.0): void
    {
        if ($this->metricRegistry !== null) {
            $counter = $this->metricRegistry->getOrRegisterCounter('app', $name, 'Auto-generated counter', array_keys($labels));
            $counter->incBy($value, array_values($labels));
        } else {
            $this->writeMetricFallback('counter', $name, $value, $labels);
        }
    }

    /**
     * Observes a value for a histogram metric.
     *
     * Use this method to record measured observations, like response times or payload sizes.
     * If a registry is not set, the metric operation will be logged instead.
     *
     * @param string $name The metric name.
     * @param float $observation The value to observe.
     * @param array $labels Optional labels for the metric.
     */
    public function observeHistogram(string $name, float $observation, array $labels = []): void
    {
        if ($this->metricRegistry !== null) {
            $histogram = $this->metricRegistry->getOrRegisterHistogram('app', $name, 'Auto-generated histogram', array_keys($labels));
            $histogram->observe($observation, array_values($labels));
        } else {
            $this->writeMetricFallback('histogram', $name, $observation, $labels);
        }
    }

    /**
     * Sets the value of a gauge metric.
     *
     * Use this method to record values that can go up and down (e.g., current memory usage, number of active sessions).
     * If a registry is not set, the metric operation will be logged instead.
     *
     * @param string $name The metric name.
     * @param float $value The value to set.
     * @param array $labels Optional labels for the metric.
     */
    public function setGauge(string $name, float $value, array $labels = []): void
    {
        if ($this->metricRegistry !== null) {
            $gauge = $this->metricRegistry->getOrRegisterGauge('app', $name, 'Auto-generated gauge', array_keys($labels));
            $gauge->set($value, array_values($labels));
        } else {
            $this->writeMetricFallback('gauge', $name, $value, $labels);
        }
    }

    /**
     * Writes a fallback log entry when no registry is available.
     *
     * This method ensures that metric operations are still traceable when metrics cannot be recorded.
     *
     * @param string $type The metric type ('counter', 'histogram', or 'gauge').
     * @param string $name The metric name.
     * @param mixed $value The metric value.
     * @param array $labels Optional labels for the metric.
     */
    private function writeMetricFallback(string $type, string $name, $value = null, array $labels = []): void
    {
        $labelStr = !empty($labels) ? json_encode($labels) : '';
        $valueStr = $value !== null ? " $value" : "";
        error_log("[METRIC][$type] $name$valueStr $labelStr");
    }
}
