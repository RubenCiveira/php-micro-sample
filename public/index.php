<?php

use Civi\Repomanager\Features\Repository\Access\Gateway\CredentialGateway;
use DI\Container;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$container = new Container();
$di = require __DIR__ . '/../dependencies.php';
$di($container);

AppFactory::setContainer($container);
$app = AppFactory::create();
$scriptName = $_SERVER['SCRIPT_NAME']; // Devuelve algo como "/midashboard/index.php"
$basePath = str_replace('/index.php', '', $scriptName); // "/midashboard"
$app->setBasePath($basePath);

// Middleware para parsear json
$app->addBodyParsingMiddleware();

$routes = require __DIR__ . '/../routes.php';
$routes($app);

$container->set(\Slim\App::class, \DI\value($app));

$app->run();