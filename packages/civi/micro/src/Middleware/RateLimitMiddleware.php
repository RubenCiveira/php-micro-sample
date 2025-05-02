<?php

namespace Civi\Micro\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Civi\Micro\Rate\RateConfig;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use Symfony\Component\RateLimiter\RateLimit;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\StorageInterface;

/**
 * Middleware that applies rate limiting to incoming HTTP requests using Symfony RateLimiter.
 *
 * This middleware uses resolver services defined in the container to dynamically determine
 * the rate limiting strategy and bucket identification for each request.
 */
class RateLimitMiddleware
{
    /**
     * @param ContainerInterface $container The dependency injection container used to resolve bucket and connection resolvers.
     * @param RateConfig $rateConfig Configuration object specifying rate limiter parameters and resolver service IDs.
     * @param StorageInterface $storage The storage mechanism for tracking rate limiter state.
     */
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly RateConfig $rateConfig,
        private readonly StorageInterface $storage
    ) {
    }

    /**
     * Middleware handler. Applies rate limiting if a rate is defined for the request.
     *
     * @param ServerRequestInterface $request The current HTTP request.
     * @param RequestHandlerInterface $handler The next handler in the middleware stack.
     * @return ResponseInterface The response returned either from the handler or rate limiter.
     */
    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($rate = $this->filterRates($request)) {
            return $this->handleWithRates($rate, $request, $handler);
        } else {
            return $handler->handle($request);
        }
    }

    /**
     * Filters and applies the appropriate rate limiter based on the resolved bucket and connection.
     *
     * @param ServerRequestInterface $request The current request used for resolving limits.
     * @return RateLimit|null The rate limit result if a limiter applies, or null if not.
     */
    private function filterRates(ServerRequestInterface $request): ?RateLimit
    {
        $lev_resolver = $this->container->get($this->rateConfig->bucketResolverType);
        $limiterFactory = $this->rateFactory($lev_resolver->resolve($request));
        if ($limiterFactory) {
            $consume = 1;
            $gran_resolver = $this->container->get($this->rateConfig->connectionResolverType);
            $limiter = $limiterFactory->create($gran_resolver->resolve($request));
            return $limiter->consume($consume);
        } else {
            return null;
        }
    }

    /**
     * Creates a RateLimiterFactory based on the current level identifier and configured policy.
     *
     * @param mixed $level The resolved identifier for the bucket (e.g., IP address, user ID).
     * @return RateLimiterFactory|null The constructed factory or null if the level is empty.
     */
    private function rateFactory($level): ?RateLimiterFactory
    {
        return $level ? new RateLimiterFactory(
            [
                    'id' => $this->rateConfig->id,
                    'policy' => $this->rateConfig->policy,
                    'limit' => $this->rateConfig->limit,
                    'interval' => $this->rateConfig->interval,
                ],
            $this->storage
        ) : null;
    }

    /**
     * Handles the request using the provided RateLimit result.
     *
     * If accepted, forwards the request and appends rate limiting headers.
     * If rejected, returns a 429 response with retry information.
     *
     * @param RateLimit $limit The result of the token consumption attempt.
     * @param ServerRequestInterface $request The current request.
     * @param RequestHandlerInterface $handler The next middleware handler.
     * @return ResponseInterface The resulting HTTP response.
     */
    private function handleWithRates(RateLimit $limit, ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($limit->isAccepted()) {
            $remainingTokens = $limit->getRemainingTokens();
            $response = $handler->handle($request);
            return $response->withHeader('X-RateLimit-Remaining', (string)$remainingTokens);
        } else {
            $retryAfterSeconds = $limit->getRetryAfter()->getTimestamp() - time();
            $response = new Response();
            $response->getBody()->write('Rate limit exceeded.');
            return $response->withStatus(429)->withHeader('Retry-After', (string)$retryAfterSeconds);
        }
    }
}
