<?php

declare(strict_types=1);

use App\Enums\Institution\LevelEnum;
use App\Enums\Institution\ModeOfStudyEnum;
use App\Enums\Shared\IdTypeEnum;
use App\Models\HMS\HostelRoomAllocation;
use App\Models\Institution\Course;
use App\Models\Institution\Department;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Level;
use App\Models\Institution\ModeOfStudy;
use App\Models\Students\Student;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentIdCardSetting;
use App\Support\Students\StudentIdCardFace;
use Carbon\Carbon;

if (! function_exists('studentWithIdCardFace')) {
    /**
     * @param  array{level?: string, mode?: string, resident?: bool}  $overrides
     */
    function studentWithIdCardFace(array $overrides = []): Student
    {
        ['student' => $student, 'user' => $user] = createIdCardStudent();

        $department = new Department(['name' => 'Information Science']);
        $institutionDepartment = new InstitutionDepartment;
        $institutionDepartment->setRelation('department', $department);

        $level = new Level(['name' => $overrides['level'] ?? LevelEnum::ND->value]);
        $departmentLevel = new DepartmentLevel;
        $departmentLevel->setRelation('level', $level);

        $course = new Course(['name' => 'Information Technology']);
        $departmentCourse = new DepartmentCourse;
        $departmentCourse->setRelation('course', $course);

        $mode = new ModeOfStudy(['name' => $overrides['mode'] ?? ModeOfStudyEnum::BLOCK_RELEASE->value]);

        $enrolment = new StudentEnrolment;
        $enrolment->setRelation('institutionDepartment', $institutionDepartment);
        $enrolment->setRelation('departmentLevel', $departmentLevel);
        $enrolment->setRelation('departmentCourse', $departmentCourse);
        $enrolment->setRelation('modeOfStudy', $mode);

        $student->setRelation('user', $user);
        $student->setRelation('latestEnrolment', $enrolment);
        $student->setRelation(
            'activeHostelAllocation',
            ($overrides['resident'] ?? false) ? new HostelRoomAllocation : null,
        );

        return $student;
    }
}

test('id card face maps enrolment fields block mode and year-end expiry', function () {
    $this->travelTo(Carbon::parse('2026-08-14 12:00:00'));

    $student = studentWithIdCardFace();
    $face = StudentIdCardFace::fromStudent($student);

    expect($face->studentName)->toBe($student->user->full_name)
        ->and($face->studentNumber)->toBe((string) $student->student_number)
        ->and($face->department)->toBe('Information Science')
        ->and($face->level)->toBe(LevelEnum::ND->value)
        ->and($face->course)->toBe('Information Technology')
        ->and($face->mode)->toBe('Block')
        ->and($face->sdp)->toBe('No')
        ->and($face->residence)->toBe('NON Res')
        ->and($face->expiryDate)->toBe('31 Dec 2026')
        ->and($face->nationalId)->toBe((string) $student->passport_number)
        ->and($face->identityLabel)->toBe(__('trans.student_id_card_passport_number'));
});

test('id card face uses national id and label for zimbabwean students', function () {
    $student = studentWithIdCardFace();
    $student->id_type_id = IdTypeEnum::ZIMBABWEAN_ID_NUMBER->id();
    $student->id_number = '63-123456A63';
    $student->passport_number = 'P12345678';

    $face = StudentIdCardFace::fromStudent($student);

    expect($face->nationalId)->toBe('63-123456A63')
        ->and($face->identityLabel)->toBe(__('trans.student_id_card_national_id'));
});

test('id card face uses passport and label for non-citizen students', function () {
    $student = studentWithIdCardFace();
    $student->id_number = '63-123456A63';
    $student->passport_number = 'P87654321';

    $face = StudentIdCardFace::fromStudent($student);

    expect($face->nationalId)->toBe('P87654321')
        ->and($face->identityLabel)->toBe(__('trans.student_id_card_passport_number'));
});

test('id card face marks sdp yes when the enrolment level is sdp', function () {
    $student = studentWithIdCardFace(['level' => LevelEnum::SDP->value]);
    $face = StudentIdCardFace::fromStudent($student);

    expect($face->level)->toBe(LevelEnum::SDP->value)
        ->and($face->sdp)->toBe('Yes');
});

test('id card face marks residence as res when a hostel allocation is active', function () {
    $student = studentWithIdCardFace(['resident' => true]);
    $face = StudentIdCardFace::fromStudent($student);

    expect($face->residence)->toBe('RES');
});

test('id card face maps full time and part time mode labels', function () {
    $fullTime = StudentIdCardFace::fromStudent(
        studentWithIdCardFace(['mode' => ModeOfStudyEnum::FULL_TIME->value]),
    );
    $partTime = StudentIdCardFace::fromStudent(
        studentWithIdCardFace(['mode' => ModeOfStudyEnum::PART_TIME->value]),
    );

    expect($fullTime->mode)->toBe('Full time')
        ->and($partTime->mode)->toBe('Part time');
});

test('id card face uses persisted branding settings', function () {
    $student = studentWithIdCardFace();
    $settings = StudentIdCardSetting::factory()->make([
        'institution_name' => 'Example Polytechnic',
        'website' => 'www.example.ac.zw',
        'return_name' => 'Example Registry',
        'return_address' => '1 Test Street',
        'return_phone' => '0999',
    ]);

    $face = StudentIdCardFace::fromStudent($student, settings: $settings);

    expect($face->institutionName)->toBe('Example Polytechnic')
        ->and($face->website)->toBe('www.example.ac.zw')
        ->and($face->returnName)->toBe('Example Registry')
        ->and($face->returnAddress)->toBe('1 Test Street')
        ->and($face->returnPhone)->toBe('0999');
});
