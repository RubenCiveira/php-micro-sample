<?php

declare(strict_types=1);

namespace Civi\Micro\Schema;

/**
 * Represents an immutable filter definition used within an entity schema.
 *
 * A FilterType defines the name of a filter that can be applied to an entity,
 * typically used for querying or restricting entity data based on specific criteria.
 */
class FilterType
{
    /**
     * The name of the filter.
     *
     * This value is used as the key when applying filters to an entity.
     *
     * @var string
     */
    public readonly string $name;

    /**
     * Constructs a new FilterType instance.
     *
     * @param string $name The unique name of the filter.
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
