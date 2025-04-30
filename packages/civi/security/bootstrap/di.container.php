<?php

use Civi\Security\Guard\AccessGuard;
use DI\Container;
use Psr\Log\LoggerInterface;

return function (Container $container) {
    $container->set(AccessGuard::class, \DI\autowire()
        ->method('setLogger', \DI\get(LoggerInterface::class) ));
};
