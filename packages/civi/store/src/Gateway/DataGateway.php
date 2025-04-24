<?php declare(strict_types=1);

namespace Civi\Store\Gateway;

use Civi\Store\DataQueryParam;
use Civi\Store\Filter\DataQueryFilter;
use Civi\Store\Filter\DataQueryOperator;
use Civi\Store\Filter\DataQueryCondition;
use Civi\Store\Service\ExecPipeline;
use Civi\Store\Service\RestrictionPipeline;
use Civi\Micro\ProjectLocator;
use Civi\Security\Guard\AccessGuard;
use Civi\Security\Redaction\OutputRedactor;
use Civi\Security\Sanitization\InputSanitizer;
use Civi\Security\UnauthorizedException;
use InvalidArgumentException;

class DataGateway
{
    private readonly string $baseDir;
    public function __construct(
        private readonly RestrictionPipeline $restrictor,
        private readonly ExecPipeline $execPipeline,
        private readonly AccessGuard $guard,
        private readonly InputSanitizer $sanitizer,
        private readonly OutputRedactor $redactor,
        string $baseDir = ''
    ) {
        $this->baseDir = $baseDir !== '' ? $baseDir : ProjectLocator::getRootPath() . '/storage'; 
    }

    public function create(string $namespace, string $typeName, string $idName, string $from, array $data): array
    {
        $sanitized = $this->sanitizer->sanitizeInput($namespace, $typeName, $data);
        if( !$this->guard->canExecute($from, $namespace, $typeName, $sanitized, []) ) {
            throw new UnauthorizedException("Not allowed to $from onver $namespace:$typeName");
        }
        $sanitized = $this->execPipeline->executeOperation($namespace, $typeName, [ucfirst($from), 'Write', 'Read'], function () use ($namespace, $typeName, $sanitized, $idName) {
            $this->save($namespace, $typeName, $sanitized[$idName], $sanitized);
        }, $sanitized);
        return [$this->redactor->filterOutput($namespace, $typeName, $sanitized)];
    }
    public function modify(string $namespace, string $typeName, string $idName, string $from, DataQueryParam $filters, array $data): array
    {
        $sanitized = $this->sanitizer->sanitizeInput($namespace, $typeName, $data);
        $readed = $this->fetch($namespace, $typeName, $filters);
        if (!count($readed)) {
            throw new InvalidArgumentException("Not found");
        }
        if( !$this->guard->canExecute($from, $namespace, $typeName, $sanitized, $readed) ) {
            throw new UnauthorizedException("Not allowed to $from onver $namespace:$typeName");
        }
        $saved = [];
        foreach ($readed as $read) {
            $save = array_merge($read, $sanitized);
            $output = $this->execPipeline->executeOperation($namespace, $typeName, [ucfirst($from), 'Write', 'Read'], function () use ($namespace, $typeName, $read, $save, $idName) {
                $this->save($namespace, $typeName, $read[$idName], $save);
                return $save;
            }, $save, $read);
            $saved[] = $this->redactor->filterOutput($namespace, $typeName, $output);
        }
        return $saved;
    }
    public function delete(string $namespace, string $typeName, string $idName, string $from, DataQueryParam $filters): void
    {
        $readed = $this->fetch($namespace, $typeName, $filters);
        $path = "{$this->baseDir}/$namespace/$typeName/";
        if (is_dir($path)) {
            foreach ($readed as $read) {
                $this->execPipeline->executeOperation($namespace, $typeName, ['Delete'], function () use ($read, $path, $idName) {
                    $file = "{$path}/{$read[$idName]}.json";
                    unlink($file);
                }, $read, $read);
            }
        }
    }

