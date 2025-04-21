<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\Store;

use Civi\Repomanager\Shared\Infrastructure\View\ViewMetadata;
use Civi\Repomanager\Shared\Infrastructure\Store\Gateway\DataGateway;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class EntityViewMetadata
{
    private readonly EntityRepository $repository;

    public function __construct(
        private readonly string $namespace,
        private readonly string $type,
        private readonly Schemas $schemas,
        private readonly DataGateway $dataGateway,
        private readonly Validator $validator
    ) {
        $this->repository = new EntityRepository(
            $this->namespace,
            $this->type,
            $this->schemas,
            $this->dataGateway,
            $this->validator
        );
    }

    public function build(): ViewMetadata
    {
        $namespace = $this->namespace;
        $resource = $this->className($this->type);
        $schema = $this->schemas->schema($namespace);
        $jsonSchema = $this->schemas->jsonSchema($namespace, $resource);
        $type = $schema->getType($resource);
        $idField = $this->searchIdField($type);
        $meta = new ViewMetadata('', '', $idField);
        $defaultForm = [];
        foreach ($jsonSchema['properties'] as $name => $info) {
            if ($name !== $idField) {
                $detail = $info;
                $detail['required'] = in_array($name, $jsonSchema['required']);
                $defaultForm[] = $name;
                $meta->addField($name, $detail);
            }
        }
        $meta->addStandaloneFormAction('create', 'Create', $defaultForm, fn($data) => $this->repository->create($data) );
        $meta->addContextualFormAction('edit', 'Edit', $defaultForm, fn($data) => $this->repository->modify($data[$idField], $data) );
        $meta->addContextualConfirmAction('remove', 'Remove', fn($data) => $this->repository->delete($data[$idField]));
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