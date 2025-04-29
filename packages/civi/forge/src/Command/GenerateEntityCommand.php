<?php

namespace Civi\Forge\Command;

use Civi\Forge\Builder\EntityBuilder;
use Civi\Forge\Builder\RepositoryBuilder;
use Civi\Forge\Builder\SchemaBuilder;
use Civi\Micro\ProjectLocator;
use Civi\Store\Gateway\SchemaGateway;
use Civi\Store\Service\GraphQlEnrich;
use DI\Container;
use GraphQL\Utils\BuildSchema;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateEntityCommand extends Command
{
    protected static $defaultName = 'entity';

    protected function configure(): void
    {
        $this
            ->setDescription('Genera la entidad, filtro y repositorio desde GraphQL DSL.')
            ->addArgument('package', InputArgument::REQUIRED, 'Namespace PHP donde generar')
            ->addArgument('namespace', InputArgument::REQUIRED, 'Namespace config')
            ->addArgument('entity', InputArgument::REQUIRED, 'Nombre de la entidad');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $package = $input->getArgument('package');
        $namespace = $input->getArgument('namespace');
        $entityName = $input->getArgument('entity');

        $output->writeln("🚀 Generando entidad '<info>$entityName</info>'...");

        $packageFull = "{$package}.{$entityName}";
        $container = new Container();
        // $di = require __DIR__ . '/../../../dependencies.php';
        // $di($container);

        $schemaGateway = $container->get(SchemaGateway::class);
        $sdl = $schemaGateway->sdl($namespace);

        $enrich = $container->get(GraphQlEnrich::class);
        $esdl = $enrich->augmentAndSave($sdl);

        $schema = BuildSchema::build($esdl);
        $type = $schema->getType($entityName);
        $filterType = $schema->getType("{$entityName}Filter");

        $directory = $this->resolveNamespacePath($packageFull);

        $output->writeln(" - Escribiendo en <info>{$directory}</info>");

        EntityBuilder::generateEntityFromType($type, true, $packageFull, "{$directory}/{$type->name}.php");
        EntityBuilder::generateEntityFromType($filterType, false, "{$packageFull}.Query", "{$directory}/Query/{$type->name}Filter.php");
        RepositoryBuilder::generateEntityRepositoryFromType($type, $namespace, "{$packageFull}.{$type->name}", "{$directory}/Gateway/{$type->name}Gateway.php");
        SchemaBuilder::generateSchemaFromType($type, $namespace, "{$packageFull}.{$type->name}", "{$directory}/View/{$type->name}TypeSchemaBuilder.php");

        $output->writeln("✅ Entidad '<info>$entityName</info>' generada correctamente.");

        return Command::SUCCESS;
    }

    private function resolveNamespacePath(string $package): ?string
    {
        $root = ProjectLocator::getRootPath();
        $namespace = str_replace('.', '\\', $package);
        $psr4 = require $root . '/vendor/composer/autoload_psr4.php';

        foreach ($psr4 as $prefix => $paths) {
            if (str_starts_with($namespace, $prefix)) {
                $relative = str_replace('\\', '/', substr($namespace, strlen($prefix)));
                return rtrim($paths[0], '/') . '/' . $relative;
            }
        }

        throw new \RuntimeException("❌ Error: No se encuentra el paquete $namespace.");
    }
}
