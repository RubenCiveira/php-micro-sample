<?php

declare(strict_types=1);

namespace Civi\Micro\Management\Tests;

use Civi\Micro\Management\HealthDetail;
use Civi\Micro\Management\HealthManagement;
use Civi\Micro\Management\ManagementInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Civi\Micro\Management\HealthManagement
 */
class HealthManagementUnitTest extends TestCase
{
    public function testName(): void
    {
        $management = new HealthManagement([]);
        $this->assertSame('health', $management->name());
    }

    public function testSetReturnsNull(): void
    {
        $management = new HealthManagement([]);
        $this->assertNull($management->set());
    }

    public function testGetWithNoProviders(): void
    {
        $management = new HealthManagement([]);
        $closure = $management->get();
        $result = $closure();

        $this->assertSame(['status' => 'UP'], $result);
    }

    public function testGetWithSingleUpProvider(): void
    {
        $provider = $this->createMockProvider('componentA', 'UP');
        $management = new HealthManagement([$provider]);

        $closure = $management->get();
        $result = $closure();

        $this->assertSame('UP', $result['status']);
        $this->assertArrayHasKey('componentA', $result['components']);
        $this->assertSame('UP', $result['components']['componentA']['status']);
    }

    public function testGetWithSingleDownProvider(): void
    {
        $provider = $this->createMockProvider('componentB', 'DOWN');
        $management = new HealthManagement([$provider]);

        $closure = $management->get();
        $result = $closure();

        $this->assertSame('DOWN', $result['status']);
        $this->assertArrayHasKey('componentB', $result['components']);
        $this->assertSame('DOWN', $result['components']['componentB']['status']);
    }

    public function testGetWithUnknownProvider(): void
    {
        $provider = $this->createMockProvider('componentC', 'UNKWOWN'); // typo en el código original
        $management = new HealthManagement([$provider]);

        $closure = $management->get();
        $result = $closure();

        $this->assertSame('UNKWOWN', $result['status']);
        $this->assertArrayHasKey('componentC', $result['components']);
        $this->assertSame('UNKWOWN', $result['components']['componentC']['status']);
    }

    public function testGetWithDownAndUnknownProviders(): void
    {
        $providerDown = $this->createMockProvider('componentD', 'DOWN');
        $providerUnknown = $this->createMockProvider('componentE', 'UNKWOWN');

        $management = new HealthManagement([$providerDown, $providerUnknown]);
        $closure = $management->get();
        $result = $closure();

        // DOWN has priority over UNKWOWN
        $this->assertSame('DOWN', $result['status']);
        $this->assertArrayHasKey('componentD', $result['components']);
        $this->assertArrayHasKey('componentE', $result['components']);
    }

    public function testGetWithDetails(): void
    {
        $details = ['foo' => 'bar'];
        $provider = $this->createMockProvider('componentF', 'UP', $details);

        $management = new HealthManagement([$provider]);
        $closure = $management->get();
        $result = $closure();

        $this->assertSame('UP', $result['status']);
        $this->assertSame('bar', $result['components']['componentF']['details']['foo']);
    }

    private function createMockProvider(string $name, string $status, ?array $details = null)
    {
        if( $status == 'UP') {
            $health = HealthDetail::up($name, $details);
        } else if( $status == 'DOWN') {
            $health = HealthDetail::down($name, $details);
        } else if( $status == 'UNKWOWN') {
            $health = HealthDetail::unknown($name, $details);
        } else {
            throw new InvalidArgumentException($status);
        }
        $provider = $this->createMock(HealthProviderInterface::class);
        $provider->method('check')
            ->willReturn($health);
        return $provider;
    }
}

/**
 * Dummy interface for testing purposes, simulating a health provider.
 */
interface HealthProviderInterface
{
    public function check(): HealthDetail;
}
