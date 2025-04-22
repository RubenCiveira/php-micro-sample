<?php declare(strict_types=1);

namespace Tests\Shared\Infrastructure\Service;

use Civi\Repomanager\Shared\Infrastructure\Store\Service\ExtractMutation;
use PHPUnit\Framework\TestCase;

class ExtractMutationTest extends TestCase
{
    public function test_extracts_default_mutations()
    {
        $sdl = '
        directive @mutation(
            create: Boolean = true,
            update: Boolean = true,
            delete: Boolean = true,
            extra: [String!] = []
        ) on OBJECT

        type Empleado @mutation {
            id: ID!
            nombre: String!
        }';

        $mutations = (new ExtractMutation())->fromSchema($sdl);

        $this->assertArrayHasKey('Empleado', $mutations);
        $this->assertCount(3, $mutations['Empleado']);

        $names = array_column($mutations['Empleado'], 'name');
        $this->assertContains('create', $names);
        $this->assertContains('update', $names);
        $this->assertContains('delete', $names);
    }

    public function test_extracts_extra_mutation_assign()
    {
        $sdl = '
        directive @mutation(create: Boolean = true, update: Boolean = true, delete: Boolean = true, extra: [String!] = []) on OBJECT

        type Empleado @mutation(extra: ["asignar: assign = [oficina]"]) {
            id: ID!
            nombre: String!
            oficina: String
        }';

        $mutations = (new ExtractMutation())->fromSchema($sdl);

        $extra = array_filter($mutations['Empleado'], fn($m) => $m['name'] === 'asignar');
        $this->assertNotEmpty($extra);
        $this->assertEquals(['oficina'], array_keys( array_values($extra)[0]['assign']) );
    }

    public function test_extracts_extra_mutation_set()
    {
        $sdl = '
        directive @mutation(create: Boolean = true, update: Boolean = true, delete: Boolean = true, extra: [String!] = []) on OBJECT

        type Oficina @mutation(extra: ["habilitar: set = { desabilitado: false }"]) {
            id: ID!
            nombre: String!
            desabilitado: Boolean
        }';

        $mutations = (new ExtractMutation())->fromSchema($sdl);

        $extra = array_filter($mutations['Oficina'], fn($m) => $m['name'] === 'habilitar');
        $this->assertNotEmpty($extra);
        $this->assertFalse(array_values($extra)[0]['set']['desabilitado']);
    }

    public function test_extracts_extra_mutation_set_and_assign_and_context()
    {
        $sdl = '
        directive @mutation(create: Boolean = true, update: Boolean = true, delete: Boolean = true, extra: [String!] = []) on OBJECT

        type Oficina @mutation(extra: ["activar: assign = [usuario], set = { activo: true }, context = \"modify\""]) {
            id: ID!
            nombre: String!
            usuario: String
            activo: Boolean
        }';

        $mutations = (new ExtractMutation())->fromSchema($sdl);

        $extra = array_filter($mutations['Oficina'], fn($m) => $m['name'] === 'activar');
        $this->assertNotEmpty($extra);
        $mut = array_values($extra)[0];
        $this->assertEquals("modify", $mut['context'], );
        $this->assertEquals(['usuario'], array_keys($mut['assign']));
        $this->assertTrue($mut['set']['activo']);
    }
}
