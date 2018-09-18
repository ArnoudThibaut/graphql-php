<?php


namespace GraphQL\Type\Resolver;


use GraphQL\Language\DirectiveLocation;

class DirectiveOnOperationResolver
{
    public function __invoke($d)
    {
        return in_array(DirectiveLocation::QUERY, $d->locations) ||
            in_array(DirectiveLocation::MUTATION, $d->locations) ||
            in_array(DirectiveLocation::SUBSCRIPTION, $d->locations);
    }
}