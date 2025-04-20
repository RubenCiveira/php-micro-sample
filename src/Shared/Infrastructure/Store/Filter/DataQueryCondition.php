<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\Store\Filter;

class DataQueryCondition
{
    public function __construct(
        private string $field,
        private DataQueryOperator $operator,
        private mixed $value
    ) {}

    public function field(): string
    {
        return $this->field;
    }

    public function operator(): DataQueryOperator
    {
        return $this->operator;
    }

    public function value(): mixed
    {
        return $this->value;
    }
} 
