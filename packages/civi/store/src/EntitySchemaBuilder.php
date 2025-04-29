<?php declare(strict_types=1);

namespace Civi\Store;

use Civi\Micro\Telemetry\LoggerAwareInterface;
use Civi\Micro\Telemetry\LoggerAwareTrait;
use Civi\Store\Service\ExtractMutation;
use Civi\Micro\Schema\TypeSchemaBuilder;
use Civi\Store\Service\DataService;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class EntitySchemaBuilder implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    
    private readonly EntityRepository $repository;

    public function __construct(
        private readonly string $namespace,
        private readonly string $type,
        private readonly Schemas $schemas,
        private readonly DataService $dataService,
        private readonly Validator $validator
    ) {
        $this->repository = new EntityRepository(
            $this->namespace,
            $this->type,
            $this->schemas,
            $this->dataService,
            $this->validator
        );
    }

    public function build(): TypeSchemaBuilder
    {
        $namespace = $this->namespace;
        $resource = $this->className($this->type);
        $schema = $this->schemas->schema($namespace);
        $jsonSchema = $this->schemas->jsonSchema($namespace, $resource);
        $type = $schema->getType($resource);
        $idField = $this->searchIdField($type);
        $meta = new TypeSchemaBuilder('', '', $idField);
        // $defaultForm = [];
        foreach ($jsonSchema['properties'] as $name => $info) {
            if ($name !== $idField) {
                $detail = $info;
                if (isset($info['format'])) {
                    $detail['type'] = $info['format'];
                }
                $detail['required'] = in_array($name, $jsonSchema['required']);
                if( $type instanceof ObjectType) {
                    $field = $type->getField($name);
                    $baseType = Type::getNamedType($field->getType());
                    if( $baseType instanceof ObjectType ) {
                        $detail['reference'] = ['id' => 'id', 'label' => 'name', 'load' => function() use ($baseType, $schema) {
                            $targetNamespace = $this->namespace;
                            $targetType = $baseType->toString();
                            $query = new DataQueryParam($schema, $targetType, []);
                            return $this->dataService->fetch($targetNamespace, $targetType, new StoreSchema('', []), $query);
                        }];
                    }
                }
                $meta->addField($name, $detail);
            }
        }
        $mut = new ExtractMutation();
        $mutations = $mut->fromType($type);
        foreach ($mutations as $mutation) {
            $form = [];
            foreach($mutation['assign'] as $asName => $asField) {
                if( $asName != $idField ) {
                    $detail = $jsonSchema['properties'][$asName];
                    if (isset($info['format'])) {
                        $detail['type'] = $info['format'];
                    }
                    $detail['required'] = in_array($name, $jsonSchema['required']);
                    $form[] = $asName;
                }
            }
            switch ($mutation['context']) {
                case 'create':
                    $meta->addStandaloneFormAction($mutation['name'], ucfirst($mutation['name']), $form, fn($data) => $this->repository->create($mutation['name'], $data));
                    break;
                case 'modify':
                    if (count($form) == 0) {
                        $meta->addContextualConfirmAction($mutation['name'], ucfirst($mutation['name']), fn($data) => $this->repository->modify($mutation['name'], $data[$idField], $data));
                    } else {
                        $meta->addContextualFormAction($mutation['name'], ucfirst($mutation['name']), $form, fn($data) => $this->repository->modify($mutation['name'], $data[$idField], $data));
                    }
                    break;
                case 'delete':
                    $meta->addContextualConfirmAction($mutation['name'], ucfirst($mutation['name']), fn($data) => $this->repository->change($mutation['name'], $data[$idField]));
            }
        }
        return $meta;
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
}