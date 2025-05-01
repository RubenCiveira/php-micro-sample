<?php
namespace Civi\SecurityStoreBackoffice;

use Civi\SecurityStore\Features\Access\Rol\Gateway\RolGateway;
use Civi\View\Twig\MasterDetailListQuery;
use Civi\View\Twig\MasterDetailView;
use Civi\Micro\Schema\TypeSchemaBuilder;
use Civi\SecurityStore\Features\Access\Rol\Schema\RolEntitySchemaBuilder;
use Civi\View\ViewServices;

class RolesView extends MasterDetailView
{
    public function __construct(ViewServices $services, private readonly RolGateway $roles, private readonly RolEntitySchemaBuilder $meta)
    {
        parent::__construct($services, 'roles', __DIR__ . '/templates');
    }

    protected function list(MasterDetailListQuery $query): array
    {
        return $this->roles->listRoles();
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