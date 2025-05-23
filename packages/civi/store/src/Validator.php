<?php declare(strict_types=1);

namespace Civi\Store;

use JsonSchema\Constraints\Constraint;

class Validator
{
    public function __construct(private readonly Schemas $schema)
    {}
    public function getErrors(string $namespace, string $resource, array &$data): array
    {
        $validator = new \JsonSchema\Validator;
        $schema = $this->schema->jsonSchema($namespace, $resource);
        $values = $this->encode( $this->normalizeEmptyStringsToNull($data) );
        $validator->validate( $values, $schema,
            Constraint::CHECK_MODE_COERCE_TYPES | Constraint::CHECK_MODE_APPLY_DEFAULTS);
        if( $validator->isValid() ) {
            $array  = $this->decode( $values );
            foreach($array as $k => $v) {
                $data[$k] = $v;
            }
            return [];
        } else {
            return $validator->getErrors();
        }
    }

    private function normalizeEmptyStringsToNull(array $input): array {
        foreach ($input as $key => $value) {
            if (is_string($value) && trim($value) === '') {
                unset( $input[$key] );
            }
        }
        return $input;
    }

    private function encode(array $data): object
    {
        return json_decode(json_encode($data));
    }
    private function decode(object $data): array
    {
        return json_decode(json_encode($data), true);
    }
}