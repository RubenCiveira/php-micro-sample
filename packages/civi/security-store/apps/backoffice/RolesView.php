<?php
namespace Civi\SecurityStoreBackoffice;

use Civi\SecurityStore\Features\Access\Rol\Gateway\RolGateway;
use Civi\SecurityStore\Features\Access\Rol\RolEntitySchema;
use Civi\View\Twig\MasterDetailListQuery;
use Civi\View\Twig\MasterDetailView;
use Civi\View\ViewConfig;
use Civi\Micro\Schema\EntitySchema;

class RolesView extends MasterDetailView
{
    public function __construct(ViewConfig $config, private readonly RolGateway $roles, private readonly RolEntitySchema $meta)
    {
        parent::__construct($config, 'roles', __DIR__ . '/templates');
    }

    protected function list(MasterDetailListQuery $query): array
    {
        return $this->roles->listRoles();
    }

    protected function meta(): EntitySchema
    {
        $view = $this->meta->build();

        return $view 
            // ->markReadonly(['name', 'user'])
            //->excludeColumn('pass')
            //->addFilter('name')
            ;
    }
}