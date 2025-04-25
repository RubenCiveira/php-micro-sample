<?php

declare(strict_types=1);

namespace Civi\Micro\Telemetry;

use Psr\Log\LoggerInterface;

trait LoggerAwareTrait
{
    protected ?LoggerInterface $logger = null;

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function logEmergency(string|\Stringable $message, array $context = []): void
    {
        $this->logger?->emergency($message, $context) ?? $this->writeLogFallback('emergency', (string)$message, $context);
    }

    public function logAlert(string|\Stringable $message, array $context = []): void
    {
        $this->logger?->alert($message, $context) ?? $this->writeLogFallback('alert', (string)$message, $context);
    }

    public function logCritical(string|\Stringable $message, array $context = []): void
    {
        $this->logger?->critical($message, $context) ?? $this->writeLogFallback('critical', (string)$message, $context);
    }

    public function logError(string|\Stringable $message, array $context = []): void
    {
        $this->logger?->error($message, $context) ?? $this->writeLogFallback('error', (string)$message, $context);
    }

    public function logWarning(string|\Stringable $message, array $context = []): void
    {
        $this->logger?->warning($message, $context) ?? $this->writeLogFallback('warning', (string)$message, $context);
    }

    public function logNotice(string|\Stringable $message, array $context = []): void
    {
        $this->logger?->notice($message, $context) ?? $this->writeLogFallback('notice', (string)$message, $context);
    }

    public function logInfo(string|\Stringable $message, array $context = []): void
    {
        $this->logger?->info($message, $context) ?? $this->writeLogFallback('info', (string)$message, $context);
    }

    public function logDebug(string|\Stringable $message, array $context = []): void
    {
        $this->logger?->debug($message, $context) ?? $this->writeLogFallback('debug', (string)$message, $context);
    }

    private function writeLogFallback(string $level, string $message, array $context = []): void
    {
        $contextStr = !empty($context) ? json_encode($context) : '';
        error_log(strtoupper($level) . ': ' . $message . ($contextStr ? ' ' . $contextStr : ''));
    }
}
