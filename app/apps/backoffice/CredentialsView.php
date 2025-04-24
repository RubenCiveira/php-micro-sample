<?php
namespace Civi\RepomanagerBackoffice;

use Civi\Repomanager\Features\Repository\Credential\Gateway\CredentialGateway;
use Civi\Repomanager\Features\Repository\Credential\View\CredentialViewMetadata;
use Civi\View\Twig\MasterDetailView;
use Civi\View\ViewConfig;
use Civi\View\ViewMetadata;

class CredentialsView extends MasterDetailView
{
    public function __construct(ViewConfig $config, private readonly CredentialGateway $credentials, private readonly CredentialViewMetadata $meta)
    {
        parent::__construct($config, 'credentials', __DIR__ . '/templates');
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