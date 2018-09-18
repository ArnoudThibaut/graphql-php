<?php

declare(strict_types=1);

namespace GraphQL\Type;

use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\FieldsProvider\FieldFieldsProvider;
use GraphQL\Type\FieldsProvider\InputValueFieldsProvider;
use GraphQL\Type\Resolver\DirectiveFieldsResolver;
use GraphQL\Type\Resolver\DirectiveOnFieldResolver;
use GraphQL\Type\Resolver\DirectiveOnFragmentResolver;
use GraphQL\Type\Resolver\DirectiveOnOperationResolver;
use GraphQL\Type\Resolver\EnumValueIsDeprecatedResolver;
use GraphQL\Type\Resolver\SchemaDirectiveResolver;
use GraphQL\Type\Resolver\SchemaMutationTypeResolver;
use GraphQL\Type\Resolver\SchemaQueryTypeResolver;
use GraphQL\Type\Resolver\SchemaResolver;
use GraphQL\Type\Resolver\SchemaSubscriptionTypeResolver;
use GraphQL\Type\Resolver\SchemaTypesResolver;
use GraphQL\Type\FieldsProvider\TypeFieldsProvider;
use GraphQL\Type\Resolver\TypenameResolver;
use GraphQL\Type\Resolver\TypeResolver;
use function array_key_exists;
use function is_bool;
use function trigger_error;
use const E_USER_DEPRECATED;

class Introspection
{
    /** @var Type[] */
    private static $map = [];

    /**
     * Options:
     *   - descriptions
     *     Whether to include descriptions in the introspection result.
     *     Default: true
     *
     * @param bool[]|bool $options
     * @return string
     */
    public static function getIntrospectionQuery($options = [])
    {
        if (is_bool($options)) {
            trigger_error(
                'Calling Introspection::getIntrospectionQuery(boolean) is deprecated. ' .
                'Please use Introspection::getIntrospectionQuery(["descriptions" => boolean]).',
                E_USER_DEPRECATED
            );
            $descriptions = $options;
        } else {
            $descriptions = ! array_key_exists('descriptions', $options) || $options['descriptions'] === true;
        }
        $descriptionField = $descriptions ? 'description' : '';

        return <<<EOD
  query IntrospectionQuery {
    __schema {
      queryType { name }
      mutationType { name }
      subscriptionType { name }
      types {
        ...FullType
      }
      directives {
        name
        {$descriptionField}
        locations
        args {
          ...InputValue
        }
      }
    }
  }

  fragment FullType on __Type {
    kind
    name
    {$descriptionField}
    fields(includeDeprecated: true) {
      name
      {$descriptionField}
      args {
        ...InputValue
      }
      type {
        ...TypeRef
      }
      isDeprecated
      deprecationReason
    }
    inputFields {
      ...InputValue
    }
    interfaces {
      ...TypeRef
    }
    enumValues(includeDeprecated: true) {
      name
      {$descriptionField}
      isDeprecated
      deprecationReason
    }
    possibleTypes {
      ...TypeRef
    }
  }

  fragment InputValue on __InputValue {
    name
    {$descriptionField}
    type { ...TypeRef }
    defaultValue
  }

  fragment TypeRef on __Type {
    kind
    name
    ofType {
      kind
      name
      ofType {
        kind
        name
        ofType {
          kind
          name
          ofType {
            kind
            name
            ofType {
              kind
              name
              ofType {
                kind
                name
                ofType {
                  kind
                  name
                }
              }
            }
          }
        }
      }
    }
  }
EOD;
    }

    /**
     * @param Type $type
     * @return bool
     */
    public static function isIntrospectionType($type)
    {
        return array_key_exists($type->name, self::getTypes());
    }

    public static function getTypes()
    {
        return [
            '__Schema'            => self::_schema(),
            '__Type'              => self::_type(),
            '__Directive'         => self::_directive(),
            '__Field'             => self::_field(),
            '__InputValue'        => self::_inputValue(),
            '__EnumValue'         => self::_enumValue(),
            '__TypeKind'          => self::_typeKind(),
            '__DirectiveLocation' => self::_directiveLocation(),
        ];
    }

