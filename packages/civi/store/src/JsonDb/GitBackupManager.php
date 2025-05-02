<?php

declare(strict_types=1);

namespace Civi\Store\JsonDb;

use Civi\Micro\ProjectLocator;

class GitBackupManager
{
    private string $storageDir;
    private string $branch;

    public function __construct(private readonly JsonDbConfig $config, string $storageDir = '', string $branch = 'main')
    {
        $this->storageDir = $storageDir ? rtrim($storageDir, '/') : ProjectLocator::getRootPath() . '/storage';
        $this->branch = $branch;
    }

    public function init(): string
    {
        $log = [];

        // Inicializar el repo si aún no existe
        if (!is_dir("{$this->storageDir}/.git")) {
            $log[] = $this->run("git init");
            $log[] = $this->run("git config user.name \"{$this->config->userName}\"");
            $log[] = $this->run("git config user.email \"{$this->config->userEmail}\"");
        } else {
            $log[] = "Repositorio Git ya inicializado.";
        }

        // Crear .gitignore si no existe
        $gitignorePath = "{$this->storageDir}/.gitignore";
        if (!file_exists($gitignorePath)) {
            file_put_contents($gitignorePath, ".index.lock\n.git-credentials\n.last-sync\n.sync.lock\n");
            $log[] = "Archivo .gitignore creado.";
        }

        // Añadir el remote si se proporciona y no existe
        $remotes = $this->run("git remote");
        if (!in_array('origin', explode("\n", $remotes))) {
            $log[] = $this->run("git remote add origin {$this->config->backupRemote}");
            $log[] = "Remote 'origin' añadido: this->config->remoteUrl";

            // Commit inicial si necesario
            if ($this->run("git status --porcelain") !== '') {
                $log[] = $this->run("git add .");
                $log[] = $this->run("git commit -m 'Commit inicial'");
            }

            // Crear rama si no existe y hacer push
            $log[] = $this->run("git branch -M {$this->branch}");
            $this->loginWithToken();
            $log[] = $this->run("git push -u origin {$this->branch}");
        } else {
            $log[] = "Remote 'origin' ya existe.";
        }

        return implode("\n", array_filter($log));
    }

    public function removeLogin(): void
    {
        $repoPath = rtrim($this->storageDir, '/');
        $credFile = "{$repoPath}/.git-credentials";
        unlink( $credFile );
    }

    public function loginWithToken(): void
    {
        $parsed = parse_url($this->config->backupRemote);

        if (!$parsed || !isset($parsed['host'])) {
            throw new \InvalidArgumentException("URL no válida: {$this->config->backupRemote}");
        }

        $host = $parsed['host'];
        $user = $parsed['user'] ?? 'git';
        $path = $parsed['path'] ?? '';
        $scheme = $parsed['scheme'] ?? 'https';

        // URL con token inyectado
        $urlWithToken = "{$scheme}://{$this->config->backupToken}@{$host}{$path}";

        // Guardar credencial en archivo local
        $repoPath = rtrim($this->storageDir, '/');
        $credFile = "{$repoPath}/.git-credentials";

        file_put_contents($credFile, $urlWithToken . "\n");
        chmod($credFile, 0600);

        // Configurar git para usar este helper local
        $this->run("git config credential.helper 'store --file=.git-credentials'");
    }

    public function diary(): string
    {
        $tag = 'backup-' . date('Y-m-d');
        $existingTags = $this->run('git tag');
        if (str_contains($existingTags, $tag)) {
            return "Tag '$tag' ya existe.";
        }

        return $this->run("git tag $tag");
    }

    private function shouldDebounce(int $seconds = 60): bool
    {
        $file = "{$this->storageDir}/.last-sync";

        if (file_exists($file)) {
            $last = (int) file_get_contents($file);
            if (time() - $last < $seconds) {
                return true; // Saltar backup
            }
        }

        file_put_contents($file, time());
        return false; // Continuar
    }

    private function withLock(callable $callback, int $timeout = 10): bool
    {
        $lockFile = "{$this->storageDir}/.sync.lock";
        $fp = fopen($lockFile, 'c');

        if (!$fp) {
            echo "<h1>Vamos a ver</h1>";
            return false;
        }

        $locked = flock($fp, LOCK_EX | LOCK_NB);

        if (!$locked) {
            fclose($fp);
            return false; // Otro proceso tiene el lock
        }

        try {
            echo "<h1>LLamada</h1>";
            $callback();
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }

        return true;
    }

    private function run(string $command): string
    {
        $fullCommand = "cd {$this->storageDir} && $command 2>&1";
        $output = shell_exec($fullCommand);
        return trim($output ?? '');
    }

    public function backup()
    {
        $this->withLock(function () {
            echo "<h1>Intentamos en {$this->config->backupRemote}</h1>";
            $hasWait = !$this->shouldDebounce(60);
            $hasChanges = $this->hasChanges();
            var_dump($hasChanges);
            var_dump($hasWait);
            if ($hasWait && $hasChanges) {
                echo "<h1>GO TO IT</h1>";
                $this->save('Backup automático ' . date('Y-m-d H:i:s'));
                echo "<h1>PUSH</h1>";
                $this->loginWithToken();
                var_Dump($this->push());
            } else {
                echo "<h1>NOP</h1>";
            }
        });
    }

    public function restore(string $commit = 'HEAD'): string
    {
        return $this->run("git checkout $commit");
    }

    private function save(string $msg): string
    {
        $this->run('git add .');
        return $this->run("git commit -m \"$msg\"") ?: 'No changes to commit';
    }

    private function push(): string
    {
        putenv('GIT_ASKPASS=' . __DIR__ . '/git-askpass.sh');
        putenv('GIT_HUB_TOKEN=tu-token-aqui');
        return $this->run("git push origin {$this->branch}");
    }

    public function hasChanges(): bool
    {
        $status = $this->run('git status --porcelain');
        return trim($status) !== '';
    }

    public function history(int $limit = 10): array
    {
        $log = $this->run("git log -n $limit --pretty=format:'%h|%an|%ad|%s'");
        $lines = explode("\n", $log);
        return array_map(fn ($line) => str_getcsv($line, '|'), $lines);
    }

    public function status(): string
    {
        return $this->run("git status -s");
    }
}
