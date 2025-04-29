<?php

declare(strict_types=1);

namespace Civi\Micro\Management;

use Closure;
use Override;

/**
 * @api
 */
class HealthManagement implements ManagementInterface
{
    public function __construct(private readonly array $providers)
    {
    }

    #[Override]
    public function name(): string
    {
        return 'health';
    }

    #[Override]
    public function get(): ?Closure
    {
        return function (): array {
            $status = 'UP';
            $details = [];
            foreach ($this->providers as $provider) {
                $detail = $provider->check();
                if ($detail->status === 'DOWN') {
                    $status = 'DOWN';
                } elseif ($detail->status === 'UNKWOWN') {
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

    #[Override]
    public function set(): ?Closure
    {
        return null;
    }
}
