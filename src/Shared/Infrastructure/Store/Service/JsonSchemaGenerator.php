<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\Store\Service;

use GraphQL\Type\Definition\Argument;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InputObjectField;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Utils\BuildSchema;
use InvalidArgumentException;

class JsonSchemaGenerator
{
    public function generateSchema(string|Type|Schema $schema, string|null $recurso = null): string
    {
        if (is_string($schema)) {
            $schema = BuildSchema::build($schema);
        }
        if( is_a($schema, Schema::class)) {
            if( is_null($recurso) ) {
                throw new InvalidArgumentException("If a schema is provided, a resource name is required");
            }
            $typeMap = $schema->getTypeMap();
            if( !isset($typeMap[ $recurso ] ) ) {
                throw new InvalidArgumentException("There is no resource {$recurso} on the schema");
            }
            $field = $typeMap[$recurso];
            $schema = Type::getNamedType($field);
        }
        $json = $this->generateSchemaFromObjectType( $schema );
        return json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    private function generateSchemaFromObjectType( InputObjectType|EnumType|ObjectType $type): array
    {
        $props = [];
        $required = [];
        foreach ($type->getFields() as $field) {
            if( $field->getType() instanceof NonNull ) {
                $required[] = $field->name;
            }
            $props[$field->name] = $this->graphqlTypeToJsonType( $field);
        }
        return [
            'type' => 'object',
            'properties' => $props,
            'required' => $required
        ];
    }

    private function graphqlTypeToJsonType(FieldDefinition|Argument|InputObjectField $field): array
    {
        $type = Type::getNamedType($field->getType());
        $ast = $field->astNode;
        $typeName = $type->name;

        if ($type instanceof ObjectType) {
            return [
                'type' => 'string',
                'x-reference' => $type->name
            ];
        } else if ($type instanceof EnumType) {
            return ['type' => 'string', 'enum' => array_map(fn($v) => $v->name, $type->getValues())];
        } else if ($typeName == 'int') {
            return ['type' => 'integer'];
        } else if ($typeName == 'float') {
            return ['type' => 'number'];
        } else if ($typeName == 'boolean') {
            return ['type' => 'string'];
        } else {
            $result = ['type' => 'string'];
            if ($ast && !empty($ast->directives)) {
                foreach ($ast->directives as $directive) {
                    if ($directive->name->value === 'format') {
                        foreach ($directive->arguments as $arg) {
                            if ($arg->name->value === 'type') {
                                $def = $arg->value->value;
                                if( $def == 'date-time' ) {
                                    $def = 'datetime-local';
                                }
                                $result['format'] = $def; // ej: "date"
                            }
                        }
                    }
                }
            }
            return $result;
        }
    }
}