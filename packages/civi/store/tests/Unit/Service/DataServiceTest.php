<?php

declare(strict_types=1);

namespace Tests\Shared\Infrastructure\Gateway;

use Civi\Store\Service\DataService;
use Civi\Store\DataQueryParam;
use Civi\Store\Service\ExecPipeline;
use Civi\Store\Service\RestrictionPipeline;
use Civi\Security\Guard\AccessGuard;
use Civi\Security\Redaction\OutputRedactor;
use Civi\Security\Sanitization\InputSanitizer;
use Civi\Security\UnauthorizedException;
use Civi\Store\Gateway\DataGateway;
use GraphQL\Type\Schema;
use GraphQL\Utils\BuildSchema;
use PHPUnit\Framework\TestCase;

class DataServiceTest extends TestCase
{
    private Schema $schema;
    private DataService $adapter;

    private $allow = true;
    private $accessPipeline;
    private $execPipeline;
    private $guardMock;
    private $inputMock;
    private $outputMock;
    private $gatewayMock;

    protected function setUp(): void
    {
        $sdl = file_get_contents(__DIR__ . '/../../Resources/store/schema_full.graphql');
        $this->schema = BuildSchema::build($sdl);

        $this->accessPipeline = $this->createMock(RestrictionPipeline::class);
        $this->accessPipeline->method('restrictFilter')->willReturnCallback(fn(...$args) => $args[2]);
        $this->execPipeline = $this->createMock(ExecPipeline::class);
        $this->execPipeline->method('executeOperation')->willReturnCallback(function (...$args) {
            if (is_callable($args[3])) {
                $args[3]($args[4]);
            }
            return $args[4];
        });
        $this->guardMock = $this->createMock(AccessGuard::class);
        $this->guardMock->method('canExecute')->willReturnCallback(function(...$args){ 
            return $this->allow; 
        });
        $this->inputMock = $this->createMock(InputSanitizer::class);
        $this->inputMock->method('sanitizeInput')->willReturnCallback(fn(...$args) => $args[2]);
        $this->outputMock = $this->createMock(OutputRedactor::class);
        $this->outputMock->method('filterOutput')->willReturnCallback(fn(...$args) => $args[2]);
        $this->gatewayMock = $this->createMock(DataGateway::class);

        $this->adapter = new DataService(
            $this->gatewayMock, 
            $this->accessPipeline, 
            $this->execPipeline, 
            $this->guardMock, 
            $this->inputMock, 
            $this->outputMock);
        /*
private readonly DataGateway $gateway,
        private readonly RestrictionPipeline $restrictor,
        private readonly ExecPipeline $execPipeline,
        private readonly AccessGuard $guard,
        private readonly InputSanitizer $sanitizer,
        private readonly OutputRedactor $redactor,
        */
    }

    public function testCreateShouldSaveData()
    {
        $this->gatewayMock->expects($this->once())
            ->method('save')
            ->with('namespace', 'typeName', 'id123', ['id' => 'id123', 'foo' => 'bar']);

        $result = $this->adapter->create('namespace', 'typeName', 'id', 'create', ['id' => 'id123', 'foo' => 'bar']);

        $this->assertIsArray($result);
        $this->assertEquals([['id' => 'id123', 'foo' => 'bar']], $result);
    }

    public function testModifyShouldUpdateExistingData()
    {
        $this->gatewayMock->expects($this->exactly(1))
            ->method('read')
            ->willReturn([['id' => 'id123', 'foo' => 'bar']]);

        $this->gatewayMock->expects($this->once())
            ->method('save')
            ->with('namespace', 'typeName', 'id123', ['id' => 'id123', 'foo' => 'baz']);

        $filters = new DataQueryParam($this->schema, 'Empleado', []);
        $result = $this->adapter->modify('namespace', 'typeName', 'id', 'update', $filters, ['foo' => 'baz']);

        $this->assertCount(1, $result);
        $this->assertEquals(['id' => 'id123', 'foo' => 'baz'], $result[0]);
    }

    public function testDeleteShouldCallGatewayDelete()
    {
        $readed = [['id' => 'id123']];

        $this->gatewayMock->expects($this->once())
            ->method('read')
            ->willReturn($readed);

        $this->gatewayMock->expects($this->once())
            ->method('delete')
            ->with(
                'namespace',
                'typeName',
                $readed[0],
                'id'
            );

        $filters = new DataQueryParam($this->schema, 'Empleado', []);
        $this->adapter->delete('namespace', 'typeName', 'id', 'delete', $filters);
    }

    public function testFetchShouldReturnRedactedData()
    {
        $this->gatewayMock->expects($this->once())
            ->method('read')
            ->willReturn([['id' => 'id123', 'foo' => 'bar']]);

        $filters = new DataQueryParam($this->schema, 'Empleado', []);
        $result = $this->adapter->fetch('namespace', 'typeName', $filters);

        $this->assertCount(1, $result);
        $this->assertEquals(['id' => 'id123', 'foo' => 'bar'], $result[0]);
    }

    public function testCreateShouldThrowUnauthorizedException()
    {
        $this->allow = false;

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('Not allowed to create onver namespace:typeName');

        $this->adapter->create('namespace', 'typeName', 'id', 'create', ['id' => 'id123', 'foo' => 'bar']);
    }
}
