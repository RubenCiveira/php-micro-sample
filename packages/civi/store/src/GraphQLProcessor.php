<?php

declare(strict_types=1);

namespace Civi\Store;

use ArrayAccess;
use Civi\Store\Service\DataService;
use Civi\Store\DataQueryParam;
use Civi\Store\Service\ExtractMutation;
use DI\Definition\ObjectDefinition;
use GraphQL\Deferred;
use GraphQL\Error\Error;
use GraphQL\Error\FormattedError;
use GraphQL\Executor\ExecutionResult;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use TypeError;

class GraphQLProcessor
{
    private $pending = [];
    private $batches = [];

    public function __construct(
        private readonly Schemas $schemas,
        private readonly DataService $datas,
        private readonly Validator $validator,
        private readonly string $namespace,
        private readonly string $query,
        private readonly array|null $variables
    ) {}

    public function result(): ExecutionResult
    {
        $namepace = $this->namespace;
        $schema = $this->schemas->schema($namepace);
        $rootQuery = $schema->getQueryType();

        $schemaMeta = new StoreSchema("id", ["name"]);

        $resolvers = [];
        $names = $rootQuery->getFieldNames();
        foreach ($names as $name) {
            $resolvers[$name] = function ($root, array $args, $context, ResolveInfo $resolveInfo) use ($namepace, $schema, $name, $rootQuery, $schemaMeta) {
                if (isset($args['id'])) {
                    $args['filter'] = ['idEquals' => $args['id']];
                    unset($args['id']);
                }
                $type = $rootQuery->getField($name)->getType();
                $theType = Type::getNamedType($type);
                $filter = new DataQueryParam($schema, $theType->toString(), $args);
                $response = $this->datas->fetch(
                    $namepace,
                    $theType->toString(),
                    $schemaMeta,
                    $filter
                );
                if ($type instanceof NonNull) {
                    $type = $type->getWrappedType();
                }
                return ($type instanceof ListOfType) ? $response : $response[0];
            };
        }
        $extractor = new ExtractMutation();
        $mutations = $extractor->fromSchema($schema);
        foreach ($mutations as $theType => $hisMutations) {
            foreach ($hisMutations as $mutation) {
                $resolvers[lcfirst($theType) . ucfirst($mutation['name'])] = function ($root, array $args, $context, ResolveInfo $resolveInfo) use ($namepace, $schema, $mutation, $theType, $schemaMeta) {
                    $data = [];
                    // Suposition, first argument ID, segund argument is a data array
                    foreach ($args as $ks => $vs) {
                        if (is_array($vs)) {
                            foreach ($vs as $k => $v) {
                                $data[$k] = $v;
                            }
                        } else {
                            $data[$ks] = $vs;
                        }
                    }
                    foreach ($mutation['set'] as $k => $v) {
                        $data[$k] = $v;
                    }
                    switch ($mutation['context']) {
                        case 'delete':
                            $id = array_key_first($args);
                            $condition = ["filter" => ["{$id}Equals" => $args[$id]]];
                            $this->datas->delete(
                                $namepace,
                                $theType,
                                $schemaMeta,
                                $mutation['name'],
                                new DataQueryParam($schema, $theType, $condition),
                            );
                            return true;
                        case 'modify':
                            $this->validateReferences($namepace, $schema, $schema->getType($theType), $data);
                            $errors = $this->validator->getErrors($namepace, $theType, $data);
                            if ($errors) {
                                throw new ConstraintException($errors);
                            }
                            $id = array_key_first($args);
                            $condition = ["filter" => ["{$id}Equals" => $args[$id]]];
                            $filter = new DataQueryParam($schema, $theType, $condition);
                            return $this->datas->modify(
                                $namepace,
                                $theType,
                                $schemaMeta,
                                $mutation['name'],
                                $filter,
                                $data
                            )[0];
                        case 'create':
                            $this->validateReferences($namepace, $schema, $schema->getType($theType), $data);
                            $errors = $this->validator->getErrors($namepace, $theType, $data);
                            if ($errors) {
                                throw new ConstraintException($errors);
                            }
                            return $this->datas->create(
                                $namepace,
                                $theType,
                                $schemaMeta,
                                $mutation['name'],
                                $data
                            )[0];
                    }
                };
            }
        }

        $customFieldResolver = function ($objectValue, array $args, $context, ResolveInfo $info) use($schemaMeta) {
            $fieldName = trim($info->fieldName);
            $property = null;
            if (is_array($objectValue) || $objectValue instanceof ArrayAccess) {
                if (isset($objectValue[$fieldName])) {
                    $property = $objectValue[$fieldName];
                }
            } else if (is_object($objectValue)) {
                if (isset($objectValue->{$fieldName})) {
                    $property = $objectValue->{$fieldName};
                }
            }
            if (!$property) {
                if (is_array($objectValue)) {
                    return null;
                } else {
                    $baseType = Type::getNamedType($info->fieldDefinition->getType());
                    return $baseType instanceof ObjectDefinition ? null : $this->expand($objectValue, $schemaMeta, $info);
                }
            }
            return $property instanceof \Closure
                ? $property($objectValue, $args, $context, $info)
                : $property;
        };

        $result = GraphQL::executeQuery(
            $schema,
            $this->query,
            $resolvers,
            null,
            $this->variables,
            null,
            $customFieldResolver
        )->setErrorFormatter(fn(Error $error): array => MyFormater::fromException($error));
        return $result;
    }

