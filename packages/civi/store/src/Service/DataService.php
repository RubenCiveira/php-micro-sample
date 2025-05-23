<?php

declare(strict_types=1);

namespace Civi\Store\Service;

use Civi\Store\DataQueryParam;
use Civi\Store\Service\ExecPipeline;
use Civi\Store\Service\RestrictionPipeline;
use Civi\Micro\Telemetry\LoggerAwareInterface;
use Civi\Micro\Telemetry\LoggerAwareTrait;
use Civi\Security\Guard\AccessGuard;
use Civi\Security\Redaction\OutputRedactor;
use Civi\Security\Sanitization\InputSanitizer;
use Civi\Security\UnauthorizedException;
use Civi\Store\Gateway\DataGateway;
use Civi\Store\StoreSchema;
use InvalidArgumentException;

class DataService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly DataGateway $gateway,
        private readonly RestrictionPipeline $restrictor,
        private readonly ExecPipeline $execPipeline,
        private readonly AccessGuard $guard,
        private readonly InputSanitizer $sanitizer,
        private readonly OutputRedactor $redactor,
    ) {
    }

    public function create(string $namespace, string $typeName, StoreSchema $meta, string $from, array $data): array
    {
        $sanitized = $this->sanitizer->sanitizeInput($namespace, $typeName, $data);
        if (!$this->guard->canExecute($from, $namespace, $typeName, $sanitized, [])) {
            throw new UnauthorizedException("Not allowed to $from onver $namespace:$typeName");
        }
        $sanitized = $this->execPipeline->executeOperation($namespace, $typeName, [ucfirst($from), 'Write', 'Read'], function () use ($namespace, $typeName, $sanitized, $meta) {
            $this->save($namespace, $typeName, $meta, $sanitized);
        }, $sanitized);
        return [$this->redactor->filterOutput($namespace, $typeName, $sanitized)];
    }

    public function modify(string $namespace, string $typeName, StoreSchema $meta, string $from, DataQueryParam $filters, array $data): array
    {
        $sanitized = $this->sanitizer->sanitizeInput($namespace, $typeName, $data);
        $readed = $this->read($namespace, $typeName, $meta, $filters);
        if (!count($readed)) {
            throw new InvalidArgumentException("Not found");
        }
        if (!$this->guard->canExecute($from, $namespace, $typeName, $sanitized, $readed)) {
            throw new UnauthorizedException("Not allowed to $from onver $namespace:$typeName");
        }
        $saved = [];
        foreach ($readed as $read) {
            $save = array_merge($read, $sanitized);
            $output = $this->execPipeline->executeOperation($namespace, $typeName, [ucfirst($from), 'Write', 'Read'], function () use ($namespace, $typeName, $read, $save, $meta) {
                $this->save($namespace, $typeName, $meta, $save);
                return $save;
            }, $save, $read);
            $saved[] = $this->redactor->filterOutput($namespace, $typeName, $output);
        }
        return $saved;
    }

    public function delete(string $namespace, string $typeName, StoreSchema $meta, string $from, DataQueryParam $filters): void
    {
        $readed = $this->read($namespace, $typeName, $meta, $filters);
        if (!$this->guard->canExecute($from, $namespace, $typeName, $readed, $readed)) {
            throw new UnauthorizedException("Not allowed to $from onver $namespace:$typeName");
        }
        foreach ($readed as $read) {
            $this->execPipeline->executeOperation($namespace, $typeName, ['Delete'], function () use ($namespace, $typeName, $read, $meta) {
                $this->gateway->delete($namespace, $typeName, $read, $meta);
            }, $read, $read);
        }
    }

    public function fetch(string $namespace, string $typeName, StoreSchema $meta, DataQueryParam $originalFilter): array
    {
        if (!$this->guard->canExecute('read', $namespace, $typeName, [], [])) {
            throw new UnauthorizedException("Not allowed to read over $namespace:$typeName");
        }
        $all = $this->read($namespace, $typeName, $meta, $originalFilter);
        return array_map(fn($row) => $this->redactor->filterOutput($namespace, $typeName, $row), $all);
    }

    private function read(string $namespace, string $typeName, StoreSchema $meta, DataQueryParam $originalFilter): array
    {
        $filter = $this->restrictor->restrictFilter($namespace, $typeName, $originalFilter->toArray());
        $filters = DataQueryParam::replaceInto($originalFilter, $filter);
        $values = $this->gateway->read($namespace, $typeName, $meta, $filters);
        $result = [];
        foreach ($values as $value) {
            $result[] = $this->execPipeline->executeOperation($namespace, $typeName, ['Read'], null, $value);
        }
        return $result;
    }

    private function save(string $namespace, string $typeName, StoreSchema $meta, array $data) 
    {
        $this->gateway->save($namespace, $typeName, $meta, $data);
    }
}
