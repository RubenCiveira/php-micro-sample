<?php

declare(strict_types=1);

namespace Civi\Micro\Schema\Tests;

use Civi\Micro\Schema\FieldSchema;
use Civi\Micro\Schema\ReferenceType;
use PHPUnit\Framework\TestCase;

class FieldSchemaUnitTest extends TestCase
{
    public function testFieldSchemaFullInitialization(): void
    {
        $reference = new ReferenceType('relatedEntity', 'relatedLabel', fn () => []);

        $field = new FieldSchema(
            name: 'username',
            type: 'string',
            label: 'Username',
            required: true,
            calculated: true,
            readonly: true,
            enum: ['admin', 'user', 'guest'],
            reference: $reference
        );

        $this->assertSame('username', $field->name);
        $this->assertSame('string', $field->type);
        $this->assertSame('Username', $field->label);
        $this->assertTrue($field->required);
        $this->assertTrue($field->calculated);
        $this->assertTrue($field->readonly);
        $this->assertSame(['admin', 'user', 'guest'], $field->enum);
        $this->assertSame($reference, $field->reference);
    }

    public function testFieldSchemaMinimalInitialization(): void
    {
        $field = new FieldSchema(
            name: 'email',
            type: 'string',
            label: 'Email',
            required: false
            // Not setting optional params
        );

        $this->assertSame('email', $field->name);
        $this->assertSame('string', $field->type);
        $this->assertSame('Email', $field->label);
        $this->assertFalse($field->required);
        $this->assertFalse($field->calculated);
        $this->assertFalse($field->readonly);
        $this->assertNull($field->enum);
        $this->assertNull($field->reference);
    }

    public function testAsReadonlyCreatesNewInstanceWithCalculatedTrue(): void
    {
        $field = new FieldSchema(
            name: 'username',
            type: 'string',
            label: 'Username',
            required: true,
            calculated: false,
            readonly: false
        );

        $readonlyField = $field->asReadonly();

        $this->assertNotSame($field, $readonlyField);
        $this->assertSame($field->name, $readonlyField->name);
        $this->assertSame($field->type, $readonlyField->type);
        $this->assertSame($field->label, $readonlyField->label);
        $this->assertSame($field->required, $readonlyField->required);
        $this->assertTrue($readonlyField->readonly);
        $this->assertSame($field->calculated, $readonlyField->calculated);
        $this->assertSame($field->enum, $readonlyField->enum);
        $this->assertSame($field->reference, $readonlyField->reference);
    }

    public function testAsCalculatedCreatesNewInstanceWithReadonlyTrue(): void
    {
        $field = new FieldSchema(
            name: 'username',
            type: 'string',
            label: 'Username',
            required: false,
            calculated: true, // ya está calculated
            readonly: false
        );

        $calculatedField = $field->asCalculated();

        $this->assertNotSame($field, $calculatedField);
        $this->assertSame($field->name, $calculatedField->name);
        $this->assertSame($field->type, $calculatedField->type);
        $this->assertSame($field->label, $calculatedField->label);
        $this->assertSame($field->required, $calculatedField->required);
        $this->assertTrue($calculatedField->calculated); // no cambia
        $this->assertFalse($calculatedField->readonly); // cambia
        $this->assertSame($field->enum, $calculatedField->enum);
        $this->assertSame($field->reference, $calculatedField->reference);
    }

    public function testOriginalFieldRemainsUnchangedAfterAsReadonlyOrCalculated(): void
    {
        $field = new FieldSchema(
            name: 'email',
            type: 'string',
            label: 'Email',
            required: true
        );

        $readonlyField = $field->asReadonly();
        $calculatedField = $field->asCalculated();

        $this->assertFalse($field->calculated);
        $this->assertFalse($field->readonly);

        $this->assertTrue($readonlyField->readonly);
        $this->assertFalse($readonlyField->calculated);

        $this->assertFalse($calculatedField->readonly);
        $this->assertTrue($calculatedField->calculated);
    }
}
