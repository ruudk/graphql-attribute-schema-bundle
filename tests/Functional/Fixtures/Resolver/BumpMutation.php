<?php

declare(strict_types=1);

namespace Jerowork\GraphqlAttributeSchemaBundle\Test\Functional\Fixtures\Resolver;

use Jerowork\GraphqlAttributeSchema\Attribute\Autowire;
use Jerowork\GraphqlAttributeSchema\Attribute\Mutation;
use Jerowork\GraphqlAttributeSchemaBundle\Test\Functional\Fixtures\Service\Counter;

final readonly class BumpMutation
{
    #[Mutation(name: 'bump', description: 'Bump a counter via the autowired Counter service')]
    public function __invoke(
        #[Autowire]
        Counter $counter,
    ): int {
        return $counter->bump();
    }
}
