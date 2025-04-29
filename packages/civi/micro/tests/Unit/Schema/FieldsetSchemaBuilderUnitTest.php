<?php

declare(strict_types=1);

namespace Civi\Micro\Schema\Tests;

use Civi\Micro\Schema\FieldSchema;
use Civi\Micro\Schema\FieldsetSchemaBuilder;
use Civi\Micro\Schema\ReferenceType;
use PHPUnit\Framework\TestCase;

class FieldsetSchemaBuilderUnitTest extends TestCase
{
    private FieldsetSchemaBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new FieldsetSchemaBuilder();
    }

    public function testExportInitiallyEmpty(): void
    {
        $this->assertSame([], $this->builder->export());
    }

    public function testAddFieldFromArrayWithoutReference(): void
    {
        $this->builder->addField('testField', [
            'type' => 'string',
            'label' => 'Test Field',
            'required' => true,
        ]);

        $fields = $this->builder->export();
        $this->assertCount(1, $fields);
        $this->assertInstanceOf(FieldSchema::class, $fields['testField']);
        $this->assertSame('testField', $fields['testField']->name);
        $this->assertSame('string', $fields['testField']->type);
        $this->assertSame('Test Field', $fields['testField']->label);
        $this->assertTrue($fields['testField']->required);
    }

    public function testAddFieldFromArrayWithReference(): void
    {
        $this->builder->addField('referenceField', [
            'reference' => [
                'id' => 'userId',
                'label' => 'User',
                'load' => static fn () => [],
            ]
        ]);

        $fields = $this->builder->export();
        $this->assertInstanceOf(FieldSchema::class, $fields['referenceField']);
        $this->assertInstanceOf(ReferenceType::class, $fields['referenceField']->reference);
        $this->assertSame('userId', $fields['referenceField']->reference->id);
        $this->assertSame('User', $fields['referenceField']->reference->label);
    }

    public function testAddFieldDirectlyFromFieldSchema(): void
    {
        $field = new FieldSchema('directField', 'int', 'Direct Field', true);
        $this->builder->addField('directField', $field);

        $fields = $this->builder->export();
        $this->assertSame($field, $fields['directField']);
    }

    public function testMarkCalculatedUpdatesFields(): void
    {
        $this->builder->addField('calcField', [
            'type' => 'number',
            'label' => 'Calc Field'
        ]);
        $this->builder->markCalculated(['calcField']);
        $fields = $this->builder->export();

        $this->assertTrue($fields['calcField']->calculated);
    }

    public function testMarkReadonlyUpdatesFields(): void
    {
        $this->builder->addField('readonlyField', [
            'type' => 'number',
            'label' => 'Readonly Field'
        ]);
        $this->builder->markReadonly(['readonlyField']);
        $fields = $this->builder->export();

        $this->assertTrue($fields['readonlyField']->readonly);
    }

    public function testMarkCalculatedDoesNothingIfFieldDoesNotExist(): void
    {
        $this->builder->markCalculated(['nonexistent']);
        $this->assertSame([], $this->builder->export());
    }

    public function testMarkReadonlyDoesNothingIfFieldDoesNotExist(): void
    {
        $this->builder->markReadonly(['nonexistent']);
        $this->assertSame([], $this->builder->export());
    }
}
