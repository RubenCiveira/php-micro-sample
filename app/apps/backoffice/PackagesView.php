<?php
namespace Civi\RepomanagerBackoffice;

use Civi\Repomanager\Features\Repository\Package\Gateway\PackageGateway;
use Civi\View\Twig\MasterDetailListQuery;
use Civi\View\Twig\MasterDetailView;
use Civi\Micro\Schema\TypeSchemaBuilder;
use Civi\Repomanager\Features\Repository\Package\Schema\PackageEntitySchemaBuilder;
use Civi\View\ViewServices;

class PackagesView extends MasterDetailView
{
    public function __construct(ViewServices $services, private readonly PackageGateway $packages, private readonly PackageEntitySchemaBuilder $meta)
    {
        parent::__construct($services, 'packages', __DIR__ . '/templates');
    }

    protected function list(MasterDetailListQuery $query): array
    {
        return $this->packages->listPackages();
    }

    protected function meta(): TypeSchemaBuilder
    {
        $form = $this->meta->build();
        return $form
                ->addFilter('status')
                ->addFilter('url')
                ->excludeColumn('description');
    }
}