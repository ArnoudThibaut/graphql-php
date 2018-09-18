<?php


namespace GraphQL\Type\Resolver;


use GraphQL\Type\Definition\ResolveInfo;

class SchemaResolver
{
    public function __invoke($source, $args, $context, ResolveInfo $info)
    {
        return $info->schema;
    }
}