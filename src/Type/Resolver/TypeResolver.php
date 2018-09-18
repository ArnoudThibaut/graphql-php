<?php


namespace GraphQL\Type\Resolver;


use GraphQL\Type\Definition\ResolveInfo;

class TypeResolver
{
    public function __invoke($source, $args, $context, ResolveInfo $info)
    {
        return $info->schema->getType($args['name']);
    }
}