<?php declare(strict_types=1);

namespace Civi\Store\Service;

use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Schema;
use GraphQL\Utils\BuildSchema;

class ExtractDirectives
{

    public static function fromNode($node): array {
        $directives = [];
        $ast = $node->astNode;
        if ($ast && !empty($ast->directives)) {
            foreach ($ast->directives as $directive) {
                $args = [];
                foreach ($directive->arguments as $arg) {
                    $args[$arg->name->value] = $arg->value->value;
                }
                $directives[$directive->name->value] = $args;
            }
        }
        return $directives;
    }
}