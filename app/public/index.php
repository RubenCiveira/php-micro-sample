<?php

use Civi\Micro\AppBuilder;

require __DIR__ . '/../vendor/autoload.php';

$app = AppBuilder::buildApp();

$app->run();