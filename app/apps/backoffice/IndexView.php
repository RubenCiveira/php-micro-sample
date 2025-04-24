<?php

namespace Civi\RepomanagerBackoffice;

use Civi\View\Twig\BaseView;
use Civi\View\ViewConfig;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class IndexView extends BaseView
{
    public function __construct(ViewConfig $config)
    {
        parent::__construct($config, 'index', __DIR__ . '/templates');
    }
    public function get(Request $request, Response $response, array $args): Response 
    {
        $context = [];
        return $this->render([], $request, $response);
    }
}