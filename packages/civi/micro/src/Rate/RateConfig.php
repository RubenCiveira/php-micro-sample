<?php

declare(strict_types=1);

namespace Civi\Micro\Rate;

use Civi\Micro\Rate\Resolver\BaseBucketResolver;
use Civi\Micro\Rate\Resolver\IpGranularityResolver;

class RateConfig
{
    public readonly string $id;
    public readonly string $policy;
    public readonly int $limit;
    public readonly string $interval;
    public readonly string $bucketResolverType;
    public readonly string $connectionResolverType;

    public function __construct(
        ?string $id = null,
        ?string $policy = null,
        ?int $limit = null,
        ?string $interval = null,
        ?string $bucketResolverType = null,
        ?string $connectionResolverType = null,
    ) {
        $this->id = $id ?? 'app_global_limit';
        $this->policy = $policy ?? 'sliding_window';
        $this->limit = $limit ?? 100;
        $this->interval = $interval ?? '1 minute';
        $this->bucketResolverType = $bucketResolverType ?? BaseBucketResolver::class;
        $this->connectionResolverType = $connectionResolverType ?? IpGranularityResolver::class;
    }
}