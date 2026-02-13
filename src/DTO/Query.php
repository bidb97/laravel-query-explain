<?php

declare(strict_types = 1);

namespace Bidb97\QueryExplain\DTO;

readonly class Query
{
    public function __construct(
        public string $className,
        public string $methodName,
        public ?int $lineNumber,
        public string $label,
        public ?string $sqlQuery,
        public ?array $explainResults = null,
    ) {}
}
