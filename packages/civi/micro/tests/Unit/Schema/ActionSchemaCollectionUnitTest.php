<?php

declare(strict_types=1);

namespace Civi\Micro\Schema\Tests;

use Civi\Micro\Schema\ActionSchema;
use Civi\Micro\Schema\ActionSchemaCollection;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Civi\Micro\Schema\ActionSchemaCollection
 */
class ActionSchemaCollectionUnitTest extends TestCase
{
    public function testConstructorAcceptsOnlyActionSchemaInstances(): void
    {
        $action1 = new ActionSchema('name1', 'Label 1', 'success', true);
        $action2 = new ActionSchema('name2', 'Label 2', 'danger', false);

        $collection = new ActionSchemaCollection([$action1, $action2]);

        $this->assertCount(2, $collection);
        $this->assertSame([$action1, $action2], $collection->all());
    }

    public function testConstructorThrowsExceptionOnInvalidElement(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('All elements must be instances of ActionSchema.');

        new ActionSchemaCollection([
            new ActionSchema('name1', 'Label 1', 'success', true),
            new \stdClass() // <- Invalid element
        ]);
    }

    public function testGetIteratorReturnsTraversable(): void
    {
        $action1 = new ActionSchema('name1', 'Label 1', 'success', true);
        $action2 = new ActionSchema('name2', 'Label 2', 'danger', false);

        $collection = new ActionSchemaCollection([$action1, $action2]);

        $iterator = $collection->getIterator();
        $this->assertInstanceOf(\Traversable::class, $iterator);
        $this->assertSame([$action1, $action2], iterator_to_array($iterator));
    }

    public function testCountReturnsCorrectNumberOfElements(): void
    {
        $collection = new ActionSchemaCollection([
            new ActionSchema('name1', 'Label 1', 'success', true),
            new ActionSchema('name2', 'Label 2', 'danger', false),
            new ActionSchema('name3', 'Label 3', 'info', true),
        ]);

        $this->assertSame(3, $collection->count());
    }

    public function testAllReturnsActionsArray(): void
    {
        $actions = [
            new ActionSchema('name1', 'Label 1', 'success', true),
            new ActionSchema('name2', 'Label 2', 'danger', false),
        ];

        $collection = new ActionSchemaCollection($actions);

        $this->assertSame($actions, $collection->all());
    }
}
