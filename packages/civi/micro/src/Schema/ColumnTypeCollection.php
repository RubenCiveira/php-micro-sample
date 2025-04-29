<?php

declare(strict_types=1);

namespace Civi\Micro\Schema;

/**
 * Represents a strongly-typed, read-only collection of ColumnType objects.
 *
 * @implements \IteratorAggregate<int, ColumnType>
 */
class ColumnTypeCollection implements \IteratorAggregate, \Countable
{
    /**
     * @param ColumnType[] $columns
     */
    public function __construct(
        private readonly array $columns
    ) {
        foreach ($columns as $column) {
            if (!$column instanceof ColumnType) {
                throw new \InvalidArgumentException('All elements must be instances of ColumnType.');
            }
        }
    }

    /**
     * @return \ArrayIterator<int, ColumnType>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->columns);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->columns);
    }

    /**
     * @return ColumnType[]
     */
    public function all(): array
    {
        return $this->columns;
    }
}
