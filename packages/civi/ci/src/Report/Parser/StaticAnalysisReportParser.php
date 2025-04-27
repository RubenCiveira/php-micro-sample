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

        foreach ($xml->item as $item) {
            $issues[] = new StaticAnalysisIssue(
                fileName: (string) $item->file_name,
                lineFrom: (int) $item->line_from,
                lineTo: (int) $item->line_to,
                columnFrom: (int) $item->column_from,
                columnTo: (int) $item->column_to,
                type: (string) $item->type,
                message: (string) $item->message,
                severity: (string) $item->severity,
                snippet: (string) $item->snippet,
                selectedText: (string) $item->selected_text,
                from: (int) $item->from,
                to: (int) $item->to,
                snippetFrom: (int) $item->snippet_from,
                snippetTo: (int) $item->snippet_to,
                link: (string) $item->link,
                shortcode: (int) $item->shortcode,
                errorLevel: (int) $item->error_level,
            );
        }

        return new StaticAnalysisReportResult($issues);
    }
}
