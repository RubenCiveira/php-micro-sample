<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\Store\Service;

use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Schema;
use GraphQL\Utils\BuildSchema;

class ExtractMutation
{
    public function fromSchema(string|Schema $schema): array
    {
        if (is_string($schema)) {
            $schema = BuildSchema::build($schema);
        }
        $resources = [];
        foreach ($schema->getTypeMap() as $type) {
            $mutations = $this->fromType($type);
            if ($mutations) {
                $resources[$type->name] = $mutations;
            }

        }
        return $resources;
    }

    public function fromType(NamedType $type): array
    {
        $mutations = [];
        $ast = $type->astNode();
        if ($ast && isset($ast->directives)) {
            foreach ($ast->directives as $directive) {
                if ($directive->name->value === 'mutation') {
                    $create = true;
                    $update = true;
                    $delete = true;
                    foreach ($directive->arguments as $arg) {
                        if ($arg->name->value === 'extra') {
                            foreach ($arg->value->values as $valueNode) {
                                if( $valueNode->value ) {
                                    $mutations[] = $this->parseMutationExtras($valueNode->value);
                                }
                            }
                        } else if ($arg->name->value === 'create') {
                            $create = $arg->value->value == 'true';
                        } else if ($arg->name->value === 'update') {
                            $update = $arg->value->value == 'true';
                        } else if ($arg->name->value === 'delete') {
                            $delete = $arg->value->value == 'true';
                        }
                    }
                    if( $create ) {
                        $mutations[] = ['name' => 'create',  'context' => 'create', 'assign' => [], 'set' => []];
                    }
                    if( $update ) {
                        $mutations[] = ['name' => 'update', 'context' => 'modify', 'assign' => [], 'set' => []];
                    }
                    if( $delete ) {
                        $mutations[] = ['name' => 'delete', 'context' => 'delete', 'assign' => [], 'set' => []];
                    }
                }
            }
        }
        return $mutations;
    }

    private function parseMutationExtras(string $line): array
    {
        $line = trim($line, " \t\n\r\"");

        [$name, $rest] = explode(':', $line, 2);
        $name = trim($name);
        $rest = trim($rest);

        $assign = [];
        $set = [];
        $context = false;

        // Extraer fragmentos clave = valor (puede haber arrays, objetos, strings, booleanos...)
        preg_match_all('/(\w+)\s*=\s*({[^}]+}|\[[^\]]+\]|[^,]+)/', $rest, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $key = trim($match[1]);
            $value = trim($match[2]);

            if ($key === 'assign') {
                $value = trim($value, '[] ');
                $assign = array_map('trim', explode(',', $value));
            } elseif ($key === 'set') {
                $value = trim($value, '{} ');
                $entries = explode(',', $value);
                foreach ($entries as $entry) {
                    [$k, $v] = explode(':', $entry, 2);
                    $k = trim($k);
                    $v = trim($v);
                    if ($v === 'true')
                        $v = true;
                    elseif ($v === 'false')
                        $v = false;
                    elseif (is_numeric($v))
                        $v = $v + 0;
                    else
                        $v = trim($v, '"\'');
                    $set[$k] = $v;
                }
            } elseif ($key === 'context') {
                $context = trim($value, '"\'');
            }
        }

        return [
            'name' => $name,
            'context' => $context,
            'assign' => $assign,
            'set' => $set,
        ];
    }
}