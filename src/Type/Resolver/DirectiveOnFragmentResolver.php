<?php


namespace GraphQL\Type\Resolver;


use GraphQL\Language\DirectiveLocation;

class DirectiveOnFragmentResolver
{
    public function __invoke($d)
    {
        return in_array(DirectiveLocation::FRAGMENT_SPREAD, $d->locations) ||
            in_array(DirectiveLocation::INLINE_FRAGMENT, $d->locations) ||
            in_array(DirectiveLocation::FRAGMENT_DEFINITION, $d->locations);
    }
}