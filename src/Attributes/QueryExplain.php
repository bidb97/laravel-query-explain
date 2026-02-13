<?php

declare(strict_types = 1);

namespace Bidb97\QueryExplain\Attributes;

use Attribute;

/**
 * QueryExplain attribute
 *
 * This attribute marks methods that should be analyzed for SQL queries.
 * It enables zero-runtime SQL EXPLAIN auditing using static analysis.
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_FUNCTION)]
final class QueryExplain
{
    /**
     * Create a new QueryExplain attribute instance.
     *
     * @param  array  $labels
     * @return void
     */
    public function __construct(
        public array $labels = [],
    ) {}
}
