<?php

declare(strict_types=1);

namespace Civi\Micro\Management;

use Closure;

/**
 * @api
 */
interface ManagementInterface
{
    public function name(): string;
    public function get(): ?Closure;
    public function set(): ?Closure;
}
