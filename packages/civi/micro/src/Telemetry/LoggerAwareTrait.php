<?php

declare(strict_types=1);

namespace Civi\Micro\Telemetry;

use Psr\Log\LoggerInterface;

/**
 * Trait that provides logging capabilities to classes.
 *
 * Classes using this trait can perform structured logging
 * at various severity levels. If no logger is set, logs
 * will fallback to PHP's native error_log().
 *
 * @api
 */
trait LoggerAwareTrait
{
    /**
     * Logger instance.
     *
     * @var LoggerInterface|null
     */
    protected ?LoggerInterface $logger = null;

    /**
     * Sets the logger instance.
     *
     * @param LoggerInterface $logger Logger implementation to use.
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Logs an emergency message.
     *
     * Emergency: system is unusable.
     * Use this level for fatal conditions that require immediate intervention (e.g., database corrupted).
     *
     * @param string|\Stringable $message Log message
     * @param array $context Additional context
     */
    public function logEmergency(string|\Stringable $message, array $context = []): void
    {
        $this->logger?->emergency($message, $context) ?? $this->writeLogFallback('emergency', (string)$message, $context);
    }

    /**
     * Logs an alert message.
     *
     * Alert: action must be taken immediately.
     * Use for critical conditions like missing system resources or significant security issues.
     *
     * @param string|\Stringable $message Log message
     * @param array $context Additional context
     */
    public function logAlert(string|\Stringable $message, array $context = []): void
    {
        $this->logger?->alert($message, $context) ?? $this->writeLogFallback('alert', (string)$message, $context);
    }

    /**
     * Logs a critical message.
     *
     * Critical: critical conditions.
     * Use for serious application failures such as loss of critical services.
     *
     * @param string|\Stringable $message Log message
     * @param array $context Additional context
     */
    public function logCritical(string|\Stringable $message, array $context = []): void
    {
        $this->logger?->critical($message, $context) ?? $this->writeLogFallback('critical', (string)$message, $context);
    }

    /**
     * Logs an error message.
     *
     * Error: runtime errors that do not require immediate action but should be monitored.
     * Use for failures that impact only specific parts of the system.
     *
     * @param string|\Stringable $message Log message
     * @param array $context Additional context
     */
    public function logError(string|\Stringable $message, array $context = []): void
    {
        $this->logger?->error($message, $context) ?? $this->writeLogFallback('error', (string)$message, $context);
    }

    /**
     * Logs a warning message.
     *
     * Warning: exceptional but expected situations.
     * Use for recoverable problems, deprecated APIs, or unexpected states.
     *
     * @param string|\Stringable $message Log message
     * @param array $context Additional context
     */
    public function logWarning(string|\Stringable $message, array $context = []): void
    {
        $this->logger?->warning($message, $context) ?? $this->writeLogFallback('warning', (string)$message, $context);
    }

    /**
     * Logs a notice message.
     *
     * Notice: normal but significant events.
     * Use for important, non-critical events like successful background job completion.
     *
     * @param string|\Stringable $message Log message
     * @param array $context Additional context
     */
    public function logNotice(string|\Stringable $message, array $context = []): void
    {
        $this->logger?->notice($message, $context) ?? $this->writeLogFallback('notice', (string)$message, $context);
    }

    /**
     * Logs an informational message.
     *
     * Info: interesting events.
     * Use for regular application flow like user logins, processing milestones, or system health pings.
     *
     * @param string|\Stringable $message Log message
     * @param array $context Additional context
     */
    public function logInfo(string|\Stringable $message, array $context = []): void
    {
        $this->logger?->info($message, $context) ?? $this->writeLogFallback('info', (string)$message, $context);
    }

    /**
     * Logs a debug message.
     *
     * Debug: detailed debug information.
     * Use for in-depth troubleshooting and fine-grained information for developers.
     *
     * @param string|\Stringable $message Log message
     * @param array $context Additional context
     */
    public function logDebug(string|\Stringable $message, array $context = []): void
    {
        $this->logger?->debug($message, $context) ?? $this->writeLogFallback('debug', (string)$message, $context);
    }

    /**
     * Writes a fallback log entry directly to the PHP error log if no logger is available.
     *
     * @param string $level Log level name
     * @param string $message Log message
     * @param array $context Additional context
     */
    private function writeLogFallback(string $level, string $message, array $context = []): void
    {
        $contextStr = !empty($context) ? json_encode($context) : '';
        error_log(strtoupper($level) . ': ' . $message . ($contextStr ? ' ' . $contextStr : ''));
    }
}
