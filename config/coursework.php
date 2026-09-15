<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Capture windows
    |--------------------------------------------------------------------------
    |
    | When true, an assessment type without an assessment calendar for the
    | class's academic calendar is closed for capture. Set to false only as a
    | temporary rollout measure while calendars are being created.
    |
    */

    'require_assessment_calendar' => env('COURSEWORK_REQUIRE_ASSESSMENT_CALENDAR') === null
        ? true
        : filter_var(env('COURSEWORK_REQUIRE_ASSESSMENT_CALENDAR'), FILTER_VALIDATE_BOOL),

    /*
    |--------------------------------------------------------------------------
    | Extensions
    |--------------------------------------------------------------------------
    |
    | Maximum number of days a lecturer may ask capture to be reopened for.
    |
    */

    // Empty env values must not override defaults (env('KEY', 'default') returns '' when KEY=).
    'extension_max_days' => (int) (env('COURSEWORK_EXTENSION_MAX_DAYS') ?: 14),

    /*
    |--------------------------------------------------------------------------
    | Import templates
    |--------------------------------------------------------------------------
    |
    | Downloaded templates older than this many days are rejected on import.
    |
    */

    'template_ttl_days' => (int) (env('COURSEWORK_TEMPLATE_TTL_DAYS') ?: 14),

];
