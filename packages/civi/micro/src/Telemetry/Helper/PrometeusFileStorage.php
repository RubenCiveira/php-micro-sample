<?php

declare(strict_types=1);

namespace Civi\Micro\Telemetry\Helper;

use Override;
use Prometheus\Storage\Adapter;
use Prometheus\Math;
use Prometheus\MetricFamilySamples;
use RuntimeException;

/**
 * PrometeusFileStorage
 *
 * A file-based storage adapter for Prometheus metrics, supporting counters, gauges,
 * histograms, and summaries. Metrics are serialized and persisted as JSON files.
 *
 * This adapter enables durable metrics collection across server restarts
 * and allows aggregation of Prometheus data in environments without full
 * in-memory collectors.
 *
 * Implements the {@see Prometheus\Storage\Adapter} interface.
 *
 * @package Civi\Micro\Telemetry\Helper
 */
class PrometeusFileStorage implements Adapter
{
    /**
         * The file path where metrics are persisted.
         *
         * @var string
         */
    private string $filePath;

    /**
     * Tracks if any metrics have changed and need to be persisted.
     *
     * @var bool
     */
    private bool $change = false;

    /**
     * Registered counters.
     *
     * @var mixed[]
     */
    protected $counters = [];

    /**
     * Registered gauges.
     *
     * @var mixed[]
     */
    protected $gauges = [];

    /**
     * Registered histograms.
     *
     * @var mixed[]
     */
    protected $histograms = [];

    /**
     * Registered summaries.
     *
     * @var mixed[]
     */
    protected $summaries = [];

    /**
     * Constructor.
     *
     * Loads existing metrics from a file if it exists.
     *
     * @param string $filePath Path to the metrics storage file.
     */
    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;

