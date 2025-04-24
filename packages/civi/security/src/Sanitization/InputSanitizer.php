<?php declare(strict_types=1);

namespace Civi\Security\Sanitization;

use Civi\Micro\Kernel\AbstractPipeline;

class InputSanitizer extends AbstractPipeline
{
    public function sanitizeInput(string $namespace, string $typeName, array $input): array
    {
        return $input;
    }
}