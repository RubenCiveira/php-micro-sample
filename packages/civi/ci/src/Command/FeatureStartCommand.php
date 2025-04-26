<?php declare(strict_types=1);

namespace Civi\Ci\Command;

use Civi\Ci\Git\Feature;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FeatureStartCommand extends Command
{
    protected static $defaultName = 'feature:start';
    protected static $defaultDescription = 'Inicia una nueva rama de feature a partir de develop';

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Nombre de la feature (sin prefijo "feature/")');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');

        $output->writeln("<info>Iniciando feature: $name</info>");

        try {
            Feature::start($name);
            $output->writeln("<comment>Feature '$name' creada correctamente.</comment>");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln("<error>Error: {$e->getMessage()}</error>");
            return Command::FAILURE;
        }
    }
}
