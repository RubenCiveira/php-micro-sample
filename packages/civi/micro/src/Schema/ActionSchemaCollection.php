<?php

declare(strict_types=1);

namespace Civi\Micro\Schema;

use Override;

/**
 * Represents a strongly-typed, read-only collection of ActionSchema objects.
 *
 * This collection ensures that all elements are valid instances of ActionSchema,
 * provides iteration capabilities, counting, and access to the full array of actions.
 *
 * @api
 *
 * @implements \IteratorAggregate<int, ActionSchema>
 */
class ActionSchemaCollection implements \IteratorAggregate, \Countable
{
    /**
     * Internal storage for ActionSchema instances.
     *
     * @var ActionSchema[]
     */
    private readonly array $actions;

    /**
    * Creates a new ActionSchemaCollection instance.
    *
    * @param ActionSchema[] $actions List of ActionSchema objects.
    * @throws \InvalidArgumentException If any element is not an instance of ActionSchema.
    */
    public function __construct(array $actions)
    {
        foreach ($actions as $action) {
            if (!$action instanceof ActionSchema) {
                throw new \InvalidArgumentException('All elements must be instances of ActionSchema.');
            }
        }
        $this->actions = $actions;
    }

    /**
     * Returns an iterator to traverse the collection.
     *
     * @return \ArrayIterator<int, ActionSchema> An iterator over ActionSchema elements.
     */
    #[Override]
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->actions);
    }

    /**
     * Returns the number of ActionSchema objects in the collection.
     *
     * @return int The total number of actions.
     */
    #[Override]
    public function count(): int
    {
        return count($this->actions);
    }

    /**
     * Returns all ActionSchema instances as an array.
     *
     * @return ActionSchema[] List of all actions.
     */
    public function all(): array
    {
        return $this->actions;
    }
}
