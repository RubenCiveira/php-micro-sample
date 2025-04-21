<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\Store;


use GraphQL\Error\UserError;

class ConstraintException extends UserError
{
    public array $errors;
    public function __construct(array $errors)
    {
        $this->errors = $errors;
    }

}