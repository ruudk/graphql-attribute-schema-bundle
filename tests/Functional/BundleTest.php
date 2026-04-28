<?php

declare(strict_types=1);

namespace Jerowork\GraphqlAttributeSchemaBundle\Test\Functional;

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use Jerowork\GraphqlAttributeSchemaBundle\SchemaProvider;
use Jerowork\GraphqlAttributeSchemaBundle\Test\Functional\Fixtures\Resolver\BumpMutation;
use Jerowork\GraphqlAttributeSchemaBundle\Test\Functional\Fixtures\Resolver\HelloQuery;
use Jerowork\GraphqlAttributeSchemaBundle\Test\Functional\Fixtures\Service\Counter;
use Jerowork\GraphqlAttributeSchemaBundle\Test\Functional\Fixtures\Service\Greeter;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @internal
 */
final class BundleTest extends KernelTestCase
{
    /**
     * @var list<callable|null>
     */
    private array $errorHandlerSnapshot = [];

    /**
     * @var list<callable|null>
     */
    private array $exceptionHandlerSnapshot = [];

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    /**
     * @param array<string, mixed> $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new TestKernel();
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->errorHandlerSnapshot = $this->snapshotHandlers('error');
        $this->exceptionHandlerSnapshot = $this->snapshotHandlers('exception');
    }

    #[Override]
    protected function tearDown(): void
    {
        parent::tearDown();

        // Symfony's ErrorHandler is registered on boot but not unregistered on shutdown, which trips
        // PHPUnit's failOnRisky. Pop any handlers added during the test back down to the snapshot.
        $this->restoreHandlers('error', $this->errorHandlerSnapshot);
        $this->restoreHandlers('exception', $this->exceptionHandlerSnapshot);
    }

    /**
     * @return list<callable|null>
     */
    private function snapshotHandlers(string $kind): array
    {
        $stack = [];
        $set = $kind === 'error' ? 'set_error_handler' : 'set_exception_handler';
        $restore = $kind === 'error' ? 'restore_error_handler' : 'restore_exception_handler';

        while (($handler = $set(static fn() => null)) !== null) {
            $stack[] = $handler;
            $restore();
            $restore();
        }
        $restore();

        // Re-push the snapshot so the stack is unchanged.
        foreach (array_reverse($stack) as $handler) {
            $set($handler);
        }

        return $stack;
    }

    /**
     * @param list<callable|null> $snapshot
     */
    private function restoreHandlers(string $kind, array $snapshot): void
    {
        $set = $kind === 'error' ? 'set_error_handler' : 'set_exception_handler';
        $restore = $kind === 'error' ? 'restore_error_handler' : 'restore_exception_handler';

        // Pop until empty.
        while ($set(static fn() => null) !== null) {
            $restore();
            $restore();
        }
        $restore();

        // Re-push the original snapshot.
        foreach (array_reverse($snapshot) as $handler) {
            $set($handler);
        }
    }

    #[Test]
    public function itShouldExposeTheSchemaProviderAsAPublicService(): void
    {
        $container = self::getContainer();

        self::assertTrue($container->has(SchemaProvider::class));
        self::assertInstanceOf(SchemaProvider::class, $container->get(SchemaProvider::class));
    }

    #[Test]
    public function itShouldBuildAGraphqlSchema(): void
    {
        $schema = $this->getSchema();

        self::assertSame([], $schema->validate());
        self::assertNotNull($schema->getQueryType());
        self::assertNotNull($schema->getMutationType());
    }

    #[Test]
    public function itShouldPopulateTheResolverLocatorWithAutowiredServiceIds(): void
    {
        $locator = $this->getResolverLocator();

        // Services referenced via #[Autowire] on resolver methods.
        self::assertTrue($locator->has(Greeter::class), 'Greeter should be in the resolver locator');
        self::assertTrue($locator->has(Counter::class), 'Counter should be in the resolver locator');

        // Root resolver classes (Query/Mutation) are fetched by FQCN at runtime.
        self::assertTrue($locator->has(HelloQuery::class), 'HelloQuery should be in the resolver locator');
        self::assertTrue($locator->has(BumpMutation::class), 'BumpMutation should be in the resolver locator');
    }

    #[Test]
    public function itShouldNotExposeUnrelatedServicesViaTheLocator(): void
    {
        $locator = $this->getResolverLocator();

        // Framework services must not leak into the resolver locator.
        self::assertFalse($locator->has('kernel'));
        self::assertFalse($locator->has('router'));
    }

    #[Test]
    public function itShouldExecuteAQueryWithAnAutowiredService(): void
    {
        $result = GraphQL::executeQuery(
            $this->getSchema(),
            'query { hello(name: "Ruud") }',
        )->toArray();

        self::assertArrayNotHasKey('errors', $result, sprintf('GraphQL errors: %s', json_encode($result['errors'] ?? [])));
        self::assertSame(['data' => ['hello' => 'Hello, Ruud!']], $result);
    }

    #[Test]
    public function itShouldExecuteAMutationWithAnAutowiredService(): void
    {
        $schema = $this->getSchema();

        $first = GraphQL::executeQuery($schema, 'mutation { bump }')->toArray();
        $second = GraphQL::executeQuery($schema, 'mutation { bump }')->toArray();

        self::assertSame(['data' => ['bump' => 1]], $first);
        self::assertSame(['data' => ['bump' => 2]], $second, 'Counter should be the same instance across calls (shared service)');
    }

    private function getSchema(): Schema
    {
        /** @var SchemaProvider $provider */
        $provider = self::getContainer()->get(SchemaProvider::class);

        return $provider->get();
    }

    private function getResolverLocator(): ContainerInterface
    {
        /** @var ContainerInterface $locator */
        $locator = self::getContainer()->get('graphql_attribute_schema.resolver_locator');

        return $locator;
    }
}
