<?php

declare(strict_types = 1);

namespace Bidb97\QueryExplain\Tools;

use explain\src\DTO\TargetTransfer;
use packages\laraveluse packages\laraveluse explain\src\VO\QueryChain\{Root};
use Bidb97\QueryExplain\Services\QueryExplainService;
use PhpParser\Parser;
use PhpParser\Node\Stmt\Expression;
use PhpParser\{Node, NodeFinder};
use PhpParser\Node\Expr\{Assign, Variable, MethodCall, StaticCall, PropertyFetch, New_, FuncCall};
use PhpParser\Node\Expr;

/**
 * Query Analyzer
 */
final class Analyzer
{
    /**
     * Create a new Analyzer instance.
     *
     * @param  explain\src\Tools\Finder  $finder  The finder instance used to scan the codebase
     */
    public function __construct(
        private Parser $parser,
        private NodeFinder $nodeFinder,
        private ?QueryExplainService $queryExplainService = null,
    )
    {}

    public function analyze(TargetTransfer $targetTransfer): array
    {
        try {
            $content = file_get_contents($targetTransfer->class->getFileName());

            if (empty($content) || empty(($ast = $this->parser->parse($content)[0]))) {
                throw new \Exception('Could not parse target file: ' . $targetTransfer->class->getFileName());
            }

            $method = $this->nodeFinder->findFirst($ast->stmts, function (Node $node) use ($targetTransfer) {
                return $node instanceof Node\Stmt\ClassMethod && $node->name->name === $targetTransfer->method;
            });

            if (empty($method)) {
                throw new \Exception("Method '{$targetTransfer->method}' not found in class '{$targetTransfer->class->getName()}'");
            }

            $assignments = $this->nodeFinder->find($method->stmts, function (Node $node) {
                return $node instanceof Expression &&
                    $node->expr instanceof Assign;
            });

            $results = [];

            foreach ($targetTransfer->labels as $label) {

                foreach ($assignments as $stmt) {

                    $assign = $stmt->expr;
                    $var = $assign->var;

                    if ($var instanceof Variable && $var->name === $label) {
                        $chain = $this->extractChain($assign->expr);

                        // Generate SQL from chain
                        $sqlQuery = $this->generateSqlFromChain($chain);

                        // Get EXPLAIN results if service is available and query is safe
                        $explainResults = null;
                        if ($this->queryExplainService && $sqlQuery && $this->queryExplainService->isSafeToExplain($sqlQuery)) {
                            $explainResults = $this->queryExplainService->explainQuery($sqlQuery);
                        }

                        $results[] = new explain\src\DTO\Query(
                            className: $targetTransfer->class->getName(),
                            methodName: $targetTransfer->method,
                            lineNumber: $stmt->getLine(),
                            label: $label,
                            sqlQuery: $sqlQuery,
                            explainResults: $explainResults,
                        );
                    }
                }
            }

            return $results;
        } catch (\Throwable $e) {
            throw new \Exception("Error analyzing {$targetTransfer->class->getName()}::{$targetTransfer->method}: " . $e->getMessage(), 0, $e);
        }
    }

    private function extractChain(Expr $expr): array
    {
        $methods = [];

        while ($expr instanceof MethodCall) {

            $methods[] = new Method(
                name: $expr->name->name,
                args: $expr->args,
            );

            $expr = $expr->var;
        }

        $root = $this->createRootFromExpr($expr);

        $methods = array_reverse($methods);

        $execute = null;

        if (!empty($methods)) {
            /** @var explain\src\VO\QueryChain\Method $last */
            $last = end($methods);

            if (Execute::isExecuteMethod($last->name)) {
                $execute = new Execute($last->name, $last->args);
                array_pop($methods);
            }
        }

        $chain = [];

        if ($root !== null) {
            $chain[] = $root;
        }

        foreach ($methods as $method) {
            $chain[] = $method;
        }

        if ($execute !== null) {
            $chain[] = $execute;
        }

        return $chain;
    }

    private function createRootFromExpr(Expr $expr): ?Root
    {
        if ($expr instanceof Variable) {
            return Root::fromVariable((string) $expr->name);
        }

        if ($expr instanceof PropertyFetch) {
            $objectName = $expr->var instanceof Variable ? (string) $expr->var->name : '';
            $propertyName = (string) $expr->name;

            return Root::fromProperty($objectName, $propertyName);
        }

        if ($expr instanceof StaticCall) {
            $class = $expr->class instanceof Node\Name ? $expr->class->toString() : (string) $expr->class;
            $method = (string) $expr->name;

            if ($class === 'DB' && $method === 'table' && isset($expr->args[0]) && $expr->args[0]->value instanceof Node\Scalar\String_) {
                /** @var \PhpParser\Node\Scalar\String_ $table */
                $table = $expr->args[0]->value;

                return Root::fromDbTable($table->value);
            }

            return Root::fromStaticModel($class);
        }

        if ($expr instanceof New_) {
            $class = $expr->class instanceof Node\Name ? $expr->class->toString() : (string) $expr->class;

            return Root::fromNewModel($class);
        }

        if ($expr instanceof FuncCall) {
            $name = $expr->name instanceof Node\Name ? $expr->name->toString() : (string) $expr->name;

            return Root::fromVariable($name);
        }

        return null;
    }

