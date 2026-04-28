<?php

declare(strict_types=1);

namespace Jerowork\GraphqlAttributeSchemaBundle\Test\Functional\Fixtures\Service;

final class Counter
{
    private int $value = 0;

    public function bump(): int
    {
        return ++$this->value;
    }
}
