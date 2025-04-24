<?php

use Civi\Micro\Config;
use Civi\View\ViewConfig;
use DI\Container;

return function (Container $container) {
    $container->set(ViewConfig::class, \DI\factory(function () {
        return Config::load('app.templates', ViewConfig::class, 'templates');
    }));

};
