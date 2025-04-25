<?php

use Carbon\Carbon;

return [
	'pagination_items_per_page' => env('PAGINATION_ITEMS_PER_PAGE', 10),
	'policy-number-prefix' => env('POLICY_NUMBER_PREFIX', 'POL').Carbon::now()->format('ymd'),
	'policy-number-suffix' => Carbon::now()->format('his').env('POLICY_NUMBER_SUFFIX', ''),
	'scheme-number-prefix' => env('SCHEME_NUMBER_PREFIX', 'SCH').Carbon::now()->format('ymd'),
	'scheme-number-suffix' => Carbon::now()->format('his').env('SCHEME_NUMBER_SUFFIX', ''),
];
