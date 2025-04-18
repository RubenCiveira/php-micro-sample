<?php
namespace Civi\RepomanagerBackoffice;

use Civi\Repomanager\Features\Repository\Package\Gateway\PackageGateway;
use Civi\Repomanager\Features\Repository\Package\Package;
use Civi\Repomanager\Shared\Infrastructure\Simple\FormMetadata;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Ramsey\Uuid\Uuid;

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

    protected function save(array $data)
    {
        $pack = new Package(
            id: $data['id'],
            name: $data['name'],
            url: $data['url'],
            type: $data['type'],
            status: $data['status'],
            description: $data['description']
        );
        $this->packages->savePackage($pack);
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
                ->excludeColumn('description');
    }

    /*
    public function post(Request $request, Response $response, array $args): Response
    {
        $data = $request->getParsedBody();
        if (isset($data['delete'])) {
            $this->packages->removePackage($data['delete']);
        } else {
            if (!$data['id']) {
                $data['id'] = Uuid::uuid4()->toString();
            }
            $pack = new Package(
                id: $data['id'],
                name: $data['name'],
                url: $data['url'],
                type: $data['type'],
                status: $data['status'],
                description: $data['description']
            );
            $this->packages->savePackage($pack);
        }
        return BaseView::redirect('packages', $request, $response);
    }
    public function get(Request $request, Response $response, array $args): Response
    {
        $context = [
            'packageTypes' => [
                'website' => 'Website',
                'composer' => 'Composer'
            ],
            'statusTypes' => [
                'active' => 'Activo',
                'deprecated' => 'Obsoleto',
                'pending' => 'Pendiente'
            ],
            'packages' => $this->packages->listPackages()
        ];
        return BaseView::template('packages', $context, $request, $response);
    }
    */
}