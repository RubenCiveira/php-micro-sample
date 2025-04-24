<?php

namespace Civi\RepomanagerBackoffice;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class IndexView
{
    public function __construct(private readonly BaseView $base)
    {
        
    }
    public function get(Request $request, Response $response, array $args): Response 
    {
        $context = [];
        return BaseView::template('index', $context, $request, $response);
    }
}