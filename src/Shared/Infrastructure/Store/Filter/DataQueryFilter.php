<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\Store\Filter;

class DataQueryFilter
{
    public const TYPE_AND = 'AND';
    public const TYPE_OR = 'OR';
    public const TYPE_CONDITION = 'CONDITION';

    private string $type;

    /** @var DataQueryFilter[]|DataQueryCondition[] */
    private array $elements;

    private function __construct(string $type, array $elements)
    {
        $this->type = $type;
        $this->elements = $elements;
    }

    public static function and(array $filters): self
    {
        return new self(self::TYPE_AND, $filters);
    }

    public static function or(array $filters): self
    {
        return new self(self::TYPE_OR, $filters);
    }

    public static function condition(DataQueryCondition $condition): self
    {
        return new self(self::TYPE_CONDITION, [$condition]);
    }

    public function isCondition(): bool
    {
        return $this->type === self::TYPE_CONDITION;
    }

    public function isAnd(): bool
    {
        return $this->type === self::TYPE_AND;
    }

    public function isOr(): bool
    {
        return $this->type === self::TYPE_OR;
    }

    /** @return DataQueryFilter[]|DataQueryCondition[] */
    public function elements(): array
    {
        return $this->elements;
    }

    public function type(): string
    {
        return $this->type;
    }
}

