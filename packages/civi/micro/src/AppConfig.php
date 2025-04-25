<?php

declare(strict_types=1);

namespace Civi\Micro;

class AppConfig
{
    public readonly string $managementEndpoint;
    public function __construct(?string $managementEndpoint)
    {
        $this->managementEndpoint = "/management";
    }
}