<?php

require_once __DIR__.'/../../Support/SyllabusModuleTestHelpers.php';

it('returns academic staff grouped by department', function () {
    $ctx = makeSyllabusModuleContext();
    $lecturer = makeSyllabusModuleLecturerStaff($ctx);

    $response = $this->actingAs($ctx['user'])->getJson(route('v1.academic-staff.grouped-by-department'));

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                [
                    'departmentId',
                    'departmentName',
                    'staff' => [
                        ['id', 'name'],
                    ],
                ],
            ],
        ]);

    $staffIds = collect($response->json('data'))
        ->flatMap(fn (array $group) => collect($group['staff'])->pluck('id'))
        ->all();

    expect($staffIds)->toContain($lecturer->id);
});

it('filters grouped academic staff by search term', function () {
    $ctx = makeSyllabusModuleContext();
    $lecturer = makeSyllabusModuleLecturerStaff($ctx);
    $lecturer->user?->update(['first_name' => 'UniqueSearch', 'last_name' => 'LecturerName']);

    $matchingResponse = $this->actingAs($ctx['user'])->getJson(route('v1.academic-staff.grouped-by-department', [
        'search' => 'UniqueSearch',
    ]));

    $matchingResponse->assertOk();

    $matchingIds = collect($matchingResponse->json('data'))
        ->flatMap(fn (array $group) => collect($group['staff'])->pluck('id'))
        ->all();

    expect($matchingIds)->toContain($lecturer->id);

    $nonMatchingResponse = $this->actingAs($ctx['user'])->getJson(route('v1.academic-staff.grouped-by-department', [
        'search' => 'NoSuchStaffNameXYZ',
    ]));

    $nonMatchingResponse->assertOk()
        ->assertJsonPath('data', []);
});

it('excludes non academic staff from grouped academic staff endpoint', function () {
    $ctx = makeSyllabusModuleContext();
    $nonLecturer = makeSyllabusModuleNonLecturerStaff($ctx);

    $response = $this->actingAs($ctx['user'])->getJson(route('v1.academic-staff.grouped-by-department'));

    $response->assertOk();

    $staffIds = collect($response->json('data'))
        ->flatMap(fn (array $group) => collect($group['staff'])->pluck('id'))
        ->all();

    expect($staffIds)->not->toContain($nonLecturer->id);
});
