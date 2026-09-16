<?php

declare(strict_types=1);

use App\Enums\Students\StudyPositionAnswerEnum;
use App\Enums\Students\StudyPositionSourceEnum;
use App\Enums\Students\StudyPositionSyncStatusEnum;
use App\Models\Students\Student;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentStudyPositionConfirmation;
use App\Models\Users\User;
use App\Services\Students\StudyPosition\StudyPositionService;

function actingAsStudyPositionStudent(StudentEnrolment $enrolment): User
{
    $student = Student::query()->withoutGlobalScopes()->findOrFail($enrolment->student_id);
    $user = User::query()->findOrFail($student->user_id);
    $user->givePermissionTo([
        'viewOwnDashboard:students',
        'manageOwnStudentAcademicDetails:students',
        'manageOwnStudentPersonalDetails:students',
    ]);

    StudyPositionService::forget((int) $student->id);
    test()->actingAs($user);

    return $user;
}

/**
 * @return array{context: array<string, mixed>, offering: array<string, mixed>, enrolment: StudentEnrolment}
 */
function makePortalStudyPositionStudent(): array
{
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [$y1s1, $y1s2] = $offering['phases'];

    $enrolment = makeEnrolmentWithPhases($context, $offering, [
        ['slug' => 'semester-1', 'phase' => $y1s1],
        ['slug' => 'semester-2', 'phase' => $y1s2],
    ]);

    return compact('context', 'offering', 'enrolment');
}

