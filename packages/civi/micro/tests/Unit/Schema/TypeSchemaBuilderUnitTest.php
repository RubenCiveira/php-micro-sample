<?php

declare(strict_types=1);

namespace Civi\Micro\Schema\Tests;

use Civi\Micro\Schema\ActionSchemaBuilder;
use Civi\Micro\Schema\TypeSchemaBuilder;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class TypeSchemaBuilderUnitTest extends TestCase
{
    private TypeSchemaBuilder $schema;

    protected function setUp(): void
    {
        $this->schema = new TypeSchemaBuilder('TestEntity', 'Entity Title', 'id');
    }

    public function testExportWithoutColumns(): void
    {
        $this->schema->addField('field1', ['label' => 'Field 1']);
        $exported = $this->schema->export();

        $this->assertSame('TestEntity', $exported['title']);
        $this->assertSame('Entity Title', $exported['description']);
        $this->assertSame('id', $exported['id']);
        $this->assertArrayHasKey('field1', $exported['fields']);
        $this->assertArrayHasKey('field1', $exported['columns']);
        $this->assertEmpty($exported['filters']);
        $this->assertEmpty($exported['actions']);
    }

    public function testExportWithExplicitColumns(): void
    {
        $this->schema->addColumn('col1', 'Column 1');
        $exported = $this->schema->export();
        $this->assertArrayHasKey('col1', $exported['columns']);
        $this->assertSame('Column 1', $exported['columns']['col1']['label']);
    }

    public function testExcludeColumn(): void
    {
        $this->schema->addField('field1', ['label' => 'Field 1']);
        $this->schema->excludeColumn('field1');
        $exported = $this->schema->export();

        $this->assertArrayNotHasKey('field1', $exported['columns']);
    }

    public function testAddContextualConfirmAction(): void
    {
        $callback = function () {};
        $this->schema->addContextualConfirmAction('delete', 'Delete', $callback);
        $exported = $this->schema->export();
        $this->assertArrayHasKey('delete', $exported['actions']);
        $this->assertTrue($exported['actions']['delete']['contextual']);
        $this->assertSame('danger', $exported['actions']['delete']['kind']);
    }

    public function testAddStandaloneFormActionWithActionSchemaBuilder(): void
    {
        $form = new ActionSchemaBuilder();
        $form->addField('username', []);
        $this->schema->addStandaloneFormAction('register', 'Register', $form, function () {});
        $exported = $this->schema->export();

        $this->assertArrayHasKey('register', $exported['actions']);
        $this->assertSame('success', $exported['actions']['register']['kind']);
    }

    public function testAddStandaloneFormActionWithArray(): void
    {
        $this->schema->addField('email', ['label' => 'Email']);
        $this->schema->addStandaloneFormAction('invite', 'Invite', ['email'], function () {});
        $exported = $this->schema->export();

        $this->assertArrayHasKey('invite', $exported['actions']);
        $this->assertSame('success', $exported['actions']['invite']['kind']);
        $this->assertArrayHasKey('email', $exported['actions']['invite']['form']);
    }

    public function testAddContextualFormActionWithActionSchemaBuilder(): void
    {
        $form = new ActionSchemaBuilder();
        $form->addField('username', []);
        $this->schema->addContextualFormAction('edit', 'Edit', $form, function () {});
        $exported = $this->schema->export();

        $this->assertArrayHasKey('edit', $exported['actions']);
        $this->assertSame('warn', $exported['actions']['edit']['kind']);
    }

    public function testAddContextualFormActionWithArray(): void
    {
        $this->schema->addField('email', ['label' => 'Email']);
        $this->schema->addContextualFormAction('change', 'Change Email', ['email'], function () {});
        $exported = $this->schema->export();

        $this->assertArrayHasKey('change', $exported['actions']);
        $this->assertSame('warn', $exported['actions']['change']['kind']);
        $this->assertArrayHasKey('email', $exported['actions']['change']['form']);
    }

    public function testExecClosureCallback(): void
    {
        $executed = false;
        $this->schema->addContextualConfirmAction('delete', 'Delete', function (array $data) use (&$executed) {
            $executed = true;
        });

        $result = $this->schema->exec(['delete' => 'some-id']);

        $this->assertTrue($executed);
        $this->assertSame('Se ha delete correctamente', $result);
    }

    public function testExecCallableCallback(): void
    {
        $executed = false;
        $callback = function (array $data) use (&$executed) {
            $executed = true;
        };
        $this->schema->addContextualConfirmAction('delete', 'Delete', $callback);
        $result = $this->schema->exec(['delete' => 'some-id']);
        $this->assertTrue($executed);
        $this->assertSame('Se ha delete correctamente', $result);
    }

    public function testExecGenerateUuidWhenEmpty(): void
    {
        $executed = false;
        $this->schema->addContextualConfirmAction('delete', 'Delete', function (array $data) use (&$executed) {
            $executed = Uuid::isValid($data['id']);
        });

        $result = $this->schema->exec(['delete' => '']); // Empty value, should generate UUID

        $this->assertTrue($executed);
        $this->assertSame('Se ha delete correctamente', $result);
    }

    public function testExecNoActionProcessed(): void
    {
        $this->schema->addContextualConfirmAction('delete', 'Delete', function () {});
        $result = $this->schema->exec([]);
        $this->assertNull($result);
    }

    public function testAddResumeAction(): void
    {
        $this->schema->addResumeAction('resume', 'Resume', 'JSON.stringify({})');
        $exported = $this->schema->export();

        $this->assertArrayHasKey('resume', $exported['actions']);
        $this->assertSame('info', $exported['actions']['resume']['kind']);
        $this->assertStringContainsString('copyToClipboard', $exported['actions']['resume']['functions']);
    }

    public function testExportHandlesReferenceField(): void
    {
        $this->schema->addField('user', [
            'label' => 'User',
            'reference' => ['label' => 'name']
        ]);

        $exported = $this->schema->export();

        $this->assertArrayHasKey('user', $exported['columns']);
        $this->assertSame('user.name', $exported['columns']['user']['name']);
        $this->assertSame('User', $exported['columns']['user']['label']);
    }

    public function testAddFilter(): void
    {
        $this->schema->addFilter('status');
        $exported = $this->schema->export();
        $this->assertArrayHasKey('status', $exported['filters']);
    }
    public function testExecWithCallableString(): void
    {
        $this->schema->addField('id', ['label' => 'ID']);

        // Registramos una función global de prueba
        $called = false;
        // Registramos el callback como un [objeto, método] (no Closure)
        $this->schema->addContextualConfirmAction('confirm', 'Confirm', [$this, 'dummyCallback']);

        $result = $this->schema->exec(['confirm' => 'test-id']);

        $this->assertSame('Se ha confirm correctamente', $result);
        $this->assertSame('test-id', $this->executedData['id']);
    }

    private array $executedData = [];

    public function dummyCallback(array $data): void
    {
        $this->executedData = $data;
    }
}
