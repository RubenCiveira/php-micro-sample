<?php
namespace Civi\RepomanagerBackoffice;

use Civi\Repomanager\Features\Repository\Package\Schema\PackageEntitySchema;
use Civi\Repomanager\Features\Repository\Package\Gateway\PackageGateway;
use Civi\View\Twig\MasterDetailListQuery;
use Civi\View\Twig\MasterDetailView;
use Civi\View\ViewConfig;
use Civi\Micro\Schema\EntitySchema;

class PackagesView extends MasterDetailView
{
    public function __construct(ViewConfig $config, private readonly PackageGateway $packages, private readonly PackageEntitySchema $meta)
    {
        parent::__construct($config, 'packages', __DIR__ . '/templates');
    }

    protected function list(MasterDetailListQuery $query): array
    {
        return $this->packages->listPackages();
    }

    protected function meta(): EntitySchema
    {
        $form = $this->meta->build();
        return $form
                ->addFilter('status')
                ->addFilter('url')
                ->excludeColumn('description');
    }
}