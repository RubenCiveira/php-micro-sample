<?php declare(strict_types=1);

namespace Civi\Repomanager\Features\Repository\Package\Rule;

use Civi\Repomanager\Features\Repository\Package\Query\PackageFilter;

class OnlyPublicUrlAccess
{

    public function __invoke(PackageFilter $filter, $next): PackageFilter
    {
        $filter->urlEquals = 'dos';
        return $next( $filter );
    }
}