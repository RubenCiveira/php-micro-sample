<?php

declare(strict_types=1);

namespace Civi\Store\Gateway;

use Civi\Micro\ProjectLocator;
use Civi\Store\DataQueryParam;
use Civi\Store\Filter\DataQueryCondition;
use Civi\Store\Filter\DataQueryFilter;
use Civi\Store\Filter\DataQueryOperator;
use Civi\Store\StoreSchema;

class DataGateway
{
    private readonly string $baseDir;

    public function __construct(string $baseDir = '')
    {
        $this->baseDir = $baseDir !== '' ? $baseDir : ProjectLocator::getRootPath() . '/storage';
    }

    public function read(string $namespace, string $typeName, StoreSchema $meta, DataQueryParam $filters): array
    {
        $path = "{$this->baseDir}/$namespace/$typeName/";
        if (!is_dir($path)) {
            return [];
        }

        $filter = $filters->filter();
        $useIndexes = true;
        $candidateIds = null;
        $fields = array_merge([$meta->idName], $meta->indexFields);
        if ($filter && $filter->isAnd()) {
            // Consulta compuesta: comprobar cada subfiltro
            $candidateSets = [];

            foreach ($filter->elements() as $subFilter) {
                if (!$subFilter->isCondition()) {
                    $useIndexes = false;
                    break;
                }

                $field = $subFilter->elements()[0]->field();
                $operator = $subFilter->elements()[0]->operator();
                $value = $subFilter->elements()[0]->value();

                if (!in_array($field, $fields) || !in_array($operator, [DataQueryOperator::EQ, DataQueryOperator::IN])) {
                    $useIndexes = false;
                    break;
                }

                $indexFile = "{$path}index_{$field}.json";
                if (!file_exists($indexFile)) {
                    $useIndexes = false;
                    break;
                }

                $index = json_decode(file_get_contents($indexFile), true);
                if (!$index) {
                    $useIndexes = false;
                    break;
                }

                $ids = [];

                if ($operator === DataQueryOperator::EQ && isset($index[$value])) {
                    $ids = $index[$value];
                } elseif ($operator === DataQueryOperator::IN && is_array($value)) {
                    foreach ($value as $v) {
                        if (isset($index[$v])) {
                            $ids = array_merge($ids, $index[$v]);
                        }
                    }
                }

                $candidateSets[] = $ids;
            }

            if ($useIndexes && !empty($candidateSets)) {
                // Intersección de todos los sets de IDs
                $candidateIds = array_shift($candidateSets);
                foreach ($candidateSets as $ids) {
                    $candidateIds = array_intersect($candidateIds, $ids);
                }
                $candidateIds = array_values($candidateIds);
            }
        } elseif ($filter && $filter->isCondition()) {
            // Consulta simple de un único campo
            $field = $filter->elements()[0]->field();
            $operator = $filter->elements()[0]->operator();
            $value = $filter->elements()[0]->value();

            if (in_array($field, $fields) && in_array($operator, [DataQueryOperator::EQ, DataQueryOperator::IN])) {
                $indexFile = "{$path}index_{$field}.json";
                if (file_exists($indexFile)) {
                    $index = json_decode(file_get_contents($indexFile), true);
                    if ($index) {
                        if ($operator === DataQueryOperator::EQ && isset($index[$value])) {
                            $candidateIds = $index[$value];
                        } elseif ($operator === DataQueryOperator::IN && is_array($value)) {
                            $candidateIds = [];
                            foreach ($value as $v) {
                                if (isset($index[$v])) {
                                    $candidateIds = array_merge($candidateIds, $index[$v]);
                                }
                            }
                        }
                    }
                }
            }
        }

        $items = [];

        if ($candidateIds !== null) {
            // Leer solo los IDs candidatos
            foreach ($candidateIds as $id) {
                $file = $this->getPathForId($namespace, $typeName, $id);
                if (file_exists($file)) {
                    $data = json_decode(file_get_contents($file), true);
                    if ($this->matchesFilter($data, $filters->filter(), $namespace)) {
                        $items[] = $data;
                    }
                }
            }
        } else {
            // Fallback: leer todos los ficheros recursivamente
            $directory = new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS);
            $iterator = new \RecursiveIteratorIterator($directory);

            foreach ($iterator as $fileInfo) {
                if (!$fileInfo->isFile()) {
                    continue;
                }

                $filename = $fileInfo->getFilename();
                if (str_starts_with($filename, 'index_') || pathinfo($filename, PATHINFO_EXTENSION) !== 'json') {
                    continue;
                }

                $data = json_decode(file_get_contents($fileInfo->getPathname()), true);
                if ($this->matchesFilter($data, $filters->filter(), $namespace)) {
                    $items[] = $data;
                }
            }
        }

