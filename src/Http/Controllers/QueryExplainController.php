<?php

namespace Bidb97\QueryExplain\Http\Controllers;

class QueryExplainController
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function queries()
    {
        return view('query-explain::queries');
    }

    public function query()
    {

    }
}
