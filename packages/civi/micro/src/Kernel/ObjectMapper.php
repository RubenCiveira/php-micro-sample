<?php

declare(strict_types=1);

namespace Civi\Micro\Kernel;

/**
 * ObjectMapper
 *
 * Utility class that provides methods to map between arrays and objects.
 * It supports transforming an object into an array and constructing an object from an associative array,
 * including type casting based on the target property types.
 */
class ObjectMapper
{
    /**
     * Converts an object or an array into an array.
     *
     * If the input is already an array, it is returned as-is.
     * If it is an object, its public properties are extracted into an associative array,
     * omitting any properties with null values.
     *
     * @param array|object $object The array or object to convert.
     *
     * @return array The resulting array representation.
     */
    public function toArray(array|object $object): array
    {
        if (is_array($object)) {
            return $object;
        } else {
            $response = [];
            $extract = get_object_vars($object);
            foreach ($extract as $k => $v) {
                if ($v !== null) {
                    $response[$k] = $v;
                }
            }
            return $response;
        }
    }

    /**
     * Creates an object of the given type from an associative array.
     *
     * Properties are assigned based on their names. 
     * If a property has a declared type, the method attempts to cast the value accordingly:
     * - `int`, `float`, `bool`, and `string` are cast natively.
     * - `DateTimeInterface` properties are instantiated from a string value.
     * - If the property is nullable and the value is `null`, it is assigned as `null`.
     * - If no type is declared or the type is not specifically handled, the value is assigned directly.
     *
     * Properties that do not exist in the target class are ignored.
     *
     * @param array|null $data      The associative array of data to map.
     * @param string     $typeName  The fully qualified class name to instantiate.
     *
     * @return object|null The constructed object, or null if input data is null.
     */
    public function toObject(array|null $data, string $typeName): object|null
    {
        if ($data === null) {
            return null;
        }

        $object = new $typeName();
        $refClass = new \ReflectionClass($typeName);

        foreach ($data as $key => $value) {
            if (!$refClass->hasProperty($key)) {
                continue;
            }

            $prop = $refClass->getProperty($key);
            $type = $prop->getType();

            if (!$type) {
                $object->$key = $value;
                continue;
            }

            $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : null;

            if ($type->allowsNull() && $value === null) {
                $object->$key = null;
            } elseif ($typeName === 'int') {
                $object->$key = (int) $value;
            } elseif ($typeName === 'float') {
                $object->$key = (float) $value;
            } elseif ($typeName === 'bool') {
                $object->$key = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
            } elseif ($typeName === 'string') {
                $object->$key = (string) $value;
            } elseif (is_a($typeName, \DateTimeInterface::class, true)) {
                $object->$key = new \DateTimeImmutable($value);
            } else {
                // fallback genérico
                $object->$key = $value;
            }
        }
        return $object;
    }
}