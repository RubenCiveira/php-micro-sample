<?php

declare(strict_types=1);

namespace Civi\Micro\Management;

use Closure;

class HealthManagement implements ManagementInterface
{
    public function __construct(private readonly array $providers) {}

    public function name(): string
    {
        return 'health';
    }

    public function get(): ?Closure
    {
        return function (): array {
            $status = 'UP';
            $details = [];
            foreach ($this->providers as $provider) {
                $detail = $provider->check();
                if ($detail->status === 'DOWN') {
                    $status = 'DOWN';
                } else if ($detail->status === 'UNKWOWN') {
                    $status = $status === 'DOWN' ? 'DOWN' : 'UNKWOWN';
                }
                $details[$detail->name] = ['status' => $detail->status];
                if ($detail->details) {
                    $details[$detail->name]['details'] = $detail->details;
                }
            }
            return $details ? ['status' => $status, 'components' => $details] : ['status' => $status];
        };
    }

    public function set(): ?Closure
    {
        return null;
    }
}
