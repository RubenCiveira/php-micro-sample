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
use Civi\Store\Gateway\DataGateway;
use Civi\Store\SchemaMetadata;
use GraphQL\Type\Schema;
use GraphQL\Utils\BuildSchema;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class DataGatewayTest extends TestCase
{
    private Schema $schema;
    private DataGateway $adapter;

    protected function setUp(): void
    {
        $sdl = file_get_contents(__DIR__ . '/../../Resources/store/schema_full.graphql');
        $this->schema = BuildSchema::build($sdl);
        $this->adapter = new DataGateway(__DIR__ . '/../../Resources/store/');
        // $this->migrate(__DIR__ . '/../../Resources/store/', 'schema_full', 'Empleado');
        // $this->migrate(__DIR__ . '/../../Resources/store/', 'schema_full', 'Oficina');
        // $this->migrate(__DIR__ . '/../../Resources/store/', 'schema_full', 'Provincia');
    }

    public function migrate($baseDir, $namespace, $typeName)
    {
        $sourcePath = "{$baseDir}/{$namespace}/{$typeName}";

        if (!is_dir($sourcePath)) {
            throw new RuntimeException("El directorio no existe: $sourcePath");
        }

        // Recorrer todos los archivos JSON
        foreach (glob("$sourcePath/*.json") as $filePath) {
            $basename = basename($filePath);

            if (str_starts_with($basename, 'index_') || $basename === '.index.lock') {
                continue;
            }

            $data = json_decode(file_get_contents($filePath), true);
            if (!is_array($data)) {
                echo "Saltando archivo inválido: $basename\n";
                continue;
            }

            // Usar md5 predecible del nombre (sin extensión)
            $oldId = (int) basename($filePath, '.json');
            $newId = substr(md5((string)$oldId), 0, 8);

            // Actualizar el campo 'id' en el contenido
            $data['id'] = $newId;
            if( isset($data['oficina_id']) ) {
                $data['oficina_id'] = substr(md5((string)$data['oficina_id']), 0, 8);
            }
            if( isset($data['provincia_id']) ) {
                $data['provincia_id'] = substr(md5((string)$data['provincia_id']), 0, 8);
            }

            // Nueva estructura de carpetas
            $prefix1 = substr($newId, 0, 2);
            $prefix2 = substr($newId, 2, 2);

            $newDir = "{$sourcePath}/data/{$prefix1}/{$prefix2}";
            if (!is_dir($newDir)) {
                mkdir($newDir, 0755, true);
            }

            $newPath = "{$newDir}/{$newId}.json";

            // Guardar el nuevo archivo
            file_put_contents($newPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            // Borrar el archivo original
            unlink($filePath);

            echo "Migrado {$basename} → {$newPath}\n";
        }

        echo "✅ Migración completa.\n";
    }

    public function test_filter_by_provincia_nombre(): void
    {
        $args = [
            'filter' => [
                'oficinaProvinciaNombreEquals' => 'Galicia'
            ]
        ];
        $meta = new SchemaMetadata('id', []);
        $param = new DataQueryParam($this->schema, 'Empleado', $args);
        $result = $this->adapter->read('schema_full', 'Empleado', $meta, $param);

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

        $meta = new SchemaMetadata('id', []);
        $param = new DataQueryParam($this->schema, 'Empleado', $args);
        $result = $this->adapter->read('schema_full', 'Empleado', $meta, $param);

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

        $meta = new SchemaMetadata('id', []);
        $param = new DataQueryParam($this->schema, 'Empleado', $args);
        $result = $this->adapter->read('schema_full', 'Empleado', $meta, $param);

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
                'idIn' => '1c383cd3,4e732ced,6ea9ab1b'
            ]
        ];

        $meta = new SchemaMetadata('id', []);
        $param = new DataQueryParam($this->schema, 'Empleado', $args);
        $result = $this->adapter->read('schema_full', 'Empleado', $meta, $param);

        $this->assertIsArray(actual: $result);
        $this->assertEQuals(3, count($result));
        foreach ($result as $empleado) {
            $this->assertContains($empleado['id'], ['1c383cd3', '4e732ced', '6ea9ab1b']);
        }
    }

    public function test_filter_by_fecha_ingreso_after(): void
    {
        $args = [
            'filter' => [
                'fechaIngresoGreaterThan' => '2022-01-01'
            ]
        ];

        $meta = new SchemaMetadata('id', []);
        $param = new DataQueryParam($this->schema, 'Empleado', $args);
        $result = $this->adapter->read('schema_full', 'Empleado', $meta, $param);

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

        $meta = new SchemaMetadata('id', []);
        $param = new DataQueryParam($this->schema, 'Empleado', $args);
        $result = $this->adapter->read('schema_full', 'Empleado', $meta, $param);

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

        $meta = new SchemaMetadata('id', []);
        $param = new DataQueryParam($this->schema, 'Empleado', $args);
        $result = $this->adapter->read('schema_full', 'Empleado', $meta, $param);

        $this->assertIsArray($result);
        $this->assertEQuals(42, count($result));
        foreach ($result as $empleado) {
            $this->assertLessThanOrEqual(50000, $empleado['salario']);
        }
    }
}
