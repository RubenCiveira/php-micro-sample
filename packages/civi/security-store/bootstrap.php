<?php

use Civi\Micro\AppBuilder;
use Civi\Micro\ProjectLocator;
use Civi\Store\Schemas;

Schemas::register('usermanagement', __DIR__.'/config/schemas/usermanagement');
AppBuilder::dependencies(__DIR__.'/dependencies.php');
AppBuilder::routes(__DIR__.'/routes.php');
