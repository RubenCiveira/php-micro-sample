<?php

namespace Civi\Ci\Report\Parser;

use Civi\Ci\Report\Result\LintError;
use Civi\Ci\Report\Result\LintReportResult;

class LintReportParser
{
    public function parse(string $filepath): LintReportResult
    {
        $xml = simplexml_load_file($filepath);

        $errors = [];

        foreach ($xml->file as $file) {
            $filePath = (string) $file['name'];

            foreach ($file->error as $error) {
                $line = isset($error['line']) ? (int) $error['line'] : 0; // Línea opcional

                $errors[] = new LintError(
                    file: $filePath,
                    line: $line,
                    severity: (string) $error['severity'],
                    message: (string) $error['message']
                );
            }
        }
        return new LintReportResult($errors);
    }
}
