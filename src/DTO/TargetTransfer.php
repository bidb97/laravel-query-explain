<?php

namespace Bidb97\QueryExplain\DTO;

readonly class TargetTransfer
{
    public function __construct(
        public \ReflectionClass $class,
        public string $method,
        public array $labels,
    )
    {}
}
