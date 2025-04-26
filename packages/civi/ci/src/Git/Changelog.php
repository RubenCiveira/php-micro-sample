<?php

declare(strict_types=1);

namespace Civi\Ci\Git;

use Civi\Ci\Artifact\ArtifactCommon;

class Changelog extends GitCommon
{
    public static function generate(?string $version=null): void
    {
        if( !$version ) {
            $version = ArtifactCommon::getCurrentVersion();
        }
        echo "📌 Generando changelog para la versión '$version'...\n";

        $fromTag = trim(self::runGit(['describe', '--tags', '--abbrev=0', self::getDevelopBranch()]));

        if (empty($fromTag)) {
            echo "❌ No se pudo encontrar un tag anterior.\n";
            return;
        }

        echo "📎 Último tag encontrado: $fromTag\n";

        $changelog = self::readCommits($fromTag, self::getDevelopBranch(), $version);

        self::updateChangelogFile($changelog);
    }

    protected static function readCommits(string $from, string $to, string $version): string
    {
        $logFormat = "%h%n%s%n%b%n==END==";
        $commits = self::runGit(['log', "$from..$to", '--no-merges', "--pretty=format:$logFormat"]);

        if (empty($commits)) {
            echo "⚠️ No hay commits nuevos entre $from y $to.\n";
            return '';
        }

        $entries = [
            'breaking' => [],
            'added' => [],
            'fixed' => [],
            'changed' => [],
            'removed' => [],
            'other' => [],
        ];

        $lines = explode("\n", $commits);

        $currentHash = '';
        $currentTitle = '';
        $currentBody = '';

        foreach ($lines as $line) {
            if ($line === '==END==') {
                self::classifyCommit($currentHash, $currentTitle, $currentBody, $entries);
                $currentHash = '';
                $currentTitle = '';
                $currentBody = '';
            } elseif (empty($currentHash)) {
                $currentHash = $line;
            } elseif (empty($currentTitle)) {
                $currentTitle = $line;
            } else {
                $currentBody .= $line . "\n";
            }
        }

        return self::formatChangelogBlock($version, $entries);
    }

    protected static function classifyCommit(string $hash, string $title, string $body, array &$entries): void
    {
        if (str_contains($body, 'BREAKING CHANGE')) {
            $entries['breaking'][] = self::formatCommit($hash, $body, true);
            return;
        }

        $message = trim(preg_replace('/^[a-z]+(\([a-zA-Z0-9_-]+\))?:\s*/', '', $title));
        $formatted = "- $message (`$hash`)";

        if (str_starts_with($title, 'feat:') || str_starts_with($title, 'feat(')) {
            $entries['added'][] = $formatted;
        } elseif (str_starts_with($title, 'fix:') || str_starts_with($title, 'fix(')) {
            $entries['fixed'][] = $formatted;
        } elseif (str_starts_with($title, 'chore:') || str_starts_with($title, 'refactor:')) {
            $entries['changed'][] = $formatted;
        } elseif (str_starts_with($title, 'remove:') || str_starts_with($title, 'removed:')) {
            $entries['removed'][] = $formatted;
        } else {
            $entries['other'][] = $formatted;
        }
    }

    protected static function formatCommit(string $hash, string $body, bool $breaking = false): string
    {
        $lines = explode("\n", $body);
        $breakingLine = '';

        foreach ($lines as $line) {
            if (str_starts_with($line, 'BREAKING CHANGE:')) {
                $breakingLine = trim(substr($line, strlen('BREAKING CHANGE:')));
                break;
            }
        }

        $formatted = ucfirst($breakingLine) . " (`$hash`)";
        return "- $formatted";
    }

    protected static function formatChangelogBlock(string $version, array $entries): string
    {
        $date = date('Y-m-d');

        $sections = [];
        $sections[] = "## [$version] - $date";

        foreach ([
            'breaking' => '💥 Breaking Changes',
            'added' => '✨ Added',
            'fixed' => '🐛 Fixed',
            'changed' => '♻️ Changed',
            'removed' => '🔥 Removed',
            'other' => '🧩 Other'
        ] as $key => $title) {
            if (!empty($entries[$key])) {
                $sections[] = "\n### $title\n" . implode("\n", $entries[$key]);
            }
        }

        return implode("\n", $sections) . "\n";
    }

    protected static function updateChangelogFile(string $block): void
    {
        if (empty($block)) {
            echo "⚠️ No se generó contenido para el changelog.\n";
            return;
        }

        $changelogPath = getcwd() . '/CHANGELOG.md';

        if (file_exists($changelogPath)) {
            copy($changelogPath, $changelogPath . '.bak');

            $currentContent = file_get_contents($changelogPath);
            file_put_contents($changelogPath, $block . "\n\n" . $currentContent);

            echo "📝 CHANGELOG.md actualizado.\n";
        } else {
            file_put_contents($changelogPath, $block);
            echo "📝 CHANGELOG.md creado.\n";
        }
    }
}
