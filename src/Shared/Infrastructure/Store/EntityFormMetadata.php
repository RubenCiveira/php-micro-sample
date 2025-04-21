<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\Store;

use Civi\Repomanager\Shared\Infrastructure\Form\FormMetadata;
use Civi\Repomanager\Shared\Infrastructure\Store\Gateway\DataGateway;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class EntityFormMetadata
{

    public function __construct(
        private readonly string $namespace, private readonly string $type,
        private readonly Schemas $schemas,
        private readonly DataGateway $dataGateway
    ) {
    }
    
    public function build(): FormMetadata
    {
        $namespace = $this->namespace;
        $resource = $this->className( $this->type );
        $schema = $this->schemas->schema($namespace);
        $jsonSchema = $this->schemas->jsonSchema( $namespace, $resource );
        $type = $schema->getType($resource);
        $meta = new FormMetadata('', '', $this->searchIdField($type) );
        // Es necesario añadir los tipos.
        // print_r( $jsonSchema );
        // foreach($jsonSchema['properties'] as $properties) {

        // }
        // die();
        return $meta;
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
}