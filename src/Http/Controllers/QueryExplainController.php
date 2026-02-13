<?php

declare(strict_types = 1);

namespace Bidb97\QueryExplain\Http\Controllers;

use explain\src\Services\QueryExplainManager;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * QueryExplain Controller
 *
 * Handles HTTP requests for the query explanation interface.
 * Provides methods to display query analysis results to users.
 */
class QueryExplainController
{
    /**
     * Display all queries found in the codebase
     *
     * @param Request $request
     * @param explain\src\Services\QueryExplainManager $queryExplainManager
     * @return \Illuminate\Contracts\View\View|JsonResponse
     */
    public function queries(Request $request, QueryExplainManager $queryExplainManager)
    {
        try {
            $filters = [];

            // Add filters if provided
            if ($request->has('classes')) {
                $filters['classes'] = $request->input('classes');
            }

            $queries = $queryExplainManager->getQueries($filters);

            // Return JSON if requested
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'queries' => array_map(function($query) {
                        return [
                            'className' => $query->className,
                            'methodName' => $query->methodName,
                            'lineNumber' => $query->lineNumber,
                            'label' => $query->label,
                            'sqlQuery' => $query->sqlQuery,
                            'explainResults' => $query->explainResults,
                        ];
                    }, $queries),
                    'count' => count($queries),
                ]);
            }

            // Return view for HTML requests
            return view('query-explain::queries', [
                'queries' => $queries,
            ]);

        } catch (\Throwable $e) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }

            throw $e;
        }
    }

    /**
     * Display a single query analysis.
     *
     * @param Request $request
     * @param explain\src\Services\QueryExplainManager $queryExplainManager
     * @param string $className
     * @param string $methodName
     * @param string $label
     * @return JsonResponse|\Illuminate\Contracts\View\View
     */
    public function query(
        Request $request,
        QueryExplainManager $queryExplainManager,
        string $className,
        string $methodName,
        string $label
    )
    {
        try {
            $query = $queryExplainManager->getQuery($className, $methodName, $label);

            if (!$query) {
                if ($request->wantsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Query not found',
                    ], 404);
                }

                abort(404, 'Query not found');
            }

            // Return JSON if requested
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'query' => [
                        'className' => $query->className,
                        'methodName' => $query->methodName,
                        'lineNumber' => $query->lineNumber,
                        'label' => $query->label,
                        'sqlQuery' => $query->sqlQuery,
                        'explainResults' => $query->explainResults,
                    ],
                ]);
            }

            // Return view for HTML requests
            return view('query-explain::query', [
                'query' => $query,
            ]);

        } catch (\Throwable $e) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }

            throw $e;
        }
    }
}
