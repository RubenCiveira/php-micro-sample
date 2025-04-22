<?php
namespace Civi\RepomanagerBackoffice;

use Civi\Repomanager\Features\Repository\Package\View\PackageViewMetadata;
use Civi\Repomanager\Features\Repository\Package\Gateway\PackageGateway;
use Civi\Repomanager\Features\Repository\Package\Package;
use Civi\Repomanager\Shared\Infrastructure\View\ViewMetadata;

class PackagesView extends MasterDetailView
{
    public function __construct(private readonly PackageGateway $packages, private readonly PackageViewMetadata $meta)
    {
    }

    protected function template(): string
    {
        return 'packages';
    }

    protected function delete(string $id) 
    {
        $this->packages->removePackage( $id );
    }

    protected function create(array $data)
    {
        $pack = Package::from($data);
        $this->packages->createPackage($pack);
    }

    protected function update(string $id, $data)
    {
        $pack = Package::from($data);
        $this->packages->updatePackage($id, $pack);
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