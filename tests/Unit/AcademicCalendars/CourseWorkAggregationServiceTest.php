<?php

use App\Services\AcademicCalendars\CourseWorkAggregationService;

test('aggregates weighted course work total out of 60', function () {
    $service = new CourseWorkAggregationService;

    $assessmentTypes = [
        ['id' => 1, 'name' => 'Research Based', 'weightPercent' => 20],
        ['id' => 2, 'name' => 'Innovation Based', 'weightPercent' => 20],
        ['id' => 3, 'name' => 'Test', 'weightPercent' => 20],
    ];

    $assessments = [
        ['assessmentTypeId' => 1, 'assessmentTypeName' => 'Research Based', 'mark' => 80, 'remark' => null],
        ['assessmentTypeId' => 2, 'assessmentTypeName' => 'Innovation Based', 'mark' => 70, 'remark' => null],
        ['assessmentTypeId' => 3, 'assessmentTypeName' => 'Test', 'mark' => 90, 'remark' => 'Strong'],
    ];

    $result = $service->aggregateStudentModule($assessmentTypes, $assessments);

    expect($result['isComplete'])->toBeTrue()
        ->and($result['courseWorkTotal60'])->toBe(48.0)
        ->and($result['remark'])->toBe('Strong');
});

test('returns incomplete aggregation when a component mark is missing', function () {
    $service = new CourseWorkAggregationService;

    $assessmentTypes = [
        ['id' => 1, 'name' => 'Research Based', 'weightPercent' => 20],
        ['id' => 2, 'name' => 'Innovation Based', 'weightPercent' => 20],
    ];

    $assessments = [
        ['assessmentTypeId' => 1, 'assessmentTypeName' => 'Research Based', 'mark' => 80, 'remark' => null],
        ['assessmentTypeId' => 2, 'assessmentTypeName' => 'Innovation Based', 'mark' => null, 'remark' => null],
    ];

    $result = $service->aggregateStudentModule($assessmentTypes, $assessments);

    expect($result['isComplete'])->toBeFalse()
        ->and($result['courseWorkTotal60'])->toBeNull();
});

test('uses equal weight split when assessment weights are missing', function () {
    $service = new CourseWorkAggregationService;

    $assessmentTypes = [
        ['id' => 1, 'name' => 'A', 'weightPercent' => null],
        ['id' => 2, 'name' => 'B', 'weightPercent' => null],
        ['id' => 3, 'name' => 'C', 'weightPercent' => null],
    ];

    $assessments = [
        ['assessmentTypeId' => 1, 'mark' => 100, 'remark' => null],
        ['assessmentTypeId' => 2, 'mark' => 100, 'remark' => null],
        ['assessmentTypeId' => 3, 'mark' => 100, 'remark' => null],
    ];

    $result = $service->aggregateStudentModule($assessmentTypes, $assessments);

    expect($result['courseWorkTotal60'])->toBe(60.0);
});
