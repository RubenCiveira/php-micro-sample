<?php
namespace Civi\SecurityStoreBackoffice;

use Civi\SecurityStore\Features\Access\User\Gateway\UserGateway;
use Civi\View\Twig\MasterDetailListQuery;
use Civi\View\Twig\MasterDetailView;
use Civi\Micro\Schema\TypeSchemaBuilder;
use Civi\SecurityStore\Features\Access\User\Schema\UserEntitySchemaBuilder;
use Civi\View\ViewServices;

class UsersView extends MasterDetailView
{
    public function __construct(ViewServices $services, private readonly UserGateway $users, private readonly UserEntitySchemaBuilder $meta)
    {
        parent::__construct($services, 'users', __DIR__ . '/templates');
    }

    protected function list(MasterDetailListQuery $query): array
    {
        return $this->users->listUsers($query->query, $query->include);
    }

    protected function meta(): TypeSchemaBuilder
    {
        $view = $this->meta->build();

        return $view 
            // ->markReadonly(['name', 'user'])
            //->excludeColumn('pass')
            //->addFilter('name')
            ;
    }
}