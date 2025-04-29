<?php

declare(strict_types=1);

namespace Civi\Micro\Schema\Tests;

use Civi\Micro\Schema\FieldSchema;
use Civi\Micro\Schema\FieldSchemaCollection;
use PHPUnit\Framework\TestCase;

class FieldSchemaCollectionUnitTest extends TestCase
{
    public function testConstructWithValidFields(): void
    {
        $fields = [
            new FieldSchema('id', 'string', 'ID', true),
            new FieldSchema('name', 'string', 'Name', false),
        ];

        $collection = new FieldSchemaCollection($fields);

        $this->assertInstanceOf(FieldSchemaCollection::class, $collection);
        $this->assertCount(2, $collection);
    }

    public function testConstructWithInvalidFieldThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new FieldSchemaCollection([
            new FieldSchema('id', 'string', 'ID', true),
            'not_a_field', // This should trigger an exception
        ]);
    }

    public function testGetIterator(): void
    {
        $fields = [
            new FieldSchema('id', 'string', 'ID', true),
        ];

        $collection = new FieldSchemaCollection($fields);

        $iterator = $collection->getIterator();
        $this->assertInstanceOf(\Traversable::class, $iterator);
        $this->assertSame($fields, iterator_to_array($iterator));
    }

    public function testCount(): void
    {
        $fields = [
            new FieldSchema('id', 'string', 'ID', true),
            new FieldSchema('email', 'string', 'Email', false),
        ];

        $collection = new FieldSchemaCollection($fields);

        $this->assertSame(2, $collection->count());
    }

    public function testAll(): void
    {
        $fields = [
            new FieldSchema('id', 'string', 'ID', true),
            new FieldSchema('email', 'string', 'Email', false),
        ];

        $collection = new FieldSchemaCollection($fields);

        $this->assertSame($fields, $collection->all());
    }

    public function testFindByNameExistingField(): void
    {
        $fields = [
            new FieldSchema('id', 'string', 'ID', true),
            new FieldSchema('email', 'string', 'Email', false),
        ];

        $collection = new FieldSchemaCollection($fields);

        $field = $collection->findByName('email');

        $this->assertInstanceOf(FieldSchema::class, $field);
        $this->assertSame('email', $field->name);
    }

    public function testFindByNameNonExistingFieldReturnsNull(): void
    {
        $fields = [
            new FieldSchema('id', 'string', 'ID', true),
        ];

        $collection = new FieldSchemaCollection($fields);

        $this->assertNull($collection->findByName('other'));
    }
}
