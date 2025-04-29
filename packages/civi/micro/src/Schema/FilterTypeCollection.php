<?php

declare(strict_types=1);

namespace Civi\Micro\Schema;

/**
 * Represents a strongly-typed, read-only collection of FilterType objects.
 *
 * @implements \IteratorAggregate<int, FilterType>
 */
class FilterTypeCollection implements \IteratorAggregate, \Countable
{
    /**
     * @param FilterType[] $filters
     */
    public function __construct(
        private readonly array $filters
    ) {
        foreach ($filters as $filter) {
            if (!$filter instanceof FilterType) {
                throw new \InvalidArgumentException('All elements must be instances of FilterType.');
            }
        }
    }

    /**
     * @return \ArrayIterator<int, FilterType>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->filters);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->filters);
    }

    /**
     * @return FilterType[]
     */
    public function all(): array
    {
        return $this->filters;
    }
}
