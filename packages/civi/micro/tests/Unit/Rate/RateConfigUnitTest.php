<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Civi\Micro\Rate\RateConfig;
use Civi\Micro\Rate\Resolver\BaseBucketResolver;
use Civi\Micro\Rate\Resolver\IpGranularityResolver;

class RateConfigUnitTest extends TestCase
{
    public function testDefaultValuesAreAssignedWhenNoArgumentsProvided(): void
    {
        $config = new RateConfig();

        $this->assertSame('app_global_limit', $config->id);
        $this->assertSame('sliding_window', $config->policy);
        $this->assertSame(100, $config->limit);
        $this->assertSame('1 minute', $config->interval);
        $this->assertSame(BaseBucketResolver::class, $config->bucketResolverType);
        $this->assertSame(IpGranularityResolver::class, $config->connectionResolverType);
    }

    public function testCustomValuesArePreserved(): void
    {
        $config = new RateConfig(
            'custom_id',
            'fixed_window',
            50,
            '10 seconds',
            'Custom\\BucketResolver',
            'Custom\\ConnResolver'
        );

        $this->assertSame('custom_id', $config->id);
        $this->assertSame('fixed_window', $config->policy);
        $this->assertSame(50, $config->limit);
        $this->assertSame('10 seconds', $config->interval);
        $this->assertSame('Custom\\BucketResolver', $config->bucketResolverType);
        $this->assertSame('Custom\\ConnResolver', $config->connectionResolverType);
    }

    public function testPartialArgumentsUseDefaults(): void
    {
        $config = new RateConfig('abc', null, null, null, null, 'My\\Conn');

        $this->assertSame('abc', $config->id);
        $this->assertSame('sliding_window', $config->policy);
        $this->assertSame(100, $config->limit);
        $this->assertSame('1 minute', $config->interval);
        $this->assertSame(BaseBucketResolver::class, $config->bucketResolverType);
        $this->assertSame('My\\Conn', $config->connectionResolverType);
    }
}
