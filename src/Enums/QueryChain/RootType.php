<?php

namespace Bidb97\QueryExplain\Enums\QueryChain;

enum RootType: string
{
    case STATIC_MODEL = 'static_model';
    case PARAM        = 'param';
    case PROPERTY     = 'property';
    case NEW_MODEL    = 'new_model';
    case DB_TABLE     = 'db_table';
    case VARIABLE     = 'variable';
}

