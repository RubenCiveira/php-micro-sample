<?php
namespace Civi\RepomanagerBackoffice;

use Civi\Repomanager\Features\Repository\Access\Credential;
use Civi\Repomanager\Features\Repository\Access\Gateway\CredentialGateway;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CredentialsView
{
    public function __construct(private readonly CredentialGateway $credentials)
    {
    }

    public function post(Request $request, Response $response, array $args): Response
    {
        $data = $request->getParsedBody();
        if( isset($data['delete'] ) ) {
            $this->credentials->removeCredential( $data['delete'] );
        } else {
            $cred = new Credential(name: $data['name'], user: $data['user'], pass: $data['pass'], until: new \DateTimeImmutable($data['expiration'])) ;
            $this->credentials->saveCredential($cred);
        }
        return BaseView::redirect('credentials', $request, $response);
    }

    public function get(Request $request, Response $response, array $args): Response 
    {
        $all = $this->credentials->listCredentials();
        $context = [
            'url' => "composer.civeira.net",
            'credentials' => $all];
        return BaseView::template('credentials', $context, $request, $response);
    }
}