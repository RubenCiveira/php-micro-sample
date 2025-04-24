<?php declare(strict_types=1);

namespace Civi\Security\Redaction;

use Civi\Micro\Kernel\AbstractPipeline;

class OutputRedactor extends AbstractPipeline
{
    public function filterOutput(string $namespace, string $typeName, array $content): mixed
    {
        return $content;
    }
}