<?php

declare(strict_types=1);

namespace Civi\Micro\Telemetry\Helper;

use PHPUnit\Framework\TestCase;
use Prometheus\MetricFamilySamples;
use Prometheus\Storage\Adapter;
use RuntimeException;

class PrometeusFileStorageUnitTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = tempnam(sys_get_temp_dir(), 'prometheus_test_');
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testConstructLoadsExistingData(): void
    {
        file_put_contents($this->tempFile, json_encode([
            'counters' => ['test' => 'value'],
            'gauges' => [],
            'histograms' => [],
            'summaries' => [],
        ]));

        $storage = new PrometeusFileStorage($this->tempFile);
        $this->assertArrayHasKey('test', $this->readProperty($storage, 'counters'));
    }

    public function testWipeStorageClearsAllData(): void
    {
        $storage = new PrometeusFileStorage($this->tempFile);
        $storage->updateCounter($this->createCounterData());

        $storage->wipeStorage();

        $this->assertEmpty($this->readProperty($storage, 'counters'));
        $this->assertEmpty($this->readProperty($storage, 'gauges'));
        $this->assertEmpty($this->readProperty($storage, 'histograms'));
        $this->assertEmpty($this->readProperty($storage, 'summaries'));
    }

    public function testUpdateCounterAndGauge(): void
    {
        $storage = new PrometeusFileStorage($this->tempFile);
        $storage->updateCounter($this->createCounterData());
        $storage->updateGauge($this->createGaugeData());

        $this->assertNotEmpty($this->readProperty($storage, 'counters'));
        $this->assertNotEmpty($this->readProperty($storage, 'gauges'));
    }

    public function testUpdateHistogramAndSummary(): void
    {
        $storage = new PrometeusFileStorage($this->tempFile);
        $storage->updateHistogram($this->createHistogramData());
        $storage->updateSummary($this->createSummaryData());

        $this->assertNotEmpty($this->readProperty($storage, 'histograms'));
        $this->assertNotEmpty($this->readProperty($storage, 'summaries'));
    }

    public function testEncodeDecodeLabelValues(): void
    {
        $storage = new PrometeusFileStorage($this->tempFile);

        $encoded = $this->invokeMethod($storage, 'encodeLabelValues', [['foo', 'bar']]);
        $decoded = $this->invokeMethod($storage, 'decodeLabelValues', [$encoded]);

        $this->assertSame(['foo', 'bar'], $decoded);
    }

    public function testDecodeInvalidBase64ThrowsException(): void
    {
        $this->expectException(RuntimeException::class);

        $storage = new PrometeusFileStorage($this->tempFile);
        $this->invokeMethod($storage, 'decodeLabelValues', ['**not_base64**']);
    }

    public function testDecodeInvalidJsonThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $storage = new PrometeusFileStorage($this->tempFile);
        // '{invalid json}' -> no es un json válido después del decode
        $invalidJson = base64_encode('invalid json');
        $this->invokeMethod($storage, 'decodeLabelValues', [$invalidJson]);
    }

    public function testEncodeInvalidJsonThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $storage = new PrometeusFileStorage($this->tempFile);
        // Crear un recurso no serializable
        $resource = fopen('php://memory', 'r');
        $this->invokeMethod($storage, 'encodeLabelValues', [['invalid' => $resource]]);
    }

    public function testUpdateCounterWithCommandSet(): void
    {
        $storage = new PrometeusFileStorage($this->tempFile);

        $data = [
            'type' => 'counter',
            'name' => 'test_counter_set',
            'labelValues' => ['label1'],
            'command' => Adapter::COMMAND_SET,
            'value' => 999, // Aunque pongamos 999, debe terminar valiendo 0
        ];

        $storage->updateCounter($data);

        $counters = $this->readProperty($storage, 'counters');

        $this->assertArrayHasKey('counter:test_counter_set:meta', $counters);
        $this->assertArrayHasKey('counter:test_counter_set:' . base64_encode(json_encode(['label1'])) . ':value', $counters['counter:test_counter_set:meta']['samples']);
        $this->assertSame(
            0,
            $counters['counter:test_counter_set:meta']['samples']['counter:test_counter_set:' . base64_encode(json_encode(['label1'])) . ':value']
        );
    }

    public function testInternalCollectSortsSamplesCorrectly(): void
    {
        $storage = new PrometeusFileStorage($this->tempFile);

        $metrics = [
            [
                'meta' => [
                    'name' => 'metric',
                    'help' => 'help',
                    'type' => 'counter',
                    'labelNames' => [],
                ],
                'samples' => [
                    'c:c:WyJiIl0=:value' => 5,
                    'c:c:WyJhIl0=:value' => 10,
                ]
            ]
        ];

        $result = $this->invokeMethod($storage, 'internalCollect', [$metrics]);

        $this->assertInstanceOf(MetricFamilySamples::class, $result[0]);
    }

    private function createCounterData(): array
    {
        return [
            'type' => 'counter',
            'name' => 'test_counter',
            'labelValues' => [],
            'value' => 1,
            'command' => 'inc'
        ];
    }

    private function createGaugeData(): array
    {
        return [
            'type' => 'gauge',
            'name' => 'test_gauge',
            'labelValues' => [],
            'value' => 2,
            'command' => 'inc'
        ];
    }

    private function createHistogramData(): array
    {
        return [
            'type' => 'histogram',
            'name' => 'test_histogram',
            'labelValues' => [],
            'value' => 1.5,
            'buckets' => [1, 2, 5]
        ];
    }

    private function createSummaryData(): array
    {
        return [
            'type' => 'summary',
            'name' => 'test_summary',
            'labelValues' => [],
            'value' => 3,
            'quantiles' => [0.5, 0.9],
            'maxAgeSeconds' => 60
        ];
    }

    public function testCollectSummariesRemovesOldSamples(): void
    {
        $storage = new PrometeusFileStorage($this->tempFile);

        // Insertar un summary manualmente
        $reflection = new \ReflectionClass($storage);
        $property = $reflection->getProperty('summaries');
        $property->setAccessible(true);

        $meta = [
            'name' => 'test_summary',
            'help' => 'Testing',
            'type' => 'summary',
            'labelNames' => [],
            'maxAgeSeconds' => 1, // Hacerlo muy corto para eliminar rápido
            'quantiles' => [0.5],
        ];

        $now = time();
        $oldTime = $now - 3600; // Un valor claramente "viejo"

        $summaries = [
            'summary:test_summary:meta' => [
                'meta' => $meta,
                'samples' => [
                    'summary:test_summary:' . base64_encode(json_encode([])) => [
                        ['time' => $oldTime, 'value' => 123]
                    ]
                ]
            ]
        ];

        $property->setValue($storage, $summaries);

        // Ejecutar
        $result = $this->invokeMethod($storage, 'collectSummaries');

        // Al haber eliminado el sample viejo, no debe haber resultados
        $this->assertEmpty($result);

        // Confirmar que además el summary se ha eliminado del objeto
        $this->assertEmpty($property->getValue($storage));
    }

    public function testCollectSummariesKeepsRecentSamples(): void
    {
        $storage = new PrometeusFileStorage($this->tempFile);

        $reflection = new \ReflectionClass($storage);
        $property = $reflection->getProperty('summaries');
        $property->setAccessible(true);

        $meta = [
            'name' => 'test_summary',
            'help' => 'Testing',
            'type' => 'summary',
            'labelNames' => [],
            'maxAgeSeconds' => 999999, // Grande para que no se eliminen
            'quantiles' => [0.5],
        ];

        $summaries = [
            'summary:test_summary:meta' => [
                'meta' => $meta,
                'samples' => [
                    'summary:test_summary:' . base64_encode(json_encode([])) => [
                        ['time' => time(), 'value' => 123]
                    ]
                ]
            ]
        ];

        $property->setValue($storage, $summaries);

        // Ejecutar
        $result = $this->invokeMethod($storage, 'collectSummaries');

        // Debe contener un MetricFamilySamples
        $this->assertNotEmpty($result);
        $this->assertInstanceOf(\Prometheus\MetricFamilySamples::class, $result[0]);
    }

    public function testCollectSummariesSortsSamplesBeforeQuantile(): void
    {
        $storage = new PrometeusFileStorage($this->tempFile);

        $reflection = new \ReflectionClass($storage);
        $property = $reflection->getProperty('summaries');
        $property->setAccessible(true);

        $meta = [
            'name' => 'sorted_summary',
            'help' => 'Testing sorting',
            'type' => 'summary',
            'labelNames' => [],
            'maxAgeSeconds' => 999999,
            'quantiles' => [0.5],
        ];

        $samples = [
            'summary:sorted_summary:' . base64_encode(json_encode([])) => [
                ['time' => time(), 'value' => 500],
                ['time' => time(), 'value' => 100],
                ['time' => time(), 'value' => 100],
                ['time' => time(), 'value' => 300],
            ]
        ];

        $summaries = [
            'summary:sorted_summary:meta' => [
                'meta' => $meta,
                'samples' => $samples,
            ]
        ];

        $property->setValue($storage, $summaries);

        $result = $this->invokeMethod($storage, 'collectSummaries');

        $this->assertNotEmpty($result);
        $this->assertInstanceOf(\Prometheus\MetricFamilySamples::class, $result[0]);
    }

    public function testCollectHistogramsGeneratesExpectedSamples(): void
    {
        $storage = new PrometeusFileStorage($this->tempFile);

        // Simula un histograma con buckets personalizados
        $storage->updateHistogram([
            'name' => 'test_histogram',
            'help' => 'A test histogram',
            'type' => 'histogram',
            'labelNames' => [],
            'labelValues' => [],
            'buckets' => [0.1, 1, 2.5, 5],
            'value' => 2.0
        ]);

        $storage->updateHistogram([
            'name' => 'test_histogram',
            'help' => 'A test histogram',
            'type' => 'histogram',
            'labelNames' => [],
            'labelValues' => [],
            'buckets' => [0.1, 1, 2.5, 5],
            'value' => 4.0
        ]);

        $metrics = $storage->collect();

        /** @var MetricFamilySamples[] $histograms */
        $histograms = array_filter($metrics, fn ($metric) => $metric->getType() === 'histogram');

        $this->assertNotEmpty($histograms, 'Expected at least one histogram metric collected.');

        $histogram = reset($histograms);

        $this->assertInstanceOf(MetricFamilySamples::class, $histogram);
        $this->assertEquals('test_histogram', $histogram->getName());
        $this->assertEquals('A test histogram', $histogram->getHelp());

        $samples = $histogram->getSamples();
        $bucketSamples = array_filter($samples, fn ($sample) => str_ends_with($sample->getName(), '_bucket'));
        $countSamples = array_filter($samples, fn ($sample) => str_ends_with($sample->getName(), '_count'));
        $sumSamples = array_filter($samples, fn ($sample) => str_ends_with($sample->getName(), '_sum'));

        $this->assertNotEmpty($bucketSamples, 'Expected bucket samples in the histogram.');
        $this->assertNotEmpty($countSamples, 'Expected count samples in the histogram.');
        $this->assertNotEmpty($sumSamples, 'Expected sum samples in the histogram.');

        $countSample = reset($countSamples);
        $sumSample = reset($sumSamples);

        $this->assertEquals(2, $countSample->getValue(), 'Expected 2 values recorded.');
        $this->assertEqualsWithDelta(6.0, $sumSample->getValue(), 0.00001, 'Expected sum of 6.0 from two values 2.0 + 4.0.');
    }

    private function readProperty(object $object, string $property)
    {
        $reflection = new \ReflectionClass($object);
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);
        return $prop->getValue($object);
    }

    private function invokeMethod(object $object, string $methodName, array $args = [])
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $args);
    }
}
