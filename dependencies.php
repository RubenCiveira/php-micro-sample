<?php

use Civi\Repomanager\Bootstrap\Security\SecurityConfig;
use Civi\Repomanager\Features\Repository\Package\Package;
use Civi\Repomanager\Features\Repository\Package\Query\PackageFilter;
use Civi\Repomanager\Features\Repository\Package\Rule\OnlyPublicUrlAccess;
use Civi\Repomanager\Shared\Config;
use Civi\Repomanager\Shared\Infrastructure\Store\ClearArchitectureRegister;
use DI\Container;

return function (Container $container) {
    $container->set(SecurityConfig::class, \DI\factory(function() {
        return Config::load('app.security', SecurityConfig::class, 'security');
    }));
    ClearArchitectureRegister::mappers($container, 'repos::Package', Package::class);
    // $container->set('repos::PackageFilter', PackageFilter::class);
    // $container->set('repos::PackageAccess', [OnlyPublicUrlAccess::class]);
};