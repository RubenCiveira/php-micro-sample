<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Security\Redaction;

use Civi\Repomanager\Shared\Kernel\AbstractPipeline;

class OutputRedactor extends AbstractPipeline
{
    public function filterOutput(string $namespace, string $typeName, array $content): mixed
    {
        return $content;
    }
}