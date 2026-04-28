<?php

declare(strict_types=1);

namespace Jerowork\GraphqlAttributeSchemaBundle\Test\Functional\Fixtures\Resolver;

use Jerowork\GraphqlAttributeSchema\Attribute\Arg;
use Jerowork\GraphqlAttributeSchema\Attribute\Autowire;
use Jerowork\GraphqlAttributeSchema\Attribute\Query;
use Jerowork\GraphqlAttributeSchemaBundle\Test\Functional\Fixtures\Service\Greeter;

final readonly class HelloQuery
{
    #[Query(name: 'hello', description: 'Greet someone via the autowired Greeter service')]
    public function __invoke(
        #[Autowire]
        Greeter $greeter,
        #[Arg]
        string $name,
    ): string {
        return $greeter->greet($name);
    }
}
