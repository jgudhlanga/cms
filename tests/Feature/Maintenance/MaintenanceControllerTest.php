<?php

use App\Jobs\Applications\ExportApplicationJob;
use App\Jobs\Enrolments\ExportStudentEnrollmentJob;
use App\Models\Acl\Permission;
use App\Models\Users\User;
use Illuminate\Support\Facades\Queue;

function actingAsRootMaintenanceUser(): User
{
    Permission::findOrCreate('root:manage', 'web');

    $user = User::factory()->create();
    $user->givePermissionTo('root:manage');
    test()->actingAs($user);

    return $user;
}

it('redirects guests from maintenance index', function (): void {
    $this->get(route('maintenance.index'))
        ->assertRedirect('/login');
});

it('forbids users without root manage from maintenance index', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('maintenance.index'))
        ->assertForbidden();
});

it('renders maintenance index for root users', function (): void {
    actingAsRootMaintenanceUser();

    $this->get(route('maintenance.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('maintenance/Index')
            ->has('exportCounts.studentEnrolments')
            ->has('exportCounts.applications')
            ->has('exportCounts.faultyStudentIds'));
});

it('redirects guests from maintenance export counts endpoint', function (): void {
    $this->get(route('maintenance.exports.counts'))
        ->assertRedirect('/login');
});

it('forbids users without root manage from maintenance export counts endpoint', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('maintenance.exports.counts'))
        ->assertForbidden();
});

it('returns maintenance export counts for root users', function (): void {
    actingAsRootMaintenanceUser();

    $this->getJson(route('maintenance.exports.counts'))
        ->assertSuccessful()
        ->assertJsonStructure([
            'studentEnrolments',
            'applications',
            'faultyStudentIds',
        ]);
});

it('redirects guests from student enrollment export endpoint', function (): void {
    $this->post(route('maintenance.exports.student-enrollment'))
        ->assertRedirect('/login');
});

it('forbids users without root manage from student enrollment export endpoint', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->post(route('maintenance.exports.student-enrollment'))
        ->assertForbidden();
});

it('requires recipient emails for student enrollment export', function (): void {
    actingAsRootMaintenanceUser();

    $this->from(route('maintenance.index'))
        ->post(route('maintenance.exports.student-enrollment'))
        ->assertSessionHasErrors('recipient_emails');
});

it('queues student enrollment export for root users', function (): void {
    Queue::fake();
    actingAsRootMaintenanceUser();

    $this->from(route('maintenance.index'))
        ->post(route('maintenance.exports.student-enrollment'), [
            'recipient_emails' => 'exports@example.test',
        ])
        ->assertRedirect(route('maintenance.index'))
        ->assertSessionHas('success', __('trans.maintenance_export_queued_message'));

    Queue::assertPushed(ExportStudentEnrollmentJob::class, function (ExportStudentEnrollmentJob $job): bool {
        return $job->intakeYear === null
            && $job->recipientEmails === ['exports@example.test'];
    });
});

it('queues student enrollment export with intake year and multiple recipient emails', function (): void {
    Queue::fake();
    actingAsRootMaintenanceUser();

    $this->from(route('maintenance.index'))
        ->post(route('maintenance.exports.student-enrollment'), [
            'intake_year' => '2025/2026',
            'recipient_emails' => 'one@example.test, two@example.test',
        ])
        ->assertRedirect(route('maintenance.index'))
        ->assertSessionHas('success');

    Queue::assertPushed(ExportStudentEnrollmentJob::class, function (ExportStudentEnrollmentJob $job): bool {
        return $job->intakeYear === '2025/2026'
            && $job->recipientEmails === ['one@example.test', 'two@example.test'];
    });
});

it('redirects guests from application export endpoint', function (): void {
    $this->post(route('maintenance.exports.application'))
        ->assertRedirect('/login');
});

it('forbids users without root manage from application export endpoint', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->post(route('maintenance.exports.application'))
        ->assertForbidden();
});

it('requires recipient emails for application export', function (): void {
    actingAsRootMaintenanceUser();

    $this->from(route('maintenance.index'))
        ->post(route('maintenance.exports.application'))
        ->assertSessionHasErrors('recipient_emails');
});

it('queues application export for root users', function (): void {
    Queue::fake();
    actingAsRootMaintenanceUser();

    $this->from(route('maintenance.index'))
        ->post(route('maintenance.exports.application'), [
            'recipient_emails' => 'exports@example.test',
        ])
        ->assertRedirect(route('maintenance.index'))
        ->assertSessionHas('success', __('trans.maintenance_export_application_queued_message'));

    Queue::assertPushed(ExportApplicationJob::class, function (ExportApplicationJob $job): bool {
        return $job->intakeYear === null
            && $job->recipientEmails === ['exports@example.test'];
    });
});

it('queues application export with intake year and multiple recipient emails', function (): void {
    Queue::fake();
    actingAsRootMaintenanceUser();

    $this->from(route('maintenance.index'))
        ->post(route('maintenance.exports.application'), [
            'intake_year' => '2025/2026',
            'recipient_emails' => 'one@example.test, two@example.test',
        ])
        ->assertRedirect(route('maintenance.index'))
        ->assertSessionHas('success');

    Queue::assertPushed(ExportApplicationJob::class, function (ExportApplicationJob $job): bool {
        return $job->intakeYear === '2025/2026'
            && $job->recipientEmails === ['one@example.test', 'two@example.test'];
    });
});
