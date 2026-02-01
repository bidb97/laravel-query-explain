<?php

declare(strict_types = 1);

namespace Bidb97\QueryExplain\Http\Controllers;

use Bidb97\QueryExplain\Tools\Analyzer;

/**
 * QueryExplain Controller
 *
 * Handles HTTP requests for the query explanation interface.
 * Provides methods to display query analysis results to users.
 */
class QueryExplainController
{
    /**
     * Display the queries analysis page.
     *
     * This method initiates the query analysis process by calling the Analyzer,
     * which scans the codebase for methods marked with the QueryExplain attribute.
     * Then returns the view that displays the collected query analysis data.
     *
     * @param  \Bidb97\QueryExplain\Tools\Analyzer  $analyzer
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function queries(Analyzer $analyzer)
    {
        // Trigger the analysis of queries in the codebase
        $analyzer->getQueries();

        // Return the view that displays the query analysis results
        return view('query-explain::queries');
    }

    /**
     * Display a single query analysis.
     *
     * This method handles the display of individual query analysis details.
     * Currently, the implementation is pending and serves as a placeholder
     * for future functionality to show detailed information about a specific query.
     *
     * @return void
     */
    public function query()
    {
        // TODO: Implement single query display functionality
        // This method will eventually show detailed information about a specific query
    }
}
