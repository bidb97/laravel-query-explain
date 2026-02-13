<?php

namespace Bidb97\QueryExplain\Enums\QueryChain;

enum ExecuteKind: string
{
    case READ      = 'read';
    case AGGREGATE = 'aggregate';
    case WRITE     = 'write';
}

