<?php

declare(strict_types=1);

namespace Civi\Ci\Git;

use Civi\Ci\Artifact\ArtifactCommon;
use Civi\Ci\Artifact\ArtifactCommons;

class Release extends GitCommon
{
    public static function propose(): void
    {
        // Preparar entorno
        self::prepareEnvForFeature();

        // Detectar tipo de incremento
        $incrementType = self::getIncrementType();
        echo "🔍 Incremento detectado: $incrementType\n";

        // Obtener versión base
        $baseVersion = ArtifactCommon::getCurrentVersion();
        echo "📦 Versión base: $baseVersion\n";

        // Calcular nueva versión
        $newVersion = self::bumpVersion($baseVersion, $incrementType);
        echo "🎯 Nueva versión candidata: $newVersion\n";

        // Calcular siguiente número RC
        $rcNumber = self::nextRcNumber($newVersion);

        // Crear nombre de rama release
        $releaseBranch = "release/{$newVersion}-rc.{$rcNumber}";

        // Checkout y crear nueva rama
        $developBranch = self::getDevelopBranch();
        self::runGit(['checkout', $developBranch]);
        self::runGit(['pull', 'origin', $developBranch]);
        self::runGit(['checkout', '-b', $releaseBranch]);

        echo "🚀 Rama creada: $releaseBranch\n";

        // Establecer nueva versión en composer.json
        ArtifactCommon::setVersion("{$newVersion}-rc.{$rcNumber}");

        // Aquí sería ideal generar changelog si tienes script de generación automático
        // (por ahora solo indicamos el paso)
        echo "ℹ️ Recuerda generar el changelog manualmente o integrarlo más adelante.\n";

        // Hacer commit de cambios
        self::runGit(['add', '.']);
        self::runGit(['commit', '-m', "chore(release): {$newVersion}-rc.{$rcNumber}"]);

        echo "ℹ️  Revisa los cambios y ejecuta:\n";
        echo "    git push --set-upstream origin $releaseBranch\n";
    }
}
