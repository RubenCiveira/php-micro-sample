<?php

namespace Civi\Ci\Report\Parser;

use Civi\Ci\Report\Result\TestCaseResult;
use Civi\Ci\Report\Result\TestReportResult;

class TestReportParser
{
    public function parse(string $filepath): TestReportResult
    {
        $xml = simplexml_load_file($filepath);

        $testCases = [];

        foreach ($xml->xpath('//testcase') as $testcase) {
            $name = (string) $testcase['name'];
            $file = (string) $testcase['file'];
            $class = (string) $testcase['class'];
            $time = (float) $testcase['time'];

            $passed = true;
            $skipped = isset($testcase->skipped);
            $failureMessage = null;
            $errorMessage = null;

            if (isset($testcase->failure)) {
                $passed = false;
                $failureMessage = (string) $testcase->failure;
            }

            if (isset($testcase->error)) {
                $passed = false;
                $errorMessage = (string) $testcase->error;
            }

            $testCases[] = new TestCaseResult(
                name: $name,
                file: $file,
                class: $class,
                time: $time,
                passed: $passed,
                skipped: $skipped,
                failureMessage: $failureMessage,
                errorMessage: $errorMessage
            );
        }

        return new TestReportResult($testCases);
    }
}