    public static function _schema()
    {
        if (! isset(self::$map['__Schema'])) {
            self::$map['__Schema'] = new ObjectType([
                'name'            => '__Schema',
                'isIntrospection' => true,
                'description'     =>
                    'A GraphQL Schema defines the capabilities of a GraphQL ' .
                    'server. It exposes all available types and directives on ' .
                    'the server, as well as the entry points for query, mutation, and ' .
                    'subscription operations.',
                'fields'          => [
                    'types'            => [
                        'description' => 'A list of all types supported by this server.',
                        'type'        => new NonNull(new ListOfType(new NonNull(self::_type()))),
                        'resolve'     => new SchemaTypesResolver(),
                    ],
                    'queryType'        => [
                        'description' => 'The type that query operations will be rooted at.',
                        'type'        => new NonNull(self::_type()),
                        'resolve'     => new SchemaQueryTypeResolver(),
                    ],
                    'mutationType'     => [
                        'description' =>
                            'If this server supports mutation, the type that ' .
                            'mutation operations will be rooted at.',
                        'type'        => self::_type(),
                        'resolve'     => new SchemaMutationTypeResolver(),
                    ],
                    'subscriptionType' => [
                        'description' => 'If this server support subscription, the type that subscription operations will be rooted at.',
                        'type'        => self::_type(),
                        'resolve'     => new SchemaSubscriptionTypeResolver(),
                    ],
                    'directives'       => [
                        'description' => 'A list of all directives supported by this server.',
                        'type'        => Type::nonNull(Type::listOf(Type::nonNull(self::_directive()))),
                        'resolve'     => new SchemaDirectiveResolver(),
                    ],
                ],
            ]);
        }

        return self::$map['__Schema'];
    }

    public static function _type()
    {
        if (! isset(self::$map['__Type'])) {
            self::$map['__Type'] = new ObjectType([
                'name'            => '__Type',
                'isIntrospection' => true,
                'description'     =>
                    'The fundamental unit of any GraphQL Schema is the type. There are ' .
                    'many kinds of types in GraphQL as represented by the `__TypeKind` enum.' .
                    "\n\n" .
                    'Depending on the kind of a type, certain fields describe ' .
                    'information about that type. Scalar types provide no information ' .
                    'beyond a name and description, while Enum types provide their values. ' .
                    'Object and Interface types provide the fields they describe. Abstract ' .
                    'types, Union and Interface, provide the Object types possible ' .
                    'at runtime. List and NonNull types compose other types.',
                'fields'          => new TypeFieldsProvider(),
            ]);
        }

        return self::$map['__Type'];
    }

    public static function _typeKind()
    {
        if (! isset(self::$map['__TypeKind'])) {
            self::$map['__TypeKind'] = new EnumType([
                'name'            => '__TypeKind',
                'isIntrospection' => true,
                'description'     => 'An enum describing what kind of type a given `__Type` is.',
                'values'          => [
                    'SCALAR'       => [
                        'value'       => TypeKind::SCALAR,
                        'description' => 'Indicates this type is a scalar.',
                    ],
                    'OBJECT'       => [
                        'value'       => TypeKind::OBJECT,
                        'description' => 'Indicates this type is an object. `fields` and `interfaces` are valid fields.',
                    ],
                    'INTERFACE'    => [
                        'value'       => TypeKind::INTERFACE_KIND,
                        'description' => 'Indicates this type is an interface. `fields` and `possibleTypes` are valid fields.',
                    ],
                    'UNION'        => [
                        'value'       => TypeKind::UNION,
                        'description' => 'Indicates this type is a union. `possibleTypes` is a valid field.',
                    ],
                    'ENUM'         => [
                        'value'       => TypeKind::ENUM,
                        'description' => 'Indicates this type is an enum. `enumValues` is a valid field.',
                    ],
                    'INPUT_OBJECT' => [
                        'value'       => TypeKind::INPUT_OBJECT,
                        'description' => 'Indicates this type is an input object. `inputFields` is a valid field.',
                    ],
                    'LIST'         => [
                        'value'       => TypeKind::LIST_KIND,
                        'description' => 'Indicates this type is a list. `ofType` is a valid field.',
                    ],
                    'NON_NULL'     => [
                        'value'       => TypeKind::NON_NULL,
                        'description' => 'Indicates this type is a non-null. `ofType` is a valid field.',
                    ],
                ],
            ]);
        }

        return self::$map['__TypeKind'];
    }

