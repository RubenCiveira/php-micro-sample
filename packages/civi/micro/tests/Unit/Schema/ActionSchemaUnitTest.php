<?php

declare(strict_types=1);

namespace Civi\Micro\Schema\Tests;

use PHPUnit\Framework\TestCase;
use Civi\Micro\Schema\ActionSchema;

/**
 * @covers \Civi\Micro\Schema\ActionSchema
 */
class ActionSchemaUnitTest extends TestCase
{
    public function testCanInstantiateWithRequiredFields(): void
    {
        $action = new ActionSchema(
            name: 'test_action',
            label: 'Test Action',
            kind: 'danger',
            contextual: true
        );

        $this->assertSame('test_action', $action->name);
        $this->assertSame('Test Action', $action->label);
        $this->assertSame('danger', $action->kind);
        $this->assertTrue($action->contextual);

        $this->assertSame([], $action->fields);
        $this->assertNull($action->callback);
        $this->assertNull($action->code);
        $this->assertNull($action->template);
        $this->assertNull($action->buttons);
        $this->assertNull($action->functions);
    }

    public function testCanInstantiateWithAllFields(): void
    {
        $callback = function () {
            return 'callback';
        };

        $action = new ActionSchema(
            name: 'full_action',
            label: 'Full Action',
            kind: 'success',
            contextual: false,
            fields: ['field1' => 'value1', 'field2' => 'value2'],
            callback: $callback,
            code: 'console.log("test")',
            template: '<div>template</div>',
            buttons: ['save' => 'Save', 'cancel' => 'Cancel'],
            functions: 'function doSomething() {}'
        );

        $this->assertSame('full_action', $action->name);
        $this->assertSame('Full Action', $action->label);
        $this->assertSame('success', $action->kind);
        $this->assertFalse($action->contextual);
        $this->assertSame(['field1' => 'value1', 'field2' => 'value2'], $action->fields);
        $this->assertSame($callback, $action->callback);
        $this->assertSame('console.log("test")', $action->code);
        $this->assertSame('<div>template</div>', $action->template);
        $this->assertSame(['save' => 'Save', 'cancel' => 'Cancel'], $action->buttons);
        $this->assertSame('function doSomething() {}', $action->functions);
    }
}
