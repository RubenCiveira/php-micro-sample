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
        $reference = new ReferenceType('relatedEntity', 'relatedLabel', fn() => []);

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
}
