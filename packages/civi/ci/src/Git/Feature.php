<?php

declare(strict_types=1);

namespace Civi\Ci\Git;

class Feature extends GitCommon
{
    public static function start(string $name): void
    {
        $featureBranch = self::featureBranch($name);

        // Verificar que la rama no existe
        if (!self::ensureBranchDoesNotExist($featureBranch)) {
            exit(1); // Igual que en shell
        }

        // Preparar entorno
        self::prepareEnvForFeature();

        // Crear rama
        if (! self::checkGit(['-b', $featureBranch])) {
            echo "❌ Error creando la rama '$featureBranch'\n";
            exit(1);
        }
        echo "🚀 Se ha creado la rama: $featureBranch\n";
    }

    public static function end(string $name): void
    {
        if (self::isDevelopProtected()) {
            echo "❌ La rama develop está protegida, no se puede mergear directamente.\n";
        }

        $featureBranch = self::featureBranch($name);

        // Verificar que la rama existe
        if (!self::ensureBranchExists($featureBranch)) {
            exit(1);
        }

        // Preparar entorno
        self::prepareEnvForFeature();

        // Hacer merge
        $developBranch = self::getDevelopBranch();

        self::runGit(['checkout', $developBranch]);
        self::runGit(['merge', '--no-ff', $featureBranch]);
        self::runGit(['branch', '-d', $featureBranch]);
        self::runGit(['push', 'origin', $developBranch]);
        self::runGit(['push', 'origin', '--delete', $featureBranch]);

        echo "✅ Feature '$featureBranch' finalizado y fusionado a '$developBranch'.\n";
    }

    private static function featureBranch(string $name): string
    {
        if (empty($name)) {
            echo "❌ No se indica el nombre de la feature.\n";
            exit(1);
        }

        $prefix = '';

        if (self::featureUseVersionPrefix()) {
            $prefix = self::getCurrentVersion() . '-';
        }

        return "feature/{$prefix}{$name}";
    }
}
