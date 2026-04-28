<?php

declare(strict_types=1);

namespace Jerowork\GraphqlAttributeSchemaBundle;

use GraphQL\Type\Schema;
use Jerowork\GraphqlAttributeSchema\SchemaBuilder;

final class SchemaProvider
{
    private ?Schema $schema = null;

    public function __construct(
        private readonly SchemaBuilder $schemaBuilder,
        private readonly AstProvider $astProvider,
    ) {}

    public function get(): Schema
    {
        return $this->schema ??= $this->schemaBuilder->build($this->astProvider->get());
    }
}
