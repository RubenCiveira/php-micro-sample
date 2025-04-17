<?php

use Civi\Repomanager\Bootstrap\Security\SecurityConfig;
use Civi\Repomanager\Shared\Config;
use DI\Container;

return function (Container $container) {
    $container->set(SecurityConfig::class, \DI\factory(function() {
        return Config::load('app.security', SecurityConfig::class, 'security');
    }));
};