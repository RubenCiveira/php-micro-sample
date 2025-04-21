<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\Store;

use Civi\Repomanager\Shared\Infrastructure\Store\Gateway\DataGateway;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use InvalidArgumentException;

class EntityRepository
{

    public function __construct(
        private readonly string $namespace, private readonly string $type,
        private readonly Schemas $schemas, private readonly DataGateway $dataGateway,
        private readonly Validator $validator)
    {
    }

    public function listView(array $args, array $include): array
    {
        $arguments = $this->expandGraphQLArguments($args);
        $name = $this->className( $this->type );
        $schema = $this->schemas->schema($this->namespace);
        $type = $schema->getType( $name );
        $fields = $type->getFields();
        foreach($fields as $field) {
            $baseType = Type::getNamedType($field->getType());
            if( is_a($baseType, ObjectType::class)) {
                $include[] = "{$field->name}." . $this->searchIdField( $baseType ); 
            } else {
                $include[] = $field->name;
            }
        }
        $query = "query { " . lcfirst($this->className($this->type)) . "s ".($arguments ? "($arguments)" : ""). " { " 
                    . $this->expandGraphQLFields($include) 
                    . " } }";
        $query = new GraphQLProcessor($this->schemas, $this->dataGateway, $this->validator, $this->namespace, $query, null);
        $result = $query->result()->toArray();
        if( !isset($result['data']) && isset($result['errors']) ) {
            throw $this->errorException($result['errors']);
        } else if ( !isset($result['data']) ) {
            print_r( $result );
            throw new InvalidArgumentException('Unable to retrieve');
        }
        return $result['data'][lcfirst($this->className($this->type)).'s'];
    }

    public function retrieveView(string $id, array $include)
    {
    }

    public function listEntities(array $args)
    {
    }

    public function retrieveEntity(string $id)
    {
    }

    public function create($instance): array
    {
        if( !is_a($instance, $this->type)) {
            throw new InvalidArgumentException();
        }
        $name = $this->className( $this->type );
        $schema = $this->schemas->schema($this->namespace);
        $type = $schema->getType( $name );
        $fields = $type->getFields();
        $insert = "";
        $retrieve = "";
        foreach($fields as $field) {
            $baseType = Type::getNamedType($field->getType());
            if( $baseType->name == 'String' || $baseType->name == 'ID' ) {
                $insert .= ", {$field->name }: \"" . $instance->{$field->name} . "\"";
            } else if( is_a($baseType, ObjectType::class)) {
                $insert .= ", {$field->name }: \"" . $instance->{$field->name} . "\"";
            } else {
                $insert .= ", {$field->name }: " . $instance->{$field->name} . "";
            }
            if( is_a($baseType, ObjectType::class)) {
                $retrieve .= ", {$field->name} { " . $this->searchIdField( $baseType ) ." }"; 
            } else {
                $retrieve .= ", {$field->name}";
            }
        }
        $query = "mutation { ".lcfirst($name)."Create(input: {".substr($insert, 2)."}) { ".substr($retrieve,2)." } }";
        $query = new GraphQLProcessor($this->schemas, $this->dataGateway, $this->validator, $this->namespace, $query, null);
        $result = $query->result();
        $result = $result->toArray();
        if( !isset($result['data']) && isset($result['errors']) ) {
            throw $this->errorException($result['errors']);
        } else if ( !isset($result['data']) ) {
            throw new InvalidArgumentException('Unable to store');
        } else {
            return $result['data'];
        }
    }

    public function modify(string $id, $instance)
    {
        
        if( !is_a($instance, $this->type)) {
            throw new InvalidArgumentException();
        }
        $name = $this->className( $this->type );
        $schema = $this->schemas->schema($this->namespace);
        $type = $schema->getType( $name );
        $fields = $type->getFields();
        $insert = "";
        $retrieve = "";
        foreach($fields as $field) {
            $baseType = Type::getNamedType($field->getType());
            if( $baseType->name == 'ID' ) {
            } else if( $baseType->name == 'String' ) {
                $insert .= ", {$field->name }: \"" . $instance->{$field->name} . "\"";
            } else if( is_a($baseType, ObjectType::class)) {
                $insert .= ", {$field->name }: \"" . $instance->{$field->name} . "\"";
            } else {
                $insert .= ", {$field->name }: " . $instance->{$field->name} . "";
            }
            if( is_a($baseType, ObjectType::class)) {
                $retrieve .= ", {$field->name} { " . $this->searchIdField( $baseType ) ." }"; 
            } else {
                $retrieve .= ", {$field->name}";
            }
        }
        $query = "mutation { ".lcfirst($name)."Update(id: \"".$id."\", input: {".substr($insert, 2)."}) { ".substr($retrieve,2)." } }";
        $query = new GraphQLProcessor($this->schemas, $this->dataGateway, $this->validator, $this->namespace, $query, null);
        $result = $query->result();
        $result = $result->toArray();
        if( !isset($result['data']) && isset($result['errors']) ) {
            throw $this->errorException($result['errors']);
        } else if ( !isset($result['data']) ) {
            throw new InvalidArgumentException('Unable to store');
        } else {
            return $result['data'];
        }
    }

    public function delete(string $id)
    {
        $name = $this->className( $this->type );
        $query = "mutation { ".lcfirst($name)."Delete(id: \"".$id."\") }";
        $query = new GraphQLProcessor($this->schemas, $this->dataGateway, $this->validator, $this->namespace, $query, null);
        $result = $query->result();
        $result = $result->toArray();
        if( !isset($result['data']) && isset($result['errors']) ) {
            throw $this->errorException($result['errors']);
        } else if ( !isset($result['data']) ) {
            throw new InvalidArgumentException('Unable to store');
        } else {
            return $result['data'];
        }
    }

    private function className($kind)
    {
        return basename(str_replace('\\', '/', $kind));
    }

    private function searchIdField(ObjectType $type) 
    {
        $id = 'id';
        foreach($type->getFields() as $field) {
            $baseType = Type::getNamedType($field->getType());
            if( $baseType->name == 'ID' ) {
                $id = $field->name;
            }
        }
        return $id;
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

    private function errorException(array $graphQlErrors) 
    { 
        $errors = [];
        foreach($graphQlErrors as $graphQlError) {
            if( isset($graphQlError['constraints'])) {
                $errors[] = $graphQlError['constraints'];
            } else {
                $errors[] = $graphQlError['error'];
            }
        }
        return new ConstraintException( $errors);
    }
}