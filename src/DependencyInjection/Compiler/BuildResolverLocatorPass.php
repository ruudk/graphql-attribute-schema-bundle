<?php

declare(strict_types=1);

namespace Jerowork\GraphqlAttributeSchemaBundle\DependencyInjection\Compiler;

use Jerowork\GraphqlAttributeSchema\Attribute\Autowire;
use Jerowork\GraphqlAttributeSchema\Attribute\Field;
use Jerowork\GraphqlAttributeSchema\Attribute\Mutation;
use Jerowork\GraphqlAttributeSchema\Attribute\Query;
use Jerowork\GraphqlAttributeSchemaBundle\GraphqlAttributeSchemaBundle;
use Override;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class BuildResolverLocatorPass implements CompilerPassInterface
{
    #[Override]
    public function process(ContainerBuilder $container): void
    {
        /**
         * @var array<string, Reference> $refs
         */
        $refs = [];

        foreach (array_keys($container->findTaggedServiceIds(GraphqlAttributeSchemaBundle::RESOLVER_TAG)) as $id) {
            /**
             * @var class-string $className
             */
            $className = $container->getDefinition($id)->getClass() ?? $id;

            $this->collectFromClass(new ReflectionClass($className), $container, $refs);
        }

        $container->getDefinition('graphql_attribute_schema.resolver_locator')->setArgument(0, $refs);
    }

    /**
     * @param ReflectionClass<object>  $reflection
     * @param array<string, Reference> $refs
     */
    private function collectFromClass(ReflectionClass $reflection, ContainerBuilder $container, array &$refs): void
    {
        $className = $reflection->getName();

        // Root resolvers (Query / Mutation classes) are fetched by FQCN from the container at runtime.
        // Register the class as a service if not already, then expose it via the locator.
        foreach ($reflection->getMethods() as $method) {
            if ($method->getAttributes(Query::class) !== [] || $method->getAttributes(Mutation::class) !== []) {
                $this->ensureServiceRegistered($className, $container);
                $refs[$className] = new Reference($className);
                break;
            }
        }

        foreach ($reflection->getMethods() as $method) {
            $this->collectFromMethod($method, $container, $refs);
        }
    }

    /**
     * @param array<string, Reference> $refs
     */
    private function collectFromMethod(ReflectionMethod $method, ContainerBuilder $container, array &$refs): void
    {
        foreach ($method->getParameters() as $parameter) {
            foreach ($parameter->getAttributes(Autowire::class) as $attr) {
                $autowire = $attr->newInstance();
                $serviceId = $autowire->service ?? $this->parameterTypeName($parameter);

                if ($serviceId === null) {
                    continue;
                }

                $refs[$serviceId] = new Reference($serviceId);
            }
        }

        // Deferred type loaders are fetched by FQCN from the container at runtime.
        foreach ($method->getAttributes(Field::class) as $fieldAttr) {
            $field = $fieldAttr->newInstance();
            if ($field->deferredTypeLoader !== null) {
                $loader = $field->deferredTypeLoader;
                $this->ensureServiceRegistered($loader, $container);
                $refs[$loader] = new Reference($loader);
            }
        }
    }

    private function parameterTypeName(ReflectionParameter $parameter): ?string
    {
        $type = $parameter->getType();
        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        return $type->getName();
    }

    /**
     * @param class-string $className
     */
    private function ensureServiceRegistered(string $className, ContainerBuilder $container): void
    {
        if ($container->hasDefinition($className) || $container->hasAlias($className)) {
            return;
        }

        $definition = new Definition($className);
        $definition->setAutowired(true);
        $definition->setAutoconfigured(true);
        $container->setDefinition($className, $definition);
    }
}
