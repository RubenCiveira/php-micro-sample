<?php

declare(strict_types=1);

namespace Civi\Ci\Artifact;

use Civi\Ci\Artifact\Composer\ComposerCommon;

class ArtifactCommon
{

    public static function getCurrentVersion(): string
    {
        return ComposerCommon::getCurrentVersion();
    }

    public static function setVersion(string $version): void
    {
        ComposerCommon::setVersion($version);
    }
}