    public function fetch(string $namespace, string $typeName, DataQueryParam $originalFilter): array
    {
        $filter = $this->restrictor->restrictFilter($namespace, $typeName, $originalFilter->toArray());
        $filters = DataQueryParam::replaceInto($originalFilter, $filter);

        $path = "{$this->baseDir}/$namespace/$typeName/";
        if (!is_dir($path)) {
            return [];
        }
        $items = [];
        foreach (glob("$path/*.json") as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($this->matchesFilter($data, $filters->filter(), $namespace)) {
                $items[] = $data;
            }
        }

        // Ordenar
        $order = $filters->order();
        if ($order) {
            usort($items, function ($a, $b) use ($order) {
                foreach ($order as $ord) {
                    $field = $ord['field'];
                    $dir = strtolower($ord['direction']);
                    $av = $this->getNested($a, $field);
                    $bv = $this->getNested($b, $field);
                    if ($av === $bv)
                        continue;
                    return ($av <=> $bv) * ($dir === 'desc' ? -1 : 1);
                }
                return 0;
            });
        }

        // Aplicar since como cursor
        $since = $filters->since();
        if ($since) {
            $items = array_filter($items, function ($item) use ($since) {
                foreach ($since as $field => $value) {
                    $current = $this->getNested($item, $field);
                    if ($current <= $value) {
                        return false;
                    }
                }
                return true;
            });
        }

        // Aplicar límite
        $limit = $filters->limit();
        if ($limit !== null) {
            $items = array_slice($items, 0, $limit);
        }

        $values = array_values($items);
        $result = [];
        foreach($values as $value) {
            $result[] = $this->execPipeline->executeOperation($namespace, $typeName, ['Read'], null, $value);
        }
        return $result;
    }

    private function save(string $namespace, string $typeName, string $id, array $data)
    {
        $path = "{$this->baseDir}/$namespace/$typeName/";
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $file = "{$path}/{$id}.json";
        $tempFile = "{$file}.tmp";

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $fp = fopen($tempFile, 'w');
        if (!$fp) {
            throw new \RuntimeException("No se pudo abrir archivo temporal para escribir: $tempFile");
        }

        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            throw new \RuntimeException("No se pudo bloquear el archivo temporal: $tempFile");
        }

        fwrite($fp, $json);
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        rename($tempFile, $file); // operación atómica
    }

    private function matchesFilter(array &$item, ?DataQueryFilter $filter, string $namespace): bool
    {
        if (!$filter)
            return true;

        if ($filter->isCondition()) {
            return $this->evaluateCondition($item, $filter->elements()[0], $namespace);
        }

        if ($filter->isAnd()) {
            foreach ($filter->elements() as $sub) {
                if (!$this->matchesFilter($item, $sub, $namespace)) {
                    return false;
                }
            }
            return true;
        }

        if ($filter->isOr()) {
            foreach ($filter->elements() as $sub) {
                if ($this->matchesFilter($item, $sub, $namespace)) {
                    return true;
                }
            }
            return false;
        }

        return true;
    }

    private function evaluateCondition(array &$item, DataQueryCondition $cond, string $namespace): bool
    {
        $value = $this->resolveValue($item, $cond->field(), $namespace);
        $target = $cond->value();
        return match ($cond->operator()) {
            DataQueryOperator::EQ => $value == $target,
            DataQueryOperator::NE => $value != $target,
            DataQueryOperator::GT => $value > $target,
            DataQueryOperator::GTE => $value >= $target,
            DataQueryOperator::LT => $value < $target,
            DataQueryOperator::LTE => $value <= $target,
            DataQueryOperator::LIKE, DataQueryOperator::CONTAINING => str_contains($value, $target),
            DataQueryOperator::STARTING_WITH => str_starts_with($value, $target),
            DataQueryOperator::ENDING_WITH => str_ends_with($value, $target),
            DataQueryOperator::IN => in_array($value, $target),
            DataQueryOperator::NIN => !in_array($value, $target),
            DataQueryOperator::BETWEEN => $value >= $target[0] && $value <= $target[1],
            default => $value == $target,
        };
    }

    private function resolveValue(array &$item, string $path, string $namespace)
    {
        $parts = explode('.', $path);
        $current = &$item;

        for ($i = 0; $i < count($parts); $i++) {
            $key = $parts[$i];

            if (!is_array($current)) {
                return null;
            }

            if (array_key_exists($key, $current)) {
                $current = &$current[$key];
            } elseif (array_key_exists($key . '_id', $current)) {
                $relatedType = ucfirst($key);
                $relatedId = $current[$key . '_id'];
                $file = "{$this->baseDir}/$namespace/{$relatedType}/{$relatedId}.json";
                if (!file_exists($file)) {
                    return null;
                }
                $related = json_decode(file_get_contents($file), true);
                $current[$key] = $related; // Expandimos el dato relacionado en el array
                $current = &$current[$key];
            } else {
                return null;
            }
        }

        return $current;
    }

    private function getNested(array $item, string $path)
    {
        $parts = explode('.', $path);
        $current = $item;
        foreach ($parts as $part) {
            if (!is_array($current) || !array_key_exists($part, $current)) {
                return null;
            }
            $current = $current[$part];
        }
        return $current;
    }
}
