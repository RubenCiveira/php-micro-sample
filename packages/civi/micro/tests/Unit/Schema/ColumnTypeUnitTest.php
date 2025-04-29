<?php

declare(strict_types=1);

namespace Civi\Micro\Schema\Tests;

use PHPUnit\Framework\TestCase;
use Civi\Micro\Schema\ColumnType;

/**
 * @covers \Civi\Micro\Schema\ColumnType
 */
class ColumnTypeUnitTest extends TestCase
{
    public function testConstructorAssignsProperties(): void
    {
        $name = 'columnName';
        $label = 'Column Label';

        $columnType = new ColumnType($name, $label);

        $this->assertSame($name, $columnType->name);
        $this->assertSame($label, $columnType->label);
    }
}
