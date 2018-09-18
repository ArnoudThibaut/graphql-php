<?php

namespace GraphQL\Type\Resolver;

use GraphQL\Type\Schema;

class SchemaTypesResolver
{
    public function __invoke(Schema $schema)
    {
        return array_values($schema->getTypeMap());
    }
}