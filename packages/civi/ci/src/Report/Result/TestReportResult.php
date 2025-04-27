<?php

namespace Civi\Ci\Report\Result;

class TestReportResult
{
    /**
     * @param TestCaseResult[] $testCases
     */
    public function __construct(
        public array $testCases
    ) {}

    public function getTotalTests(): int
    {
        return count($this->testCases);
    }

    public function getPassedTests(): int
    {
        return count(array_filter($this->testCases, fn($test) => $test->passed && !$test->skipped));
    }

    public function getFailures(): int
    {
        return count(array_filter($this->testCases, fn($test) => !$test->passed && $test->failureMessage !== null));
    }

    public function getErrors(): int
    {
        return count(array_filter($this->testCases, fn($test) => !$test->passed && $test->errorMessage !== null));
    }

    public function getSkipped(): int
    {
        return count(array_filter($this->testCases, fn($test) => $test->skipped));
    }
}
