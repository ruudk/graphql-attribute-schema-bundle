<?php

declare(strict_types=1);

namespace Jerowork\GraphqlAttributeSchemaBundle;

use Jerowork\GraphqlAttributeSchema\Attribute\Enum;
use Jerowork\GraphqlAttributeSchema\Attribute\InputType;
use Jerowork\GraphqlAttributeSchema\Attribute\InterfaceType;
use Jerowork\GraphqlAttributeSchema\Attribute\Mutation;
use Jerowork\GraphqlAttributeSchema\Attribute\Query;
use Jerowork\GraphqlAttributeSchema\Attribute\Scalar;
use Jerowork\GraphqlAttributeSchema\Attribute\Type;
use Jerowork\GraphqlAttributeSchemaBundle\DependencyInjection\Compiler\BuildResolverLocatorPass;
use Override;
use Reflector;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class GraphqlAttributeSchemaBundle extends AbstractBundle
{
    public const string RESOLVER_TAG = 'graphql_attribute_schema.resolver_class';

    #[Override]
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('Resources/config/services.php');

        foreach ([Query::class, Mutation::class, Type::class, InputType::class, InterfaceType::class, Enum::class, Scalar::class] as $attribute) {
            $builder->registerAttributeForAutoconfiguration($attribute, static function (ChildDefinition $definition): void {
                $definition->addTag(self::RESOLVER_TAG);
            });
        }
    }

    #[Override]
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new BuildResolverLocatorPass());
    }
}
