<?php


namespace GraphQL\Type\FieldsProvider;


use GraphQL\Language\Printer;
use GraphQL\Type\Definition\FieldArgument;
use GraphQL\Type\Definition\InputObjectField;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Introspection;
use GraphQL\Utils\AST;

class InputValueFieldsProvider
{
    public function __invoke()
    {
        return [
            'name' => ['type' => Type::nonNull(Type::string())],
            'description' => ['type' => Type::string()],
            'type' => [
                'type' => Type::nonNull(Introspection::_type()),
                'resolve' => function ($value) {
                    return method_exists($value, 'getType') ? $value->getType() : $value->type;
                },
            ],
            'defaultValue' => [
                'type' => Type::string(),
                'description' =>
                    'A GraphQL-formatted string representing the default value for this input value.',
                'resolve' => function ($inputValue) {
                    /** @var FieldArgument|InputObjectField $inputValue */
                    return !$inputValue->defaultValueExists()
                        ? null
                        : Printer::doPrint(AST::astFromValue(
                            $inputValue->defaultValue,
                            $inputValue->getType()
                        ));
                },
            ],
        ];
    }
}