        if (file_exists($filePath)) {
            $data = json_decode(file_get_contents($filePath), true);
            $this->counters = $data['counters'] ?? [];
            $this->gauges = $data['gauges'] ?? [];
            $this->histograms = $data['histograms'] ?? [];
            $this->summaries = $data['summaries'] ?? [];
        }
    }

    /**
     * Destructor.
     *
     * Persists metrics to file if any changes occurred during the lifecycle.
     */
    public function __destruct()
    {
        if ($this->change) {
            $this->persist();
        }
    }

    /**
     * {@inheritdoc}
     *
     * Collects all available metrics: counters, gauges, histograms, and summaries.
     *
     * @return MetricFamilySamples[]
     */
    #[Override]
    public function collect(bool $sortMetrics = true): array
    {
        $metrics = $this->internalCollect($this->counters, $sortMetrics);
        $metrics = array_merge($metrics, $this->internalCollect($this->gauges, $sortMetrics));
        $metrics = array_merge($metrics, $this->collectHistograms());
        $metrics = array_merge($metrics, $this->collectSummaries());
        return $metrics;
    }

    /**
     * {@inheritdoc}
     *
     * Clears all stored metrics and persists an empty state to the storage file.
     */
    #[Override]
    public function wipeStorage(): void
    {
        $this->counters = [];
        $this->gauges = [];
        $this->histograms = [];
        $this->summaries = [];
        $this->persist();
    }

    /**
     * Collects all registered histograms into MetricFamilySamples.
     *
     * @return MetricFamilySamples[]
     */
    protected function collectHistograms(): array
    {
        $histograms = [];
        foreach ($this->histograms as $histogram) {
            $metaData = $histogram['meta'];
            $data = [
                'name' => $metaData['name'],
                'help' => $metaData['help'],
                'type' => $metaData['type'],
                'labelNames' => $metaData['labelNames'],
                'buckets' => $metaData['buckets'],
            ];

            // Add the Inf bucket so we can compute it later on
            $data['buckets'][] = '+Inf';

            $histogramBuckets = [];
            foreach ($histogram['samples'] as $key => $value) {
                $parts = explode(':', $key);
                $labelValues = $parts[2];
                $bucket = $parts[3];
                // Key by labelValues
                $histogramBuckets[$labelValues][$bucket] = $value;
            }

            // Compute all buckets
            $labels = array_keys($histogramBuckets);
            sort($labels);
            foreach ($labels as $labelValues) {
                $acc = 0;
                $decodedLabelValues = $this->decodeLabelValues($labelValues);
                foreach ($data['buckets'] as $bucket) {
                    $bucket = (string)$bucket;
                    if (!isset($histogramBuckets[$labelValues][$bucket])) {
                        $data['samples'][] = [
                            'name' => $metaData['name'] . '_bucket',
                            'labelNames' => ['le'],
                            'labelValues' => array_merge($decodedLabelValues, [$bucket]),
                            'value' => $acc,
                        ];
                    } else {
                        $acc += $histogramBuckets[$labelValues][$bucket];
                        $data['samples'][] = [
                            'name' => $metaData['name'] . '_' . 'bucket',
                            'labelNames' => ['le'],
                            'labelValues' => array_merge($decodedLabelValues, [$bucket]),
                            'value' => $acc,
                        ];
                    }
                }

                // Add the count
                $data['samples'][] = [
                    'name' => $metaData['name'] . '_count',
                    'labelNames' => [],
                    'labelValues' => $decodedLabelValues,
                    'value' => $acc,
                ];

                // Add the sum
                $data['samples'][] = [
                    'name' => $metaData['name'] . '_sum',
                    'labelNames' => [],
                    'labelValues' => $decodedLabelValues,
                    'value' => $histogramBuckets[$labelValues]['sum'],
                ];
            }
            $histograms[] = new MetricFamilySamples($data);
        }
        return $histograms;
    }

    /**
     * Collects all registered summaries into MetricFamilySamples.
     *
     * @return MetricFamilySamples[]
     */
    protected function collectSummaries(): array
    {
        $math = new Math();
        $summaries = [];
        foreach ($this->summaries as $metaKey => &$summary) {
            $metaData = $summary['meta'];
            $data = [
                'name' => $metaData['name'],
                'help' => $metaData['help'],
                'type' => $metaData['type'],
                'labelNames' => $metaData['labelNames'],
                'maxAgeSeconds' => $metaData['maxAgeSeconds'],
                'quantiles' => $metaData['quantiles'],
                'samples' => [],
            ];

            foreach ($summary['samples'] as $key => &$values) {
                $parts = explode(':', $key);
                $labelValues = $parts[2];
                $decodedLabelValues = $this->decodeLabelValues($labelValues);

                // Remove old data
                $values = array_filter($values, function (array $value) use ($data): bool {
                    return time() - $value['time'] <= $data['maxAgeSeconds'];
                });
                if (count($values) === 0) {
                    unset($summary['samples'][$key]);
                    continue;
                }

                // Compute quantiles
                usort($values, function (array $value1, array $value2) {
                    if ($value1['value'] === $value2['value']) {
                        return 0;
                    }
                    return ($value1['value'] < $value2['value']) ? -1 : 1;
                });

                foreach ($data['quantiles'] as $quantile) {
                    $data['samples'][] = [
                        'name' => $metaData['name'],
                        'labelNames' => ['quantile'],
                        'labelValues' => array_merge($decodedLabelValues, [$quantile]),
                        'value' => $math->quantile(array_column($values, 'value'), $quantile),
                    ];
                }

                // Add the count
                $data['samples'][] = [
                    'name' => $metaData['name'] . '_count',
                    'labelNames' => [],
                    'labelValues' => $decodedLabelValues,
                    'value' => count($values),
                ];

                // Add the sum
                $data['samples'][] = [
                    'name' => $metaData['name'] . '_sum',
                    'labelNames' => [],
                    'labelValues' => $decodedLabelValues,
                    'value' => array_sum(array_column($values, 'value')),
                ];
            }
            if (count($data['samples']) > 0) {
                $summaries[] = new MetricFamilySamples($data);
            } else {
                unset($this->summaries[$metaKey]);
            }
        }
        return $summaries;
    }

    /**
     * Collects and structures basic metric types (counters or gauges).
     *
     * @param mixed[] $metrics Array of raw metrics.
     * @param bool $sortMetrics Whether to sort samples.
     * @return MetricFamilySamples[]
     */
    protected function internalCollect(array $metrics, bool $sortMetrics = true): array
    {
        $result = [];
        foreach ($metrics as $metric) {
            $metaData = $metric['meta'];
            $data = [
                'name' => $metaData['name'],
                'help' => $metaData['help'],
                'type' => $metaData['type'],
                'labelNames' => $metaData['labelNames'],
                'samples' => [],
            ];
            foreach ($metric['samples'] as $key => $value) {
                $parts = explode(':', $key);
                $labelValues = $parts[2];
                $data['samples'][] = [
                    'name' => $metaData['name'],
                    'labelNames' => [],
                    'labelValues' => $this->decodeLabelValues($labelValues),
                    'value' => $value,
                ];
            }

            if ($sortMetrics) {
                $this->sortSamples($data['samples']);
            }

            $result[] = new MetricFamilySamples($data);
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * Updates the stored histogram metrics with a new observation.
     *
     * @param mixed[] $data Histogram observation data.
     */
    #[Override]
    public function updateHistogram(array $data): void
    {
        // Initialize the sum
        $metaKey = $this->metaKey($data);
        if (array_key_exists($metaKey, $this->histograms) === false) {
            $this->histograms[$metaKey] = [
                'meta' => $this->metaData($data),
                'samples' => [],
            ];
        }
        $sumKey = $this->histogramBucketValueKey($data, 'sum');
        if (array_key_exists($sumKey, $this->histograms[$metaKey]['samples']) === false) {
            $this->histograms[$metaKey]['samples'][$sumKey] = 0;
        }

        $this->histograms[$metaKey]['samples'][$sumKey] += $data['value'];


        $bucketToIncrease = '+Inf';
        foreach ($data['buckets'] as $bucket) {
            if ($data['value'] <= $bucket) {
                $bucketToIncrease = $bucket;
                break;
            }
        }

        $bucketKey = $this->histogramBucketValueKey($data, $bucketToIncrease);
        if (array_key_exists($bucketKey, $this->histograms[$metaKey]['samples']) === false) {
            $this->histograms[$metaKey]['samples'][$bucketKey] = 0;
        }
        $this->histograms[$metaKey]['samples'][$bucketKey] += 1;
        $this->change = true;
    }

    /**
     * {@inheritdoc}
     *
     * Updates the stored summary metrics with a new observation.
     *
     * @param mixed[] $data Summary observation data.
     */
    #[Override]
    public function updateSummary(array $data): void
    {
        $metaKey = $this->metaKey($data);
        if (array_key_exists($metaKey, $this->summaries) === false) {
            $this->summaries[$metaKey] = [
                'meta' => $this->metaData($data),
                'samples' => [],
            ];
        }

        $valueKey = $this->valueKey($data);
        if (array_key_exists($valueKey, $this->summaries[$metaKey]['samples']) === false) {
            $this->summaries[$metaKey]['samples'][$valueKey] = [];
        }

        $this->summaries[$metaKey]['samples'][$valueKey][] = [
            'time' => time(),
            'value' => $data['value'],
        ];
        $this->change = true;
    }

    /**
     * {@inheritdoc}
     *
     * Updates the stored gauge metrics.
     *
     * @param mixed[] $data Gauge data, supporting increment, decrement, or set.
     */
    #[Override]
    public function updateGauge(array $data): void
    {
        $metaKey = $this->metaKey($data);
        $valueKey = $this->valueKey($data);
        if (array_key_exists($metaKey, $this->gauges) === false) {
            $this->gauges[$metaKey] = [
                'meta' => $this->metaData($data),
                'samples' => [],
            ];
        }
        if (array_key_exists($valueKey, $this->gauges[$metaKey]['samples']) === false) {
            $this->gauges[$metaKey]['samples'][$valueKey] = 0;
        }
        if ($data['command'] === Adapter::COMMAND_SET) {
            $this->gauges[$metaKey]['samples'][$valueKey] = $data['value'];
        } else {
            $this->gauges[$metaKey]['samples'][$valueKey] += $data['value'];
        }
        $this->change = true;
    }

    /**
     * {@inheritdoc}
     *
     * Updates the stored counter metrics.
     *
     * @param mixed[] $data Counter data, supporting increment or reset to 0.
     */
    #[Override]
    public function updateCounter(array $data): void
    {
        $metaKey = $this->metaKey($data);
        $valueKey = $this->valueKey($data);
        if (array_key_exists($metaKey, $this->counters) === false) {
            $this->counters[$metaKey] = [
                'meta' => $this->metaData($data),
                'samples' => [],
            ];
        }
        if (array_key_exists($valueKey, $this->counters[$metaKey]['samples']) === false) {
            $this->counters[$metaKey]['samples'][$valueKey] = 0;
        }
        if ($data['command'] === Adapter::COMMAND_SET) {
            $this->counters[$metaKey]['samples'][$valueKey] = 0;
        } else {
            $this->counters[$metaKey]['samples'][$valueKey] += $data['value'];
        }
        $this->change = true;
    }

    /**
     * Builds the unique key for identifying a histogram bucket sample.
     *
     * @param mixed[] $data
     * @param string|int $bucket
     * @return string
     */
    protected function histogramBucketValueKey(array $data, $bucket): string
    {
        return implode(':', [
            $data['type'],
            $data['name'],
            $this->encodeLabelValues($data['labelValues']),
            $bucket,
        ]);
    }

    /**
     * Builds the unique key for identifying the meta information of a metric.
     *
     * @param mixed[] $data
     * @return string
     */
    protected function metaKey(array $data): string
    {
        return implode(':', [
            $data['type'],
            $data['name'],
            'meta'
        ]);
    }

    /**
     * Builds the unique key for identifying a specific sample's value.
     *
     * @param mixed[] $data
     * @return string
     */
    protected function valueKey(array $data): string
    {
        return implode(':', [
            $data['type'],
            $data['name'],
            $this->encodeLabelValues($data['labelValues']),
            'value'
        ]);
    }

    /**
     * Extracts metadata from a sample data array.
     *
     * @param mixed[] $data
     * @return mixed[]
     */
    protected function metaData(array $data): array
    {
        $metricsMetaData = $data;
        unset($metricsMetaData['value'], $metricsMetaData['command'], $metricsMetaData['labelValues']);
        return $metricsMetaData;
    }

    /**
     * Sorts metric samples by their label values.
     *
     * @param mixed[] $samples
     */
    protected function sortSamples(array &$samples): void
    {
        usort($samples, function ($a, $b): int {
            return strcmp(implode("", $a['labelValues']), implode("", $b['labelValues']));
        });
    }

    /**
     * Encodes an array of label values into a safe base64 string.
     *
     * @param mixed[] $values
     * @return string
     * @throws RuntimeException If encoding fails.
     */
    protected function encodeLabelValues(array $values): string
    {
        $json = json_encode($values);
        if (false === $json) {
            throw new RuntimeException(json_last_error_msg());
        }
        return base64_encode($json);
    }

    /**
     * Decodes a base64-encoded string of label values back to an array.
     *
     * @param string $values
     * @return mixed[]
     * @throws RuntimeException If decoding fails.
     */
    protected function decodeLabelValues(string $values): array
    {
        $json = base64_decode($values, true);
        if (false === $json) {
            throw new RuntimeException('Cannot base64 decode label values');
        }
        $decodedValues = json_decode($json, true);
        if (null === $decodedValues) {
            throw new RuntimeException(json_last_error_msg());
        }
        return $decodedValues;
    }

    /**
     * Persists the current metrics state to the storage file.
     */
    private function persist(): void
    {
        $data = [
            'counters' => $this->counters,
            'gauges' => $this->gauges,
            'histograms' => $this->histograms,
            'summaries' => $this->summaries,
        ];
        if (file_exists(dirname($this->filePath))) {
            file_put_contents($this->filePath, json_encode($data, JSON_PRETTY_PRINT));
        }
    }
}
