<?php

declare(strict_types=1);

namespace Jerowork\GraphqlAttributeSchemaBundle\Test\Functional\Fixtures\Service;

final readonly class Greeter
{
    public function __construct(
        private string $prefix = 'Hello',
    ) {}

    public function greet(string $name): string
    {
        return sprintf('%s, %s!', $this->prefix, $name);
    }
}
