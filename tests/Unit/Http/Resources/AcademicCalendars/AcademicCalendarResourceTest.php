<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Http\Resources\AcademicCalendars\AcademicCalendarResource;
use Illuminate\Http\Request;

test('abma calendar name uses ABMA 1 when opening month is in first quarter', function () {
    $calendar = (object) [
        'id' => 1,
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::ABMA,
        'opening_date' => '2026-01-10',
        'closing_date' => '2026-03-20',
    ];

    $data = (new AcademicCalendarResource($calendar))->toArray(Request::create('/'));

    expect($data['attributes']['name'])->toStartWith('ABMA 1 - ');
});

test('abma calendar name uses ABMA 2 when opening month is in second quarter', function () {
    $calendar = (object) [
        'id' => 2,
        'calendar_year' => '2026',
        'type' => 'abma',
        'opening_date' => '2026-04-01',
        'closing_date' => '2026-06-30',
    ];

    $data = (new AcademicCalendarResource($calendar))->toArray(Request::create('/'));

    expect($data['attributes']['name'])->toStartWith('ABMA 2 - ');
});

test('abma calendar name uses ABMA 3 when opening month is in third quarter', function () {
    $calendar = (object) [
        'id' => 3,
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::ABMA,
        'opening_date' => '2026-07-01',
        'closing_date' => '2026-09-15',
    ];

    $data = (new AcademicCalendarResource($calendar))->toArray(Request::create('/'));

    expect($data['attributes']['name'])->toStartWith('ABMA 3 - ');
});

test('abma calendar name uses ABMA 4 when opening month is in fourth quarter', function () {
    $calendar = (object) [
        'id' => 4,
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::ABMA,
        'opening_date' => '2026-10-01',
        'closing_date' => '2026-12-15',
    ];

    $data = (new AcademicCalendarResource($calendar))->toArray(Request::create('/'));

    expect($data['attributes']['name'])->toStartWith('ABMA 4 - ');
});
