<?php

declare(strict_types=1);

namespace Civi\Security\Policy;

use Civi\Security\Authentication;
use Civi\Security\Connection;
use Civi\Security\SecurityContext;
use Symfony\Component\Yaml\Yaml;

final class CompiledPolicy
{
    /**
     * @var array<string, PolicyRule>
     *   Indexed as "namespace|resource|action"
     */
    private array $rules = [];

    public static function fromYamlFiles($base, array $paths): self
    {
        $compiled = new self();
        if (file_exists($base)) {
            $data = Yaml::parseFile($base);
            $compiled->mergeYaml($data, true);
        }
        foreach ($paths as $path) {
            if (file_exists($path)) {
                $data = Yaml::parseFile($path);
                $compiled->mergeYaml($data, false);
            }
        }
        return $compiled;
    }

    public static function loadFromCacheFile(string $path): ?CompiledPolicy
    {
        if (!file_exists($path)) {
            return null;
        }
        return include $path;
    }

    public static function __set_state(array $data): CompiledPolicy
    {
        $c = new CompiledPolicy();
        $c->rules = $data['rules'];
        return $c;
    }

    public function dumpToCacheFile(string $path): void
    {
        $content = '<?php return ' . var_export($this, true) . ';';
        file_put_contents($path, $content);
    }

    private function mergeYaml(array $data, $first): void
    {
        $default = $data['default'] ?? true;
        if (!isset($this->rules['*']['*']['*'])) {
            $this->rules[ $this->makeKey("*", "*", "*") ] = ['default' => $default, 'rules' => []];
        }
        $namespaces = $data['rules'] ?? [];
        $this->loadRules("*", "*", "*", $namespaces, $first, true);
    }

    private function loadRules($namespace, $resource, $action, $rulesData, $first, $allowOverride): void
    {
        $key = $this->makeKey($namespace, $resource, $action);
        $in = $action;
        if( $in === "*" ) {
            $in = $resource;
        }
        if( $in === "*" ) {
            $in = $namespace;
        }
        $ignore = $in == 'default' || $in == 'allowed' || $in == 'disallowed' || $in == 'override';
        if (!$ignore && $allowOverride) {
            $rules = [];
            if (isset($rulesData['default'])) {
                $rules['default'] = (bool)$rulesData['default'];
            }
            if( isset($this->rules[ $key ]['override']) ) {
                $allowOverride = !$this->rules[ $key ]['override'];
            }
            if( isset($this->rules[ $key ]['rules']) ) {
                $rules['rules'] = $this->rules[ $key ]['rules'];
            }
            if ($first && isset($rulesData['override'])) {
                $rules['override'] = $rulesData['override'];
            }
            if (is_array($rulesData['allowed'] ?? false)) {
                foreach ($rulesData['allowed'] as $rule) {
                    $rule['allow'] = true;
                    $rules['rules'][] = PolicyRule::fromArray($rule);
                }
            }
            if (is_array($rulesData['disallowed'] ?? false)) {
                foreach ($rulesData['disallowed'] as $rule) {
                    $rule['allow'] = false;
                    $rules['rules'][] = PolicyRule::fromArray($rule);
                }
            }
            if (isset($rules['default']) || isset($rules['override']) || isset($rules['rules'])) {
                $this->rules[ $key ] = $rules;
            }
            if( is_array($rulesData) && $action === "*" ) {
                foreach ($rulesData as $childName => $childs) {
                    $cnamespace = $namespace === "*" ? $childName : $namespace;
                    $cresource = $namespace !== "*" && $resource === "*" ? $childName : $resource;
                    $caction = $namespace !== "*" && $resource !== "*" ? $childName : $action;
                    $this->loadRules($cnamespace, $cresource, $caction, $childs, $first, $allowOverride);
                }
            }
        }
    }

    /**
     * Registers a rule in the compiled index.
     *
     * @param string $namespace
     * @param string $resource
     * @param string $action
     * @param PolicyRule $rule
     * @return void
     */
    private function addRule(string $namespace, string $resource, string $action, PolicyRule $rule): void
    {
        $key = $this->makeKey($namespace, $resource, $action);

        if ($rule->override || !isset($this->rules[$key])) {
            $this->rules[$key] = $rule;
        } else {
            // Merge conditions (restrictive approach)
            $this->rules[$key] = $this->rules[$key]->mergeWith($rule);
        }
    }

    /**
     * Checks if access is allowed based on flattened rules.
     *
     * @param string $namespace
     * @param string $resource
     * @param string $action
     * @param Authentication $auth
     * @param Connection $conn
     * @return bool
     */
    public function isAllowed(string $namespace, string $resource, string $action, SecurityContext $context): bool
    {
        $candidates = [
            $this->makeKey($namespace, $resource, $action),
            $this->makeKey($namespace, $resource, '*'),
            $this->makeKey($namespace, '*', '*'),
            $this->makeKey('*', '*', '*'),
        ];
        foreach ($candidates as $key) {
            if (!isset($this->rules[$key])) {
                continue;
            }
            $rules = $this->rules[$key];
            if (isset($rules['rules'])) {
                foreach ($rules['rules'] as $rule) {
                    if ($rule->matches($context->authentication, $context->connection)) {
                        return $rule->allow;
                    }
                }
            }
            if (isset($rules['default'])) {
                return (bool)$rules['default'];
            }
        }
        return true;
    }

    /**
     * @param string $namespace
     * @param string $resource
     * @param string $action
     * @return string
     */
    private function makeKey(string $namespace, string $resource, string $action): string
    {
        return "$namespace|$resource|$action";
    }
}
