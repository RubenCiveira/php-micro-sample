<?php

declare(strict_types=1);

namespace Civi\Store;

use Civi\Store\Service\ExtractDirectives;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use InvalidArgumentException;

class StoreSchema
{
    public function __construct(
        public readonly string $idName,
        public readonly array $indexFields = [],
        public readonly array $uniqueFields = []
    ) {
    }

    public static function fromTypedName(string $name, Schema $schema): StoreSchema
    {
        $type = $schema->getType($name);
        if( $type instanceof ObjectType ) {
            return self::fromType($type);
        } else {
            throw new InvalidArgumentException("The type $name is not an object definition");
        }
    }

    public static function fromType(ObjectType $type): StoreSchema
    {
        $id = "id";
        $uniques = [];
        $indexables = [];
        foreach ($type->getFields() as $fieldName => $fieldDef) {
            $directives = ExtractDirectives::fromNode($fieldDef);
            if( isset($directives['unique'])) {
                $uniques[] = $fieldName;
            }
            if( isset($directives['index'])) {
                $indexables[] = $fieldName;
            }
            $baseType = Type::getNamedType($fieldDef->getType());
            if ($baseType->name === 'ID') {
                $id = $fieldName;
            }
        }
        return new StoreSchema($id, $indexables, $uniques);
    }
}
