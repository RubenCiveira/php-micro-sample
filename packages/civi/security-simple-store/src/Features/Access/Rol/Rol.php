<?php

namespace Civi\SecurityStore\Features\Access\Rol;

class Rol
{
    public ?string $id = null;
    public ?string $name = null;
    /**
     * @autogenerated
     */
    public static function from(array $data): self
    {
        $self = new self();
        $self->id = $data['id'] ?? null;
        $self->name = $data['name'] ?? null;
        return $self;
    }
}