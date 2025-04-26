<?php

declare(strict_types=1);

namespace Civi\Ci\Artifact\Composer;

class ComposerCommon
{

    private static function composerPath(): string
    {
        $composerPath = getcwd() . '/composer.json';
        if (!file_exists($composerPath)) {
            throw new \RuntimeException('No se encontró composer.json en el directorio actual.');
        }
        return $composerPath;
    }

    public static function getCurrentVersion(): string
    {
        $composerPath = self::composerPath();

        $content = file_get_contents($composerPath);
        $composerData = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('composer.json contiene errores de formato JSON.');
        }

        if (!isset($composerData['version'])) {
            throw new \RuntimeException('El campo "version" no está definido en composer.json.');
        }

        return $composerData['version'];
    }

    public static function setVersion(string $newVersion): void
    {
        $composerPath = self::composerPath();

        $content = file_get_contents($composerPath);
        $composerData = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('composer.json contiene errores de formato JSON.');
        }

        $composerData['version'] = $newVersion;

        $newContent = json_encode($composerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

        if (file_put_contents($composerPath, $newContent) === false) {
            throw new \RuntimeException('No se pudo guardar el nuevo composer.json.');
        }

        echo "✅ Versión actualizada a '$newVersion' en composer.json\n";
    }
}