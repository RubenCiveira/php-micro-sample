<?php

declare(strict_types=1);

namespace Civi\Micro\Rate;

use Civi\Micro\Rate\Resolver\BaseBucketResolver;
use Civi\Micro\Rate\Resolver\IpGranularityResolver;

/**
 * Configuration container for rate limiting behavior in the application.
 *
 * This class encapsulates the definition of a rate limiter instance, including
 * its ID, policy, request limit, time interval, and resolver types for both
 * bucket (e.g., per service) and connection (e.g., per user or IP).
 */
class RateConfig
{
    /**
     * The unique identifier for the rate limiter configuration.
     * Used as the base key in storage.
     *
     * @var string
     */
    public readonly string $id;

    /**
     * The rate limiting policy (e.g., 'fixed_window', 'sliding_window').
     *
     * @var string
     */
    public readonly string $policy;

    /**
     * The maximum number of requests allowed within the given interval.
     *
     * @var int
     */
    public readonly int $limit;

    /**
     * The interval (e.g., '1 minute', '10 seconds') over which the limit applies.
     *
     * @var string
     */
    public readonly string $interval;

    /**
     * The service ID or class name of the bucket resolver.
     * This resolver defines the logical grouping of rate limits (e.g., per endpoint or service).
     *
     * @var string
     */
    public readonly string $bucketResolverType;

    /**
     * The service ID or class name of the connection resolver.
     * This resolver defines the granularity of the limit (e.g., per user or IP).
     *
     * @var string
     */
    public readonly string $connectionResolverType;

    /**
     * Constructor that assigns rate limiting parameters, applying sensible defaults
     * when values are not provided.
     *
     * @param string|null $id Optional custom rate limiter ID. Defaults to 'app_global_limit'.
     * @param string|null $policy Optional rate limiter policy. Defaults to 'sliding_window'.
     * @param int|null $limit Optional request limit. Defaults to 100.
     * @param string|null $interval Optional time interval. Defaults to '1 minute'.
     * @param string|null $bucketResolverType Optional bucket resolver. Defaults to BaseBucketResolver::class.
     * @param string|null $connec   tionResolverType Optional connection resolver. Defaults to IpGranularityResolver::class.
     */
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
