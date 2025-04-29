<?php

declare(strict_types=1);

namespace Civi\Micro\Management\Tests;

use PHPUnit\Framework\TestCase;
use Civi\Micro\Management\HealthDetail;

class HealthDetailUnitTest extends TestCase
{
    public function testUp()
    {
        $detail = HealthDetail::up('Database', ['host' => 'localhost']);

        $this->assertInstanceOf(HealthDetail::class, $detail);
        $this->assertSame('UP', $detail->status);
        $this->assertSame('Database', $detail->name);
        $this->assertSame(['host' => 'localhost'], $detail->details);
    }

    public function testDown()
    {
        $detail = HealthDetail::down('Cache', ['ttl' => '5s']);

        $this->assertInstanceOf(HealthDetail::class, $detail);
        $this->assertSame('DOWN', $detail->status);
        $this->assertSame('Cache', $detail->name);
        $this->assertSame(['ttl' => '5s'], $detail->details);
    }

    public function testUnknown()
    {
        $detail = HealthDetail::unknown('ServiceX', ['info' => 'no response']);

        $this->assertInstanceOf(HealthDetail::class, $detail);
        $this->assertSame('UNKWOWN', $detail->status); // Ojo: UNKWOWN es como lo escribiste
        $this->assertSame('ServiceX', $detail->name);
        $this->assertSame(['info' => 'no response'], $detail->details);
    }

    public function testUpWithoutDetails()
    {
        $detail = HealthDetail::up('Database');

        $this->assertInstanceOf(HealthDetail::class, $detail);
        $this->assertSame('UP', $detail->status);
        $this->assertSame('Database', $detail->name);
        $this->assertNull($detail->details);
    }

    public function testDownWithoutDetails()
    {
        $detail = HealthDetail::down('Cache');

        $this->assertInstanceOf(HealthDetail::class, $detail);
        $this->assertSame('DOWN', $detail->status);
        $this->assertSame('Cache', $detail->name);
        $this->assertNull($detail->details);
    }

    public function testUnknownWithoutDetails()
    {
        $detail = HealthDetail::unknown('ServiceX');

        $this->assertInstanceOf(HealthDetail::class, $detail);
        $this->assertSame('UNKWOWN', $detail->status); // Mismo typo
        $this->assertSame('ServiceX', $detail->name);
        $this->assertNull($detail->details);
    }
}