    public static function _field()
    {
        if (! isset(self::$map['__Field'])) {
            self::$map['__Field'] = new ObjectType([
                'name'            => '__Field',
                'isIntrospection' => true,
                'description'     =>
                    'Object and Interface types are described by a list of Fields, each of ' .
                    'which has a name, potentially a list of arguments, and a return type.',
                'fields'          => new FieldFieldsProvider(),
            ]);
        }

        return self::$map['__Field'];
    }

    public static function _inputValue()
    {
        if (! isset(self::$map['__InputValue'])) {
            self::$map['__InputValue'] = new ObjectType([
                'name'            => '__InputValue',
                'isIntrospection' => true,
                'description'     =>
                    'Arguments provided to Fields or Directives and the input fields of an ' .
                    'InputObject are represented as Input Values which describe their type ' .
                    'and optionally a default value.',
                'fields'          => new InputValueFieldsProvider(),
            ]);
        }

        return self::$map['__InputValue'];
    }

    public static function _enumValue()
    {
        if (! isset(self::$map['__EnumValue'])) {
            self::$map['__EnumValue'] = new ObjectType([
                'name'            => '__EnumValue',
                'isIntrospection' => true,
                'description'     =>
                    'One possible value for a given Enum. Enum values are unique values, not ' .
                    'a placeholder for a string or numeric value. However an Enum value is ' .
                    'returned in a JSON response as a string.',
                'fields'          => [
                    'name'              => ['type' => Type::nonNull(Type::string())],
                    'description'       => ['type' => Type::string()],
                    'isDeprecated'      => [
                        'type'    => Type::nonNull(Type::boolean()),
                        'resolve' => new EnumValueIsDeprecatedResolver(),
                    ],
                    'deprecationReason' => [
                        'type' => Type::string(),
                    ],
                ],
            ]);
        }

        return self::$map['__EnumValue'];
    }

    public static function _directive()
    {
        if (! isset(self::$map['__Directive'])) {
            self::$map['__Directive'] = new ObjectType([
                'name'            => '__Directive',
                'isIntrospection' => true,
                'description'     => 'A Directive provides a way to describe alternate runtime execution and ' .
                    'type validation behavior in a GraphQL document.' .
                    "\n\nIn some cases, you need to provide options to alter GraphQL's " .
                    'execution behavior in ways field arguments will not suffice, such as ' .
                    'conditionally including or skipping a field. Directives provide this by ' .
                    'describing additional information to the executor.',
                'fields'          => [
                    'name'        => ['type' => Type::nonNull(Type::string())],
                    'description' => ['type' => Type::string()],
                    'locations'   => [
                        'type' => Type::nonNull(Type::listOf(Type::nonNull(
                            self::_directiveLocation()
                        ))),
                    ],
                    'args'        => [
                        'type'    => Type::nonNull(Type::listOf(Type::nonNull(self::_inputValue()))),
                        'resolve' => new DirectiveFieldsResolver(),
                    ],

                    // NOTE: the following three fields are deprecated and are no longer part
                    // of the GraphQL specification.
                    'onOperation' => [
                        'deprecationReason' => 'Use `locations`.',
                        'type'              => Type::nonNull(Type::boolean()),
                        'resolve'           => new DirectiveOnOperationResolver(),
                    ],
                    'onFragment'  => [
                        'deprecationReason' => 'Use `locations`.',
                        'type'              => Type::nonNull(Type::boolean()),
                        'resolve'           => new DirectiveOnFragmentResolver(),
                    ],
                    'onField'     => [
                        'deprecationReason' => 'Use `locations`.',
                        'type'              => Type::nonNull(Type::boolean()),
                        'resolve'           => new DirectiveOnFieldResolver(),
                    ],
                ],
            ]);
        }

        return self::$map['__Directive'];
    }

