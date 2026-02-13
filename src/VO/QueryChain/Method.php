<?php

namespace Bidb97\QueryExplain\VO\QueryChain;

use explain\src\Contracts\QueryChain;

class Method implements QueryChain
{
    public function __construct(
        public readonly string $name,
        public readonly array $args = [],
    )
    {}
}
