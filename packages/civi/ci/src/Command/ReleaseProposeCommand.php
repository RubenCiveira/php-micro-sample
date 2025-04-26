<?php

declare(strict_types=1);

namespace Civi\Ci\Command;

use Civi\Ci\Git\Release;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReleaseProposeCommand extends Command
{
    protected static $defaultName = 'release:propose';
    protected static $defaultDescription = 'Propone una nueva release a partir de develop';

    protected function configure(): void
    {
        $this->addArgument('version', InputArgument::REQUIRED, 'Número de versión (ej: 1.2.0)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $version = $input->getArgument('version');

        $output->writeln("<info>Proponiendo release: $version</info>");

        try {
            Release::propose($version);
            $output->writeln("<comment>Release '$version' propuesta correctamente.</comment>");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln("<error>Error: {$e->getMessage()}</error>");
            return Command::FAILURE;
        }
    }
}
