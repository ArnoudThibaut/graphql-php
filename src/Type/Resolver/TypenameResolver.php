<?php


namespace GraphQL\Type\Resolver;


use GraphQL\Type\Definition\ResolveInfo;

class TypenameResolver
{
    public function __invoke($source, $args, $context, ResolveInfo $info)
    {
        return $info->parentType->name;
    }
}