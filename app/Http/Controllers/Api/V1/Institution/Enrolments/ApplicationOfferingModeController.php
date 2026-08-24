<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Institution\Enrolments;

use App\Enums\Students\ApplicationTrackEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\Institution\ModeOfStudyResource;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\ModeOfStudy;
use App\Services\Students\ApplicationEligibilityService;
use App\Services\Students\ApplicationTrackSession;
use App\Services\Students\RegistrationProgrammeAvailabilityService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ApplicationOfferingModeController extends Controller
{
    public function __construct(
        protected RegistrationProgrammeAvailabilityService $programmeAvailability,
    ) {}

    public function courseModes(
        DepartmentCourse $departmentCourse,
        DepartmentLevel $departmentLevel,
    ): AnonymousResourceCollection {
        $departmentLevel->loadMissing('level');

        $rows = $this->programmeAvailability->offeredModesForCourseLevel(
            (int) $departmentCourse->id,
            (int) $departmentLevel->id,
        );

        $modeObjects = ModeOfStudy::query()
            ->whereIn('id', collect($rows)->pluck('id')->all())
            ->orderBy('name')
            ->get();

        $track = app(ApplicationTrackSession::class)->get();
        if ($track === ApplicationTrackEnum::Continuous && $departmentLevel->level !== null) {
            $eligibility = app(ApplicationEligibilityService::class);
            if (! $eligibility->isSdpLevel($departmentLevel->level)) {
                $modeObjects = $modeObjects->filter(
                    fn (ModeOfStudy $mode) => $eligibility->isOjetMode($mode),
                );
            }
        }

        return ModeOfStudyResource::collection($modeObjects->values());
    }
}
