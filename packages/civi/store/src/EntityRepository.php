<?php declare(strict_types=1);

namespace Civi\Store;

use Civi\Micro\Telemetry\LoggerAwareInterface;
use Civi\Micro\Telemetry\LoggerAwareTrait;
use Civi\Store\Service\DataService;
use Civi\Store\Service\ExtractMutation;
use GraphQL\Error\DebugFlag;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use InvalidArgumentException;

class EntityRepository implements LoggerAwareInterface
{
    private const FLAG = DebugFlag::RETHROW_INTERNAL_EXCEPTIONS;

    use LoggerAwareTrait;

    public function __construct(
        private readonly string $namespace,
        private readonly string $type,
        private readonly Schemas $schemas,
        private readonly DataService $dataService,
        private readonly Validator $validator
    ) {
    }

    public function listView(array $args, array $include): array
    {
        $arguments = $this->expandGraphQLArguments($args);
        $name = $this->className($this->type);
        $schema = $this->schemas->schema($this->namespace);
        $type = $schema->getType($name);
        if( $type instanceof ObjectType ) {
            $fields = $type->getFields();
            foreach ($fields as $field) {
                $baseType = Type::getNamedType($field->getType());
                if (is_a($baseType, ObjectType::class)) {
                    $include[] = "{$field->name}." . $this->searchIdField($baseType);
                } else {
                    $include[] = $field->name;
                }
            }
            $query = "query { " . lcfirst($this->className($this->type)) . "s " . ($arguments ? "($arguments)" : "") . " { "
                . $this->expandGraphQLFields($include)
                . " } }";
            $query = new GraphQLProcessor($this->schemas, $this->dataService, $this->validator, $this->namespace, $query, null);
            $result = $query->result()->toArray(self::FLAG);
            if (!isset($result['data']) && isset($result['errors'])) {
                throw $this->errorException($result['errors']);
            } else if (!isset($result['data'])) {
                print_r($result);
                throw new InvalidArgumentException('Unable to retrieve');
            }
            return $result['data'][lcfirst($this->className($this->type)) . 's'];
        } else {
            throw new InvalidArgumentException("The type {$name} dont exists as ObjectType");
        }
    }

    public function create(string $for, $instance): array
    {
        if( is_array($instance) ) {
            $instance = (object) $instance;
        } else if (!is_a($instance, $this->type)) {
            throw new InvalidArgumentException("Para crear con el timpo " . $this->type . " el argumento no puede ser " . get_class($instance) );
        }
        $name = $this->className($this->type);
        $schema = $this->schemas->schema($this->namespace);
        $type = $schema->getType($name);
        if( $type instanceof ObjectType ) {
            $fields = $type->getFields();
            $insert = "";
            $retrieve = "";
            foreach ($fields as $field) {
                $baseType = Type::getNamedType($field->getType());
                if ($baseType->name == 'String' || $baseType->name == 'ID') {
                    $insert .= ", {$field->name}: \"" . $instance->{$field->name} . "\"";
                } else if (is_a($baseType, ObjectType::class)) {
                    $insert .= ", {$field->name}: \"" . $instance->{$field->name} . "\"";
                } else {
                    $insert .= ", {$field->name}: " . $instance->{$field->name} . "";
                }
                if (is_a($baseType, ObjectType::class)) {
                    $retrieve .= ", {$field->name} { " . $this->searchIdField($baseType) . " }";
                } else {
                    $retrieve .= ", {$field->name}";
                }
            }
            $query = "mutation { " . lcfirst($name) . ucfirst($for). "(input: {" . substr($insert, 2) . "}) { " . substr($retrieve, 2) . " } }";
            $query = new GraphQLProcessor($this->schemas, $this->dataService, $this->validator, $this->namespace, $query, null);
            $result = $query->result();
            $result = $result->toArray(self::FLAG);
            if (!isset($result['data']) && isset($result['errors'])) {
                throw $this->errorException($result['errors']);
            } else if (!isset($result['data'])) {
                throw new InvalidArgumentException('Unable to store');
            } else {
                return $result['data'];
            }
        } else {
            throw new InvalidArgumentException("The type {$name} dont exists as ObjectType");
        }
    }

    public function modify(string $for, string $id, $instance)
    {
        if( is_array($instance) ) {
            $instance = (object) $instance;
        } else if (!is_a($instance, $this->type)) {
            throw new InvalidArgumentException();
        }
        $name = $this->className($this->type);
        $schema = $this->schemas->schema($this->namespace);
        $type = $schema->getType($name);
        $ext = new ExtractMutation();
        $mutations = $ext->fromType( $type );
        $insert = "";
        $retrieve = "";
        foreach ($mutations[$for]['assign'] as $field) {
            // $field = $type->getField( $fieldName );
            $baseType = Type::getNamedType($field->getType());
            if ($baseType->name == 'String') {
                $insert .= ", {$field->name}: \"" . $instance->{$field->name} . "\"";
            } else if (is_a($baseType, ObjectType::class)) {
                $insert .= ", {$field->name}: \"" . $instance->{$field->name} . "\"";
            } else {
                $insert .= ", {$field->name}: " . $instance->{$field->name} . "";
            }
            if (is_a($baseType, ObjectType::class)) {
                $retrieve .= ", {$field->name} { " . $this->searchIdField($baseType) . " }";
            } else {
                $retrieve .= ", {$field->name}";
            }
        }
        $input = $insert ? ", input: {".substr($insert, 2)."}": "";
        $query = "mutation { " . lcfirst($name) . ucfirst($for). "(id: \"" . $id . "\"{$input}) { " . substr($retrieve, 2) . " } }";
        $query = new GraphQLProcessor($this->schemas, $this->dataService, $this->validator, $this->namespace, $query, null);
        $result = $query->result();
        $result = $result->toArray(self::FLAG);
        if (!isset($result['data']) && isset($result['errors'])) {
            throw $this->errorException($result['errors']);
        } else if (!isset($result['data'])) {
            throw new InvalidArgumentException('Unable to store');
        } else {
            return $result['data'];
        }
    }

    public function change(string $for, string $id)
    {
        $name = $this->className($this->type);
        $query = "mutation { " . lcfirst($name) . ucfirst($for) . "(id: \"" . $id . "\") }";
        $query = new GraphQLProcessor($this->schemas, $this->dataService, $this->validator, $this->namespace, $query, null);
        $result = $query->result();
        $result = $result->toArray();
        if (!isset($result['data']) && isset($result['errors'])) {
            throw $this->errorException($result['errors']);
        } else if (!isset($result['data'])) {
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
        foreach ($type->getFields() as $field) {
            $baseType = Type::getNamedType($field->getType());
            if ($baseType->name == 'ID') {
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
        foreach ($graphQlErrors as $graphQlError) {
            if (isset($graphQlError['constraints'])) {
                $errors[] = $graphQlError['constraints'];
            } else if (isset($graphQlError['error'])) {
                $errors[] = $graphQlError['error'];
            } else {
                echo "<pre>";
                print_r($graphQlError);
                die();
            }
        }
        return new ConstraintException($errors);
    }
}