<?php

declare(strict_types=1);

namespace Civi\Store\Gateway;

use Civi\Micro\ProjectLocator;

class SchemaGateway
{
    private readonly string $baseDir;
    private array $aditional = [];
    public function __construct(string $base = '')
    {
        $this->baseDir = $base !== '' ? $base : ProjectLocator::getRootPath();
    }

    public function sdl(string $namespace): string
    {
        if (isset($this->aditional[$namespace])) {
            $directory = $this->aditional[$namespace];
        } else {
            $directory = "{$this->baseDir}/config/schemas/$namespace";
        }
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

    public function install(string $namespace, string $directory)
    {
        $this->aditional[$namespace] = $directory;
    }
}
