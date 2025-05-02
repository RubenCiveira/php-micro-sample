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

class RateLimitMiddleware
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly RateConfig $rateConfig,
        private readonly StorageInterface $storage
    ) {
    }

    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($rate = $this->filterRates($request)) {
            return $this->handleWithRates($rate, $request, $handler);
        } else {
            return $handler->handle($request);
        }
    }

    private function filterRates(ServerRequestInterface $request): ?RateLimit
    {

        $lev_resolver = $this->container->get( $this->rateConfig->bucketResolverType );
        $limiterFactory = $this->rateFactory($lev_resolver->resolve($request));
        if ($limiterFactory) {
            $consume = 1;
            $gran_resolver = $this->container->get( $this->rateConfig->connectionResolverType );
            $limiter = $limiterFactory->create($gran_resolver->resolve($request));
            return $limiter->consume($consume);
        } else {
            return null;
        }
    }

    private function routeMatches(string $protectedRoute, string $currentRoute): bool
    {
        // Puedes usar patrones más avanzados, pero aquí un ejemplo simple
        return fnmatch($protectedRoute, $currentRoute);
    }

    private function rateFactory($level): RateLimiterFactory
    {
        return new RateLimiterFactory(
            [
                'id' => $this->rateConfig->id,
                'policy' => $this->rateConfig->policy,
                'limit' => $this->rateConfig->limit,
                'interval' => $this->rateConfig->interval,
            ],
            $this->storage
        );
    }

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
