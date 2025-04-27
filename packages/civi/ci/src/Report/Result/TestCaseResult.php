<?php

namespace Civi\Ci\Report\Result;

class TestCaseResult
{
    public function __construct(
        public string $name,
        public string $file,
        public string $class,
        public float $time,
        public bool $passed,
        public bool $skipped = false,
        public ?string $failureMessage = null,
        public ?string $errorMessage = null
    ) {}
}
