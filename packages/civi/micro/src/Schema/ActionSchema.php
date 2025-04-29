<?php

declare(strict_types=1);

namespace Civi\Micro\Schema;

/**
 * Represents an immutable action definition used within an entity schema.
 *
 * An ActionSchema defines metadata for different types of user-triggered actions,
 * such as contextual confirmations, standalone forms, or resume actions.
 *
 * @api
 */
class ActionSchema
{
    /**
    * Creates a new ActionSchema instance.
    *
    * @param string $name
    *   The unique internal name of the action.
    *
    * @param string $label
    *   The human-readable label that describes the action in UI elements.
    *
    * @param string $kind
    *   The semantic type of the action, typically used for UI styling
    *   (e.g., 'success', 'danger', 'info', 'warn').
    *
    * @param bool $contextual
    *   Indicates whether the action is contextual (related to a specific item)
    *   or standalone (global form, resume generation, etc.).
    *
    * @param array<string, mixed> $fields
    *   Optional. An array describing the input fields required by the action.
    *   Defaults to an empty array.
    *
    * @param mixed|null $callback
    *   Optional. A callable (Closure, array callable, or string callable) that
    *   will be executed when the action is triggered. Defaults to null.
    *
    * @param string|null $code
    *   Optional. JavaScript code to be executed in the frontend when the action is performed.
    *   Useful for "resume" or custom actions. Defaults to null.
    *
    * @param string|null $template
    *   Optional. HTML template associated with the action, to be rendered on the frontend.
    *   Defaults to null.
    *
    * @param array<string, string>|null $buttons
    *   Optional. Defines extra buttons for the frontend UI,
    *   where each key is a JavaScript function and each value is a label. Defaults to null.
    *
    * @param string|null $functions
    *   Optional. Additional JavaScript functions to be injected with the action.
    *   Defaults to null.
    */
    public function __construct(
        public readonly string $name,
        public readonly string $label,
        public readonly string $kind,
        public readonly bool $contextual,
        public readonly array $fields = [],
        public readonly mixed $callback = null,
        public readonly ?string $code = null,
        public readonly ?string $template = null,
        public readonly ?array $buttons = null,
        public readonly ?string $functions = null,
    ) {
    }
}
