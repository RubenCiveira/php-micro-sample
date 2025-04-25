<?php

declare(strict_types=1);

namespace Civi\Micro\Management;

interface HealthProviderInterface
{
    public function check(): HealthDetail;
}


