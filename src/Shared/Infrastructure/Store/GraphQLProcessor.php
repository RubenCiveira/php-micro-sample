<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\Store;

use Civi\Repomanager\Shared\Infrastructure\Store\Gateway\DataGateway;
use Civi\Repomanager\Shared\Infrastructure\Store\DataQueryParam;
use Civi\Repomanager\Shared\Infrastructure\Store\Service\ExtractMutation;

use GraphQL\Deferred;
use GraphQL\Executor\ExecutionResult;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

class GraphQLProcessor
{
    private $pending = [];
    private $batches = [];

    public function __construct(
        private readonly Schemas $schemas,
        private readonly DataGateway $datas,
        private readonly string $namespace,
        private readonly string $query,
        private readonly array|null $variables
    ) {
    }

    public function result(): ExecutionResult
    {
        $namepace = $this->namespace;
        $schema = $this->schemas->schema($namepace);
        $rootQuery = $schema->getQueryType();

        $resolvers = [];
        $names = $rootQuery->getFieldNames();
        foreach ($names as $name) {
            $resolvers[$name] = function ($root, array $args, $context, ResolveInfo $resolveInfo) use ($namepace, $schema, $name, $rootQuery) {
                if( isset( $args['id'] ) ) {
                    $args['filter'] = ['idEquals' => $args['id']];
                    unset($args['id']);
                }
                // die();
                $type = $rootQuery->getField($name)->getType();
                $theType = Type::getNamedType($type );
                $filter = new DataQueryParam($schema, $theType, $args);
                $response = $this->datas->fetch(
                    $namepace,
                    $theType,
                    $filter
                );
                if( str_starts_with($name, "_") && str_ends_with($name, "Metadata") ) {
                    return [ "count" => count($response) ];
                }
                if( $type instanceof NonNull) {
                    $type = $type->getWrappedType();
                }
                var_dump( $name );
                return ( $type instanceof ListOfType ) ? $response : $response[0];
            };
        }
        $extractor = new ExtractMutation();
        $mutations = $extractor->fromSchema($schema);
        foreach ($mutations as $theType => $hisMutations) {
            foreach ($hisMutations as $mutation) {
                $resolvers[lcfirst($theType) . ucfirst($mutation['name'])] = function ($root, array $args, $context, ResolveInfo $resolveInfo) use ($namepace, $schema, $mutation, $theType) {
                    $idName = 'id';
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
                                $idName,
                                $mutation['name'],
                                new DataQueryParam($schema, $theType, $condition),
                            );
                            return true;
                        case 'modify':
                            $id = array_key_first($args);
                            $condition = ["filter" => ["{$id}Equals" => $args[$id]]];
                            return $this->datas->modify(
                                $namepace,
                                $theType,
                                $idName,
                                $mutation['name'],
                                new DataQueryParam($schema, $theType, $condition),
                                $data
                            )[0];
                        case 'create':
                            return $this->datas->create(
                                $namepace,
                                $theType,
                                $idName,
                                $mutation['name'],
                                $data
                            )[0];
                    }
                };
            }
        }

        $customFieldResolver = function ($objectValue, array $args, $context, ResolveInfo $info) {
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
                if ($info->parentType && $info->parentType->name) {
                    return $this->expand($objectValue, $info);
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
        );
        return $result;
    }

    private function expand($objectValue, ResolveInfo $info): Deferred
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
        foreach ($type->getFields() as $fieldName => $fieldDef) {
            $baseType = Type::getNamedType($fieldDef->getType());
            if ($baseType->name === 'ID') {
                $id = $fieldName;
            }
        }
        $theId = is_array($objectValue) ? $objectValue[$id] : $objectValue;
        if (!in_array($theId, $this->pending[$on])) {
            $this->pending[$on][] = $theId;
        }
        return new Deferred(function () use ($namespace, $schema, $type, $field, $on, $id, $theId) {
            if (!isset($this->batches[$on])) {
                $query = new DataQueryParam($schema, $type, []);
                $query->idIn($this->pending[$on]);
                $this->batches[$on] = [];
                $all = $this->datas->fetch($namespace, $on, $query);
                foreach ($all as $row) {
                    $this->batches[$on][$row[$id]] = $row;
                }
            }
            return $this->batches[$on][$theId][$field] ?? null;
        });
    }
}