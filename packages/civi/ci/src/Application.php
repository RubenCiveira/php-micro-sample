<?php declare(strict_types=1);

namespace Civi\Ci;

use Civi\Ci\Command\FeatureEndCommand;
use Civi\Ci\Command\FeatureStartCommand;
use Civi\Ci\Command\HotfixEndCommand;
use Civi\Ci\Command\HotfixStartCommand;
use Civi\Ci\Command\ReleaseProposeCommand;
use Symfony\Component\Console\Application as SymfonyApplication;

class Application extends SymfonyApplication
{
    public function __construct()
    {
        parent::__construct('Civi CI', '0.1.0');
        $this->add(new FeatureStartCommand());
        $this->add(new FeatureEndCommand());
        $this->add(new ReleaseProposeCommand());
        $this->add(new HotfixStartCommand());
        $this->add(new HotfixEndCommand());
        // Aquí luego añadiremos los comandos, pero ahora dejamos la base
    }
}
