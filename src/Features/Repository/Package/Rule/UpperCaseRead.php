<?php declare(strict_types=1);

namespace Civi\Repomanager\Features\Repository\Package\Rule;

use Civi\Repomanager\Features\Repository\Package\Package;

class UpperCaseRead
{
    public function __invoke(Package $package, $next): Package
    {
        $package->name = ucfirst($package->name) . "[]";
        return $next( $package );
    }
}