it('forces the prompt on portal pages while the period is unconfirmed', function (): void {
    ['enrolment' => $enrolment] = makePortalStudyPositionStudent();
    actingAsStudyPositionStudent($enrolment);

    $this->get(route('portal.profile.personal-information'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('studyPosition.required', true)
            ->where('studyPosition.state', 'unconfirmed')
            ->where('studyPosition.periodLabel', '2026 · Semester 2')
            ->has('studyPosition.items', 1));
});

it('shares nothing with staff', function (): void {
    $context = makeStudyPositionContext();
    $context['user']->givePermissionTo('viewAny:students');

    $this->get(route('students.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('studyPosition', null));
});

it('does not force the prompt on an impersonating admin', function (): void {
    ['enrolment' => $enrolment] = makePortalStudyPositionStudent();
    $admin = User::factory()->create();
    actingAsStudyPositionStudent($enrolment);

    $this->withSession(['impersonated_by' => $admin->id])
        ->get(route('portal.profile.personal-information'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('studyPosition.required', false)
            ->where('studyPosition.state', 'unconfirmed'));
});

it('returns only the student own programmes with their phase options', function (): void {
    ['context' => $context, 'offering' => $offering, 'enrolment' => $enrolment] = makePortalStudyPositionStudent();
    makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-1', 'phase' => $offering['phases'][0]]]);
    actingAsStudyPositionStudent($enrolment);

    $this->getJson(route('portal.study-position.show'))
        ->assertOk()
        ->assertJsonCount(1, 'items')
        ->assertJsonPath('items.0.enrolmentId', (int) $enrolment->id)
        ->assertJsonPath('items.0.studentCanAnswer', true)
        ->assertJsonPath('items.0.systemPhase.id', (int) $offering['phases'][1]->id)
        ->assertJsonPath('items.0.canConfirm', false)
        ->assertJsonPath('items.0.confirmation', null);
});

it('records a student answer and stops forcing the prompt', function (): void {
    ['offering' => $offering, 'enrolment' => $enrolment] = makePortalStudyPositionStudent();
    actingAsStudyPositionStudent($enrolment);

    $this->from(route('portal.dashboard'))
        ->post(route('portal.study-position.store'), [
            'answers' => [[
                'student_enrolment_id' => $enrolment->id,
                'answer' => 'phase',
                'programme_semester_id' => $offering['phases'][1]->id,
            ]],
        ])
        ->assertRedirect(route('portal.dashboard'))
        ->assertSessionHas('success');

    $confirmation = StudentStudyPositionConfirmation::query()->sole();

    expect($confirmation->source)->toBe(StudyPositionSourceEnum::STUDENT)
        ->and($confirmation->sync_status)->toBe(StudyPositionSyncStatusEnum::UNCHANGED);

    $this->get(route('portal.profile.personal-information'))
        ->assertInertia(fn ($page) => $page
            ->where('studyPosition.required', false)
            ->where('studyPosition.state', 'confirmed'));
});

it('keeps a not sure answer on the follow-up banner', function (): void {
    ['enrolment' => $enrolment] = makePortalStudyPositionStudent();
    actingAsStudyPositionStudent($enrolment);

    $this->post(route('portal.study-position.store'), [
        'answers' => [[
            'student_enrolment_id' => $enrolment->id,
            'answer' => StudyPositionAnswerEnum::NOT_SURE->value,
        ]],
    ])->assertSessionHasNoErrors();

    $this->get(route('portal.profile.personal-information'))
        ->assertInertia(fn ($page) => $page
            ->where('studyPosition.required', false)
            ->where('studyPosition.state', 'follow_up'));
});

it('refuses another student enrolment without writing anything', function (): void {
    ['context' => $context, 'offering' => $offering, 'enrolment' => $enrolment] = makePortalStudyPositionStudent();
    $someoneElse = makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-1', 'phase' => $offering['phases'][0]]]);
    actingAsStudyPositionStudent($enrolment);

    $this->post(route('portal.study-position.store'), [
        'answers' => [[
            'student_enrolment_id' => $someoneElse->id,
            'answer' => 'phase',
            'programme_semester_id' => $offering['phases'][1]->id,
        ]],
    ])->assertSessionHasErrors('answers.0.student_enrolment_id');

    expect(StudentStudyPositionConfirmation::query()->count())->toBe(0);
});

it('requires a phase when the answer is a phase', function (): void {
    ['enrolment' => $enrolment] = makePortalStudyPositionStudent();
    actingAsStudyPositionStudent($enrolment);

    $this->post(route('portal.study-position.store'), [
        'answers' => [['student_enrolment_id' => $enrolment->id, 'answer' => 'phase']],
    ])->assertSessionHasErrors('answers.0.programme_semester_id');
});

it('rejects a second answer once the period is confirmed', function (): void {
    ['offering' => $offering, 'enrolment' => $enrolment] = makePortalStudyPositionStudent();
    actingAsStudyPositionStudent($enrolment);

    $payload = ['answers' => [[
        'student_enrolment_id' => $enrolment->id,
        'answer' => 'phase',
        'programme_semester_id' => $offering['phases'][1]->id,
    ]]];

    $this->post(route('portal.study-position.store'), $payload)->assertSessionHasNoErrors();
    $this->post(route('portal.study-position.store'), $payload)->assertSessionHasErrors('answers.0.student_enrolment_id');
});

it('blocks answering while impersonating', function (): void {
    ['offering' => $offering, 'enrolment' => $enrolment] = makePortalStudyPositionStudent();
    $admin = User::factory()->create();
    actingAsStudyPositionStudent($enrolment);

    $this->withSession(['impersonated_by' => $admin->id])
        ->post(route('portal.study-position.store'), [
            'answers' => [[
                'student_enrolment_id' => $enrolment->id,
                'answer' => 'phase',
                'programme_semester_id' => $offering['phases'][1]->id,
            ]],
        ])
        ->assertForbidden();

    expect(StudentStudyPositionConfirmation::query()->count())->toBe(0);
});

it('forbids users without a student profile', function (): void {
    makeStudyPositionContext();

    $this->getJson(route('portal.study-position.show'))->assertForbidden();
    $this->postJson(route('portal.study-position.store'), [
        'answers' => [['student_enrolment_id' => 1, 'answer' => 'not_sure']],
    ])->assertForbidden();
});
