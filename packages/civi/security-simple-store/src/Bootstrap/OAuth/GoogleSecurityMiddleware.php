<?php

namespace Civi\SecurityStore\Bootstrap\OAuth;

use Civi\SecurityStore\Bootstrap\SecurityConfig;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Slim\App;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use League\OAuth2\Client\Provider\Google;

class GoogleSecurityMiddleware
{
    // private Google $provider;
    private readonly string $basePath;

    public function __construct(private readonly SecurityConfig $config, App $app)
    {
        // $this->provider = new Google([
        //     'clientId' => $config->googleClientId,
        //     'clientSecret' => $config->googleClientSecret,
        //     'redirectUri' => $config->googleRedirectUri
        // ]);
        $this->basePath = $app->getBasePath() . '/';
    }

    public function verifyAuthorization(string $redirect, Request $request): ?array
    {
        $provider = new Google([
            'clientId' => $this->config->oauthProvidersGoogleClientId,
            'clientSecret' => $this->config->oauthProvidersGoogleClientSecret,
            'redirectUri' => $redirect
        ]);
        $queryParams = $request->getQueryParams();
        if (!isset($queryParams['code']) || !isset($queryParams['state']) || $queryParams['state'] !== $_SESSION['oauth2state']) {
            unset($_SESSION['oauth2state']);
            return null;
        }
        $token = $provider->getAccessToken('authorization_code', ['code' => $queryParams['code']]);
        $user = $provider->getResourceOwner($token);
        return $user->toArray();
    }

    // public function verifyFullAuthorization(Request $request, Response $response): Response
    // {
    //     $queryParams = $request->getQueryParams();
    //     if (!isset($queryParams['code']) || !isset($queryParams['state']) || $queryParams['state'] !== $_SESSION['oauth2state']) {
    //         $message = $queryParams['msg'] ?? 'Error de autenticación.';
    //         unset($_SESSION['oauth2state']);
    //         $response->getBody()->write($message);
    //         return $response->withStatus(400);
    //     }
    //     try {
    //         $token = $this->provider->getAccessToken('authorization_code', ['code' => $queryParams['code']]);
    //         $user = $this->provider->getResourceOwner($token);
    //         $email = $user->getEmail();
    //         $_SESSION['user'] = ['name' => $user->getName(), 'email' => $email];
    //         die("UPPLASA");
    //         return $response->withHeader('Location', $this->basePath)->withStatus(302);
    //     } catch(IdentityProviderException $ex) {
    //         return $response->withHeader('Location', '?msg=Token invalido')->withStatus(302);
    //     }
    // }

    public function id(): string
    {
        return 'google';
    }

    public function buttons($redirect): array
    {
        $provider = new Google([
            'clientId' => $this->config->oauthProvidersGoogleClientId,
            'clientSecret' => $this->config->oauthProvidersGoogleClientSecret,
            'redirectUri' => $redirect
        ]);
        // No autenticado, redirigir a Google
        $authUrl = $provider->getAuthorizationUrl();
        $_SESSION['oauth2state'] = $provider->getState();
        return [ $authUrl => 'Google'];
    }
}