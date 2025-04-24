<?php

use Civi\Repomanager\Shared\ProjectLocator;
use DI\Container;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = ProjectLocator::buildApp();

$app->run();