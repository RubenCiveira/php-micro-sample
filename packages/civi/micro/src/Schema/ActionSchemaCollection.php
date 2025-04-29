<?php

declare(strict_types=1);

namespace Civi\Micro\Schema;

/**
 * Represents a strongly-typed, read-only collection of ActionSchema objects.
 *
 * @implements \IteratorAggregate<int, ActionSchema>
 */
class ActionSchemaCollection implements \IteratorAggregate, \Countable
{
    /**
     * @param ActionSchema[] $actions
     */
    public function __construct(
        private readonly array $actions
    ) {
        foreach ($actions as $action) {
            if (!$action instanceof ActionSchema) {
                throw new \InvalidArgumentException('All elements must be instances of ActionSchema.');
            }
        }
    }

    /**
     * @return \ArrayIterator<int, ActionSchema>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->actions);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->actions);
    }

    /**
     * @return ActionSchema[]
     */
    public function all(): array
    {
        return $this->actions;
    }
}
