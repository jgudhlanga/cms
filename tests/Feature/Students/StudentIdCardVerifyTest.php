<?php

declare(strict_types=1);

use App\Enums\Students\IdCardRequestReasonEnum;
use App\Services\Students\StudentIdCardRequestService;

test('guests can verify a printed card by serial', function () {
    ['tenant' => $tenant, 'student' => $student] = createIdCardStudent();
    attachIdCardPhoto($student);
    $service = app(StudentIdCardRequestService::class);
    $admin = createIdCardStaff((int) $tenant->id, [
        'review:student-id-card-requests',
        'print:student-id-card-requests',
    ]);
    $request = $service->approve($service->submit($student, IdCardRequestReasonEnum::NEW), $admin);
    $service->print($request, $admin);
    $serial = $request->fresh()->serial_number;

    $this->get(route('id-cards.verify', $serial))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('site/id-cards/Verify')
            ->where('outcome', 'valid')
            ->where('card.studentNumber', $student->student_number)
            ->where('studentProfileUrl', null)
        );
});

test('unknown serials show an invalid public page without student data', function () {
    $this->get(route('id-cards.verify', 'HPC-UNKNOWN-1'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('site/id-cards/Verify')
            ->where('outcome', 'invalid')
            ->where('card', null)
            ->where('studentProfileUrl', null)
        );
});

test('expired cards show expired without student data', function () {
    ['tenant' => $tenant, 'student' => $student] = createIdCardStudent();
    attachIdCardPhoto($student);
    $service = app(StudentIdCardRequestService::class);
    $admin = createIdCardStaff((int) $tenant->id, [
        'review:student-id-card-requests',
        'print:student-id-card-requests',
    ]);
    $this->travelTo(now()->subYear()->startOfYear()->addMonth());
    $request = $service->approve($service->submit($student, IdCardRequestReasonEnum::NEW), $admin);
    $service->print($request, $admin);
    $serial = $request->fresh()->serial_number;
    $this->travelBack();

    $this->get(route('id-cards.verify', $serial))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('site/id-cards/Verify')
            ->where('outcome', 'expired')
            ->where('card', null)
        );
});

test('logged in staff with view students see a profile link', function () {
    ['tenant' => $tenant, 'student' => $student] = createIdCardStudent();
    attachIdCardPhoto($student);
    $service = app(StudentIdCardRequestService::class);
    $admin = createIdCardStaff((int) $tenant->id, [
        'review:student-id-card-requests',
        'print:student-id-card-requests',
        'view:students',
    ]);
    $request = $service->approve($service->submit($student, IdCardRequestReasonEnum::NEW), $admin);
    $service->print($request, $admin);
    $serial = $request->fresh()->serial_number;

    $this->actingAs($admin)
        ->get(route('id-cards.verify', $serial))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('site/id-cards/Verify')
            ->where('outcome', 'valid')
            ->where('studentProfileUrl', route('students.show', $student))
        );
});

test('pending cards cannot be verified', function () {
    ['student' => $student] = createIdCardStudent();
    attachIdCardPhoto($student);
    $request = app(StudentIdCardRequestService::class)->submit($student, IdCardRequestReasonEnum::NEW);
    $request->update(['serial_number' => 'HPC-PENDING-'.$request->id]);

    $this->get(route('id-cards.verify', $request->serial_number))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('outcome', 'invalid')
            ->where('card', null)
        );
});
