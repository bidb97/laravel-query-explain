<?php

declare(strict_types = 1);

namespace Bidb97\QueryExplain\Tools;

use explain\src\Attributes\QueryExplain;
use explain\src\DTO\TargetTransfer;
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

    public function getQueries(array $filters = []): \Generator
    {
        if (empty($filters)) {
            $filters[] = "*.php";
        }

        yield from $this->scan($filters);
    }

    public function getQuery()
    {

    }

    /**
     * Scan configured directories for classes with QueryExplain attributes.
     *
     */
    private function scan(array $filters): \Generator
    {
        foreach (config('query-explain.scan_dirs') as $dir) {

            $this->finder->files()
                ->in($dir)
                ->name($filters)
                ->contains(QueryExplain::class);

            foreach ($this->finder as $file) {

                $content = file_get_contents($file->getRealPath());
                if (!str_contains($content, QueryExplain::class)) {
                    continue;
                }

                $fullClassName = $this->getFullClassName($content);

                if (!class_exists($fullClassName)) {
                    continue;
                }

                $reflection = new \ReflectionClass($fullClassName);

                foreach ($reflection->getMethods() as $method) {

                    $target = $method->getAttributes(QueryExplain::class);

                    if (empty($target)) {
                        continue;
                    }

                    $attribute = $target[0]->newInstance();

                    if (empty($attribute->labels)) {
                        continue;
                    }

                    yield new TargetTransfer(
                        class: $reflection,
                        method: $method->getName(),
                        labels: $attribute->labels
                    );
                }
            }
        }
    }

    /**
     * Extract the full class name from a PHP file using AST parsing.
     *
     * @param string $fileContent
     * @return string
     */
    private function getFullClassName(string $fileContent): string
    {
        // Parse the file content into an Abstract Syntax Tree (AST)
        $ast = $this->parser->parse($fileContent);

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
