<?php

namespace Civi\Ci\Report;

use Civi\Ci\Report\Parser\TestReportParser;
use Civi\Ci\Report\Parser\CoverageReportParser;
use Civi\Ci\Report\Parser\StaticAnalysisReportParser;
use Civi\Ci\Report\Parser\LintReportParser;
use Civi\Ci\Report\Parser\SastReportParser;
use Civi\Ci\Report\Result\LintReportResult;

class ReportAnalyzer
{
    public function __construct(
        private TestReportParser $testParser,
        private CoverageReportParser $coverageParser,
        private StaticAnalysisReportParser $staticAnalysisParser,
        private LintReportParser $lintParser,
        private SastReportParser $sastParser
    ) {}

    public function analyze(string $reportDir): ReportSummary
    {
        $testReport = $this->testParser->parse("$reportDir/test-report.xml");
        $coverageReport = $this->coverageParser->parse("$reportDir/coverage-report.xml");
        $staticAnalysisReport = $this->staticAnalysisParser->parse("$reportDir/static-analysis-report.xml");
        // $lintReport = new LintReportResult([]);
        $lintReport = $this->lintParser->parse("$reportDir/lint-report.xml");
        $sastReport = $this->sastParser->parse("$reportDir/sast-report.xml");

        return new ReportSummary(
            testReport: $testReport,
            coverageReport: $coverageReport,
            staticAnalysisReport: $staticAnalysisReport,
            lintReport: $lintReport,
            sastReport: $sastReport
        );
    }
}
