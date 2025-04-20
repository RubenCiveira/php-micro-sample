<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\Store;

use Civi\Repomanager\Shared\Infrastructure\Store\Filter\DataQueryCondition;
use Civi\Repomanager\Shared\Infrastructure\Store\Filter\DataQueryFilter;
use Civi\Repomanager\Shared\Infrastructure\Store\Filter\DataQueryOperator;
use GraphQL\Type\Schema;

class DataQueryParam
{
    private ?DataQueryFilter $filter = null;
    private array $order = [];
    private array $since = [];
    private ?int $limit = null;

    public function __construct(
        private readonly Schema $schema,
        private readonly string $resource,
        array $args
    ) {
        $this->processFilter($args['filter'] ?? []);
        $this->processOrder($args['order'] ?? []);
        $this->processSince($args['since'] ?? []);
        $this->limit = $args['limite'] ?? null;
    }

    public function add(string $name, array $value): void
    {
        if( $this->filter && $this->filter->elements() ) {
            $prev = $this->filter()->elements();
            $this->processFilter([$name => $value]);
            $conditions = array_merge( $prev, $this->filter->elements() );
            $this->filter = DataQueryFilter::and( $conditions );
        } else {
            $this->processFilter([$name => $value]);
        }
    }

    public function __call(string $name, array $args): self
    {
        if( $name == 'limit' ) {
            $this->limit = $args[0];
        } else if( $name == 'order' ) {
            $this->order[] = [
                'field' => $args[0],
                'direction' => $args[1]
            ];
        } else if( substr($name, 0, 5) == 'since' ) {
            $field = lcfirst(substr($name, 5));
            $this->since[$field] = $args[0];
        } else {
            $this->add( $name, $args[0]);
        }
        return $this;
    }

    private function processFilter(array $filtro): void
    {
        $filters = [];
        $validOps = [
            'Is',
            'Equals',
            'Between',
            'LessThan',
            'LessThanEqual',
            'GreaterThan',
            'GreaterThanEqual',
            'After',
            'Before',
            'IsNull',
            'Null',
            'IsNotNull',
            'NotNull',
            'Like',
            'NotLike',
            'StartingWith',
            'EndingWith',
            'Containing',
            'Not',
            'In',
            'NotIn',
            'True',
            'False',
            'IgnoreCase'
        ];

        foreach ($filtro as $key => $value) {
            $parts = preg_split('/(?=[A-Z])/', $key, -1, PREG_SPLIT_NO_EMPTY);
            $groups = [];

            while (!empty($parts)) {
                // Detecta 'or' o 'and'
                if (strtolower($parts[0]) === 'or' || strtolower($parts[0]) === 'and') {
                    $groups[] = strtolower(array_shift($parts));
                    continue;
                }

                // Acumulamos palabras para campo
                $fieldParts = [];
                while (!empty($parts)) {
                    $fieldParts[] = array_shift($parts);

                    // Intentamos encontrar el operador más largo desde el final
                    for ($i = count($parts); $i > 0; $i--) {
                        $candidate = implode('', array_slice($parts, 0, $i));
                        if (in_array($candidate, $validOps)) {
                            $field = implode('', $fieldParts);
                            $groups[] = [$field, $candidate];
                            array_splice($parts, 0, $i);
                            continue 3;
                        }
                    }
                }

                // Si no encontramos ningún operador, asumimos Equals
                $groups[] = [implode('', $fieldParts), 'Equals'];
            }

            $conditions = [];
            $logicOps = [];
            $hasIn = false;

            foreach ($groups as $group) {
                if (is_string($group)) {
                    $logicOps[] = $group;
                } else {
                    [$field, $op] = $group;
                    $operator = DataQueryOperator::fromString($op);
                    if ($operator == DataQueryOperator::IN) {
                        $hasIn = true;
                    } else if ($hasIn) {
                        throw new \InvalidArgumentException('The "IN" operator must be the last condition in a composite filter');
                    }
                    $conditions[] = ['field' => 
                        $this->resolveFieldPath( lcfirst($field)), 'operator' => $operator];
                }
            }

            $values = is_string($value) ? explode(',', $value) : (array) $value;

            if (count($values) < count($conditions)) {
                $values = array_pad($values, count($conditions), end($values));
            }

            $subfilters = [];
            foreach ($conditions as $index => $cond) {
                $parsedValue = match ($cond['operator']) {
                    DataQueryOperator::BETWEEN => array_slice($values, $index, 2),
                    DataQueryOperator::IN, DataQueryOperator::NIN => array_slice($values, $index),
                    default => $values[$index] ?? null
                };
                $subfilters[] = DataQueryFilter::condition(
                    new DataQueryCondition($cond['field'], $cond['operator'], $parsedValue)
                );
            }

            $finalFilter = array_shift($subfilters);
            while (!empty($logicOps)) {
                $logic = array_shift($logicOps);
                $b = array_shift($subfilters);
                $finalFilter = match ($logic) {
                    'or' => DataQueryFilter::or([$finalFilter, $b]),
                    'and' => DataQueryFilter::and([$finalFilter, $b]),
                    default => $finalFilter,
                };
            }

            if ($finalFilter) {
                $filters[] = $finalFilter;
            }
        }

        $this->filter = DataQueryFilter::and($filters);
    }

    private function resolveFieldPath(string $field): ?string
    {
        $type = $this->schema->getType( $this->resource );
        // $type = $this->schema?->getQueryType()->getField($resource)?->getType();
        $type = \GraphQL\Type\Definition\Type::getNamedType($type);
        $parts = preg_split('/(?=[A-Z])/', $field, -1, PREG_SPLIT_NO_EMPTY);
        $path = [];
        $current = $type;
        $fieldName = '';
        foreach ($parts as $i => $part) {
            // $fieldName = lcfirst(implode('', array_slice($parts, 0, $i + 1)));
            $attempt = $fieldName ? "{$fieldName}{$part}" : lcfirst($part);
            if ($current instanceof \GraphQL\Type\Definition\ObjectType && $current->hasField($attempt)) {
                $path[] = $attempt;
                $fieldName = '';
                $current = \GraphQL\Type\Definition\Type::getNamedType($current->getField($attempt)->getType());
            } else {
                $fieldName = $attempt;
            }
        }
        if( empty($path) || $fieldName ) {
            throw new \InvalidArgumentException("Unkown field $field");
        }
        return empty($path) ? null : implode('.', $path);
    }

    private function processOrder(array $order): void
    {
        foreach ($order as $rule) {
            $this->order[] = [
                'field' => $rule['field'],
                'direction' => strtoupper($rule['direction'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC'
            ];
        }
    }

    private function processSince(array $since): void
    {
        $this->since = $since;
    }

    public function filter(): ?DataQueryFilter
    {
        return $this->filter;
    }

    public function order(): array
    {
        return $this->order;
    }

    public function since(): array
    {
        return $this->since;
    }

    public function limit(): ?int
    {
        return $this->limit;
    }
}
