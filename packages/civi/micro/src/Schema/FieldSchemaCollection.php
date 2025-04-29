<?php

declare(strict_types=1);

namespace Civi\Micro\Schema;

use Override;

/**
 * Represents a strongly-typed, read-only collection of FieldSchema objects.
 *
 * Provides iteration, counting, and lookup capabilities.
 *
 * @api
 * 
 * @implements \IteratorAggregate<int, FieldSchema>
 */
class FieldSchemaCollection implements \IteratorAggregate, \Countable
{
    /**
     * Creates a new FieldSchemaCollection.
     *
     * @param FieldSchema[] $fields An array of FieldSchema objects.
     *
     * @throws \InvalidArgumentException if any element is not a FieldSchema instance.
     */
    public function __construct(
        private readonly array $fields
    ) {
        foreach ($fields as $field) {
            if (!$field instanceof FieldSchema) {
                throw new \InvalidArgumentException('All elements must be instances of FieldSchema.');
            }
        }
    }

    /**
     * Returns an iterator for the collection.
     *
     * @return \ArrayIterator<int, FieldSchema> The iterator over FieldSchema objects.
     */
    #[Override]
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->fields);
    }

    /**
     * Returns the number of fields in the collection.
     *
     * @return int The total number of FieldSchema elements.
     */
    #[Override]
    public function count(): int
    {
        return count($this->fields);
    }

    /**
     * Finds a FieldSchema by its declared `name` attribute.
     *
     * This is a search based on the logical field name, not the array key.
     *
     * @param string $name The name attribute of the FieldSchema to find.
     * @return FieldSchema|null The matching FieldSchema, or null if not found.
     */
    public function findByName(string $name): ?FieldSchema
    {
        foreach ($this->fields as $field) {
            if ($field->name === $name) {
                return $field;
            }
        }
        return null;
    }

    /**
     * Returns all FieldSchema elements in the collection.
     *
     * @return FieldSchema[] The array of FieldSchema objects.
     */
    public function all(): array
    {
        return $this->fields;
    }
}
