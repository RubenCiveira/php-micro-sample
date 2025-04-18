<?php
namespace Civi\RepomanagerBackoffice;

use Civi\Repomanager\Features\Repository\Access\Credential;
use Civi\Repomanager\Features\Repository\Access\Gateway\CredentialGateway;
use Civi\Repomanager\Shared\Infrastructure\Simple\FormMetadata;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CredentialsView extends MasterDetailView
{
    public function __construct(private readonly CredentialGateway $credentials)
    {
    }

    protected function template(): string
    {
        return 'credentials';
    }

    protected function delete(string $id)
    {
        $this->credentials->removeCredential($id);
    }

    protected function save(array $data)
    {
        $cred = new Credential(
            id: $data['id'],
            name: $data['name'],
            user: $data['user'],
            pass: $data['pass'],
            until: new \DateTimeImmutable($data['until'])
        );
        $this->credentials->saveCredential($cred);
    }

    protected function list(): array
    {
        return $this->credentials->listCredentials();
    }

    protected function meta(): FormMetadata
    {
        $url = "composer.civeira.net";
        return (new FormMetadata('Credenciales', 'Manejar credenciales', 'id'))
            ->addRequiredTextField('name', 'Nombre')
            ->addRequiredTextField('user', 'User')
            ->addRequiredPasswordField('pass', 'Password')
            ->addRequiedDateField('until', 'Expiration')
            ->excludeColumn('pass')
            ->markReadonly(['name', 'user'])
            ->addResumeAction('generateAuthFile', 'Generar .auth.json', '
                    JSON.stringify({
                        "http-basic": {
                            ["'.$url.'"]: {
                                "username": value.user,
                                "password": value.pass
                            }
                        }
                    }, null, 2)');
    }
}