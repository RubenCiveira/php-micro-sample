<?php

declare(strict_types=1);

namespace Civi\Micro\Management;

/**
 * @api
 */
interface HealthProviderInterface
{
    public function check(): HealthDetail;
}
