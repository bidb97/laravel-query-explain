<?php

namespace Bidb97\QueryExplain\VO\QueryChain;

use explain\src\Contracts\QueryChain;
use explain\src\Enums\QueryChain\RootType;

class Root implements QueryChain
{
    public function __construct(
        public readonly explain\src\Enums\QueryChain\RootType $type,
        public readonly array                                 $payload,
    )
    {
        $this->assertPayload();
    }

    public static function fromStaticModel(string $class): self
    {
        return new self(explain\src\Enums\QueryChain\RootType::STATIC_MODEL, ['class' => $class]);
    }

    public static function fromParam(string $var, ?string $type = null): self
    {
        return new self(explain\src\Enums\QueryChain\RootType::PARAM, array_filter([
            'var'  => $var,
            'type' => $type,
        ]));
    }

    public static function fromProperty(string $object, string $property): self
    {
        return new self(explain\src\Enums\QueryChain\RootType::PROPERTY, [
            'object'   => $object,
            'property' => $property,
        ]);
    }

    public static function fromNewModel(string $class): self
    {
        return new self(explain\src\Enums\QueryChain\RootType::NEW_MODEL, ['class' => $class]);
    }

    public static function fromDbTable(string $table): self
    {
        return new self(explain\src\Enums\QueryChain\RootType::DB_TABLE, ['table' => $table]);
    }

    public static function fromVariable(string $name): self
    {
        return new self(explain\src\Enums\QueryChain\RootType::VARIABLE, ['name' => $name]);
    }

    public function isModelRoot(): bool
    {
        return \in_array($this->type, [explain\src\Enums\QueryChain\RootType::STATIC_MODEL, explain\src\Enums\QueryChain\RootType::NEW_MODEL, explain\src\Enums\QueryChain\RootType::PARAM], true);
    }

    public function isTableRoot(): bool
    {
        return $this->type === explain\src\Enums\QueryChain\RootType::DB_TABLE;
    }

    public function getModelClass(): ?string
    {
        if ($this->type === explain\src\Enums\QueryChain\RootType::STATIC_MODEL || $this->type === explain\src\Enums\QueryChain\RootType::NEW_MODEL) {
            return $this->payload['class'] ?? null;
        }

        if ($this->type === explain\src\Enums\QueryChain\RootType::PARAM) {
            return $this->payload['type'] ?? null;
        }

        return null;
    }

    public function getTableName(): ?string
    {
        return $this->type === explain\src\Enums\QueryChain\RootType::DB_TABLE ? ($this->payload['table'] ?? null) : null;
    }

    private function assertPayload(): void
    {
        match ($this->type) {
            explain\src\Enums\QueryChain\RootType::STATIC_MODEL,
            explain\src\Enums\QueryChain\RootType::NEW_MODEL => $this->assertHas(['class']),
            explain\src\Enums\QueryChain\RootType::PARAM => $this->assertHas(['var']),
            explain\src\Enums\QueryChain\RootType::PROPERTY => $this->assertHas(['object', 'property']),
            explain\src\Enums\QueryChain\RootType::DB_TABLE => $this->assertHas(['table']),
            explain\src\Enums\QueryChain\RootType::VARIABLE => $this->assertHas(['name']),
        };
    }

    private function assertHas(array $keys): void
    {
        foreach ($keys as $key) {
            if (!\array_key_exists($key, $this->payload)) {
                throw new \InvalidArgumentException(
                    sprintf('Root payload for "%s" must contain "%s".', $this->type->value, $key)
                );
            }
        }
    }
}
