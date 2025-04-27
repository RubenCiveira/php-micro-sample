<?php

namespace Civi\Ci\Report\Result;

class LintReportResult
{
    /**
     * @param LintError[] $errors
     */
    public function __construct(
        public array $errors
    ) {}

    public function getTotalErrors(): int
    {
        return count(array_filter($this->errors, fn($error) => $error->severity === 'error'));
    }

    public function getTotalWarnings(): int
    {
        return count(array_filter($this->errors, fn($error) => $error->severity === 'warning'));
    }

    public function getErrorsByFile(): array
    {
        $grouped = [];

        foreach ($this->errors as $error) {
            $grouped[$error->file][] = $error;
        }

        return $grouped;
    }
}
