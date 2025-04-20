<?php declare(strict_types=1);

namespace Tests\Shared\Infrastructure\Service;

use Civi\Repomanager\Shared\Infrastructure\Store\Service\GraphQlEnrich;
use PHPUnit\Framework\TestCase;

class GraphQlEnrichTest extends TestCase
{
    public function test_augment_generates_expected_sections(): void
    {
        $sdl = <<<SDL
        directive @format(type: String!) on FIELD_DEFINITION | INPUT_FIELD_DEFINITION

        type Empleado {
            id: ID!
            nombre: String
            salario: Float
            nacimiento: String @format(type: "date")
        }
        SDL;

        $enricher = new GraphQlEnrich();
        $augmented = $enricher->augmentAndSave($sdl);

        $this->assertStringContainsString('input EmpleadoFilter {', $augmented);
        $this->assertStringContainsString('nombreEquals: String', $augmented);
        $this->assertStringContainsString('salarioGreaterThan: Float', $augmented);
        $this->assertStringContainsString('@format(type: "date")', $augmented);
        $this->assertStringContainsString('enum EmpleadoOrderField {', $augmented);
        $this->assertStringContainsString('input EmpleadoOrder {', $augmented);
        $this->assertStringContainsString('type Query {', $augmented);
        $this->assertStringContainsString('empleados(filter: EmpleadoFilter, order: [EmpleadoOrder!], since: EmpleadoCursor, limit: Int): [Empleado!]!', $augmented);
    }

    public function test_fields_are_detected_as_ID_for_references(): void
    {
        $sdl = <<<SDL
        type Oficina {
            id: ID!
            nombre: String
        }

        type Empleado {
            id: ID!
            nombre: String
            oficina: Oficina
        }
        SDL;

        $enricher = new GraphQlEnrich();
        $augmented = $enricher->augmentAndSave($sdl);

        $this->assertStringContainsString('oficinaEquals: ID', $augmented);
        $this->assertStringContainsString('oficinaIn: [ID]', $augmented);
    }

    public function test_order_enum_and_input_are_generated(): void
    {
        $sdl = <<<SDL
        type Producto {
            id: ID!
            nombre: String
            precio: Float
        }
        SDL;

        $enricher = new GraphQlEnrich();
        $augmented = $enricher->augmentAndSave($sdl);

        $this->assertStringContainsString('enum ProductoOrderField {', $augmented);
        $this->assertStringContainsString('input ProductoOrder {', $augmented);
        $this->assertStringContainsString('field: ProductoOrderField!', $augmented);
        $this->assertStringContainsString('direction: OrderDirection!', $augmented);
    }

    public function test_cursor_input_includes_all_fields(): void
    {
        $sdl = <<<SDL
        type Provincia {
            id: ID!
            nombre: String
        }
        SDL;

        $enricher = new GraphQlEnrich();
        $augmented = $enricher->augmentAndSave($sdl);

        $this->assertStringContainsString('input ProvinciaCursor {', $augmented);
        $this->assertStringContainsString('id: ID', $augmented);
        $this->assertStringContainsString('nombre: String', $augmented);
    }

    public function test_create_and_update_mutations_are_generated(): void
    {
        $sdl = <<<SDL
        directive @mutation(create: Boolean = true, update: Boolean = true, delete: Boolean = false) on OBJECT

        type Cliente @mutation(delete: false) {
            id: ID!
            nombre: String
            direccion: String
        }
        SDL;

        $enricher = new GraphQlEnrich();
        $augmented = $enricher->augmentAndSave($sdl);

        $this->assertStringContainsString('input ClienteCreateInput {', $augmented);
        $this->assertStringContainsString('nombre: String', $augmented);
        $this->assertStringContainsString('direccion: String', $augmented);
        $this->assertStringContainsString('clienteCreate(input: ClienteCreateInput!): Cliente!', $augmented);

        $this->assertStringContainsString('input ClienteUpdateInput {', $augmented);
        $this->assertStringContainsString('clienteUpdate(id: ID!, input: ClienteUpdateInput!): Cliente!', $augmented);

        $this->assertStringNotContainsString('clienteDelete(id: ID!): Cliente!', $augmented);
    }

    public function test_extra_mutation_generates_input_and_mutation(): void
    {
        $sdl = <<<SDL
        directive @mutation(
            create: Boolean = true, update: Boolean = true, delete: Boolean = true, extra: [String!] = []
        ) on OBJECT

        type Dispositivo @mutation(create: false, update: false, delete: false, extra: ["activar: assign = [usuario], set = { activo: true }, context = modify"]) {
            id: ID!
            nombre: String
            usuario: String
            activo: Boolean
        }
        SDL;

        $enricher = new GraphQlEnrich();
        $augmented = $enricher->augmentAndSave($sdl);

        $this->assertStringContainsString('input DispositivoActivarInput {', $augmented);
        $this->assertStringContainsString('usuario: String', $augmented);
        $this->assertStringContainsString('dispositivoActivar(id: ID!, input: DispositivoActivarInput!): Dispositivo!', $augmented);
    }

    public function test_mutation_with_only_set_fields(): void
    {
        $sdl = <<<SDL
        directive @mutation(
            create: Boolean = true, update: Boolean = true, delete: Boolean = true, extra: [String!] = []
        ) on OBJECT

        type Documento @mutation(extra: ["archivar: set = { estado: archivado }, assign = [titulo], context = modify"]) {
            id: ID!
            titulo: String
            estado: String
        }
        SDL;

        $enricher = new GraphQlEnrich();
        $augmented = $enricher->augmentAndSave($sdl);

        $this->assertStringContainsString('input DocumentoArchivarInput {', $augmented);
        $this->assertStringContainsString('documentoArchivar(id: ID!, input: DocumentoArchivarInput!): Documento!', $augmented);
    }
}
