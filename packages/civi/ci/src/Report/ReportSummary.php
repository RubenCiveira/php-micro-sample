<?php

namespace Civi\Ci\Report;

use Civi\Ci\Report\Result\TestReportResult;
use Civi\Ci\Report\Result\CoverageReportResult;
use Civi\Ci\Report\Result\StaticAnalysisReportResult;
use Civi\Ci\Report\Result\LintReportResult;
use Civi\Ci\Report\Result\SastReportResult;

class ReportSummary
{
    public function __construct(
        public TestReportResult $testReport,
        public CoverageReportResult $coverageReport,
        public StaticAnalysisReportResult $staticAnalysisReport,
        public LintReportResult $lintReport,
        public SastReportResult $sastReport
    ) {}
}
