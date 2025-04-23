<?php
namespace Civi\RepomanagerBackoffice;

use Civi\Repomanager\Features\Repository\Credential\Credential;
use Civi\Repomanager\Features\Repository\Credential\Gateway\CredentialGateway;
use Civi\Repomanager\Features\Repository\Credential\View\CredentialViewMetadata;
use Civi\Repomanager\Shared\Infrastructure\Simple\FormMetadata;
use Civi\Repomanager\Shared\Infrastructure\View\ViewMetadata;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CredentialsView extends MasterDetailView
{
    public function __construct(private readonly CredentialGateway $credentials, private readonly CredentialViewMetadata $meta)
    {
    }

    protected function template(): string
    {
        return 'credentials';
    }

    protected function list(): array
    {
        return $this->credentials->listCredentials();
    }

    protected function meta(): ViewMetadata
    {
        $url = "composer.civeira.net";

        $view = $this->meta->build();

        return $view 
            // ->markReadonly(['name', 'user'])
            ->excludeColumn('pass')
            ->addFilter('name')
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