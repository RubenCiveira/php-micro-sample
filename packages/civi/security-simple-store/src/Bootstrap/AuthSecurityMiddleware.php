<?php

namespace Civi\SecurityStore\Bootstrap;

use Civi\Security\Authentication;
use Civi\Security\Connection;
use Civi\Security\SecurityContext;
use Civi\Security\SecurityContextHolder;
use Civi\SecurityStore\Features\Access\User\Gateway\UserGateway;
use Exception;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\App;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

session_start();

class AuthSecurityMiddleware
{
    // private readonly string $verifyRoute;
    private readonly string $basePath;
    private readonly string $loginUrl;
    private readonly string $redirectUrl;
    private readonly string $redirectPath;
    private readonly array $auths;

    public function __construct(SecurityConfig $config, private readonly UserGateway $users, App $app, array $auths)
    {
        $this->auths = $auths;
        $this->loginUrl = $config->loginUrl;
        $this->redirectPath = $config->oauthRedirectPath;
        $this->redirectUrl = $config->oauthRedirectHost . $config->oauthRedirectPath;
        $this->basePath = $app->getBasePath() . '/';
        // $this->verifyRoute = $this->basePath. basename($config->googleRedirectUri);
    }

    public static function register(SecurityConfig $config, App $app)
    {
        $app->get("{$config->oauthRedirectPath}/{provider}", [AuthSecurityMiddleware::class, 'verifyAuthorization']);
    }

    public function __invoke(Request $request, RequestHandlerInterface $handler): Response
    {
        // Obtiene la ruta actual
        $login = substr($this->loginUrl, 1);
        $route = $request->getUri()->getPath();
        if (str_starts_with($route, $this->basePath)) {
            $route = substr($route, strlen($this->basePath));
        }
        $rutasPermitidas = [$login];
        // Si la ruta está permitida, se omite la autenticación
        if (in_array($route, $rutasPermitidas) || str_starts_with("/{$route}", "{$this->redirectPath}/")) {
            return $handler->handle($request);
        }
        $user = $this->getUsername();
        if (!$user) {
            $response = new Response();
            // TODO: if request dont accept Html => error with message
            return $response->withHeader('Location', $this->basePath . $login)->withStatus(302);
        } else {
            $auth = $this->validUser();
            if( $auth ) {
                $context = new SecurityContext($auth, Connection::remoteHttp());
                SecurityContextHolder::set($context);
                return $handler->handle($request);
            } else {
                $response = new Response();
                $response->getBody()->write("El usuario $user no tiene acceso permitido");
                return $response->withStatus(403);
            }
        }
    }

    public function verifyAuthorization(Request $request, Response $response, $args)
    {
        $provider = $args['provider'];
        $queryParams = $request->getQueryParams();
        if( !isset($queryParams['msg']) ) {
            foreach ($this->auths as $auth) {
                if ($provider == $auth->id()) {
                    try {
                        $username = $auth->verifyAuthorization("{$this->redirectUrl}/" . $auth->id(), $request);
                        if ($username) {
                            $_SESSION['user'] = ['name' => $username['name'], 'email' => $username['email']];
                            return $response->withHeader('Location', $this->basePath)->withStatus(302);
                        } else {
                            return $response->withHeader('Location', '?msg=Token invalido')->withStatus(302);
                        }
                    } catch (Exception $ex) {
                        return $response->withHeader('Location', '?msg=' . $ex->getMessage())->withStatus(302);
                    }
                }
            }
        }
        $message = $queryParams['msg'] ?? 'Error de autenticación.';
        $response->getBody()->write($message);
        return $response->withStatus(400);
    }

    public function providers(): array
    {
        $providers = [];
        foreach ($this->auths as $auth) {
            $providers = [...$auth->buttons("{$this->redirectUrl}/" . $auth->id()), ...$providers];
        }
        return $providers;
    }

    private function validUser(): ?Authentication
    {
        $email = $_SESSION['user']['email'] ?? null;
        $users = $this->users->listUsers(['filter' => ['emailEquals' => $email]], ['rol.name']);
        if( $users[0] ?? false ) {
            $user = $users[0];
            return new Authentication(
                        anonimous: false,
                        name: $user['email'],
                        roles: isset($user['rol']['name']) ? [$user['rol']['name']] : []
                    );
        } else {
            return null;
        }
        // return $email && in_array($email, $this->users);
    }

    private function getUsername(): mixed
    {
        return $_SESSION['user']['name'] ?? null;
    }

}
