<?php

declare(strict_types=1);

use App\Enums\Students\StudentExamResultComment;
use App\Models\Institution\InstitutionFeature;
use App\Services\Institution\InstitutionFeatureService;

test('maps known course comments to enum values', function () {
    expect(StudentExamResultComment::tryFromCourseComment('AWARD'))->toBe(StudentExamResultComment::Award)
        ->and(StudentExamResultComment::tryFromCourseComment(' proceed '))->toBe(StudentExamResultComment::Proceed)
        ->and(StudentExamResultComment::tryFromCourseComment('UNKNOWN'))->toBeNull()
        ->and(StudentExamResultComment::tryFromCourseComment(null))->toBeNull();
});

test('institution features default allow online clearance to false', function () {
    $service = new InstitutionFeatureService;
    $defaults = $service->defaults();

    expect($defaults[InstitutionFeature::ALLOW_ONLINE_CLEARANCE])->toBeFalse();
});