    /**
     * Generate SQL query string from chain of method calls
     *
     * @param array $chain Array of QueryChain objects (Root, Method[], Execute)
     * @return string|null Generated SQL query or null if cannot be generated
     */
    private function generateSqlFromChain(array $chain): ?string
    {
        if (empty($chain)) {
            return null;
        }

        $root = $chain[0] ?? null;

        if (!$root instanceof Root) {
            return null;
        }

        // Get table name from root
        $tableName = $this->getTableNameFromRoot($root);

        if (!$tableName) {
            return '[Unable to determine table name]';
        }

        $parts = [
            'select' => '*',
            'from' => $tableName,
            'where' => [],
            'orderBy' => [],
            'limit' => null,
            'offset' => null,
        ];

        // Process methods in chain
        foreach ($chain as $item) {
            if ($item instanceof Method) {
                $this->processMethod($item, $parts);
            } elseif ($item instanceof Execute) {
                $this->processExecute($item, $parts);
            }
        }

        return $this->buildSqlQuery($parts);
    }

    /**
     * Get table name from Root object
     */
    private function getTableNameFromRoot(Root $root): ?string
    {
        if ($root->isTableRoot()) {
            return $root->getTableName();
        }

        if ($root->isModelRoot()) {
            $modelClass = $root->getModelClass();

            if ($modelClass && class_exists($modelClass)) {
                try {
                    $model = new $modelClass();
                    if (method_exists($model, 'getTable')) {
                        return $model->getTable();
                    }
                } catch (\Throwable $e) {
                    // Cannot instantiate model
                }
            }
        }

        return null;
    }

    /**
     * Process method and update SQL parts
     */
    private function processMethod(Method $method, array &$parts): void
    {
        match ($method->name) {
            'select' => $this->processSelect($method, $parts),
            'where', 'orWhere' => $this->processWhere($method, $parts),
            'orderBy' => $this->processOrderBy($method, $parts),
            'limit', 'take' => $this->processLimit($method, $parts),
            'offset', 'skip' => $this->processOffset($method, $parts),
            default => null,
        };
    }

    /**
     * Process execute method and update SQL parts
     */
    private function processExecute(Execute $execute, array &$parts): void
    {
        match ($execute->name) {
            'count' => $parts['select'] = 'COUNT(*)',
            'sum', 'avg', 'min', 'max' => $this->processAggregate($execute, $parts),
            default => null,
        };
    }

    /**
     * Process SELECT clause
     */
    private function processSelect(Method $method, array &$parts): void
    {
        $columns = [];
        foreach ($method->args as $arg) {
            if ($arg->value instanceof Node\Scalar\String_) {
                $columns[] = $arg->value->value;
            }
        }

        if (!empty($columns)) {
            $parts['select'] = implode(', ', $columns);
        }
    }

    /**
     * Process WHERE clause
     */
    private function processWhere(Method $method, array &$parts): void
    {
        $conditions = [];

        if (isset($method->args[0]) && $method->args[0]->value instanceof Node\Scalar\String_) {
            $column = $method->args[0]->value->value;

            if (isset($method->args[1])) {
                $operator = '=';
                $value = '?';

                if (isset($method->args[2])) {
                    // Three arguments: column, operator, value
                    if ($method->args[1]->value instanceof Node\Scalar\String_) {
                        $operator = $method->args[1]->value->value;
                    }
                } else {
                    // Two arguments: column, value
                }

                $parts['where'][] = "{$column} {$operator} {$value}";
            }
        }
    }

    /**
     * Process ORDER BY clause
     */
    private function processOrderBy(Method $method, array &$parts): void
    {
        if (isset($method->args[0]) && $method->args[0]->value instanceof Node\Scalar\String_) {
            $column = $method->args[0]->value->value;
            $direction = 'ASC';

            if (isset($method->args[1]) && $method->args[1]->value instanceof Node\Scalar\String_) {
                $direction = strtoupper($method->args[1]->value->value);
            }

            $parts['orderBy'][] = "{$column} {$direction}";
        }
    }

    /**
     * Process LIMIT clause
     */
    private function processLimit(Method $method, array &$parts): void
    {
        if (isset($method->args[0]) && $method->args[0]->value instanceof Node\Scalar\Int_) {
            $parts['limit'] = $method->args[0]->value->value;
        }
    }

    /**
     * Process OFFSET clause
     */
    private function processOffset(Method $method, array &$parts): void
    {
        if (isset($method->args[0]) && $method->args[0]->value instanceof Node\Scalar\Int_) {
            $parts['offset'] = $method->args[0]->value->value;
        }
    }

    /**
     * Process aggregate functions
     */
    private function processAggregate(Execute $execute, array &$parts): void
    {
        $function = strtoupper($execute->name);
        $column = '*';

        if (isset($execute->args[0]) && $execute->args[0]->value instanceof Node\Scalar\String_) {
            $column = $execute->args[0]->value->value;
        }

        $parts['select'] = "{$function}({$column})";
    }

    /**
     * Build final SQL query string from parts
     */
    private function buildSqlQuery(array $parts): string
    {
        $sql = "SELECT {$parts['select']} FROM {$parts['from']}";

        if (!empty($parts['where'])) {
            $sql .= ' WHERE ' . implode(' AND ', $parts['where']);
        }

        if (!empty($parts['orderBy'])) {
            $sql .= ' ORDER BY ' . implode(', ', $parts['orderBy']);
        }

        if ($parts['limit'] !== null) {
            $sql .= ' LIMIT ' . $parts['limit'];
        }

        if ($parts['offset'] !== null) {
            $sql .= ' OFFSET ' . $parts['offset'];
        }

        return $sql;
    }

}
