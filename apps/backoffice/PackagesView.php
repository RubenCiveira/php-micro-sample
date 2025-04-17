<?php
namespace Civi\RepomanagerBackoffice;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PackagesView
{
    public function get(Request $request, Response $response, array $args): Response 
    {
        $context = [];
        return BaseView::template('packages', $context, $request, $response);
    }
}