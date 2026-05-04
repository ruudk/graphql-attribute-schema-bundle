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
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class BuildResolverLocatorPass implements CompilerPassInterface
{
    #[Override]
    public function process(ContainerBuilder $container): void
    {
        /**
         * @var array<string, Reference> $services
         */
        $services = [];

        foreach ($container->findTaggedResourceIds(GraphqlAttributeSchemaBundle::TYPE_TAG) as $id => $tags) {
            foreach ($tags as $tag) {
                $reflection = new ReflectionClass($id);
                foreach ($reflection->getMethods() as $method) {
                    $this->collectFromMethod($method, $container, $services);
                }
            }
        }

        foreach (array_keys($container->findTaggedServiceIds(GraphqlAttributeSchemaBundle::ADD_TO_SERVICE_LOCATOR_TAG)) as $id ) {
            $services[$id] = new Reference($id);
        }
        
        $container->setAlias('graphql_attribute_schema.resolver_locator', (string) ServiceLocatorTagPass::register($container, $services));
    }

    /**
     * @param array<string, Reference> $services
     */
    private function collectFromMethod(ReflectionMethod $method, ContainerBuilder $container, array &$services): void
    {
        foreach ($method->getParameters() as $parameter) {
            foreach ($parameter->getAttributes(Autowire::class) as $attr) {
                $autowire = $attr->newInstance();
                $serviceId = $autowire->service ?? $this->parameterTypeName($parameter);

                if ($serviceId === null) {
                    continue;
                }

                $services[$serviceId] = new Reference($serviceId);
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
}
