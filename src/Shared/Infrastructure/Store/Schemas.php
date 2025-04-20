<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\Store;

use Civi\Repomanager\Shared\Infrastructure\Store\Gateway\SchemaGateway;
use Civi\Repomanager\Shared\Infrastructure\Store\Service\GraphQlEnrich;
use GraphQL\Type\Schema;
use GraphQL\Utils\BuildSchema;

class Schemas
{
    private readonly string $baseDir;

    public function __construct(private readonly SchemaGateway $schemas, string $base = __DIR__ . '/../../../../')
    {
        $this->baseDir = $base;
    }

    public function sdl(string $namespace): string
    {
        [,$file] = $this->buildSchema($namespace);
        return file_get_contents( $file );
    }


    public function schema(string $namespace): Schema
    {
        [$schema] = $this->buildSchema($namespace);
        return $schema;
    }

    private function buildSchema(string $namespace)
    {
        $result = [];
        $cache = "{$this->baseDir}/.cache/schemas";
        if (!is_dir($cache)) {
            mkdir($cache, 0755, true);
        }

        $enrich = new GraphQlEnrich();
        $newContent = $enrich->augmentAndSave($this->loadSdlBase($namespace));
        file_put_contents("{$cache}/{$namespace}-expand.graphql" , $newContent);
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