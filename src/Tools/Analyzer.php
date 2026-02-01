<?php

declare(strict_types = 1);

namespace Bidb97\QueryExplain\Tools;

/**
 * Query Analyzer
 *
 * Analyzes the codebase to find methods marked with the QueryExplain attribute.
 * This class orchestrates the scanning process and collects information about
 * SQL queries that need to be analyzed/explained.
 */
final class Analyzer
{
    /**
     * Create a new Analyzer instance.
     *
     * @param  \Bidb97\QueryExplain\Tools\Finder  $finder  The finder instance used to scan the codebase
     */
    public function __construct(
        private Finder $finder,
    )
    {}

    /**
     * Retrieve and analyze queries from the codebase.
     *
     * This method initiates the scanning process to find all classes and methods
     * annotated with the QueryExplain attribute. It then collects information
     * about these targets for further analysis.
     *
     * @return void
     */
    public function getQueries()
    {
        $targets = [];

        // Iterate through all files found by the Finder that contain QueryExplain attributes
        foreach ($this->finder->scan() as $file) {
            $targets[] = $file;
        }

        // Debug output - this should be replaced with proper data processing
        // in the final implementation to store results appropriately
        dd($targets);
    }
}
