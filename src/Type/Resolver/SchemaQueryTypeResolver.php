<?php


namespace GraphQL\Type\Resolver;


use GraphQL\Type\Schema;

class SchemaQueryTypeResolver
{
    public function __invoke(Schema $schema) {
        return $schema->getQueryType();
    }
}