<?php

namespace Civi\Ci\Report\Parser;

use Civi\Ci\Report\Result\CoverageFileResult;
use Civi\Ci\Report\Result\CoverageReportResult;

class CoverageReportParser
{
    public function parse(string $filepath): CoverageReportResult
    {
        $xml = simplexml_load_file($filepath);

        $files = [];

        foreach ($xml->xpath('//file') as $fileNode) {
            $filePath = (string) $fileNode['name'];

            $statements = 0;
            $covered = 0;

            foreach ($fileNode->line as $line) {
                if ((string) $line['type'] === 'stmt') {
                    $statements++;
                    if ((int) $line['count'] > 0) {
                        $covered++;
                    }
                }
            }

            $files[] = new CoverageFileResult(
                file: $filePath,
                statements: $statements,
                coveredStatements: $covered
            );
        }

        return new CoverageReportResult($files);
    }
}
