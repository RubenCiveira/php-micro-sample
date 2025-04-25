<?php

declare(strict_types=1);

namespace Civi\Micro\Telemetry;

use Psr\Log\LoggerInterface;

interface LoggerAwareInterface
{
    public function setLogger(LoggerInterface $logger): void;
}