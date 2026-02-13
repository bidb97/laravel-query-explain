<?php

namespace Bidb97\QueryExplain\VO\QueryChain;

use explain\src\Contracts\QueryChain;
use explain\src\Enums\QueryChain\ExecuteKind;

class Execute implements QueryChain
{
    private explain\src\Enums\QueryChain\ExecuteKind $kind;

    public function __construct(
        public readonly string $name,
        public readonly array $args = [],
    )
    {
        $this->kind = self::classify($name)
            ?? throw new \InvalidArgumentException(sprintf('Method "%s" is not an execute method.', $name));
    }

    public function isRead(): bool
    {
        return $this->kind === explain\src\Enums\QueryChain\ExecuteKind::READ;
    }

    public function isAggregation(): bool
    {
        return $this->kind === explain\src\Enums\QueryChain\ExecuteKind::AGGREGATE;
    }

    public function isWrite(): bool
    {
        return $this->kind === explain\src\Enums\QueryChain\ExecuteKind::WRITE;
    }

    public function isDangerous(): bool
    {
        return $this->isWrite();
    }

    public static function isExecuteMethod(string $name): bool
    {
        return self::classify($name) !== null;
    }

    private static function classify(string $name): ?explain\src\Enums\QueryChain\ExecuteKind
    {
        return match ($name) {
            'get',
            'first',
            'paginate',
            'simplePaginate',
            'pluck' => explain\src\Enums\QueryChain\ExecuteKind::READ,

            'count',
            'exists',
            'sum',
            'avg',
            'min',
            'max' => explain\src\Enums\QueryChain\ExecuteKind::AGGREGATE,

            'update',
            'delete',
            'insert',
            'insertOrIgnore',
            'upsert',
            'truncate',
            'increment',
            'decrement' => explain\src\Enums\QueryChain\ExecuteKind::WRITE,

            default => null,
        };
    }
}
