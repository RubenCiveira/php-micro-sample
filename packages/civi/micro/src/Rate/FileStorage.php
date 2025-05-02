<?php

declare(strict_types=1);

namespace Civi\Micro\Rate;

use Civi\Micro\ProjectLocator;
use Symfony\Component\RateLimiter\LimiterStateInterface;
use Symfony\Component\RateLimiter\Storage\StorageInterface;

class FileStorage implements StorageInterface
{
    private string $path;
    private array $buckets = [];

    public function __construct(?string $filePath = null)
    {
        $this->path = $filePath ?? ProjectLocator::getRootPath() . '/var/buckets.json';
        if (file_exists($this->path)) {
            $json = file_get_contents($this->path);
            $this->buckets = json_decode($json, true) ?? [];
        }
    }

    public function save(LimiterStateInterface $limiterState): void
    {
        $this->buckets[$limiterState->getId()] = [
            $this->getExpireAt($limiterState),
            serialize($limiterState)
        ];
        $this->flush();
    }

    public function fetch(string $limiterStateId): ?LimiterStateInterface
    {
        if (!isset($this->buckets[$limiterStateId])) {
            return null;
        }

        [$expireAt, $limiterState] = $this->buckets[$limiterStateId];
        if (null !== $expireAt && $expireAt <= microtime(true)) {
            unset($this->buckets[$limiterStateId]);
            $this->flush();

            return null;
        }

        return unserialize($limiterState);
    }

    public function delete(string $limiterStateId): void
    {
        if (isset($this->buckets[$limiterStateId])) {
            unset($this->buckets[$limiterStateId]);
            $this->flush();
        }
    }

    private function getExpireAt(LimiterStateInterface $limiterState): ?float
    {
        if (null !== $expireSeconds = $limiterState->getExpirationTime()) {
            return microtime(true) + $expireSeconds;
        }

        return $this->buckets[$limiterState->getId()][0] ?? null;
    }

    private function flush(): void
    {
        if( !is_dir(dirname($this->path)) ) {
            mkdir(dirname($this->path), 0755, true);
        }
        file_put_contents($this->path, json_encode($this->buckets, JSON_PRETTY_PRINT));
    }
}
