<?php

// use Civi\Repomanager\Bootstrap\Security\SecurityConfig;
use Civi\Repomanager\Features\Repository\Package\Package;
use Civi\Micro\Config;
use Civi\Store\ClearArchitectureRegister;
use DI\Container;
use DI\ContainerBuilder;

return function (ContainerBuilder $container) {
    // APP
    ClearArchitectureRegister::mappers($container, 'repos::Package', Package::class);
};