<?php

declare(strict_types=1);

namespace Civi\Micro\Schema;

/**
 * Represents a strongly-typed, read-only collection of ColumnType objects.
 *
 * This class ensures that all elements within the collection are instances of ColumnType
 * and provides standard iterable and countable capabilities.
 *
 * @implements \IteratorAggregate<int, ColumnType>
 *
 */
class ColumnTypeCollection implements \IteratorAggregate, \Countable
{
    /**
     * The array of ColumnType objects.
     *
     * @var ColumnType[]
     */
    private readonly array $columns;

    /**
     * Constructs a new ColumnTypeCollection.
     *
     * @param ColumnType[] $columns An array of ColumnType objects.
     *
     * @throws \InvalidArgumentException If any element is not an instance of ColumnType.
     */
    public function __construct(
        array $columns
    ) {
        foreach ($columns as $column) {
            if (!$column instanceof ColumnType) {
                throw new \InvalidArgumentException('All elements must be instances of ColumnType.');
            }
        }
        $this->columns = $columns;
    }

    /**
     * Returns an iterator for traversing the ColumnType elements.
     *
     * @return \ArrayIterator<int, ColumnType> An iterator over the collection.
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->columns);
    }

    /**
     * Returns the number of elements in the collection.
     *
     * @return int The number of ColumnType objects.
     */
    public function count(): int
    {
        return count($this->columns);
    }

    /**
     * Returns all ColumnType objects in the collection as an array.
     *
     * @return ColumnType[] The array of ColumnType objects.
     */
    public function all(): array
    {
        return $this->columns;
    }
}
