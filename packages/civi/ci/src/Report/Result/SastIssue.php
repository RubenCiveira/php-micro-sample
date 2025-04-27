<?php

namespace Civi\Ci\Report\Result;

class SastIssue
{
    public function __construct(
        public string $file,
        public int $line,
        public string $type, // Ej: "TaintedInput", "SQLInjection", etc.
        public string $message
    ) {}
}
