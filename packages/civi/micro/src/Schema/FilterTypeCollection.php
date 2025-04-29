<?php

declare(strict_types=1);

namespace Civi\Micro\Schema;

use Override;

/**
 * Represents a strongly-typed, read-only collection of FilterType objects.
 *
 * @api
 * 
 * @implements \IteratorAggregate<int, FilterType>
 */
class FilterTypeCollection implements \IteratorAggregate, \Countable
{
    /**
         * @var FilterType[] Internal list of filters.
         */
    private readonly array $filters;

    /**
     * Creates a new FilterTypeCollection.
     *
     * @param FilterType[] $filters An array of FilterType objects.
     *
     * @throws \InvalidArgumentException if any element is not an instance of FilterType.
     */
    public function __construct(
        array $filters
    ) {
        foreach ($filters as $filter) {
            if (!$filter instanceof FilterType) {
                throw new \InvalidArgumentException('All elements must be instances of FilterType.');
            }
        }
        $this->filters = $filters;
    }

    /**
     * Returns an iterator over the collection.
     *
     * @return \ArrayIterator<int, FilterType> Iterator over the FilterType objects.
     */
    #[Override]
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->filters);
    }

    /**
     * Returns the number of filters in the collection.
     *
     * @return int The number of elements.
     */
    #[Override]
    public function count(): int
    {
        return count($this->filters);
    }

    /**
     * Returns all filters in the collection.
     *
     * @return FilterType[] The array of FilterType objects.
     */
    public function all(): array
    {
        return $this->filters;
    }
}
