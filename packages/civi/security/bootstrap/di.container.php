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
        // AccessRuleInterface::class => new ArrayDefinition([\DI\autowire(PolicyEngine::class)]),
    ]);
    // $container->set(AccessRuleInterface::class, new ArrayDefinition([]) );
    // $container->set(AccessRuleInterface::class, \DI\add(PolicyEngine::class));
    //    \DI\autowire(PolicyEngine::class)
};
