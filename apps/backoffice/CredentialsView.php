<?php
namespace Civi\RepomanagerBackoffice;

use Civi\Repomanager\Features\Repository\Credential\Gateway\CredentialGateway;
use Civi\Repomanager\Features\Repository\Credential\View\CredentialViewMetadata;
use Civi\Repomanager\Shared\Infrastructure\View\Twig\MasterDetailView;
use Civi\Repomanager\Shared\Infrastructure\View\ViewMetadata;

class CredentialsView extends MasterDetailView
{
    public function __construct(private readonly CredentialGateway $credentials, private readonly CredentialViewMetadata $meta)
    {
        parent::__construct('credentials', __DIR__ . '/templates');
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