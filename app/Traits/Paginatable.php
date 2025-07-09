<?php

namespace App\Traits;

trait Paginatable
{

    public function getPerPage(): int
    {
        $pageSize = request('page_size', config('custom.system.pagination_items_per_page'));
        if ($pageSize === 'all') {
            return config('custom.system.pagination_max_limit');
        }
        return $pageSize;
    }
}
