<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\Store;

use Civi\Repomanager\Shared\Infrastructure\Store\Service\ExtractMutation;
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
        // $defaultForm = [];
        foreach ($jsonSchema['properties'] as $name => $info) {
            if ($name !== $idField) {
                $detail = $info;
                if (isset($info['format'])) {
                    $detail['type'] = $info['format'];
                }
                // if( $info['type'] == 'datetime-local' ) 
                $detail['required'] = in_array($name, $jsonSchema['required']);
                // $defaultForm[] = $name;
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
                    // if( $info['type'] == 'datetime-local' ) 
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