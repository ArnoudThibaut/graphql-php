<?php


namespace GraphQL\Type\FieldsProvider;


use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Type\Definition\WrappingType;
use GraphQL\Type\Introspection;
use GraphQL\Type\TypeKind;
use GraphQL\Utils\Utils;

class TypeFieldsProvider
{
    public function __invoke()
    {
        return [
            'kind' => [
                'type' => Type::nonNull(Introspection::_typeKind()),
                'resolve' => function (Type $type) {
                    switch (true) {
                        case $type instanceof ListOfType:
                            return TypeKind::LIST_KIND;
                        case $type instanceof NonNull:
                            return TypeKind::NON_NULL;
                        case $type instanceof ScalarType:
                            return TypeKind::SCALAR;
                        case $type instanceof ObjectType:
                            return TypeKind::OBJECT;
                        case $type instanceof EnumType:
                            return TypeKind::ENUM;
                        case $type instanceof InputObjectType:
                            return TypeKind::INPUT_OBJECT;
                        case $type instanceof InterfaceType:
                            return TypeKind::INTERFACE_KIND;
                        case $type instanceof UnionType:
                            return TypeKind::UNION;
                        default:
                            throw new \Exception('Unknown kind of type: ' . Utils::printSafe($type));
                    }
                },
            ],
            'name' => ['type' => Type::string()],
            'description' => ['type' => Type::string()],
            'fields' => [
                'type' => Type::listOf(Type::nonNull(Introspection::_field())),
                'args' => [
                    'includeDeprecated' => ['type' => Type::boolean(), 'defaultValue' => false],
                ],
                'resolve' => function (Type $type, $args) {
                    if ($type instanceof ObjectType || $type instanceof InterfaceType) {
                        $fields = $type->getFields();

                        if (empty($args['includeDeprecated'])) {
                            $fields = array_filter(
                                $fields,
                                function (FieldDefinition $field) {
                                    return !$field->deprecationReason;
                                }
                            );
                        }

                        return array_values($fields);
                    }

                    return null;
                },
            ],
            'interfaces' => [
                'type' => Type::listOf(Type::nonNull(Introspection::_type())),
                'resolve' => function ($type) {
                    if ($type instanceof ObjectType) {
                        return $type->getInterfaces();
                    }

                    return null;
                },
            ],
            'possibleTypes' => [
                'type' => Type::listOf(Type::nonNull(Introspection::_type())),
                'resolve' => function ($type, $args, $context, ResolveInfo $info) {
                    if ($type instanceof InterfaceType || $type instanceof UnionType) {
                        return $info->schema->getPossibleTypes($type);
                    }

                    return null;
                },
            ],
            'enumValues' => [
                'type' => Type::listOf(Type::nonNull(Introspection::_enumValue())),
                'args' => [
                    'includeDeprecated' => ['type' => Type::boolean(), 'defaultValue' => false],
                ],
                'resolve' => function ($type, $args) {
                    if ($type instanceof EnumType) {
                        $values = array_values($type->getValues());

                        if (empty($args['includeDeprecated'])) {
                            $values = array_filter(
                                $values,
                                function ($value) {
                                    return !$value->deprecationReason;
                                }
                            );
                        }

                        return $values;
                    }

                    return null;
                },
            ],
            'inputFields' => [
                'type' => Type::listOf(Type::nonNull(Introspection::_inputValue())),
                'resolve' => function ($type) {
                    if ($type instanceof InputObjectType) {
                        return array_values($type->getFields());
                    }

                    return null;
                },
            ],
            'ofType' => [
                'type' => Introspection::_type(),
                'resolve' => function ($type) {
                    if ($type instanceof WrappingType) {
                        return $type->getWrappedType();
                    }

                    return null;
                },
            ],
        ];
    }
}