<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\Store\Gateway;

class SchemaGateway
{
    public function __construct(private readonly string $baseDir = __DIR__ . '/../../../../../')
    {
    }

    public function sdl(string $namespace): string
    {
        $directory = "{$this->baseDir}/config/$namespace";
        $result = '';

        if (!is_dir($directory)) {
            throw new \RuntimeException("No existe el directorio: $directory");
        }
        $files = glob("$directory/*.schema");
        foreach ($files as $file) {
            $result .= file_get_contents($file) . "\n\n";
        }
        return $result;
    }
}