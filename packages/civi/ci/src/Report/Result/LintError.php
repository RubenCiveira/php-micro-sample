<?php

namespace Civi\Ci\Report\Result;

class LintError
{
    public function __construct(
        public string $file,
        public int $line,
        public string $severity, // Ej: "error", "warning"
        public string $message
    ) {}
}
