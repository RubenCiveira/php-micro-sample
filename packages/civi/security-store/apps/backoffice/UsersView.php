<?php
namespace Civi\SecurityStoreBackoffice;

use Civi\SecurityStore\Features\Access\User\Gateway\UserGateway;
use Civi\SecurityStore\Features\Access\User\Schema\UserEntitySchema;
use Civi\View\Twig\MasterDetailListQuery;
use Civi\View\Twig\MasterDetailView;
use Civi\View\ViewConfig;
use Civi\Micro\Schema\EntitySchema;

class UsersView extends MasterDetailView
{
    public function __construct(ViewConfig $config, private readonly UserGateway $users, private readonly UserEntitySchema $meta)
    {
        parent::__construct($config, 'users', __DIR__ . '/templates');
    }

    protected function list(MasterDetailListQuery $query): array
    {
        return $this->users->listUsers($query->query, $query->include);
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