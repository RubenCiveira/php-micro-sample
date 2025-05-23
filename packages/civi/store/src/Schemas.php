<?php

declare(strict_types=1);

namespace Civi\Store;

use Civi\Store\Gateway\SchemaGateway;
use Civi\Store\Service\GraphQlEnrich;
use Civi\Store\Service\JsonSchemaGenerator;
use Civi\Micro\ProjectLocator;
use GraphQL\Type\Schema;
use GraphQL\Utils\BuildSchema;

class Schemas
{
    private static array $registered = [];
    private readonly string $baseDir;

    public function __construct(private readonly SchemaGateway $schemas, string $base = '')
    {
        $this->baseDir = $base !== '' ? $base : ProjectLocator::getRootPath();
        foreach (self::$registered as $namesace => $directory) {
            $schemas->install($namesace, $directory);
        }
    }

    public static function register(string $namespace, string $directory)
    {
        self::$registered[$namespace] = $directory;
    }

    public function sdl(string $namespace): string
    {
        [, $file] = $this->buildSchema($namespace);
        return file_get_contents($file);
    }

    public function schema(string $namespace): Schema
    {
        [$schema] = $this->buildSchema($namespace);
        return $schema;
    }

    public function jsonSchema(string $namespace, string $resource): array
    {
        [$schema] = $this->buildSchema($namespace);
        $generator = new JsonSchemaGenerator();
        $jsonSchema = $generator->generateSchema($schema, $resource);
        $cache = "{$this->baseDir}/.cache/schemas";
        file_put_contents("{$cache}/{$namespace}-{$resource}.json-schema", $jsonSchema);
        return json_decode($jsonSchema, true);
    }

    private function buildSchema(string $namespace): array
    {
        $result = [];
        $cache = "{$this->baseDir}/.cache/schemas";
        if (!is_dir($cache)) {
            mkdir($cache, 0755, true);
        }

        $enrich = new GraphQlEnrich();
        $newContent = $enrich->augmentAndSave($this->loadSdlBase($namespace));
        file_put_contents("{$cache}/{$namespace}-expand.graphql", $newContent);
        $result[] = BuildSchema::build($newContent);
        $result[] = "{$cache}/{$namespace}-expand.graphql";

        // $openApi = new OpenApiGenerator();
        // $swagger = $openApi->generateOpenApi( $newContent );
        // file_put_contents("{$cache}/{$namespace}-openapi.yaml" , $swagger);
        // $result[] = "{$cache}/{$namespace}-openapi.yaml";
        return $result;
    }
    private function loadSdlBase(string $namespace): string
    {
        return $this->schemas->sdl($namespace);
    }
}
