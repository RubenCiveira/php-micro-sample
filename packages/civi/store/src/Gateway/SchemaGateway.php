<?php declare(strict_types=1);

namespace Civi\Store\Gateway;

use Civi\Micro\ProjectLocator;

class SchemaGateway
{
    private readonly string $baseDir;
    public function __construct(string $base = '')
    {
        $this->baseDir = $base !== '' ? $base : ProjectLocator::getRootPath();
    }

    public function sdl(string $namespace): string
    {
        $directory = "{$this->baseDir}/config/schemas/$namespace";
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