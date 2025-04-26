<?php declare(strict_types=1);

namespace Civi\Ci\Command;

use Civi\Ci\Git\Changelog;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ChangelogGenerateCommand extends Command
{
    protected static $defaultName = 'changelog:generate';
    protected static $defaultDescription = 'Genera o actualiza el archivo CHANGELOG.md basado en los commits recientes';

    protected function configure(): void
    {
        $this
            ->addArgument('version', InputArgument::OPTIONAL, 'Número de versión para el changelog. Si no se indica, se usa la versión actual del artefacto.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $version = $input->getArgument('version');

        if ($version) {
            $output->writeln("<info>Generando changelog para versión especificada: $version</info>");
        } else {
            $output->writeln("<info>Generando changelog usando la versión actual del artefacto</info>");
        }

        try {
            Changelog::generate($version);
            $output->writeln("<comment>CHANGELOG.md actualizado correctamente.</comment>");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln("<error>Error al generar el changelog: {$e->getMessage()}</error>");
            return Command::FAILURE;
        }
    }
}
