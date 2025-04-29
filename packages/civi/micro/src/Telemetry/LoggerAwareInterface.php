<?php

declare(strict_types=1);

namespace Civi\Micro\Telemetry;

use Psr\Log\LoggerInterface;

/**
 * Interface for components that are aware of a logger instance.
 *
 * Classes implementing this interface can receive a PSR-3 Logger
 * to log messages, errors, or other relevant events.
 *
 * @api
 */
interface LoggerAwareInterface
{
    /**
     * Injects a logger instance into the implementing class.
     *
     * @param LoggerInterface $logger The logger to be used by the class.
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void;
}
