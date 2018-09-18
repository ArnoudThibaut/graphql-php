<?php


namespace GraphQL\Type\Resolver;


use GraphQL\Type\Definition\Directive;

class DirectiveFieldsResolver
{
 public function __invoke(Directive $directive)
 {
     return $directive->args ?: [];
 }
}