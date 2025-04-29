<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Civi\Micro\Kernel\ObjectMapper;

final class ObjectMapperUnitTest extends TestCase
{
    private ObjectMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new ObjectMapper();
    }

    public function testToArrayReturnsArrayAsIs(): void
    {
        $array = ['a' => 1, 'b' => null];
        $result = $this->mapper->toArray($array);
        $this->assertSame($array, $result);
    }

    public function testToArrayExtractsPropertiesFromObject(): void
    {
        $object = new class {
            public $a = 1;
            public $b = null;
            public $c = 'x';
        };

        $result = $this->mapper->toArray($object);

        $this->assertSame(['a' => 1, 'c' => 'x'], $result); // b=null is ignored
    }

    public function testToObjectReturnsNullForNullData(): void
    {
        $result = $this->mapper->toObject(null, DummyMapperTestClass::class);
        $this->assertNull($result);
    }

    public function testToObjectAssignsUntypedProperty(): void
    {
        $data = ['untyped' => 'hello'];
        $object = $this->mapper->toObject($data, DummyMapperTestClass::class);

        $this->assertSame('hello', $object->untyped);
    }

    public function testToObjectCastsPrimitiveTypes(): void
    {
        $data = [
            'intField' => '123',
            'floatField' => '3.14',
            'boolFieldTrue' => '1',
            'boolFieldFalse' => '0',
            'stringField' => 456
        ];

        $object = $this->mapper->toObject($data, DummyMapperTestClass::class);

        $this->assertSame(123, $object->intField);
        $this->assertSame(3.14, $object->floatField);
        $this->assertTrue($object->boolFieldTrue);
        $this->assertFalse($object->boolFieldFalse);
        $this->assertSame('456', $object->stringField);
    }

    public function testToObjectParsesDateTime(): void
    {
        $date = '2023-01-01T00:00:00+00:00';
        $data = ['dateField' => $date];
        $object = $this->mapper->toObject($data, DummyMapperTestClass::class);

        $this->assertInstanceOf(\DateTimeInterface::class, $object->dateField);
        $this->assertEquals($date, $object->dateField->format(\DateTimeInterface::ATOM));
    }

    public function testToObjectAllowsNull(): void
    {
        $data = ['nullableField' => null];
        $object = $this->mapper->toObject($data, DummyMapperTestClass::class);

        $this->assertNull($object->nullableField);
    }

    public function testToObjectIgnoresNonExistingProperties(): void
    {
        $data = ['nonExistent' => 'x'];
        $object = $this->mapper->toObject($data, DummyMapperTestClass::class);

        $this->assertObjectNotHasProperty('nonExistent', $object);
    }

    public function testToObjectFallbackAssignment(): void
    {
        $external = new ExternalDummyMapperTestClass();
        $data = ['external' => $external];
        $object = $this->mapper->toObject($data, DummyMapperTestClass::class);

        $this->assertSame($external, $object->external);
    }
}

class DummyMapperTestClass
{
    public int $intField;
    public float $floatField;
    public bool $boolFieldTrue;
    public bool $boolFieldFalse;
    public string $stringField;
    public ?string $nullableField = null;
    public \DateTimeInterface $dateField;
    public ?ExternalDummyMapperTestClass $external;
    public $untyped;
}

class ExternalDummyMapperTestClass
{
    public string $name;
}