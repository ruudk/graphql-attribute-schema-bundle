<?php

declare(strict_types=1);

namespace Jerowork\GraphqlAttributeSchemaBundle;

use Jerowork\GraphqlAttributeSchema\Ast;
use Jerowork\GraphqlAttributeSchema\Parser;

final class AstProvider
{
    private ?Ast $ast = null;

    /**
     * @param list<string> $scanPaths
     */
    public function __construct(
        private readonly Parser $parser,
        private readonly array $scanPaths,
    ) {}

    public function get(): Ast
    {
        return $this->ast ??= $this->parser->parse(...$this->scanPaths);
    }
}
