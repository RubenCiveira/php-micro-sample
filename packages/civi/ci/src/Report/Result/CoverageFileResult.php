<?php

namespace Civi\Ci\Report\Result;

class CoverageFileResult
{
    public function __construct(
        public string $file,
        public int $statements,
        public int $coveredStatements
    ) {}

    public function getCoveragePercent(): float
    {
        return $this->statements > 0 ? round(($this->coveredStatements / $this->statements) * 100, 2) : 0.0;
    }

}