<?php

namespace Civi\Forge;

use Symfony\Component\Console\Application as SymfonyApplication;
use Civi\Forge\Command\GenerateEntityCommand;

class Application extends SymfonyApplication
{
    public function __construct()
    {
        parent::__construct('Forge CLI', '1.0.0');

        $this->add(new GenerateEntityCommand());
    }
}