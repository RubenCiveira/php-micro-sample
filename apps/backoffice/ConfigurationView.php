<?php
namespace Civi\RepomanagerBackoffice;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ConfigurationView
{
    public function post(Request $request, Response $response, array $args): Response
    {
        
    }
    public function get(Request $request, Response $response, array $args): Response
    {
        $context = [];
        return BaseView::template('configuration', $context, $request, $response);
    }
}