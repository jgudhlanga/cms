<?php

use Carbon\Carbon;

return [
    'pagination_items_per_page' => env('PAGINATION_ITEMS_PER_PAGE', 15),
    // Hard cap for page_size=all and large class-list fetches (was 1000).
    'pagination_max_limit' => env('PAGINATION_MAX_LIMIT', 200),
    // Class lists need a full filtered cohort; keep below previous 1500 hardcode.
    'class_list_page_size' => env('CLASS_LIST_PAGE_SIZE', 200),
    'application-tracking-number-prefix' => env('APPLICATION_TRACKING_NUMBER_PREFIX', '').Carbon::now()->format('y'),
    'application-tracking-number-suffix' => Carbon::now()->format('hi').env('APPLICATION_TRACKING_NUMBER_SUFFIX', ''),
    'student-number-prefix' => env('STUDENT_NUMBER_PREFIX', 'H').Carbon::now()->format('ymd'),
    'student-number-suffix' => Carbon::now()->format('his').env('STUDENT_NUMBER_SUFFIX', ''),
    'autoCardFee' => env('AUTO_CARD_FEE', 45.00),
    'partTimeLevy' => env('PART_TIME_LEVY', 35.00),
];
