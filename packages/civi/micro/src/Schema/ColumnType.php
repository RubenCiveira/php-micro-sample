<?php

declare(strict_types=1);

namespace Civi\Micro\Schema;

/**
 * Represents a display column for an entity type schema.
 *
 * Each column has a unique internal name and a human-readable label
 * that can be used when presenting data in tabular formats (e.g., list views, data grids).
 *
 * @api
 */
class ColumnType
{
    /**
     * The internal name of the column (typically matching a field or computed path).
     *
     * @var string
     */
    public readonly string $name;

    /**
     * The human-readable label to display for the column in the UI.
     *
     * @var string
     */
    public readonly string $label;

    /**
     * Creates a new ColumnType instance.
     *
     * @param string $name Internal column name (e.g., 'created_at', 'user.name').
     * @param string $label Display label for the column (e.g., 'Created At', 'User Name').
     */
    public function __construct(
        string $name,
        string $label
    ) {
        $this->name = $name;
        $this->label = $label;
    }
}
