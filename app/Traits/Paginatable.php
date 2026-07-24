<?php

namespace App\Traits;

trait Paginatable
{
    public function getPerPage(): int
    {
        $default = (int) config('custom.system.pagination_items_per_page', 15);
        $max = (int) config('custom.system.pagination_max_limit', 200);
        $pageSize = request('page_size', $default);

        if ($pageSize === 'all') {
            return max(1, $max);
        }

        $pageSize = (int) $pageSize;

        if ($pageSize < 1) {
            return $default;
        }

        return min($pageSize, $max);
    }
}
