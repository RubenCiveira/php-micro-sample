<?php

namespace Civi\Ci\Report\Generator;

use Civi\Ci\Report\ReportSummary;

interface ReportGeneratorInterface
{
    public function generate(ReportSummary $summary, string $outputPath): void;
}
