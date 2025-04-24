<?php declare(strict_types=1);

namespace Civi\Store\Filter;

enum DataQueryOperator: string
{
    case DISTINCT = 'distinct';
    case EQ = 'eq';
    case LIKE = 'like';
    case NOT_LIKE = 'notlike';
    case GT = 'gt';
    case GTE = 'gte';
    case LT = 'lt';
    case LTE = 'lte';
    case NE = 'ne';
    case IN = 'in';
    case NIN = 'nin';
    case BETWEEN = 'between';
    case ISNULL = 'isnull';
    case ISNOTNULL = 'isnotnull';
    case NOT = 'not';
    case TRUE = 'true';
    case FALSE = 'false';
    case STARTING_WITH = 'startingwith';
    case ENDING_WITH = 'endingwith';
    case CONTAINING = 'containing';
    case BEFORE = 'before';
    case AFTER = 'after';
    case IGNORE_CASE = 'ignorecase';

    public static function fromString(string $value): self
    {
        return match (strtolower($value)) {
            'distinct' => self::DISTINCT,
            'eq', 'equals', 'is' => self::EQ,
            'like' => self::LIKE,
            'notlike' => self::NOT_LIKE,
            'gt', 'greaterthan', 'after' => self::GT,
            'gte', 'greaterthanequal' => self::GTE,
            'lt', 'lessthan', 'before' => self::LT,
            'lte', 'lessthanequal' => self::LTE,
            'ne', 'not' => self::NE,
            'in' => self::IN,
            'nin', 'notin' => self::NIN,
            'between' => self::BETWEEN,
            'isnull', 'null' => self::ISNULL,
            'isnotnull', 'notnull' => self::ISNOTNULL,
            'true' => self::TRUE,
            'false' => self::FALSE,
            'startingwith' => self::STARTING_WITH,
            'endingwith' => self::ENDING_WITH,
            'containing' => self::CONTAINING,
            'ignorecase' => self::IGNORE_CASE,
            default => throw new \InvalidArgumentException("Operador no soportado: $value"),
        };
    }
}
