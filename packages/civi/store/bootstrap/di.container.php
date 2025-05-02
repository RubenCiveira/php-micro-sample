<?php

use Civi\Micro\Config;
use Civi\Store\JsonDb\JsonDbConfig;
use DI\ContainerBuilder;

return function (ContainerBuilder $container) {
    $container->addDefinitions([
        JsonDbConfig::class => \DI\factory(function (Config $config) {
            return $config->load('app.store', JsonDbConfig::class);
        })]);
};
