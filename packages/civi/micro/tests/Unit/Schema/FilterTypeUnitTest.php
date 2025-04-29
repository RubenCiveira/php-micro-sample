<?php

declare(strict_types=1);

namespace Civi\Micro\Schema\Tests;

use PHPUnit\Framework\TestCase;
use Civi\Micro\Schema\FilterType;

/**
 * @covers \Civi\Micro\Schema\FilterType
 */
class FilterTypeUnitTest extends TestCase
{
    public function testConstructorSetsNameCorrectly(): void
    {
        $filterName = 'status';
        $filterType = new FilterType($filterName);

        $this->assertInstanceOf(FilterType::class, $filterType);
        $this->assertSame($filterName, $filterType->name);
    }

}
