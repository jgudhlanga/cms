<?php

use Carbon\Carbon;

return [
	'pagination_items_per_page' => env('PAGINATION_ITEMS_PER_PAGE', 10),
	'pagination_max_limit' => env('PAGINATION_MAX_LIMIT', 1000),
	'application-tracking-number-prefix' => env('APPLICATION_TRACKING_NUMBER_PREFIX', '').Carbon::now()->format('ymd'),
	'application-tracking-number-suffix' => Carbon::now()->format('his').env('APPLICATION_TRACKING_NUMBER_SUFFIX', ''),
	'student-number-prefix' => env('STUDENT_NUMBER_PREFIX', 'SCH').Carbon::now()->format('ymd'),
	'student-number-suffix' => Carbon::now()->format('his').env('STUDENT_NUMBER_SUFFIX', ''),
];
