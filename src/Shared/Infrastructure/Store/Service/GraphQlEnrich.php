<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\Store\Service;

use GraphQL\Type\Definition\Argument;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InputObjectField;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Utils\BuildSchema;
use InvalidArgumentException;

class GraphQlEnrich
{
    public function augmentAndSave(string $sdl): string
    {
        $schema = BuildSchema::build($sdl);

        $sections = [];
        $queryFields = [];
        $mutations = [];

        $mutationsExtractor = new ExtractMutation();

        foreach ($schema->getTypeMap() as $typeName => $type) {
            if (str_starts_with($typeName, '__') || !$type instanceof ObjectType)
                continue;
            $fields = $type->getFields();
            $typeMutations = $mutationsExtractor->fromType($type);
            $idField = 'id';
            foreach ($fields as $field) {
                if ('ID' == Type::getNamedType($field->getType())) {
                    $idField = $field->getName();
                    continue;
                }
            }
            $sections[] = $this->generateFilterInput($type, $schema);
            $sections[] = $this->generateCursorInput($type, $schema);
            $sections[] = $this->generateOrderEnum($type);
            $sections[] = $this->generateOrderInput($type);
            $queryFields[] = $this->generateQueryField($type);

            foreach ($typeMutations as $typeMutation) {
                $typeMutation['name'] = lcfirst($typeName) . ucfirst($typeMutation['name']);
                $typeMutation['type'] = $typeName;
                [$input, $mutation] = $this->generateMutation($idField, $typeMutation, $fields);
                $sections[] = $input;
                $mutations[] = $mutation;
            }
        }

        $sections[] = "enum OrderDirection {\n  ASC\n  DESC\n}";

        $query = ["type Query {"];
        $query = array_merge($query, $queryFields);
        $query[] = "}";

        $sections[] = implode("\n", $query);

        $mutation = ["type Mutation {\n"];
        $mutation = array_merge($mutation, $mutations);
        $mutation[] = "}";

        $sections[] = implode("\n", $mutation);
        return $sdl . "\n\n" . implode("\n\n", $sections);
    }
    private function generateFilterInput(ObjectType $type, Schema $schema): string
    {
        $name = $type->name;
        $fields = $type->getFields();

        $lines = ["input {$name}Filter {"];

        foreach ($fields as $field) {
            $fieldType = $this->dumpField($schema, "{$field->name}Equals", $field, false, $lines);
            if ($fieldType == 'String') {
                $this->dumpField($schema, "{$field->name}Like", $field, false, $lines);
            }
            $this->dumpField($schema, "{$field->name}In", $field, true, $lines);
            if ($fieldType != 'String' && $fieldType != 'ID') {
                $this->dumpField($schema, "{$field->name}GreaterThan", $field, false, $lines);
                $this->dumpField($schema, "{$field->name}LessThan", $field, false, $lines);
                $this->dumpField($schema, "{$field->name}Between", $field, true, $lines);
            }
        }

        $lines[] = "}";
        return implode("\n", $lines);
    }

    private function generateCursorInput(ObjectType $type, Schema $schema): string
    {
        $name = $type->name;
        $fields = $type->getFields();
        $lines = ["input {$name}Cursor {"];

        foreach ($fields as $field) {
            $this->dumpField($schema, "{$field->name}", $field, false, $lines);
        }
        $lines[] = "}";
        return implode("\n", $lines);
    }

    private function generateOrderEnum(ObjectType $type): string
    {
        $name = $type->name;
        $fields = $type->getFields();
        $enumName = $name . 'OrderField';

        $lines = ["enum {$enumName} {"];

        foreach ($fields as $field) {
            $lines[] = "  {$field->name}";
        }

        $lines[] = "}";
        return implode("\n", $lines);
    }

    private function generateOrderInput(ObjectType $type): string
    {
        $name = $type->name;
        $inputName = $name . 'Order';
        $fieldEnum = $name . 'OrderField';

        $lines = ["input {$inputName} {"];
        $lines[] = "  field: {$fieldEnum}!";
        $lines[] = "  direction: OrderDirection!";
        $lines[] = "}";

        return implode("\n", $lines);
    }

    private function generateMutation(string $id, array $mutation, array $fields): array
    {
        $mutationName = lcfirst($mutation['name']);
        $inputName = ucfirst($mutationName) . 'Input';
        $params = [];
        $inputFields = [];

        if ($mutation['context'] != "create") {
            $params[] = "{$id}: ID!";
        } else {
            $inputFields[] = "  {$id}: ID!";
        }

        foreach ($mutation['assign'] as $field => $info) {
            $know = false;
            foreach ($fields as $fd) {
                if ($fd->getName() == $field) {
                    $inputFields[] = "  {$field}: " . ($fd->getType() instanceof ObjectType ? "ID" : $fd->getType());
                    $know = true;
                } 
            }
            if( !$know )  {
                throw new InvalidArgumentException("Unkown field [$field]");
            }
        }

        if ($mutation['context'] == 'delete' ) {
            $returnType = 'Boolean';
        } else if (isset($mutation['type']) && $mutation['type']) {
            $returnType = $mutation['type'];
        } else {
            // El tipo base es la primera parte antes de la acción (ej: OficinaDesabilitar → Oficina)
            preg_match('/^([A-Z][a-zA-Z0-9]*)/', $mutation['name'], $matches);
            $returnType = $matches[1] ?? 'Object';
        }

        $input = "";
        $mutationLine = "";
        if ($inputFields || $params) {
            $args = '';
            if ($params) {
                $args .= ", " . implode(", ", $params);
            }
            if ($inputFields) {
                $input = "input {$inputName} {\n" . implode("\n", $inputFields) . "\n}";
                $args .= ", input: {$inputName}!";
            }
            $mutationLine = "  {$mutationName}(" . substr($args, 2) . "): {$returnType}!";
        }

        // Guardamos ambos bloques (input y mutación)
        return [$input, $mutationLine];
    }

    private function dumpField(Schema $schema, string $name, FieldDefinition|Argument|InputObjectField $field, bool $array, &$lines): string
    {
        $fieldType = $field->getType()->toString();
        if (substr($fieldType, -1) == '!') {
            $fieldType = substr($fieldType, 0, -1);
        }
        $theType = Type::getNamedType($field->getType());
        if ($theType != $fieldType) {
            // ¿Arrays?
            return 'Array';
        }
        if ($field->getType() instanceof ObjectType) {
            $fieldType = "ID";
        }
        $lines[] = $array ? "  {$name}: [{$fieldType}]" : "  {$name}: {$fieldType}";
        $directives = ExtractDirectives::fromNode( $field );
        foreach($directives as $dirName=>$dirAttributes) {
            $params = '';
            foreach($dirAttributes as $attName=>$attValue) {
                if( $attName == 'format' && ($attValue == 'date' || $attValue == 'date-time' ) ) {
                    $fieldType = 'Date';
                }
                $params .= "  {$attName}: \"{$attValue}\"";
            }
            $lines[] = "    @" . $dirName . ($params ? "(" . substr($params, 2) . ")" : "");
        }
        return $fieldType;
    }

    private function generateQueryField(ObjectType $type): string
    {
        $name = lcfirst($type->name);
        $typeName = $type->name;
        return "  {$name}s(filter: {$typeName}Filter, order: [{$typeName}Order!], since: {$typeName}Cursor, limit: Int): [{$typeName}!]!\n"
                . "  {$name}(id: ID!): {$typeName}\n
                ";
    }
}