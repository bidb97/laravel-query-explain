<?php

declare(strict_types=1);

namespace Bidb97\QueryExplain\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\ConnectionInterface;

/**
 * QueryExplainService
 *
 * Service class to handle actual database EXPLAIN functionality
 * Executes EXPLAIN statements for SQL queries and returns performance analysis
 */
class QueryExplainService
{
    /**
     * @var ConnectionInterface
     */
    private ConnectionInterface $connection;

    public function __construct(ConnectionInterface $connection = null)
    {
        $this->connection = $connection ?: DB::connection();
    }

    /**
     * Get EXPLAIN results for a given SQL query
     *
     * @param string $sql SQL query to explain
     * @param array $bindings Parameters for the query
     * @return array Array of EXPLAIN results
     */
    public function explainQuery(string $sql, array $bindings = []): array
    {
        try {
            // Prepare the EXPLAIN query
            $explainSql = "EXPLAIN " . $sql;

            // Execute the EXPLAIN query
            $results = $this->connection->select($explainSql, $bindings);

            // Convert results to array
            $explainData = [];
            foreach ($results as $row) {
                $explainData[] = (array) $row;
            }

            return $explainData;
        } catch (\Throwable $e) {
            // If EXPLAIN fails, return error information
            return [
                'error' => true,
                'message' => $e->getMessage(),
                'sql' => $sql,
            ];
        }
    }

    /**
     * Get detailed EXPLAIN ANALYZE results for PostgreSQL
     *
     * @param string $sql SQL query to analyze
     * @param array $bindings Parameters for the query
     * @return array Array of EXPLAIN ANALYZE results
     */
    public function explainAnalyzeQuery(string $sql, array $bindings = []): array
    {
        try {
            $driver = $this->connection->getDriverName();

            if ($driver === 'pgsql') {
                // PostgreSQL uses EXPLAIN ANALYZE
                $explainSql = "EXPLAIN (ANALYZE, BUFFERS) " . $sql;
            } elseif ($driver === 'mysql') {
                // MySQL uses EXPLAIN FORMAT=JSON for detailed analysis
                $explainSql = "EXPLAIN FORMAT=JSON " . $sql;
            } else {
                // For other databases, fall back to regular EXPLAIN
                $explainSql = "EXPLAIN " . $sql;
            }

            $results = $this->connection->select($explainSql, $bindings);

            $analyzeData = [];
            foreach ($results as $row) {
                $analyzeData[] = (array) $row;
            }

            return $analyzeData;
        } catch (\Throwable $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
                'sql' => $sql,
            ];
        }
    }

    /**
     * Check if a query looks safe to run EXPLAIN on
     *
     * @param string $sql SQL query to validate
     * @return bool True if the query appears safe
     */
    public function isSafeToExplain(string $sql): bool
    {
        $sql = trim(strtoupper($sql));

        // Only allow SELECT queries for EXPLAIN
        return str_starts_with($sql, 'SELECT');
    }
}
