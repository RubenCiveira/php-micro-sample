<?php
namespace Civi\SecurityStoreBackoffice;

use Civi\SecurityStore\Bootstrap\AuthSecurityMiddleware;
use Civi\View\Twig\BaseView;
use Civi\View\ViewServices;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class LoginView extends BaseView
{
    public function __construct(ViewServices $services, private readonly AuthSecurityMiddleware $auth)
    {
        parent::__construct($services, 'login', __DIR__ . '/templates');
    }

    public function get(Request $request, Response $response): Response 
    {
        $context = [
            'providers' => $this->auth->providers()
        ];
        return $this->render($context, $request, $response);
    }
}