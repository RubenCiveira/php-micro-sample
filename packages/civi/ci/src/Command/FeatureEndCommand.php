<?php declare(strict_types=1);

namespace Civi\Ci\Command;

use Civi\Ci\Git\Feature;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FeatureEndCommand extends Command
{
    protected static $defaultName = 'feature:end';
    protected static $defaultDescription = 'Finaliza una feature fusionándola a develop y eliminando la rama';

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Nombre de la feature a finalizar');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');

        $output->writeln("<info>Finalizando feature: $name</info>");

        try {
            Feature::end($name);
            $output->writeln("<comment>Feature '$name' fusionada y rama eliminada.</comment>");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln("<error>Error: {$e->getMessage()}</error>");
            return Command::FAILURE;
        }
    }
}
