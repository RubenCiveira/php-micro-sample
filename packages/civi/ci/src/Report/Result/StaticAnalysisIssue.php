<?php

namespace Civi\Ci\Report\Result;

class StaticAnalysisIssue
{
    public function __construct(
        public string $fileName,
        public int $lineFrom,
        public int $lineTo,
        public int $columnFrom,
        public int $columnTo,
        public string $type,
        public string $message,
        public string $severity,
        public string $snippet,
        public string $selectedText,
        public int $from,
        public int $to,
        public int $snippetFrom,
        public int $snippetTo,
        public string $link,
        public int $shortcode,
        public int $errorLevel,
    ) {}
}
