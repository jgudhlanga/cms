<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Http\Resources\AcademicCalendars\AcademicCalendarResource;
use Illuminate\Http\Request;

test('abma calendar name uses term 1 when average month is in first quarter', function () {
    $calendar = (object) [
        'id' => 1,
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::ABMA,
        'opening_date' => '2026-01-10',
        'closing_date' => '2026-03-20',
    ];

    $data = (new AcademicCalendarResource($calendar))->toArray(Request::create('/'));

    expect($data['attributes']['name'])->toStartWith('Term 1 - ');
});

test('abma calendar name uses term 2 when average month is in second quarter', function () {
    $calendar = (object) [
        'id' => 2,
        'calendar_year' => '2026',
        'type' => 'abma',
        'opening_date' => '2026-04-01',
        'closing_date' => '2026-06-30',
    ];

    $data = (new AcademicCalendarResource($calendar))->toArray(Request::create('/'));

    expect($data['attributes']['name'])->toStartWith('Term 2 - ');
});

test('abma calendar name uses term 3 when average month is in third quarter', function () {
    $calendar = (object) [
        'id' => 3,
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::ABMA,
        'opening_date' => '2026-07-01',
        'closing_date' => '2026-09-15',
    ];

    $data = (new AcademicCalendarResource($calendar))->toArray(Request::create('/'));

    expect($data['attributes']['name'])->toStartWith('Term 3 - ');
});

test('abma calendar name uses term 4 when average month is in fourth quarter', function () {
    $calendar = (object) [
        'id' => 4,
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::ABMA,
        'opening_date' => '2026-10-01',
        'closing_date' => '2026-12-15',
    ];

    $data = (new AcademicCalendarResource($calendar))->toArray(Request::create('/'));

    expect($data['attributes']['name'])->toStartWith('Term 4 - ');
});
