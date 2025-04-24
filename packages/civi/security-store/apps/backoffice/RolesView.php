<?php
namespace Civi\SecurityStoreBackoffice;

use Civi\SecurityStore\Features\Access\Rol\Gateway\RolGateway;
use Civi\SecurityStore\Features\Access\Rol\View\RolViewMetadata;
use Civi\View\Twig\MasterDetailView;
use Civi\View\ViewConfig;
use Civi\View\ViewMetadata;

class RolesView extends MasterDetailView
{
    public function __construct(ViewConfig $config, private readonly RolGateway $roles, private readonly RolViewMetadata $meta)
    {
        parent::__construct($config, 'roles', __DIR__ . '/templates');
    }

    protected function list(): array
    {
        return $this->roles->listRoles();
    }

    protected function meta(): ViewMetadata
    {
        $view = $this->meta->build();

        return $view 
            // ->markReadonly(['name', 'user'])
            //->excludeColumn('pass')
            //->addFilter('name')
            ;
    }
}