<?php

use Civi\Micro\ProjectLocator;

require __DIR__ . '/../vendor/autoload.php';

$app = ProjectLocator::buildApp();

$app->run();