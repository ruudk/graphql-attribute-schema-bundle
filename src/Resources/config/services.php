<?php

declare(strict_types=1);

use Jerowork\GraphqlAttributeSchema\Parser;
use Jerowork\GraphqlAttributeSchema\ParserFactory;
use Jerowork\GraphqlAttributeSchema\SchemaBuilder;
use Jerowork\GraphqlAttributeSchema\SchemaBuilderFactory;
use Jerowork\GraphqlAttributeSchemaBundle\AstProvider;
use Jerowork\GraphqlAttributeSchemaBundle\SchemaProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\ServiceLocator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(ParserFactory::class);

    $services->set(Parser::class)
        ->factory([service(ParserFactory::class), 'create']);

    $services->set(SchemaBuilderFactory::class);

    // Compile-time service locator. Populated by BuildResolverLocatorPass with
    // every service id referenced by #[Autowire] on a resolver method, plus the
    // Query/Mutation/deferred-loader class names that the runtime resolver
    // looks up by FQCN. Starts empty; the pass overwrites the first argument.
    $services->set('graphql_attribute_schema.resolver_locator', ServiceLocator::class)
        ->args([[]])
        ->tag('container.service_locator');

    $services->set(SchemaBuilder::class)
        ->factory([service(SchemaBuilderFactory::class), 'create'])
        ->args([service('graphql_attribute_schema.resolver_locator')]);

    $services->set(AstProvider::class)
        ->args([service(Parser::class), '%graphql_attribute_schema.scan_paths%']);

    $services->set(SchemaProvider::class)
        ->args([service(SchemaBuilder::class), service(AstProvider::class)])
        ->public();
};
