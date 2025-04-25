<?php

declare(strict_types=1);

namespace Civi\Micro\Telemetry\Helper;

use Prometheus\Storage\Adapter;

class PrometeusFileStorage implements Adapter
{
    private string $file;
    private array $data = [];

    public function __construct(string $file)
    {
        $this->file = $file;
        if (file_exists($file)) {
            $json = file_get_contents($file);
            $this->data = json_decode($json, true) ?? [];
        }
    }

    public function __destruct()
    {
        $this->save();
    }

    private function save(): void
    {
        file_put_contents($this->file, json_encode($this->data, JSON_PRETTY_PRINT));
    }

    public function collect(): array
    {
        return $this->data;
    }

    public function updateHistogram(array $data): void
    {
        $this->data['histogram'][] = $data;
    }

    public function updateCounter(array $data): void
    {
        $this->data['counter'][] = $data;
    }

    public function updateGauge(array $data): void
    {
        $this->data['gauge'][] = $data;
    }

    public function updateSummary(array $data): void
    {
        $this->data['summary'][] = $data;
    }

    public function wipeStorage(): void
    {
        $this->data = [];
        $this->save();
    }
}