<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Civi\Micro\Rate\FileStorage;
use Symfony\Component\RateLimiter\LimiterStateInterface;

class FileStorageUnitTest extends TestCase
{
    private string $filePath;

    protected function setUp(): void
    {
        $this->filePath = sys_get_temp_dir() . '/bucket_test.json';
        if (file_exists($this->filePath)) {
            unlink($this->filePath);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->filePath)) {
            unlink($this->filePath);
        }
    }

    public function testSaveAndFetchValidLimiterState(): void
    {
        $limiterState = $this->createMock(LimiterStateInterface::class);
        $limiterState->method('getId')->willReturn('user_123');
        $limiterState->method('getExpirationTime')->willReturn(60);

        $storage = new FileStorage($this->filePath);
        $storage->save($limiterState);

        $fetched = $storage->fetch('user_123');

        $this->assertInstanceOf(LimiterStateInterface::class, $fetched);
    }

    public function testFetchExpiredLimiterStateReturnsNull(): void
    {
        $limiterState = $this->createMock(LimiterStateInterface::class);
        $limiterState->method('getId')->willReturn('expired_user');
        $limiterState->method('getExpirationTime')->willReturn(1); // expires immediately

        $storage = new FileStorage($this->filePath);
        $storage->save($limiterState);

        usleep(1100000); // espera 1.1 segundos
        
        $fetched = $storage->fetch('expired_user');

        $this->assertNull($fetched);
    }

    public function testFetchNonExistingReturnsNull(): void
    {
        $storage = new FileStorage($this->filePath);
        $this->assertNull($storage->fetch('unknown'));
    }

    public function testDeleteRemovesEntry(): void
    {
        $limiterState = $this->createMock(LimiterStateInterface::class);
        $limiterState->method('getId')->willReturn('to_be_deleted');
        $limiterState->method('getExpirationTime')->willReturn(null);

        $storage = new FileStorage($this->filePath);
        $storage->save($limiterState);

        $this->assertInstanceOf(LimiterStateInterface::class, $storage->fetch('to_be_deleted'));

        $storage->delete('to_be_deleted');
        $this->assertNull($storage->fetch('to_be_deleted'));
    }

    public function testConstructorLoadsExistingData(): void
    {
        file_put_contents($this->filePath, json_encode([
            'preloaded_user' => [microtime(true) + 60, serialize($this->createMock(LimiterStateInterface::class))]
        ]));

        $storage = new FileStorage($this->filePath);

        $this->assertInstanceOf(LimiterStateInterface::class, $storage->fetch('preloaded_user'));
    }
} 
