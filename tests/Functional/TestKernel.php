<?php

declare(strict_types=1);

namespace Jerowork\GraphqlAttributeSchemaBundle\Test\Functional;

use Jerowork\GraphqlAttributeSchemaBundle\GraphqlAttributeSchemaBundle;
use Jerowork\GraphqlAttributeSchemaBundle\Test\Functional\Fixtures\Resolver\BumpMutation;
use Jerowork\GraphqlAttributeSchemaBundle\Test\Functional\Fixtures\Resolver\HelloQuery;
use Jerowork\GraphqlAttributeSchemaBundle\Test\Functional\Fixtures\Service\Counter;
use Jerowork\GraphqlAttributeSchemaBundle\Test\Functional\Fixtures\Service\Greeter;
use Override;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function __construct()
    {
        parent::__construct('test', true);
    }

    #[Override]
    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new GraphqlAttributeSchemaBundle(),
        ];
    }

    #[Override]
    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/graphql-attribute-schema-bundle/test-kernel/cache/' . spl_object_hash($this);
    }

    #[Override]
    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/graphql-attribute-schema-bundle/test-kernel/log';
    }

    public function loadRoutes(RoutingConfigurator $routes): void
    {
        // No routes — schema is consumed via the SchemaProvider service.
    }

    protected function configureContainer(ContainerConfigurator $container, LoaderInterface $loader, ContainerBuilder $builder): void
    {
        $container->extension('framework', [
            'secret' => 'test',
            'http_method_override' => false,
            'handle_all_throwables' => true,
            'php_errors' => ['log' => true],
            'router' => ['utf8' => true, 'resource' => 'kernel::loadRoutes'],
            'test' => true,
        ]);

        $container->extension('graphql_attribute_schema', [
            'scan_paths' => [__DIR__ . '/Fixtures/Resolver'],
        ]);

        $services = $container->services();

        $services->set(Greeter::class)
            ->args(['Hello']);

        $services->set(Counter::class);

        $services->set(HelloQuery::class)
            ->autoconfigure();

        $services->set(BumpMutation::class)
            ->autoconfigure();
    }
}
