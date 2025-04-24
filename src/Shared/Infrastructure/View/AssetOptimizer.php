<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\View;

use MatthiasMullie\Minify\JS;
use voku\helper\HtmlMin;


class AssetOptimizer
{
    private string $cacheDir;
    private string $publicPath;
    private ScriptExtractor $extractor;

    public function __construct(string $cacheDir, string $publicPath)
    {
        $this->cacheDir = rtrim($cacheDir, '/');
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
        $this->publicPath = rtrim($publicPath, '/');
        $this->extractor = new ScriptExtractor();
    }

    public function optimize(string $html): string
    {
        ['html' => $strippedHtml, 'scripts' => $scripts] = $this->extractor->extractAndReplace($html);

        if (!empty($scripts)) {
            $combined = implode("\n", $scripts);
            $minified = (new JS($combined))->minify();
            $hash = substr(md5($minified), 0, 10);
            $filename = "app.$hash.js";
            $filePath = "{$this->cacheDir}/{$filename}";

            if (!file_exists($filePath)) {
                file_put_contents($filePath, $minified);
                $this->cleanupCacheDirectory();
            }

            $html = $this->extractor->restoreWithScriptTag(
                $strippedHtml,
                "{$this->publicPath}/$filename?v=$hash"
            );
        }
        $htmlMin = new HtmlMin();
        $html = $htmlMin->minify($html);
        return $html;
    }

    private function cleanupCacheDirectory(int $maxFiles = 200): void
    {
        $files = glob("{$this->cacheDir}/*.js");

        // ordenamos por fecha de modificación (más reciente primero)
        usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));

        // si hay más de $maxFiles, eliminamos los más antiguos
        if (count($files) > $maxFiles) {
            $oldFiles = array_slice($files, $maxFiles);
            foreach ($oldFiles as $file) {
                @unlink($file);
            }
        }
    }
}