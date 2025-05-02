<?php

declare(strict_types=1);

namespace Civi\Micro\Rate;

use Civi\Micro\ProjectLocator;
use Symfony\Component\RateLimiter\LimiterStateInterface;
use Symfony\Component\RateLimiter\Storage\StorageInterface;

/**
 * A file-based implementation of Symfony's RateLimiter StorageInterface.
 *
 * This class stores rate limiter state data in a JSON file on disk, allowing persistence
 * across application restarts. It uses serialized LimiterStateInterface objects and
 * expiration timestamps to manage limiter state.
 */
class FileStorage implements StorageInterface
{
    /**
     * The path to the JSON file used for storing limiter states.
     *
     * @var string
     */
    private string $path;

    /**
     * The in-memory representation of limiter states, indexed by limiter ID.
     * Each entry is a tuple [expirationTime, serializedLimiterState].
     *
     * @var array<string, array{0: float|null, 1: string}>
     */
    private array $buckets = [];

    /**
     * Constructs a new FileStorage instance.
     *
     * @param string|null $filePath Optional path to the storage file. If null, a default path under 'var/buckets.json' is used.
     */
    public function __construct(?string $filePath = null)
    {
        $this->path = $filePath ?? ProjectLocator::getRootPath() . '/var/buckets.json';
        if (file_exists($this->path)) {
            $json = file_get_contents($this->path);
            $this->buckets = json_decode($json, true) ?? [];
        }
    }

    /**
     * Saves the given limiter state to persistent storage.
     *
     * @param LimiterStateInterface $limiterState The limiter state to save.
     */
    public function save(LimiterStateInterface $limiterState): void
    {
        $this->buckets[$limiterState->getId()] = [
            $this->getExpireAt($limiterState),
            serialize($limiterState)
        ];
        $this->flush();
    }

    /**
     * Fetches the limiter state associated with the given ID.
     * If the state is expired or not found, null is returned.
     *
     * @param string $limiterStateId The identifier for the limiter state.
     * @return LimiterStateInterface|null The deserialized limiter state, or null if expired or not found.
     */
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

    /**
     * Deletes the limiter state associated with the given ID.
     *
     * @param string $limiterStateId The identifier of the limiter state to delete.
     */
    public function delete(string $limiterStateId): void
    {
        if (isset($this->buckets[$limiterStateId])) {
            unset($this->buckets[$limiterStateId]);
            $this->flush();
        }
    }

    /**
     * Computes the absolute expiration timestamp for a limiter state.
     *
     * @param LimiterStateInterface $limiterState The limiter state to evaluate.
     * @return float|null The Unix timestamp at which the state expires, or null if none.
     */
    private function getExpireAt(LimiterStateInterface $limiterState): ?float
    {
        if (null !== $expireSeconds = $limiterState->getExpirationTime()) {
            return microtime(true) + $expireSeconds;
        }

        return $this->buckets[$limiterState->getId()][0] ?? null;
    }

    /**
     * Writes the current bucket state to the storage file in JSON format.
     * Creates the target directory if it does not exist.
     */
    private function flush(): void
    {
        if (!is_dir(dirname($this->path))) {
            mkdir(dirname($this->path), 0755, true);
        }
        file_put_contents($this->path, json_encode($this->buckets, JSON_PRETTY_PRINT));
    }
}
