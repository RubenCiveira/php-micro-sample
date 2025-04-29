<?php

declare(strict_types=1);

namespace Civi\Micro\Schema\Tests;

use Civi\Micro\Schema\ActionSchema;
use Civi\Micro\Schema\ActionSchemaCollection;
use Civi\Micro\Schema\ColumnType;
use Civi\Micro\Schema\ColumnTypeCollection;
use Civi\Micro\Schema\FieldSchema;
use Civi\Micro\Schema\FieldSchemaCollection;
use Civi\Micro\Schema\FilterType;
use Civi\Micro\Schema\FilterTypeCollection;
use Civi\Micro\Schema\TypeSchema;
use PHPUnit\Framework\TestCase;

class TypeSchemaUnitTest extends TestCase
{
    private TypeSchema $typeSchema;

    protected function setUp(): void
    {
        $field1 = new FieldSchema('id', 'uuid', 'ID', true);
        $field2 = new FieldSchema('name', 'string', 'Name', true);

        $fields = new FieldSchemaCollection([$field1, $field2]);
        $filters = new FilterTypeCollection([new FilterType('name')]);
        $columns = new ColumnTypeCollection([new ColumnType('id', 'Identifier')]);
        $actions = new ActionSchemaCollection([
            new ActionSchema(
                name: 'delete',
                label: 'Delete',
                kind: 'danger',
                contextual: true
            )
        ]);

        $this->typeSchema = new TypeSchema(
            name: 'user',
            title: 'User Entity',
            id: 'id',
            fields: $fields,
            filters: $filters,
            columns: $columns,
            actions: $actions
        );
    }

    public function testConstructorProperties(): void
    {
        $this->assertEquals('user', $this->typeSchema->name);
        $this->assertEquals('User Entity', $this->typeSchema->title);
        $this->assertEquals('id', $this->typeSchema->id);
        $this->assertInstanceOf(FieldSchemaCollection::class, $this->typeSchema->fields);
        $this->assertInstanceOf(FilterTypeCollection::class, $this->typeSchema->filters);
        $this->assertInstanceOf(ColumnTypeCollection::class, $this->typeSchema->columns);
        $this->assertInstanceOf(ActionSchemaCollection::class, $this->typeSchema->actions);
    }

    public function testGetFieldReturnsFieldSchemaWhenExists(): void
    {
        $field = $this->typeSchema->getField('name');
        $this->assertInstanceOf(FieldSchema::class, $field);
        $this->assertEquals('name', $field->name);
    }

    public function testGetFieldReturnsNullWhenFieldDoesNotExist(): void
    {
        $field = $this->typeSchema->getField('non_existing_field');
        $this->assertNull($field);
    }
}
