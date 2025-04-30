<?php

namespace Civi\RepomanagerBackoffice;

use Civi\View\Twig\BaseView;
use Civi\View\ViewConfig;
use Civi\View\ViewGuard;
use Civi\View\ViewServices;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ConfigurationView extends BaseView
{
    public function __construct(ViewServices $services, ViewGuard $guard)
    {
        parent::__construct($services, 'configuration', __DIR__ . '/templates');
    }

    public function get(Request $request, Response $response, array $args): Response
    {
        return $this->render([], $request, $response);
    }
}
