<?php
namespace Civi\RepomanagerBackoffice;

use Civi\Repomanager\Features\Repository\Credential\Gateway\CredentialGateway;
use Civi\View\Twig\MasterDetailListQuery;
use Civi\View\Twig\MasterDetailView;
use Civi\Micro\Schema\TypeSchemaBuilder;
use Civi\Repomanager\Features\Repository\Credential\Schema\CredentialEntitySchemaBuilder;
use Civi\View\ViewServices;

class CredentialsView extends MasterDetailView
{
    public function __construct(ViewServices $services, private readonly CredentialGateway $credentials, private readonly CredentialEntitySchemaBuilder $meta)
    {
        parent::__construct($services, 'credentials', __DIR__ . '/templates');
    }

    protected function list(MasterDetailListQuery $query): array
    {
        return $this->credentials->listCredentials();
    }

    protected function meta(): TypeSchemaBuilder
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