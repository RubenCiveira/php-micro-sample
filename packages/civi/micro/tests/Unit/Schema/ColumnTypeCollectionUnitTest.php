<?php

declare(strict_types=1);

namespace Civi\Micro\Schema\Tests;

use Civi\Micro\Schema\ColumnType;
use Civi\Micro\Schema\ColumnTypeCollection;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ColumnTypeCollectionUnitTest extends TestCase
{
    public function testConstructorAcceptsOnlyColumnTypeInstances(): void
    {
        $column1 = new ColumnType('name', 'Name');
        $column2 = new ColumnType('email', 'Email');

        $collection = new ColumnTypeCollection([$column1, $column2]);

        $this->assertInstanceOf(ColumnTypeCollection::class, $collection);
        $this->assertCount(2, $collection);
    }

    public function testConstructorThrowsExceptionForInvalidElements(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('All elements must be instances of ColumnType.');

        // One valid ColumnType and one invalid string
        new ColumnTypeCollection([
            new ColumnType('name', 'Name'),
            'invalid_element',
        ]);
    }

    public function testGetIteratorReturnsCorrectElements(): void
    {
        $column = new ColumnType('id', 'ID');
        $collection = new ColumnTypeCollection([$column]);

        $elements = iterator_to_array($collection->getIterator());
        $this->assertCount(1, $elements);
        $this->assertSame($column, $elements[0]);
    }

    public function testAllReturnsAllColumns(): void
    {
        $column1 = new ColumnType('first_name', 'First Name');
        $column2 = new ColumnType('last_name', 'Last Name');

        $collection = new ColumnTypeCollection([$column1, $column2]);

        $all = $collection->all();
        $this->assertSame([$column1, $column2], $all);
    }

    public function testCountReturnsCorrectNumber(): void
    {
        $columns = [
            new ColumnType('one', 'One'),
            new ColumnType('two', 'Two'),
            new ColumnType('three', 'Three'),
        ];

        $collection = new ColumnTypeCollection($columns);

        $this->assertEquals(3, $collection->count());
    }
}
