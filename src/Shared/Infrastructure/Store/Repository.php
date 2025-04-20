<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\Store;

use Civi\Repomanager\Shared\Infrastructure\Store\Gateway\DataGateway;

class Repository
{
    public function __construct(private readonly Schemas $schemas, private readonly DataGateway $dataGateway)
    {
    }

    public function listView(array $args, array $include, string $kind)
    {

        $arguments = $this->expandGraphQLArguments($args);
        $query = "query { " . $this->className($kind) . "s ".($arguments ? "($arguments)" : ""). " { " 
                    . $this->expandGraphQLFields($include) 
                    . " } }";
        new GraphQLProcessor($this->schemas, $this->dataGateway, 'repos', $query, null);
    }

    public function retrieveView(string $id, array $include, string $kind)
    {
    }

    public function listEntities(array $args, string $kind)
    {
    }

    public function retrieveEntity(string $id, string $kind)
    {
    }

    public function create($instance, string $kind)
    {
    }

    public function modify(string $id, $instance, string $kind)
    {
    }

    public function delete(string $id, string $kind)
    {
    }

    private function className($kind)
    {
        return lcfirst( basename(str_replace('\\', '/', $kind)) );
    }

    private function expandGraphQLFields(array $fields): string
    {
        $tree = [];

        foreach ($fields as $field) {
            $parts = explode('.', $field);
            $current = &$tree;

            foreach ($parts as $part) {
                if (!isset($current[$part])) {
                    $current[$part] = [];
                }
                $current = &$current[$part];
            }
        }

        return $this->buildGraphQLSelection($tree);
    }

    private function buildGraphQLSelection(array $tree, int $indent = 0): string
    {
        $spaces = str_repeat('  ', $indent);
        $lines = [];

        foreach ($tree as $key => $children) {
            if (empty($children)) {
                $lines[] = $spaces . $key;
            } else {
                $lines[] = $spaces . $key . " {\n" . $this->buildGraphQLSelection($children, $indent + 1) . "\n" . $spaces . "}";
            }
        }

        return implode("\n", $lines);
    }

    private function expandGraphQLArguments(array $args): string
    {
        $parts = [];

        foreach ($args as $key => $value) {
            $formatted = $this->formatGraphQLValue($value);
            $parts[] = "$key: $formatted";
        }

        return implode(', ', $parts);
    }

    private function formatGraphQLValue(mixed $value): string
    {
        if (is_array($value)) {
            $fields = [];

            foreach ($value as $k => $v) {
                $fields[] = "$k: " . $this->formatGraphQLValue($v);
            }

            return "{ " . implode(', ', $fields) . " }";
        }

        if (is_string($value)) {
            return '"' . addslashes($value) . '"';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }

}