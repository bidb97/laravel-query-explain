<?php

use explain\src\Http\Controllers\QueryExplainController;
use Illuminate\Support\Facades\Route;

Route::prefix(config('query-explain.path'))
    ->middleware(config('query-explain.middleware'))
    ->group(function () {
        Route::get('/', [QueryExplainController::class, 'queries']);
        Route::get('/query/{className}/{methodName}/{label}', [QueryExplainController::class, 'query']);
    });
