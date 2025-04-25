<?php

namespace App\Traits;
use Illuminate\Support\Facades\Schema;
trait ModelTableColumn
{
	public static function getStaticTableColumns(): array
	{
		return Schema::getColumnListing((new self())->getTable());
	}
}
