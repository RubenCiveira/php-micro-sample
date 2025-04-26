<?php

declare(strict_types=1);

namespace Civi\Ci\Git;

class Hotfix extends GitCommon
{
    public static function start(string $name): void
    {
        $hotfixBranch = self::hotfixBranch($name);

        // Asegurar que la rama no existe
        self::ensureBranchDoesNotExist($hotfixBranch);

        // Preparar entorno
        self::prepareEnvForFeature();

        // Crear rama desde MAIN
        $mainBranch = self::getMainBranch();
        self::runGit(['checkout', $mainBranch]);
        self::runGit(['pull', 'origin', $mainBranch]);
        self::runGit(['checkout', '-b', $hotfixBranch]);

        echo "🚑 Se ha creado la rama: $hotfixBranch\n";
    }

    public static function end(string $name): void
    {
        if (self::isDevelopProtected()) {
            echo "❌ La rama develop está protegida, no se puede mergear directamente.\n";
        }

        $hotfixBranch = self::hotfixBranch($name);

        // Asegurar que la rama existe
        self::ensureBranchExists($hotfixBranch);

        // Preparar entorno
        self::prepareEnvForFeature();

        $mainBranch = self::getMainBranch();
        $developBranch = self::getDevelopBranch();
        $tag = "v$name";

        // Merge a MAIN + Tag
        self::runGit(['checkout', $mainBranch]);
        self::runGit(['pull', 'origin', $mainBranch]);
        self::runGit(['merge', '--no-ff', $hotfixBranch]);
        self::runGit(['tag', $tag]);
        self::runGit(['push', 'origin', $mainBranch]);
        self::runGit(['push', 'origin', $tag]);

        // Merge a DEVELOP (si se puede)
        if (!self::isDevelopProtected()) {
            self::runGit(['checkout', $developBranch]);
            self::runGit(['pull', 'origin', $developBranch]);
            self::runGit(['merge', '--no-ff', $hotfixBranch]);
            self::runGit(['push', 'origin', $developBranch]);
        }

        // Borrar rama hotfix
        self::runGit(['branch', '-d', $hotfixBranch]);
        self::runGit(['push', 'origin', '--delete', $hotfixBranch]);

        echo "🏁 Hotfix '$hotfixBranch' finalizado.\n";
    }

    protected static function hotfixBranch(string $name): string
    {
        if (empty($name)) {
            echo "❌ No se indica el nombre de la hotfix.\n";
            exit(1);
        }

        $prefix = '';

        if (self::featureUseVersionPrefix()) {
            // Aquí nos aseguramos de coger versión desde main
            $currentBranch = trim(self::runGit(['branch', '--show-current']));
            $mainBranch = self::getMainBranch();
            if ($currentBranch !== $mainBranch) {
                self::runGit(['checkout', $mainBranch]);
            }
            $prefix = self::getCurrentVersion() . '-';
        }

        return "hotfix/{$prefix}{$name}";
    }
}
