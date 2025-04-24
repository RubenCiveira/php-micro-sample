<?php

use Civi\Repomanager\Bootstrap\Security\SecurityConfig;
use Civi\Repomanager\Features\Repository\Package\Package;
use Civi\Repomanager\Shared\Config;
use Civi\Repomanager\Shared\Infrastructure\Store\ClearArchitectureRegister;
use DI\Container;

return function (Container $container) {
    // LIBRARY
    $container->set(SecurityConfig::class, \DI\factory(function () {
        return Config::load('app.security', SecurityConfig::class, 'security');
    }));

    // APP
    ClearArchitectureRegister::mappers($container, 'repos::Package', Package::class);
};