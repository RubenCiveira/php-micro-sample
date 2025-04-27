<?php

namespace Civi\Ci\Report\Result;

class StaticAnalysisIssue
{
    public function __construct(
        public string $file,
        public int $line,
        public string $type,
        public string $message
    ) {}
}
