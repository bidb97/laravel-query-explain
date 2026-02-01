<?php

declare(strict_types = 1);

namespace Bidb97\QueryExplain\Tools;

use Bidb97\QueryExplain\Attributes\QueryExplain;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use Symfony\Component\Finder\Finder as SymfonyFinder;
use PhpParser\Parser;

/**
 * Code Finder
 *
 * Scans the codebase to find PHP files containing the QueryExplain attribute.
 * Uses AST parsing to accurately identify classes and methods that need to be analyzed.
 * This approach avoids runtime execution and performs static analysis of the code.
 */
final class Finder
{
    /**
     * Create a new Finder instance.
     *
     * @param  \Symfony\Component\Finder\Finder  $finder  The Symfony Finder instance for locating files
     * @param  \PhpParser\Parser  $parser  The PHP parser instance for AST analysis
     */
    public function __construct(
        private SymfonyFinder $finder,
        private Parser $parser
    )
    {}

    /**
     * Scan configured directories for classes with QueryExplain attributes.
     *
     * This method iterates through the configured scan directories, looking for PHP files
     * that contain the QueryExplain attribute. For each matching file, it identifies
     * the classes and methods annotated with the attribute and yields them for further analysis.
     *
     * @return \Generator  A generator yielding arrays with class reflection objects and their annotated methods
     */
    public function scan(): \Generator
    {
        // Iterate through each directory specified in the configuration
        foreach (config('query-explain.scan_dirs') as $dir) {

            // Configure the finder to look for PHP files containing the QueryExplain attribute
            $this->finder->files()
                ->in($dir)
                ->name("*.php")
                ->contains(QueryExplain::class);

            // Process each file found by the finder
            foreach ($this->finder as $file) {

                $methods = [];

                // Read the file content to check for the attribute presence
                $content = file_get_contents($file->getRealPath());
                if (!str_contains($content, QueryExplain::class)) {
                    continue;
                }

                // Extract the full class name from the file using AST parsing
                $fullClassName = $this->getFullClassName($file);

                // Skip if the class doesn't exist (e.g., it's in a different namespace or not loaded)
                if (!class_exists($fullClassName)) {
                    continue;
                }

                // Create a reflection object for the class to analyze its methods
                $reflection = new \ReflectionClass($fullClassName);

                // Check each method in the class for the QueryExplain attribute
                foreach ($reflection->getMethods() as $method) {

                    // Get attributes matching the QueryExplain class
                    $target = $method->getAttributes(QueryExplain::class);

                    // Skip methods that don't have the QueryExplain attribute
                    if (empty($target)) {
                        continue;
                    }

                    // Add the method to our list of methods to analyze
                    $methods[] = $method;
                }

                // Yield the class reflection and its annotated methods for processing
                yield [
                    'class' => $reflection,
                    'methods' => $methods,
                ];
            }
        }
    }

    /**
     * Extract the full class name from a PHP file using AST parsing.
     *
     * This method parses the PHP file to extract the namespace and class name
     * without relying on autoloading or class existence. This allows us to
     * identify classes even if they're not currently loaded in the application.
     *
     * @param  \SplFileInfo  $file  The PHP file to extract the class name from
     * @return string  The fully qualified class name (with namespace if present)
     */
    private function getFullClassName(\SplFileInfo $file): string
    {
        // Parse the file content into an Abstract Syntax Tree (AST)
        $ast = $this->parser->parse(file_get_contents($file->getRealPath()));

        $namespace = '';
        $className = '';

        // Traverse the AST nodes to find namespace and class declarations
        foreach ($ast as $node) {

            // Check if the node is a namespace declaration
            if ($node instanceof Namespace_) {

                // Extract the namespace name
                $namespace = $node->name->toString();

                // Look for class declarations within the namespace
                foreach ($node->stmts as $subNode) {
                    if ($subNode instanceof Class_) {
                        $className = $subNode->name->toString();
                    }
                }
            }

            // Check if the node is a class declaration (for classes without namespace)
            if ($node instanceof Class_) {
                $className = $node->name->toString();
            }
        }

        // Combine namespace and class name if namespace exists, otherwise return just the class name
        return $namespace ? "$namespace\\$className" : $className;
    }
}