        // Aplicar orden, since, limit
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
        return array_values($items);
    }


    public function _old_read(string $namespace, string $typeName, DataQueryParam $filters): array
    {
        $path = "{$this->baseDir}/$namespace/$typeName/data/";
        if (!is_dir($path)) {
            return [];
        }
        $items = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path)) as $fileInfo) {
            if ($fileInfo->isFile() && pathinfo($fileInfo->getFilename(), PATHINFO_EXTENSION) === 'json') {
                if (str_starts_with($fileInfo->getFilename(), 'index_')) {
                    continue;
                }
                $data = json_decode(file_get_contents($fileInfo->getPathname()), true);
                if ($this->matchesFilter($data, $filters->filter(), $namespace)) {
                    $items[] = $data;
                }
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
        return array_values($items);
    }

    public function save(string $namespace, string $typeName, StoreSchema $meta, array $data)
    {
        register_shutdown_function(function () use ($namespace, $typeName, $meta, $data) {
            $this->updateIndexes($namespace, $typeName, $data, $meta);
        });
        $id = $data[$meta->idName];
        $file = $this->getPathForId($namespace, $typeName, $id);
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0755, true);
        }
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

    public function delete(string $namespace, string $typeName, array $read, StoreSchema $meta): void
    {
        $idName = $meta->idName;
        $file = $this->getPathForId($namespace, $typeName, $read[$idName]);
        if (is_file($file)) {
            $data = json_decode(file_get_contents($file), true);
            unlink($file);
            register_shutdown_function(function () use ($namespace, $typeName, $meta, $data) {
                $this->removeFromIndexes($namespace, $typeName, $data, $meta);
            });
        }
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
                $file = $this->getPathForId($namespace, $relatedType, $relatedId);
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

    private function getPathForId(string $namespace, string $typeName, string $id): string
    {
        $path = "{$this->baseDir}/$namespace/$typeName/data/";
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        $prefix1 = substr($id, 0, 2);
        $prefix2 = substr($id, 2, 2);
        return "{$path}/$prefix1/$prefix2/$id.json";
    }

    private function acquireLock(string $path, int $timeout = 30): void
    {
        $lockFile = "{$path}/.index.lock";
        $start = time();

        while (file_exists($lockFile)) {
            if (time() - $start > $timeout) {
                throw new \RuntimeException("Timeout esperando el lock: $lockFile");
            }
            usleep(100_000); // Espera 100ms antes de volver a comprobar
        }

        // Crear el lock
        file_put_contents($lockFile, (string)getmypid());
    }

    private function releaseLock(string $path): void
    {
        $lockFile = "{$path}/.index.lock";
        if (file_exists($lockFile)) {
            unlink($lockFile);
        }
    }

    private function updateIndexes(string $namespace, string $typeName, array $data, StoreSchema $meta): void
    {
        $id = $data[$meta->idName];
        $fields = array_merge([$meta->idName], $meta->indexFields);
        $all_indexes = true;
        foreach ($fields as $field) {
            $indexFile = "{$this->baseDir}/$namespace/$typeName/index_{$field}.json";
            if (!file_exists($indexFile)) {
                $all_indexes = false;
                break;
            }
        }
        if ($all_indexes) {
            $path = "{$this->baseDir}/$namespace/$typeName/";
            $this->acquireLock($path);
            try {
                foreach ($fields as $field) {
                    if (!array_key_exists($field, $data)) {
                        continue;
                    }
                    $indexFile = "{$this->baseDir}/$namespace/$typeName/index_{$field}.json";
                    $index = file_exists($indexFile)
                        ? json_decode(file_get_contents($indexFile), true)
                        : [];

                    // Borrar ID de cualquier valor antiguo (si ya estaba en otro valor)
                    foreach ($index as $value => &$ids) {
                        $ids = array_filter($ids, fn($existingId) => $existingId !== $id);
                    }
                    unset($ids);

                    // Añadir ID al valor nuevo
                    $value = (string) $data[$field];
                    $index[$value][] = $id;

                    // Guardar el índice actualizado
                    file_put_contents($indexFile, json_encode($index, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                }
            } finally {
                // Liberar el lock pase lo que pase
                $this->releaseLock($path);
            }
        } else {
            $this->rebuildIndexes($namespace, $typeName, $meta);
        }
    }

    private function removeFromIndexes(string $namespace, string $typeName, array $data, StoreSchema $meta): void
    {
        $id = $data[$meta->idName];
        $fields = array_merge([$meta->idName], $meta->indexFields);
        $all_indexes = true;
        foreach ($fields as $field) {
            $indexFile = "{$this->baseDir}/$namespace/$typeName/index_{$field}.json";
            if (!file_exists($indexFile)) {
                $all_indexes = false;
                break;
            }
        }
        if ($all_indexes) {
            $path = "{$this->baseDir}/$namespace/$typeName/";
            $this->acquireLock($path);
            try {
                foreach ($fields as $field) {
                    if (!array_key_exists($field, $data)) {
                        continue;
                    }
                    $indexFile = "{$this->baseDir}/$namespace/$typeName/index_{$field}.json";
                    if (!file_exists($indexFile)) {
                        continue;
                    }
                    $index = json_decode(file_get_contents($indexFile), true);

                    $value = (string) $data[$field];
                    if (isset($index[$value])) {
                        $index[$value] = array_filter($index[$value], fn($existingId) => $existingId !== $id);
                        if (empty($index[$value])) {
                            unset($index[$value]);
                        }
                    }
                    file_put_contents($indexFile, json_encode($index, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                }
            } finally {
                // Liberar el lock pase lo que pase
                $this->releaseLock($path);
            }
        } else {
            $this->rebuildIndexes($namespace, $typeName, $meta);
        }
    }

    public function rebuildIndexes(string $namespace, string $typeName, StoreSchema $meta): void
    {
        $fields = array_merge([$meta->idName], $meta->indexFields);
        $path = "{$this->baseDir}/$namespace/$typeName/";
        if (!is_dir($path)) {
            throw new \RuntimeException("Directorio no encontrado: $path");
        }
        $this->acquireLock($path);

        try {

            // Eliminar todos los índices anteriores
            foreach ($fields as $field) {
                $indexFile = "{$path}/index_{$field}.json";
                if (file_exists($indexFile)) {
                    unlink($indexFile);
                }
            }

            // Inicializar índices en memoria
            $indexes = [];
            foreach ($fields as $field) {
                $indexes[$field] = [];
            }

            // Recorrer recursivamente todos los ficheros JSON
            $directory = new \RecursiveDirectoryIterator($path . "/data/", \RecursiveDirectoryIterator::SKIP_DOTS);
            $iterator = new \RecursiveIteratorIterator($directory);

            /** @var \SplFileInfo $fileInfo */
            foreach ($iterator as $fileInfo) {
                if (!$fileInfo->isFile()) {
                    continue;
                }

                $filename = $fileInfo->getFilename();

                // Saltar archivos de índices
                if (str_starts_with($filename, 'index_')) {
                    continue;
                }

                if (pathinfo($filename, PATHINFO_EXTENSION) !== 'json') {
                    continue;
                }

                // Leer el contenido del archivo de registro
                $data = json_decode(file_get_contents($fileInfo->getPathname()), true);
                if (!$data || !isset($data['id'])) {
                    continue; // Saltar archivos corruptos o inválidos
                }

                $id = (string) $data['id'];

                // Para cada campo indexado, añadir el id
                foreach ($fields as $field) {
                    if (isset($data[$field])) {
                        $value = (string) $data[$field];
                        $indexes[$field][$value][] = $id;
                    }
                }
            }

            // Guardar los índices actualizados
            foreach ($indexes as $field => $indexData) {
                $indexFile = "{$path}/index_{$field}.json";
                file_put_contents($indexFile, json_encode($indexData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }
        } finally {
            // Liberar el lock pase lo que pase
            $this->releaseLock($path);
        }
    }
}
