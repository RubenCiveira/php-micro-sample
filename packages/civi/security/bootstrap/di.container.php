<?php

use Civi\Security\Guard\AccessGuard;
use Civi\Security\Guard\AccessRuleInterface;
use Civi\Security\Policy\PolicyEngine;
use DI\ContainerBuilder;
use DI\Definition\ArrayDefinition;
use Psr\Log\LoggerInterface;

return function (ContainerBuilder $container) {
    $container->addDefinitions([
        AccessGuard::class => \DI\autowire()->method('setLogger', \DI\get(LoggerInterface::class)),
        AccessRuleInterface::class => new ArrayDefinition([\DI\get(PolicyEngine::class)]),
    ]);
};
