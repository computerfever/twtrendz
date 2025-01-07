<?php

namespace Acelle\Library\LaravelExtension;

use Illuminate\Database\Eloquent\Builder as Base;
use Illuminate\Pagination\Paginator;

class EloquentBuilder extends Base
{
    // Overwrite the original method, adding an optional $count parameter
    // Why? Normally, the count method might take time, causing performance issue
    // By using this enhanced method, we provide a count value which was prevoulsy counted using a better method
    // So this method does not have to count again
    public function paginate($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null, $total = null)
    {
        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $perPage = $perPage ?: $this->model->getPerPage();

        if (is_null($total)) {
            $total = $this->toBase()->getCountForPagination();
        } else {
            //
        }

        $results = ($total)
                                    ? $this->forPage($page, $perPage)->get($columns)
                                    : $this->model->newCollection();

        return $this->paginator($results, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }

    public function fastPaginate($perPage, $total)
    {
        return $this->paginate($perPage, $columns = ['*'], $pageName = 'page', $page = null, $total);
    }
}
