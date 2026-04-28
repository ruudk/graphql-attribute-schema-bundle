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
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->arrayNode('scan_paths')
                    ->scalarPrototype()->end()
                    ->defaultValue([])
                ->end()
            ->end();
    }

    /**
     * @param array{scan_paths?: list<string>} $config
     */
    #[Override]
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('Resources/config/services.php');

        $builder->setParameter('graphql_attribute_schema.scan_paths', $config['scan_paths'] ?? []);

        $tagAsResolver = static function (ChildDefinition $definition): void {
            $definition->addTag(self::RESOLVER_TAG);
        };

        // Class-level attributes — the 1-arg closure registers as a class configurator.
        foreach ([Type::class, InputType::class, InterfaceType::class, Enum::class, Scalar::class] as $attribute) {
            $builder->registerAttributeForAutoconfiguration($attribute, $tagAsResolver);
        }

        // Method-level attributes — Symfony inspects the 3rd parameter's type to know where to call
        // this configurator. Reflector matches all targets, but Query/Mutation are TARGET_METHOD so
        // it only ever fires on method reflectors. The closure tags the *class* definition: root
        // resolvers are services we look up by FQCN at runtime.
        $builder->registerAttributeForAutoconfiguration(
            Query::class,
            static function (ChildDefinition $definition, Query $attr, Reflector $reflector) use ($tagAsResolver): void {
                $tagAsResolver($definition);
            },
        );
        $builder->registerAttributeForAutoconfiguration(
            Mutation::class,
            static function (ChildDefinition $definition, Mutation $attr, Reflector $reflector) use ($tagAsResolver): void {
                $tagAsResolver($definition);
            },
        );
    }

    #[Override]
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new BuildResolverLocatorPass());
    }
}
