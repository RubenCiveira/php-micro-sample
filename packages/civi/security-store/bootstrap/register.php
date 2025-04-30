<?php

use Civi\Micro\AppBuilder;
use Civi\Store\Schemas;

Schemas::register('usermanagement', __DIR__.'/../config/schemas/usermanagement');
AppBuilder::dependencies(__DIR__.'/di.container.php');
AppBuilder::routes(__DIR__.'/slim.routes.php');
