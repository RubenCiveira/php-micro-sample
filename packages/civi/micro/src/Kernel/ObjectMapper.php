<?php

declare(strict_types=1);

namespace Civi\Micro\Kernel;

class ObjectMapper
{
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