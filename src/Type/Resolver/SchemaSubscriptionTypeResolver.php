<?php


namespace GraphQL\Type\Resolver;


use GraphQL\Type\Schema;

class SchemaSubscriptionTypeResolver
{
    public function __invoke(Schema $schema)
    {
        return $schema->getSubscriptionType();
    }
}