<?php


namespace GraphQL\Type\FieldsProvider;


use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Introspection;

class FieldFieldsProvider
{
    public function __invoke()
    {
        return [
            'name' => ['type' => Type::nonNull(Type::string())],
            'description' => ['type' => Type::string()],
            'args' => [
                'type' => Type::nonNull(Type::listOf(Type::nonNull(Introspection::_inputValue()))),
                'resolve' => function (FieldDefinition $field) {
                    return empty($field->args) ? [] : $field->args;
                },
            ],
            'type' => [
                'type' => Type::nonNull(Introspection::_type()),
                'resolve' => function (FieldDefinition $field) {
                    return $field->getType();
                },
            ],
            'isDeprecated' => [
                'type' => Type::nonNull(Type::boolean()),
                'resolve' => function (FieldDefinition $field) {
                    return (bool)$field->deprecationReason;
                },
            ],
            'deprecationReason' => [
                'type' => Type::string(),
            ],
        ];
    }
}