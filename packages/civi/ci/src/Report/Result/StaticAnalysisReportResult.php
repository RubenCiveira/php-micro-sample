<?php

namespace Civi\Ci\Report\Result;

class StaticAnalysisReportResult
{
    /**
     * @param StaticAnalysisIssue[] $issues
     */
    public function __construct(
        public array $issues
    ) {}

    public function getTotalIssues(): int
    {
        return count($this->issues);
    }

    public function getIssuesByFile(): array
    {
        $grouped = [];

        foreach ($this->issues as $issue) {
            $grouped[$issue->file][] = $issue;
        }

        return $grouped;
    }

    public function getIssuesBySeverity(): array
    {
        $grouped = [];

        foreach ($this->issues as $issue) {
            $grouped[$issue->severity][] = $issue;
        }

        return $grouped;
    }
}
