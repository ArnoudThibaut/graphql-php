<?php


namespace GraphQL\Type\Resolver;


use GraphQL\Type\Schema;

class SchemaDirectiveResolver
{
    public function __invoke(Schema $schema)
    {
        return $schema->getDirectives();
    }
}