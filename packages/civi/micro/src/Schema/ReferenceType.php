<?php

declare(strict_types=1);

namespace Civi\Micro\Schema;

/**
 * Represents a reference to another entity or data source within a schema.
 *
 * This class is typically used to define relationships between fields and external resources.
 * It encapsulates the reference identifier, label, and a callable closure to load related data.
 *
 * @api
 */
class ReferenceType
{
    /**
     * Creates a new ReferenceType instance.
     *
     * @param string $id The unique identifier field of the referenced entity (e.g., 'user_id').
     * @param string $label A human-readable label describing the referenced entity (e.g., 'User').
     * @param \Closure $load A closure function that, when executed, retrieves the related entity or data.
     */
    public function __construct(
        public readonly string $id,
        public readonly string $label,
        public readonly \Closure $load
    ) {
    }
}
