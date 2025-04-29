<?php

declare(strict_types=1);

namespace Civi\Micro\Telemetry;

use Psr\Log\LoggerInterface;

/**
 * @api
 */
interface LoggerAwareInterface
{
    public function setLogger(LoggerInterface $logger): void;
}
