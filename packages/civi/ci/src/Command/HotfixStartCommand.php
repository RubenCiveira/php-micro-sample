<?php declare(strict_types=1);

namespace Civi\Ci\Command;

use Civi\Ci\Git\Hotfix;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class HotfixStartCommand extends Command
{
    protected static $defaultName = 'hotfix:start';
    protected static $defaultDescription = 'Inicia un hotfix a partir de main';

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Nombre del hotfix (por ejemplo: 1.2.1)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');

        $output->writeln("<info>Iniciando hotfix: $name</info>");

        try {
            Hotfix::start($name);
            $output->writeln("<comment>Hotfix '$name' creado correctamente.</comment>");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln("<error>Error: {$e->getMessage()}</error>");
            return Command::FAILURE;
        }
    }
}
