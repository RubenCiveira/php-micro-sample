<?php

declare(strict_types=1);

namespace Civi\Micro\Schema\Tests;

use Civi\Micro\Schema\TypeSchemaBuilder;
use Civi\Micro\Schema\FieldsetSchemaBuilder;
use Civi\Micro\Schema\TypeSchema;
use Civi\Micro\Schema\FieldSchema;
use PHPUnit\Framework\TestCase;

class TypeSchemaBuilderUnitTest extends TestCase
{
    private TypeSchemaBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new TypeSchemaBuilder('TestEntity', 'Test Entity', 'id');
    }

    public function testExportWithoutColumns(): void
    {
        $this->builder->addField('name', ['label' => 'Name', 'type' => 'string']);
        $schema = $this->builder->export();

        $this->assertInstanceOf(TypeSchema::class, $schema);
        $this->assertSame('TestEntity', $schema->name);
        $this->assertCount(1, $schema->fields);
        $this->assertArrayHasKey('name', $schema->fields->all());
        $this->assertSame('Name', $schema->fields->all()['name']->label);
        $this->assertCount(1, $schema->columns);
    }

    public function testExportWithCustomColumn(): void
    {
        $this->builder->addField('name', ['label' => 'Name', 'type' => 'string']);
        $this->builder->addColumn('custom', 'Custom Column');

        $schema = $this->builder->export();

        $this->assertCount(1, $schema->columns);
        $this->assertArrayHasKey('custom', $schema->columns->all());
        $this->assertSame('Custom Column', $schema->columns->all()['custom']->label);
    }

    public function testExcludeColumn(): void
    {
        $this->builder->addField('name', ['label' => 'Name', 'type' => 'string']);
        $this->builder->excludeColumn('name');
        
        $schema = $this->builder->export();

        $this->assertArrayNotHasKey('name', $schema->columns->all());
    }

    public function testAddContextualConfirmAction(): void
    {
        $callback = fn(array $data) => null;
        $this->builder->addContextualConfirmAction('delete', 'Delete', $callback);

        $schema = $this->builder->export();
        $this->assertArrayHasKey('delete', $schema->actions->all());
        $this->assertTrue($schema->actions->all()['delete']->contextual);
    }

    public function testAddStandaloneFormActionWithArray(): void
    {
        $this->builder->addField('field1', ['label' => 'Field 1', 'type' => 'text']);
        $this->builder->addStandaloneFormAction('create', 'Create', ['field1'], fn(array $data) => null);

        $schema = $this->builder->export();
        $this->assertArrayHasKey('create', $schema->actions->all());
        $this->assertFalse($schema->actions->all()['create']->contextual);
    }

    public function testAddStandaloneFormActionWithBuilder(): void
    {
        $fieldset = new FieldsetSchemaBuilder();
        $fieldset->addField('field1', ['label' => 'Field 1', 'type' => 'text']);
        
        $this->builder->addStandaloneFormAction('create', 'Create', $fieldset, fn(array $data) => null);

        $schema = $this->builder->export();
        $this->assertArrayHasKey('create', $schema->actions->all());
    }

    public function testAddContextualFormActionWithArray(): void
    {
        $this->builder->addField('field1', ['label' => 'Field 1', 'type' => 'text']);
        $this->builder->addContextualFormAction('update', 'Update', ['field1'], fn(array $data) => null);

        $schema = $this->builder->export();
        $this->assertArrayHasKey('update', $schema->actions->all());
        $this->assertTrue($schema->actions->all()['update']->contextual);
    }

    public function testAddContextualFormActionWithBuilder(): void
    {
        $fieldset = new FieldsetSchemaBuilder();
        $fieldset->addField('field1', ['label' => 'Field 1', 'type' => 'text']);
        
        $this->builder->addContextualFormAction('update', 'Update', $fieldset, fn(array $data) => null);

        $schema = $this->builder->export();
        $this->assertArrayHasKey('update', $schema->actions->all());
    }

    public function testExecWithClosureCallback(): void
    {
        $called = false;
        $this->builder->addContextualConfirmAction('delete', 'Delete', function(array $data) use (&$called) {
            $called = true;
        });

        $result = $this->builder->exec(['delete' => 'some-id']);
        $this->assertTrue($called);
        $this->assertEquals('Se ha delete correctamente', $result);
    }

    public function testExecWithCallableCallback(): void
    {
        $callback = [$this, 'dummyCallback'];
        $this->builder->addContextualConfirmAction('delete', 'Delete', $callback);

        $result = $this->builder->exec(['delete' => 'some-id']);
        $this->assertEquals('Se ha delete correctamente', $result);
    }

    public function testAddResumeAction(): void
    {
        $this->builder->addResumeAction('resume', 'Resume', 'JSON.stringify({})');

        $schema = $this->builder->export();
        $this->assertArrayHasKey('resume', $schema->actions->all());
        $this->assertSame('info', $schema->actions->all()['resume']->kind);
    }

    public function testAddFilter(): void
    {
        $this->builder->addFilter('status');
        $schema = $this->builder->export();

        $this->assertArrayHasKey('status', $schema->filters->all());
    }

    public function testExecGeneratesUuidWhenIdIsMissing(): void
    {
        $calledData = [];
    
        $this->builder->addContextualConfirmAction('delete', 'Delete', function(array $data) use (&$calledData) {
            $calledData = $data;
        });
    
        // 'delete' está presente, pero no hay campo 'id'
        $result = $this->builder->exec(['delete' => false]);
    
        $this->assertEquals('Se ha delete correctamente', $result);
    
        // Ahora comprobamos que se ha generado un UUID
        $this->assertArrayHasKey('id', $calledData);
        $this->assertNotEmpty($calledData['id']);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $calledData['id']
        );
    }

    public function dummyCallback(array $data): void
    {
        // Dummy callback for callable tests
    }
}
