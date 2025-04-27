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

    public function getIssuesByType(): array
    {
        $grouped = [];

        foreach ($this->issues as $issue) {
            $grouped[$issue->type][] = $issue;
        }

        return $grouped;
    }
}
