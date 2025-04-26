<?php

declare(strict_types=1);

namespace Civi\Ci\Git;

use Civi\Ci\Artifact\ArtifactCommon;
use Symfony\Component\Process\Process;

class GitCommon
{
    protected function featureUseVersionPrefix(): bool
    {
        return false;
    }

    protected static function getDevelopBranch(): string
    {
        return 'develop';
    }

    protected static function isDevelopProtected(): bool
    {
        return true;
    }

    protected static function getMainBranch(): string
    {
        return 'main';
    }

    protected static function getCurrentVersion(): string
    {
        return ArtifactCommon::getCurrentVersion();
    }

    protected static function ensureBranchExists(string $branch): void
    {
        $existsLocal = true;
        $existsRemote = true;

        // Comprobar rama local
        if (! self::checkGit(['show-ref', '--verify', '--quiet', "refs/heads/$branch"])) {
            echo "❌ La rama local '$branch' no existe.\n";
            $existsLocal = false;
        }

        // Comprobar rama remota si develop está protegido
        if (self::isDevelopProtected()) {
            if (! self::checkGit(['ls-remote', '--exit-code', '--heads', 'origin', $branch])) {
                echo "❌ La rama remota '$branch' no existe en origin.\n";
                $existsRemote = false;
            }
        }

        if (!$existsLocal || !$existsRemote) {
            throw new \RuntimeException("La rama '$branch' no existe completamente.");
        }
    }

    protected static function ensureBranchDoesNotExist(string $branch): void
    {
        // Comprobar rama local
        if (! self::checkGit(['show-ref', '--verify', '--quiet', "refs/heads/$branch"])) {
            throw new \RuntimeException("❌ La rama local '$branch' ya existe.");
        }
        // Comprobar rama remota
        if (! self::checkGit(['ls-remote', '--exit-code', '--heads', 'origin', $branch])) {
            throw new \RuntimeException("❌ La rama remota '$branch' ya existe en origin.");
        }
    }

    protected static function prepareEnvForFeature(): string
    {
        self::ensureCleanWorkspace();

        // 1. Checkout a main branch y actualizar
        $mainBranch = self::getMainBranch();
        self::runGit(['checkout', $mainBranch]);
        self::runGit(['pull', 'origin', $mainBranch]);

        // 2. Obtener versión actual
        $currentVersion = self::getCurrentVersion();

        // 3. Crear tag si no existe
        $tag = "v$currentVersion";
        if (!self::checkGit(['rev-parse', $tag])) {
            self::runGit(['tag', '-a', $tag, '-m', "Versión $currentVersion"]);
            self::runGit(['push', 'origin', $tag]);
            echo "🏷️ Tag '$tag' creado y subido.\n";
        } else {
            echo "✅ Tag '$tag' ya existe.\n";
        }

        // 4. Volver a develop
        $developBranch = self::getDevelopBranch();
        self::runGit(['checkout', $developBranch]);
        self::runGit(['pull', 'origin', $developBranch]);

        return $currentVersion;
    }

    protected static function getIncrementType(): string
    {
        $mainBranch = self::getMainBranch();
        $developBranch = self::getDevelopBranch();

        $output = self::runGit(['log', "$mainBranch..$developBranch", '--pretty=format:%s']);
        $commits = explode("\n", $output);

        $hasBreaking = false;
        $hasFeat = false;
        $hasFix = false;

        foreach ($commits as $commit) {
            if (str_contains($commit, 'BREAKING CHANGE') || str_contains($commit, '!:')) {
                $hasBreaking = true;
            }
            if (str_starts_with($commit, 'feat:')) {
                $hasFeat = true;
            }
            if (str_starts_with($commit, 'fix:')) {
                $hasFix = true;
            }
        }

        if ($hasBreaking) return 'major';
        if ($hasFeat) return 'minor';
        if ($hasFix) return 'patch';
        return 'patch';
    }

    protected static function bumpVersion(string $base, string $type): string
    {
        [$major, $minor, $patch] = explode('.', preg_replace('/-.*/', '', $base));

        switch ($type) {
            case 'major':
                $major++;
                $minor = 0;
                $patch = 0;
                break;
            case 'minor':
                $minor++;
                $patch = 0;
                break;
            case 'patch':
                $patch++;
                break;
        }

        return "$major.$minor.$patch";
    }

    protected static function nextRcNumber(string $baseVersion): int
    {
        $output = self::runGit(['branch', '-r']);
        $branches = explode("\n", $output);
        $rcNumbers = [];
        foreach ($branches as $branch) {
            if (preg_match("#origin/release/{$baseVersion}-rc\.(\d+)#", $branch, $matches)) {
                $rcNumbers[] = (int) $matches[1];
            }
        }
        return empty($rcNumbers) ? 1 : max($rcNumbers) + 1;
    }

    protected static function ensureCleanWorkspace(): void
    {
        $output = self::runGit(['status', '--porcelain']);
        // Si la salida no está vacía, hay cambios pendientes
        if (trim($output) !== '') {
            echo "❌ Hay cambios sin commitear. Aborta o guarda los cambios antes de continuar.\n";
            exit(1); // Igual que en shell: aborta con error
        }
    }

    protected static function checkGit(array $args): bool
    {
        $process = new Process(array_merge(['git'], $args));
        $process->setTty(Process::isTtySupported());
        return $process->run() !== 0;
    }

    protected static function runGit(array $args): string
    {
        $process = new Process(array_merge(['git'], $args));
        $process->setTty(Process::isTtySupported());
        $process->mustRun();
        return $process->getOutput();
    }
}
