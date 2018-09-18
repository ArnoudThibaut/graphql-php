<?php


namespace GraphQL\Type\Resolver;


use GraphQL\Type\Schema;

class SchemaMutationTypeResolver
{
    public function __invoke(Schema $schema)
    {
        return $schema->getMutationType();
    }
}