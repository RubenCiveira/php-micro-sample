<?php declare(strict_types=1);

namespace Tests\Shared\Infrastructure\Gateway;

use Civi\Repomanager\Shared\Infrastructure\Store\Gateway\DataGateway;
use Civi\Repomanager\Shared\Infrastructure\Store\DataQueryParam;
use Civi\Repomanager\Shared\Infrastructure\Store\Service\ExecPipeline;
use Civi\Repomanager\Shared\Infrastructure\Store\Service\RestrictionPipeline;
use Civi\Repomanager\Shared\Security\Guard\AccessGuard;
use Civi\Repomanager\Shared\Security\Redaction\OutputRedactor;
use Civi\Repomanager\Shared\Security\Sanitization\InputSanitizer;
use GraphQL\Type\Schema;
use GraphQL\Utils\BuildSchema;
use PHPUnit\Framework\TestCase;

class DataFileAdapterTest extends TestCase
{
    private Schema $schema;
    private DataGateway $adapter;

    protected function setUp(): void
    {
        $sdl = file_get_contents(__DIR__ . '/../../../../../mock/schema_full.graphql');
        $accessPipeline = $this->createMock(RestrictionPipeline::class);
        $accessPipeline->method('restrictFilter')->willReturnCallback(fn(...$args) => $args[2]);
        $execPipeline = $this->createMock(ExecPipeline::class);
        $execPipeline->method('executeOperation')->willReturnCallback(fn(...$args) => $args[4]);
        $this->schema = BuildSchema::build($sdl);
        $guardMock = $this->createMock(AccessGuard::class);
        $guardMock->method('canExecute')->willReturn(true);
        $inputMock = $this->createMock( InputSanitizer::class );
        $inputMock->method('sanitizeInput')->willReturnCallback(fn(...$args) => $args[2]);
        $outputMock = $this->createMock( OutputRedactor::class );
        $outputMock->method('filterOutput')->willReturnCallback(fn(...$args) => $args[2]);

        $this->adapter = new DataGateway( $accessPipeline, $execPipeline, $guardMock, $inputMock, $outputMock, __DIR__ . '/../../../../../mock/' );
    }

    public function test_filter_by_provincia_nombre(): void
    {
        $args = [
            'filter' => [
                 'oficinaProvinciaNombreEquals' => 'Galicia'
            ]
        ];

        $param = new DataQueryParam($this->schema, 'Empleado', $args);
        $result = $this->adapter->fetch('schema_full', 'Empleado', $param);

        $this->assertIsArray($result);
        $this->assertEQuals(35, count($result));
        foreach ($result as $empleado) {
            $this->assertEquals('Galicia', $empleado['oficina']['provincia']['nombre']);
        }
    }

    public function test_filter_by_nombre_like(): void
    {
        $args = [
            'filter' => [
                'nombreLike' => '4'
            ]
        ];

        $param = new DataQueryParam($this->schema, 'Empleado', $args);
        $result = $this->adapter->fetch('schema_full', 'Empleado', $param);

        $this->assertIsArray($result);
        $this->assertEQuals(19, count($result));
        foreach ($result as $empleado) {
            $this->assertStringContainsString('Empleado', $empleado['nombre']);
        }
    }

    public function test_filter_by_salario_between(): void
    {
        $args = [
            'filter' => [
                'salarioBetween' => '30000,70000'
            ]
        ];

        $param = new DataQueryParam($this->schema, 'Empleado', $args);
        $result = $this->adapter->fetch('schema_full', 'Empleado', $param);

        $this->assertIsArray($result);
        $this->assertEQuals(45, count($result));
        foreach ($result as $empleado) {
            $this->assertGreaterThanOrEqual(30000, $empleado['salario']);
            $this->assertLessThanOrEqual(70000, $empleado['salario']);
        }
    }

    public function test_filter_by_id_in(): void
    {
        $args = [
            'filter' => [
                'idIn' => '1,2,3'
            ]
        ];

        $param = new DataQueryParam($this->schema, 'Empleado', $args);
        $result = $this->adapter->fetch('schema_full', 'Empleado', $param);

        $this->assertIsArray(actual: $result);
        $this->assertEQuals(3, count($result));
        foreach ($result as $empleado) {
            $this->assertContains($empleado['id'], ['1', '2', '3']);
        }
    }

    public function test_filter_by_fecha_ingreso_after(): void
    {
        $args = [
            'filter' => [
                'fechaIngresoGreaterThan' => '2022-01-01'
            ]
        ];

        $param = new DataQueryParam($this->schema, 'Empleado', $args);
        $result = $this->adapter->fetch('schema_full', 'Empleado', $param);

        $this->assertIsArray($result);
        $this->assertEQuals(40, count($result));
        foreach ($result as $empleado) {
            $this->assertGreaterThan('2022-01-01', $empleado['fechaIngreso']);
        }
    }

    public function test_filter_by_salario_gt(): void
    {
        $args = [
            'filter' => [
                'salarioGreaterThan' => 50000
            ]
        ];

        $param = new DataQueryParam($this->schema, 'Empleado', $args);
        $result = $this->adapter->fetch('schema_full', 'Empleado', $param);

        $this->assertIsArray($result);
        $this->assertEQuals(58, count($result));
        foreach ($result as $empleado) {
            $this->assertGreaterThan(50000, $empleado['salario']);
        }
    }

    public function test_filter_by_salario_lte(): void
    {
        $args = [
            'filter' => [
                'salarioLessThanEqual' => 50000
            ]
        ];
        $param = new DataQueryParam($this->schema, 'Empleado', $args);
        $result = $this->adapter->fetch('schema_full', 'Empleado', $param);

        $this->assertIsArray($result);
        $this->assertEQuals(42, count($result));
        foreach ($result as $empleado) {
            $this->assertLessThanOrEqual(50000, $empleado['salario']);
        }
    }
}
