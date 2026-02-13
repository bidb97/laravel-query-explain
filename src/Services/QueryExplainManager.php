<?php

namespace Bidb97\QueryExplain\Services;

use explain\src\Tools\Analyzer;
use explain\src\Tools\Finder;
use explain\src\DTO\Query;
use Bidb97\QueryExplain\Services\QueryExplainService;

final class QueryExplainManager
{
    public function __construct(
        private Finder $finder,
        private Analyzer $analyzer,
        private ?QueryExplainService $queryExplainService = null,
    )
    {}

    /**
     * Stream all queries found in the codebase
     *
     * @param array $filters Filters for file scanning
     * @return \Generator Generator yielding Query DTOs
     */
    public function streamQueries(array $filters = []): \Generator
    {
        foreach ($this->finder->getQueries($filters) as $targetTransfer) {
            $queries = $this->analyzer->analyze($targetTransfer);

            foreach ($queries as $query) {
                yield $query;
            }
        }
    }

    /**
     * Get all queries as an array
     *
     * @param array $filters Filters for file scanning
     * @return array Array of Query DTOs
     */
    public function getQueries(array $filters = []): array
    {
        return iterator_to_array($this->streamQueries($filters));
    }

    /**
     * Get a single query by class, method and label
     *
     * @param string $className
     * @param string $methodName
     * @param string $label
     * @return explain\src\DTO\Query|null
     */
    public function getQuery(string $className, string $methodName, string $label): ?Query
    {
        foreach ($this->streamQueries() as $query) {
            if ($query->className === $className
                && $query->methodName === $methodName
                && $query->label === $label) {
                return $query;
            }
        }

        return null;
    }
}
