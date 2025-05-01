<?php

declare(strict_types=1);

namespace Civi\Security\Policy;

use Civi\Micro\ProjectLocator;
use Civi\Security\Guard\AccessRequest;
use Civi\Security\Guard\AccessRequestInterfaceHandler;
use Civi\Security\Guard\AccessRuleInterface;
use Civi\Security\SecurityContextHolder;

class PolicyEngine implements AccessRuleInterface
{
    private static array $fromLibrary = [];

    public static function register(string $file)
    {
        self::$fromLibrary[] = $file;
    }

    private readonly CompiledPolicy $policy;

    public function __construct(private readonly SecurityContextHolder $securityHolder)
    {
        $root = ProjectLocator::getRootPath();
        $dir = ProjectLocator::getCompiledPath();
        $policy = false && $dir ? CompiledPolicy::loadFromCacheFile($dir . '/compiled_policy.php') : null;
        if( ! $policy ) {
            $policy = CompiledPolicy::fromYamlFiles($root . '/config/guards.yaml', self::$fromLibrary);
            $policy->dumpToCacheFile($dir . '/compiled_policy.php');
        }
        $this->policy = $policy;
    }

    public function canExecute(AccessRequest $request, AccessRequestInterfaceHandler $handler): bool
    {
        if( !$this->policy->isAllowed( $request->namespace, $request->typeName, $request->action, $this->securityHolder->get()) ) {
            return false;
        }
        return $handler->next($request);
    }
}