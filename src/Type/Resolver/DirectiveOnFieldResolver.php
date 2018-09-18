<?php


namespace GraphQL\Type\Resolver;


use GraphQL\Language\DirectiveLocation;

class DirectiveOnFieldResolver
{
    public function __invoke($d)
    {
        return in_array(DirectiveLocation::FIELD, $d->locations);
    }
}