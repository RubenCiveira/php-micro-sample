<?php
namespace Civi\RepomanagerBackoffice;

use Civi\Repomanager\Features\Repository\Package\View\PackageViewMetadata;
use Civi\Repomanager\Features\Repository\Package\Gateway\PackageGateway;
use Civi\Repomanager\Shared\Infrastructure\View\Twig\MasterDetailView;
use Civi\Repomanager\Shared\Infrastructure\View\ViewMetadata;

class PackagesView extends MasterDetailView
{
    public function __construct(private readonly PackageGateway $packages, private readonly PackageViewMetadata $meta)
    {
        parent::__construct('packages', __DIR__ . '/templates');
    }

    protected function list(): array
    {
        return $this->packages->listPackages();
    }

    protected function meta(): ViewMetadata
    {
        $form = $this->meta->build();
        return $form
                ->addFilter('status')
                ->addFilter('url')
                ->excludeColumn('description');
    }
}