    public static function _directiveLocation()
    {
        if (! isset(self::$map['__DirectiveLocation'])) {
            self::$map['__DirectiveLocation'] = new EnumType([
                'name'            => '__DirectiveLocation',
                'isIntrospection' => true,
                'description'     =>
                    'A Directive can be adjacent to many parts of the GraphQL language, a ' .
                    '__DirectiveLocation describes one such possible adjacencies.',
                'values'          => [
                    'QUERY'                  => [
                        'value'       => DirectiveLocation::QUERY,
                        'description' => 'Location adjacent to a query operation.',
                    ],
                    'MUTATION'               => [
                        'value'       => DirectiveLocation::MUTATION,
                        'description' => 'Location adjacent to a mutation operation.',
                    ],
                    'SUBSCRIPTION'           => [
                        'value'       => DirectiveLocation::SUBSCRIPTION,
                        'description' => 'Location adjacent to a subscription operation.',
                    ],
                    'FIELD'                  => [
                        'value'       => DirectiveLocation::FIELD,
                        'description' => 'Location adjacent to a field.',
                    ],
                    'FRAGMENT_DEFINITION'    => [
                        'value'       => DirectiveLocation::FRAGMENT_DEFINITION,
                        'description' => 'Location adjacent to a fragment definition.',
                    ],
                    'FRAGMENT_SPREAD'        => [
                        'value'       => DirectiveLocation::FRAGMENT_SPREAD,
                        'description' => 'Location adjacent to a fragment spread.',
                    ],
                    'INLINE_FRAGMENT'        => [
                        'value'       => DirectiveLocation::INLINE_FRAGMENT,
                        'description' => 'Location adjacent to an inline fragment.',
                    ],
                    'SCHEMA'                 => [
                        'value'       => DirectiveLocation::SCHEMA,
                        'description' => 'Location adjacent to a schema definition.',
                    ],
                    'SCALAR'                 => [
                        'value'       => DirectiveLocation::SCALAR,
                        'description' => 'Location adjacent to a scalar definition.',
                    ],
                    'OBJECT'                 => [
                        'value'       => DirectiveLocation::OBJECT,
                        'description' => 'Location adjacent to an object type definition.',
                    ],
                    'FIELD_DEFINITION'       => [
                        'value'       => DirectiveLocation::FIELD_DEFINITION,
                        'description' => 'Location adjacent to a field definition.',
                    ],
                    'ARGUMENT_DEFINITION'    => [
                        'value'       => DirectiveLocation::ARGUMENT_DEFINITION,
                        'description' => 'Location adjacent to an argument definition.',
                    ],
                    'INTERFACE'              => [
                        'value'       => DirectiveLocation::IFACE,
                        'description' => 'Location adjacent to an interface definition.',
                    ],
                    'UNION'                  => [
                        'value'       => DirectiveLocation::UNION,
                        'description' => 'Location adjacent to a union definition.',
                    ],
                    'ENUM'                   => [
                        'value'       => DirectiveLocation::ENUM,
                        'description' => 'Location adjacent to an enum definition.',
                    ],
                    'ENUM_VALUE'             => [
                        'value'       => DirectiveLocation::ENUM_VALUE,
                        'description' => 'Location adjacent to an enum value definition.',
                    ],
                    'INPUT_OBJECT'           => [
                        'value'       => DirectiveLocation::INPUT_OBJECT,
                        'description' => 'Location adjacent to an input object type definition.',
                    ],
                    'INPUT_FIELD_DEFINITION' => [
                        'value'       => DirectiveLocation::INPUT_FIELD_DEFINITION,
                        'description' => 'Location adjacent to an input object field definition.',
                    ],

                ],
            ]);
        }

        return self::$map['__DirectiveLocation'];
    }

    public static function schemaMetaFieldDef()
    {
        if (! isset(self::$map['__schema'])) {
            self::$map['__schema'] = FieldDefinition::create([
                'name'        => '__schema',
                'type'        => Type::nonNull(self::_schema()),
                'description' => 'Access the current type schema of this server.',
                'args'        => [],
                'resolve'     => new SchemaResolver(),
            ]);
        }

        return self::$map['__schema'];
    }

    public static function typeMetaFieldDef()
    {
        if (! isset(self::$map['__type'])) {
            self::$map['__type'] = FieldDefinition::create([
                'name'        => '__type',
                'type'        => self::_type(),
                'description' => 'Request the type information of a single type.',
                'args'        => [
                    ['name' => 'name', 'type' => Type::nonNull(Type::string())],
                ],
                'resolve'     => new TypeResolver(),
            ]);
        }

        return self::$map['__type'];
    }

    public static function typeNameMetaFieldDef()
    {
        if (! isset(self::$map['__typename'])) {
            self::$map['__typename'] = FieldDefinition::create([
                'name'        => '__typename',
                'type'        => Type::nonNull(Type::string()),
                'description' => 'The name of the current Object type at runtime.',
                'args'        => [],
                'resolve'     => new TypenameResolver(),
            ]);
        }

        return self::$map['__typename'];
    }
}