    private function validateReferences(string $namespace, Schema $schema, StoreSchema $meta, NamedType|Type|null $type, $data)
    {
        if ($type instanceof ObjectType) {
            foreach ($type->getFields() as $field) {
                $baseType = Type::getNamedType($field->getType());
                if ($data[$field->name] && is_a($baseType, ObjectType::class)) {
                    $id = '';
                    $ref = $schema->getType($baseType->name);
                    if ($ref instanceof ObjectType) {
                        foreach ($ref->getFields() as $refField) {
                            $refType = Type::getNamedType($refField->getType());
                            if (!$refType == 'ID') {
                                $id = $refField->name;
                            }
                        }
                    }
                    $filter = new DataQueryParam($schema, $baseType->name, ["{$id}Equals", $data[$field->name]]);
                    if (!$this->datas->fetch($namespace, $baseType->name, $meta, $filter)) {
                        throw new NotFountException('Unable to find ' . $data[$field->name] . ' on ' . $field->name . ' for ' . $type->name);
                    }
                }
            }
        }
    }

    private function expand($objectValue, StoreSchema $meta, ResolveInfo $info): mixed
    {
        $namespace = $this->namespace;
        $schema = $this->schemas->schema($namespace);
        $field = $info->fieldName;
        $on = $info->parentType->name;
        if (!isset($this->pending[$on])) {
            $this->pending[$on] = [];
        }
        $type = $schema->getType($on);
        $id = 'id';
        if ($type instanceof ObjectType) {
            foreach ($type->getFields() as $fieldName => $fieldDef) {
                $baseType = Type::getNamedType($fieldDef->getType());
                if ($baseType->name === 'ID') {
                    $id = $fieldName;
                }
            }
        }
        $theId = is_array($objectValue) ? $objectValue[$id] : $objectValue;
        if( $theId ) {
            if (!in_array($theId, $this->pending[$on])) {
                $this->pending[$on][] = $theId;
            }
            return new Deferred(function () use ($namespace, $meta, $schema, $type, $field, $on, $id, $theId) {
                if (!isset($this->batches[$on])) {
                    $query = new DataQueryParam($schema, $type->name, []);
                    $query->idIn($this->pending[$on]);
                    $this->batches[$on] = [];
                    $all = $this->datas->fetch($namespace, $on, $meta, $query);
                    foreach ($all as $row) {
                        $this->batches[$on][$row[$id]] = $row;
                    }
                }
                return $this->batches[$on][$theId][$field] ?? null;
            });
        } else {
            echo "<h1>Retornamos un valor nulo....</h1>";
            return null;
        }
    }
}

class MyFormater extends FormattedError
{

    public static function fromException(Error $error): array
    {
        $is = FormattedError::createFromException($error);
        $previous = $error->getPrevious();
        if ($previous instanceof ConstraintException) {
            $is['constraints'] = $previous->errors;
        } else if ($previous instanceof TypeError) {
            echo "<p>" . $previous->getMessage();
        } else {
            echo "<pre>" . get_class($previous);
        }
        return $is;
    }
}
