<?php declare(strict_types=1);

namespace Civi\Repomanager\Features\Repository\Package\Rule;

use Civi\Repomanager\Features\Repository\Package\Package;

class SendNotifyDelete
{
    public function __invoke(Package $package, $next): Package
    {
        $result = $next( $package );
        echo "<p>Despues de borrar ";
        return $result;
    }
}