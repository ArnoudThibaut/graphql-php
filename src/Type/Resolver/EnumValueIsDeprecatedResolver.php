<?php

namespace GraphQL\Type\Resolver;


class EnumValueIsDeprecatedResolver
{
    public function __invoke($enumValue)
    {
        return (bool)$enumValue->deprecationReason;
    }
}