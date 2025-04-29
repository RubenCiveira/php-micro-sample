<?php

declare(strict_types=1);

namespace Civi\Micro\Schema\Tests;

use Civi\Micro\Schema\FilterType;
use Civi\Micro\Schema\FilterTypeCollection;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

class FilterTypeCollectionUnitTest extends TestCase
{
    public function testCanCreateCollectionAndRetrieveAllItems(): void
    {
        $filter1 = new FilterType('status');
        $filter2 = new FilterType('category');

        $collection = new FilterTypeCollection([$filter1, $filter2]);

        $this->assertCount(2, $collection);
        $this->assertSame([$filter1, $filter2], $collection->all());
    }

    public function testIteratorReturnsAllElements(): void
    {
        $filter1 = new FilterType('status');
        $filter2 = new FilterType('category');

        $collection = new FilterTypeCollection([$filter1, $filter2]);

        $elements = [];
        foreach ($collection as $item) {
            $elements[] = $item;
        }

        $this->assertSame([$filter1, $filter2], $elements);
    }

    public function testInvalidElementThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('All elements must be instances of FilterType.');

        new FilterTypeCollection([
            new FilterType('status'),
            new \stdClass(), // Invalid element
        ]);
    }
}
