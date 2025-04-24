<?php
namespace Civi\Repomanager\Bootstrap\Security;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\App;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use League\OAuth2\Client\Provider\Google;

session_start();

class GoogleSecurityMiddleware
{
    private readonly Google $provider;
    private readonly array $users;
    private readonly string $verifyEndpoint;
    private readonly string $verifyRoute;
    private readonly string $basePath;

    public function __construct( SecurityConfig $config, App $app)
    {
        $this->provider = new Google([
            'clientId' => $config->googleClientId,
            'clientSecret' => $config->googleClientSecret,
            'redirectUri' => $config->googleRedirectUri
        ]);
        $this->users = $config->authorizedUsers;
        $this->basePath = $app->getBasePath() . '/';
        $this->verifyRoute = $this->basePath. basename($config->googleRedirectUri);
    }

    public function __invoke(Request $request, RequestHandlerInterface $handler): Response
    {
        // Obtiene la ruta actual
        $route = $request->getUri()->getPath();
        $rutasPermitidas = [$this->verifyRoute];
        // Si la ruta está permitida, se omite la autenticación
        if (in_array($route, $rutasPermitidas)) {
            return $handler->handle($request);
        }
        $user = $this->getUsername();
        if (!$user) {
            $response = new Response();
            // TODO: if request dont accept Html => error with message
            return $response->withHeader('Location', $this->getLocationToLogin())->withStatus(302);
        } else if (!$this->isValidUser()) {
            $response = new Response();
            $response->getBody()->write("El usuario $user no tiene acceso permitido");
            return $response->withStatus(403);
        } else {
            return $handler->handle($request);
        }
    }

    public function verifyAuthorization(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        if (!isset($queryParams['code']) || !isset($queryParams['state']) || $queryParams['state'] !== $_SESSION['oauth2state']) {
            $message = $queryParams['msg'] ?? 'Error de autenticación.';
            unset($_SESSION['oauth2state']);
            $response->getBody()->write($message);
            return $response->withStatus(400);
        }
        try {
            $token = $this->provider->getAccessToken('authorization_code', ['code' => $queryParams['code']]);
            $user = $this->provider->getResourceOwner($token);
            $email = $user->getEmail();
            if (!in_array($email, $this->users)) {
                return $response->withHeader('Location', '?msg=Acceso denegado')->withStatus(302);
            } else {
                $_SESSION['user'] = ['name' => $user->getName(), 'email' => $email];
                return $response->withHeader('Location', $this->basePath)->withStatus(302);
            }
        } catch(IdentityProviderException $ex) {
            return $response->withHeader('Location', '?msg=Token invalido')->withStatus(302);
        }
    }

    private function isValidUser(): bool
    {
        $email = $_SESSION['user']['email'] ?? null;
        return $email && in_array($email, $this->users);
    }

    private function getUsername(): mixed
    {
        return $_SESSION['user']['name'] ?? null;
    }

    private function getLocationToLogin(): string
    {
        // No autenticado, redirigir a Google
        $authUrl = $this->provider->getAuthorizationUrl();
        $_SESSION['oauth2state'] = $this->provider->getState();
        return $authUrl;
    }

    private function verifyGoogleCallback(Request $request, Response $response): bool
    {
        $queryParams = $request->getQueryParams();

        if (!isset($queryParams['code']) || !isset($queryParams['state']) || $queryParams['state'] !== $_SESSION['oauth2state']) {
            unset($_SESSION['oauth2state']);
            $response->getBody()->write('Error de autenticación.');
            return false;
            // return $response->withStatus(400);
        }

        $token = $this->provider->getAccessToken('authorization_code', ['code' => $queryParams['code']]);
        $user = $this->provider->getResourceOwner($token);
        $email = $user->getEmail();

        if (!in_array($email, $this->users)) {
            return false;
            // return $response->withStatus(403)->write('Acceso denegado');
        } else {
            $_SESSION['user'] = ['name' => $user->getName(), 'email' => $email];
            return true;
            // return $response->withHeader('Location', '/dashboard')->withStatus(302);
        }
    }
}