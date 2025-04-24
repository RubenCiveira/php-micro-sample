<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Security;

use RuntimeException;

class UnauthorizedException extends RuntimeException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}