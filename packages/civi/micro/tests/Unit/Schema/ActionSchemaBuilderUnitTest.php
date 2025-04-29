<?php

declare(strict_types=1);

namespace Civi\Micro\Schema;

use PHPUnit\Framework\TestCase;

class ActionSchemaBuilderUnitTest extends TestCase
{
    private ActionSchemaBuilder $schema;

    protected function setUp(): void
    {
        $this->schema = new ActionSchemaBuilder();
    }

    public function testExportReturnsInitialEmptyFields(): void
    {
        $this->assertSame(['fields' => []], $this->schema->export());
    }

    public function testAddFieldAddsFieldWithDefaults(): void
    {
        $this->schema->addField('testField', []);
        $export = $this->schema->export();

        $this->assertArrayHasKey('testField', $export['fields']);
        $this->assertSame('testField', $export['fields']['testField']['name']);
        $this->assertFalse($export['fields']['testField']['required']);
        $this->assertSame('text', $export['fields']['testField']['type']);
        $this->assertSame('TestField', $export['fields']['testField']['label']);
    }

    public function testAddFieldPreservesProvidedValues(): void
    {
        $info = [
            'required' => true,
            'type' => 'number',
            'label' => 'Custom Label'
        ];
        $this->schema->addField('customField', $info);
        $export = $this->schema->export();

        $this->assertSame('customField', $export['fields']['customField']['name']);
        $this->assertTrue($export['fields']['customField']['required']);
        $this->assertSame('number', $export['fields']['customField']['type']);
        $this->assertSame('Custom Label', $export['fields']['customField']['label']);
    }

    public function testMarkCalculatedMarksFieldsAsCalculated(): void
    {
        $this->schema->addField('field1', []);
        $this->schema->addField('field2', []);

        $this->schema->markCalculated(['field1']);
        $export = $this->schema->export();

        $this->assertTrue($export['fields']['field1']['calculated']);
        $this->assertArrayNotHasKey('calculated', $export['fields']['field2']);
    }

    public function testMarkReadonlyMarksFieldsAsReadonly(): void
    {
        $this->schema->addField('fieldA', []);
        $this->schema->addField('fieldB', []);

        $this->schema->markReadonly(['fieldB']);
        $export = $this->schema->export();

        $this->assertTrue($export['fields']['fieldB']['readonly']);
        $this->assertArrayNotHasKey('readonly', $export['fields']['fieldA']);
    }
}
