<?php

namespace Civi\Repomanager\Features\Repository\Composer;

use \DirectoryIterator;
use Psr\Log\LoggerInterface;

class ComposerRepository
{
    private array $credentials;
    public function __construct(
        private readonly string $title,
        private readonly string $baseDir,
        private readonly string $url,
        private readonly LoggerInterface $loggerInterface
    ) {
    }

    public function sendFileThroughtHttp($file)
    {
        $ruta = realpath("{$this->baseDir}/{$file}");
        // Asegurarse de que el archivo existe y está dentro del baseDir
        if (!$ruta || !str_starts_with($ruta, realpath($this->baseDir))) {
            http_response_code(403);
            echo "Acceso denegado";
            exit;
        }
        if (!is_file($ruta)) {
            http_response_code(404);
            echo "Archivo no encontrado";
            exit;
        }
        $mime = mime_content_type($ruta);
        header("Content-Type: $mime");
        header("Content-Length: " . filesize($ruta));
        readfile($ruta);
        exit;
    }

    public function build()
    {
        $packages = [];

        // Recorrer todos los paquetes en el directorio base.
        $this->scanVendors($packages);

        // Construir el contenido de packages.json.
        $packagesJson = json_encode(['packages' => $packages], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        // Escribir el archivo packages.json en el directorio base.
        file_put_contents("{$this->baseDir}/packages.json", $packagesJson);
    }

    private function scanVendors(&$packages)
    {
        foreach (new DirectoryIterator($this->baseDir) as $vendorDir) {
            if ($vendorDir->isDot() || !$vendorDir->isDir()) {
                continue;
            }
            $this->scanVendorPackage($vendorDir, $packages);
        }
    }

    private function scanVendorPackage($vendorDir, &$packages)
    {
        foreach (new DirectoryIterator($vendorDir->getPathname()) as $packageDir) {
            if ($packageDir->isDot() || !$packageDir->isDir()) {
                continue;
            }
            $packageName = $vendorDir->getFilename() . '/' . $packageDir->getFilename();
            $this->scanVersion($packageName, $packageDir, $packages);
        }
    }

    private function scanVersion($packageName, $packageDir, &$packages)
    {
        $versionsDir = $packageDir->getPathname();

        // Recorrer cada versión disponible para el paquete.
        foreach (new DirectoryIterator($versionsDir) as $versionDir) {
            if ($versionDir->isDot() || !$versionDir->isDir()) {
                continue;
            }
            $this->generateDescriptor($packageName, $versionDir, $packages);
        }
    }

    private function generateDescriptor($packageName, $versionDir, &$packages)
    {
        $version = $versionDir->getFilename();
        $zipFiles = glob($versionDir->getPathname() . '/*.zip');

        $count = count($zipFiles);
        // Asegurarse de que hay exactamente un archivo ZIP.
        if ($count === 1) {
            $zipFile = basename($zipFiles[0]);
            $composerJsonPath = "zip://" . $zipFiles[0] . "#composer.json";

            // Leer el archivo composer.json dentro del ZIP.
            $composerData = json_decode(file_get_contents($composerJsonPath), true);

            if ($composerData) {
                $packages[$packageName][$version] = [
                    'name' => $composerData['name'] ?? $packageName,
                    'version' => $version,
                    'dist' => [
                        'url' => "{$this->url}/{$packageName}/{$version}/{$zipFile}",
                        'type' => 'zip'
                    ],
                    'autoload' => $composerData['autoload'] ?? [],
                    'require' => $composerData['require'] ?? [],
                    'description' => $composerData['description'] ?? '',
                ];
            }
        } else {
            $this->loggerInterface->warning("Error: Se esperaba un único archivo ZIP en {$versionDir->getPathname()}");
        }
    }

}