<?php

namespace Civi\Ci\Report\Parser;

use Civi\Ci\Report\Result\SastIssue;
use Civi\Ci\Report\Result\SastReportResult;

class SastReportParser
{
    public function parse(string $filepath): SastReportResult
    {
        $xml = simplexml_load_file($filepath);

        $issues = [];

        foreach ($xml->file as $file) {
            $filePath = (string) $file['name'];

            foreach ($file->issue as $issue) {
                $issues[] = new SastIssue(
                    file: $filePath,
                    line: (int) $issue['line'],
                    type: (string) $issue['type'],
                    message: (string) $issue['message']
                );
            }
        }

        return new SastReportResult($issues);
    }
}
