<?php

declare(strict_types=1);

namespace Civi\Micro\Schema;

/**
 * Represents a strongly-typed, read-only collection of FieldSchema objects.
 *
 * @implements \IteratorAggregate<int, FieldSchema>
 */
class FieldSchemaCollection implements \IteratorAggregate, \Countable
{
    /**
     * @param FieldSchema[] $fields
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
     * @return FieldSchema|null
     */
    public function find(string $name)
    {
        return $this->fields[$name] ?? null;
    }

    /**
     * @return \ArrayIterator<int, FieldSchema>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->fields);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->fields);
    }

    /**
     * Find a FieldSchema by its name.
     *
     * @param string $name
     * @return FieldSchema|null
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
     * @return FieldSchema[]
     */
    public function all(): array
    {
        return $this->fields;
    }
}
