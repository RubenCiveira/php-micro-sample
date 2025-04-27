<?php

namespace Civi\Ci\Report\Parser;

use Civi\Ci\Report\Result\StaticAnalysisIssue;
use Civi\Ci\Report\Result\StaticAnalysisReportResult;

class StaticAnalysisReportParser
{
    public function parse(string $filepath): StaticAnalysisReportResult
    {
        
        $xml = simplexml_load_file($filepath);

        $issues = [];

        foreach ($xml->file as $file) {
            $filePath = (string) $file['name'];

            foreach ($file->issue as $issue) {
                $issues[] = new StaticAnalysisIssue(
                    file: $filePath,
                    line: (int) $issue['line'],
                    type: (string) $issue['type'],
                    message: (string) $issue['message']
                );
            }
        }

        return new StaticAnalysisReportResult($issues);
    }
}
