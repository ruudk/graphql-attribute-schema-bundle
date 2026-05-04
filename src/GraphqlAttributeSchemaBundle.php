<?php

declare(strict_types=1);

namespace Jerowork\GraphqlAttributeSchemaBundle;

use Jerowork\GraphqlAttributeSchema\Attribute\Autowire;
use Jerowork\GraphqlAttributeSchema\Attribute\Enum;
use Jerowork\GraphqlAttributeSchema\Attribute\InputType;
use Jerowork\GraphqlAttributeSchema\Attribute\InterfaceType;
use Jerowork\GraphqlAttributeSchema\Attribute\Mutation;
use Jerowork\GraphqlAttributeSchema\Attribute\Query;
use Jerowork\GraphqlAttributeSchema\Attribute\Scalar;
use Jerowork\GraphqlAttributeSchema\Attribute\Type;
use Jerowork\GraphqlAttributeSchema\Parser;
use Jerowork\GraphqlAttributeSchema\ParserFactory;
use Jerowork\GraphqlAttributeSchema\SchemaBuilder;
use Jerowork\GraphqlAttributeSchema\SchemaBuilderFactory;
use Jerowork\GraphqlAttributeSchemaBundle\DependencyInjection\Compiler\BuildResolverLocatorPass;
use Override;
use ReflectionMethod;
use ReflectionParameter;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

final class GraphqlAttributeSchemaBundle extends AbstractBundle
{
    public const string ADD_TO_SERVICE_LOCATOR_TAG = 'graphql_attribute_schema.add_to_service_locator';
    public const string TYPE_TAG = 'graphql_attribute_schema.type';

    #[Override]
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $services = $container->services();

        $services->set(ParserFactory::class);

        $services->set(Parser::class)->factory([service(ParserFactory::class), 'create']);

        $services->set(SchemaBuilderFactory::class);

        $services->set(SchemaBuilder::class)
            ->factory([service(SchemaBuilderFactory::class), 'create'])
            ->args([service('graphql_attribute_schema.resolver_locator')]);
    }

    #[Override]
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        foreach ([Query::class, Mutation::class] as $attribute) {
            $container->registerAttributeForAutoconfiguration(
                $attribute,
                static function (ChildDefinition $definition, object $attribute, ReflectionMethod $reflector) : void {
                    $definition->addTag(self::ADD_TO_SERVICE_LOCATOR_TAG);
                }
            );
        }
        
        $container->registerAttributeForAutoconfiguration(Type::class, static function (ChildDefinition $definition): void {
            $definition->addResourceTag(self::TYPE_TAG);
        });

        $container->addCompilerPass(new BuildResolverLocatorPass());
    }
}
