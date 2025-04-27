<?php

namespace Civi\Ci\Report\Result;

class CoverageReportResult
{
    /**
     * @param CoverageFileResult[] $files
     */
    public function __construct(
        public array $files
    ) {}

    public function getOverallCoveragePercent(): float
    {
        $totalStatements = array_sum(array_map(fn($file) => $file->statements, $this->files));
        $totalCovered = array_sum(array_map(fn($file) => $file->coveredStatements, $this->files));

        return $totalStatements > 0 ? round(($totalCovered / $totalStatements) * 100, 2) : 0.0;
    }
}
