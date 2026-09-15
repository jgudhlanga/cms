<?php

return [
    'pagination_items_per_page' => env('PAGINATION_ITEMS_PER_PAGE', 15),
    // Hard cap for page_size=all and large class-list fetches (was 1000).
    'pagination_max_limit' => env('PAGINATION_MAX_LIMIT', 200),
    // Class lists need a full filtered cohort; keep below previous 1500 hardcode.
    'class_list_page_size' => env('CLASS_LIST_PAGE_SIZE', 200),
    // Static parts only. config:cache would freeze a date computed here, so callers add date parts at use time.
    'application-tracking-number-prefix' => env('APPLICATION_TRACKING_NUMBER_PREFIX', ''),
    'application-tracking-number-suffix' => env('APPLICATION_TRACKING_NUMBER_SUFFIX', ''),
    'student-number-prefix' => env('STUDENT_NUMBER_PREFIX', 'H'),
    'student-number-suffix' => env('STUDENT_NUMBER_SUFFIX', ''),
    'autoCardFee' => env('AUTO_CARD_FEE', 45.00),
    'partTimeLevy' => env('PART_TIME_LEVY', 35.00),
];
