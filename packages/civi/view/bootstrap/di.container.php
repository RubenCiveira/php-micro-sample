<?php

use Civi\Micro\Config;
use Civi\View\ViewConfig;
use DI\Container;
use DI\ContainerBuilder;

return function (ContainerBuilder $container) {
    $container->addDefinitions([
        ViewConfig::class => \DI\factory(function (Config $config) {
            return $config->load('app.templates', ViewConfig::class);
        })
    ]);

};
