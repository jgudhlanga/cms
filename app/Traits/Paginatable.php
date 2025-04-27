<?php

namespace App\Traits;

trait Paginatable
{

    public function getPerPage()
    {
        return request('page_size', config('custom.system.pagination_items_per_page'));
    }
}
