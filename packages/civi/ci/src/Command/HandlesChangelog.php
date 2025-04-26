<?php

declare(strict_types=1);

namespace Civi\Ci\Command;

use Symfony\Component\Console\Output\OutputInterface;
use Civi\Ci\Git\Changelog;

trait HandlesChangelog
{
    protected function updateChangelog(OutputInterface $output, string $contextDescription): void
    {
        $output->writeln("<info>Actualizando CHANGELOG.md...</info>");

        Changelog::generate();

        // Comprobar si el changelog ha cambiado
        $status = trim((string) shell_exec('git status --porcelain CHANGELOG.md'));

        if ($status !== '') {
            shell_exec('git add CHANGELOG.md');
            shell_exec(sprintf('git commit -m "chore(changelog): actualiza changelog tras %s"', escapeshellarg($contextDescription)));
            $output->writeln("<comment>CHANGELOG.md actualizado y comiteado.</comment>");
        } else {
            $output->writeln("<comment>CHANGELOG.md ya estaba actualizado, no se realizaron cambios.</comment>");
        }
    }
}
