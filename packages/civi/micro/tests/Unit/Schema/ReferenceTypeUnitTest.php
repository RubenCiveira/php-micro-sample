<?php

declare(strict_types=1);

namespace Civi\Micro\Schema\Tests;

use Civi\Micro\Schema\ReferenceType;
use PHPUnit\Framework\TestCase;

class ReferenceTypeUnitTest extends TestCase
{
    public function testConstructorAssignsPropertiesCorrectly(): void
    {
        $loadFunction = function () {
            return ['data'];
        };

        $referenceType = new ReferenceType('user_id', 'User', $loadFunction);

        $this->assertSame('user_id', $referenceType->id);
        $this->assertSame('User', $referenceType->label);
        $this->assertSame($loadFunction, $referenceType->load);
    }

    public function testLoadClosureIsCallable(): void
    {
        $loadFunction = function () {
            return ['id' => 1, 'name' => 'John Doe'];
        };

        $referenceType = new ReferenceType('user_id', 'User', $loadFunction);

        $this->assertIsCallable($referenceType->load);
        $this->assertSame(['id' => 1, 'name' => 'John Doe'], ($referenceType->load)());
    }
}
