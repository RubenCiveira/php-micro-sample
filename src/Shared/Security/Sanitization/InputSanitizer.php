<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Security\Sanitization;

use Civi\Repomanager\Shared\Kernel\AbstractPipeline;

class InputSanitizer extends AbstractPipeline
{
    public function sanitizeInput(string $namespace, string $typeName, array $context): array
    {
        return $context;
    }
}