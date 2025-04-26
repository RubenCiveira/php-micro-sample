<?php declare(strict_types=1);

namespace Civi\Ci\Command;

use Civi\Ci\Git\Hotfix;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class HotfixEndCommand extends Command
{
    protected static $defaultName = 'hotfix:end';
    protected static $defaultDescription = 'Finaliza un hotfix fusionándolo a main y etiquetándolo';

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Nombre del hotfix a finalizar');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');

        $output->writeln("<info>Finalizando hotfix: $name</info>");

        try {
            Hotfix::end($name);
            $output->writeln("<comment>Hotfix '$name' fusionado, etiquetado y rama eliminada.</comment>");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln("<error>Error: {$e->getMessage()}</error>");
            return Command::FAILURE;
        }
    }
}
