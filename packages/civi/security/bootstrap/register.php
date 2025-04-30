<?php

use Civi\Micro\AppBuilder;

AppBuilder::dependencies(__DIR__.'/di.container.php');
AppBuilder::routes(__DIR__.'/slim.routes.php');
