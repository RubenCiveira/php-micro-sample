<?php
namespace Civi\RepomanagerBackoffice;

use Civi\Repomanager\Features\Repository\Package\Gateway\PackageGateway;
use Civi\Repomanager\Features\Repository\Package\Package;
use Civi\Repomanager\Shared\Infrastructure\Simple\FormMetadata;

class PackagesView extends MasterDetailView
{
    public function __construct(private readonly PackageGateway $packages)
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
        $pack = new Package(
            id: $data['id'],
            name: $data['name'],
            url: $data['url'],
            type: $data['type'],
            status: $data['status'],
            description: $data['description']
        );
        $this->packages->createPackage($pack);
    }

    protected function update(string $id, $data)
    {
        $pack = new Package(
            id: $data['id'],
            name: $data['name'],
            url: $data['url'],
            type: $data['type'],
            status: $data['status'],
            description: $data['description']
        );
        $this->packages->updatePackage($id, $pack);
    }

    protected function list(): array
    {
        return $this->packages->listPackages();
    }

    protected function meta(): FormMetadata
    {
        return (new FormMetadata('Paquetes', 'Manejar paquetes', 'id'))
                ->addRequiredTextField('name', 'Nombre')
                ->addRequiredTextField('url', 'Url')
                ->addRequiredOptionsField('type', 'Type', [
                    'website' => 'Website',
                    'composer' => 'Composer'
                ])
                ->addRequiredOptionsField('status', 'Status', [
                    'active' => 'Activo',
                    'deprecated' => 'Obsoleto',
                    'pending' => 'Pendiente'
                ])
                ->addTextareaField('description', 'Description')
                ->addFilter('status')
                ->addFilter('url')
                ->markCalculated(['status'])
                ->excludeColumn('description');
    }
}