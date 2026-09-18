<?php

use App\Enums\Rbac\RoleEnum;
use App\Enums\Shared\POPTypeEnum;
use App\Enums\Shared\StatusEnum;
use App\Mail\Enrolments\VerifiedStudentsOfferLetterMail;
use App\Models\Institution\Staff;
use App\Models\Rbac\Permission;
use App\Models\Rbac\Role;
use App\Models\Shared\Gender;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Students\Sponsor;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use App\Services\Students\StudentOfferLetterService;
use Database\Seeders\Rbac\RoleGroupSeeder;
use Database\Seeders\Rbac\RolesTableSeeder;
use Database\Seeders\Users\UsersTableSeeder;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    (new RoleGroupSeeder)->run();
    (new RolesTableSeeder)->run();
});

function securityRole(RoleEnum $role): Role
{
    return Role::query()->where('name', $role->name())->firstOrFail();
}

/**
 * @param  array<int, string>  $permissions
 */
function securityUserWith(array $permissions = []): User
{
    $user = User::factory()->create();

    if ($permissions !== []) {
        $user->givePermissionTo($permissions);
    }

    return $user;
}

function securitySuperUser(): User
{
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::SUPER_USER->name());

    return $user;
}

/**
 * @return array<string, mixed>
 */
function securityStaffPayload(): array
{
    return [
        'first_name' => 'Security',
        'last_name' => 'Probe',
        'employee_number' => 'EC-SEC-'.Str::upper(Str::random(6)),
        'title_id' => Title::query()->firstOrCreate(['name' => 'Mr'])->id,
        'gender_id' => Gender::query()->firstOrCreate(['title' => 'Male'])->id,
        'marital_status_id' => MaritalStatus::query()->firstOrCreate(['title' => 'Single'])->id,
        'email' => 'security.probe.'.Str::lower(Str::random(8)).'@example.test',
        'phone_number' => '+26377'.random_int(1000000, 9999999),
        'role_ids' => [securityRole(RoleEnum::SUPER_USER)->id],
    ];
}

/**
 * @return array<string, mixed>
 */
function securityBulkPaymentPayload(object $application, string $field): array
{
    return [
        'intake_period_id' => $application->intake_period_id,
        'department_level_id' => $application->department_level_id,
        'current_step_id' => $application->workflow_step_id,
        'mode_of_study_id' => $application->mode_of_study_id,
        'field_to_update' => $field,
        'field_value' => true,
    ];
}

describe('role permissions', function () {
    it('forbids users without role rights from syncing role permissions', function () {
        $role = securityRole(RoleEnum::LECTURER);
        $before = $role->permissions()->pluck('permissions.id')->sort()->values()->all();
        $rootManage = Permission::query()->where('name', 'root:manage')->firstOrFail();

        $this->actingAs(securityUserWith())
            ->putJson(route('roles.sync-permissions', $role), ['permissions' => [$rootManage->id]])
            ->assertForbidden();

        expect($role->permissions()->pluck('permissions.id')->sort()->values()->all())->toBe($before);
    });

    it('lets role managers change permissions but never grant root:manage', function () {
        $role = securityRole(RoleEnum::LECTURER);
        $current = $role->permissions()->pluck('permissions.id')->map(fn ($id): int => (int) $id)->all();
        $rootManage = Permission::query()->where('name', 'root:manage')->firstOrFail();
        $viewUsers = Permission::query()->where('name', 'view:users')->firstOrFail();
        $manager = securityUserWith(['update:roles']);

        $this->actingAs($manager)
            ->putJson(route('roles.sync-permissions', $role), ['permissions' => [...$current, $rootManage->id]])
            ->assertForbidden();

        $this->actingAs($manager)
            ->putJson(route('roles.sync-permissions', $role), [
                'permissions' => array_values(array_unique([...$current, $viewUsers->id])),
            ])
            ->assertOk();

        $names = $role->permissions()->pluck('name')->all();

        expect($names)->toContain('view:users')
            ->and($names)->not->toContain('root:manage');
    });
});

describe('user and staff role assignment', function () {
    it('forbids users without user rights from creating staff accounts', function () {
        $payload = securityStaffPayload();

        $this->actingAs(securityUserWith())
            ->postJson(route('users.store-staff-user'), $payload)
            ->assertForbidden();

        expect(User::query()->where('email', $payload['email'])->exists())->toBeFalse();
    });

    it('stops user managers from assigning the super user role', function () {
        $manager = securityUserWith(['update:users']);
        $target = User::factory()->create();
        $payload = [
            'first_name' => $target->first_name,
            'last_name' => $target->last_name,
            'email' => $target->email,
        ];

        $this->actingAs($manager)
            ->putJson(route('users.update', $target), [...$payload, 'role_ids' => [securityRole(RoleEnum::SUPER_USER)->id]])
            ->assertForbidden();

        expect($target->fresh()->hasRole(RoleEnum::SUPER_USER->name()))->toBeFalse();

        $this->actingAs($manager)
            ->putJson(route('users.update', $target), [...$payload, 'role_ids' => [securityRole(RoleEnum::LECTURER)->id]])
            ->assertOk();

        expect($target->fresh()->hasRole(RoleEnum::LECTURER->name()))->toBeTrue();
    });

    it('stops non super users from editing super user accounts', function () {
        $superUser = securitySuperUser();

        $this->actingAs(securityUserWith(['update:users']))
            ->putJson(route('users.update', $superUser), [
                'first_name' => 'Changed',
                'last_name' => $superUser->last_name,
                'email' => 'takeover@example.test',
            ])
            ->assertForbidden();

        expect($superUser->fresh()->email)->not->toBe('takeover@example.test');
    });
});

describe('payment tools and webhook', function () {
    it('restricts payment status tools to payments-debug users', function () {
        $user = securityUserWith();

        $this->actingAs($user)
            ->postJson(route('integrations.payments-debug.update'), ['orderReference' => 'ORDER-SEC-1', 'paymentStatus' => 'paid'])
            ->assertForbidden();

        $this->actingAs($user)
            ->getJson(route('integrations.payments-debug.search', ['q' => 'someone@example.test']))
            ->assertForbidden();
    });

    it('refuses the payment webhook in production when gateway credentials are not configured', function () {
        config([
            'custom.payments.payment-gateway.api_key' => null,
            'custom.payments.payment-gateway.secret' => null,
        ]);
        app()->detectEnvironment(fn () => 'production');

        $this->withoutMiddleware([ValidateCsrfToken::class, PreventRequestForgery::class])
            ->postJson(route('integrations.payments.result'), ['orderReference' => 'ORDER-SEC-2', 'paymentStatus' => 'paid'])
            ->assertStatus(503);
    });

    it('refuses the payment webhook when credentials do not match', function () {
        config([
            'custom.payments.payment-gateway.api_key' => 'test-key',
            'custom.payments.payment-gateway.secret' => 'test-secret',
        ]);

        $this->postJson(route('integrations.payments.result'), ['orderReference' => 'ORDER-SEC-3', 'paymentStatus' => 'paid'], [
            'x-api-key' => 'wrong-key',
            'x-api-secret' => 'test-secret',
        ])->assertUnauthorized();
    });
});

describe('console', function () {
    it('restricts console dispatch to users who can run console commands', function () {
        $user = securityUserWith();

        $this->actingAs($user)
            ->get(route('console.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->postJson(route('console.dispatch'), [
                'command' => 'students-audit-phase-data',
                'password' => 'password',
            ])
            ->assertForbidden();
    });
});

describe('application workflows', function () {
    it('forbids students from moving applications or confirming fees', function () {
        $application = createVerifiedStudentApplication('SEC-WF-'.Str::upper(Str::random(4)));
        $student = $application->student->user;
        $student->assignRole(RoleEnum::STUDENT->name());

        $this->actingAs($student)
            ->postJson(route('students.approve-application', [
                'student_application' => $application->id,
                'workflow_step' => $application->workflow_step_id,
            ]))
            ->assertForbidden();

        $this->actingAs($student)
            ->postJson(route('students.confirm-registration-fee-payment', $application))
            ->assertForbidden();

        $this->actingAs($student)
            ->postJson(
                route('students.bulk-update-payment-statuses', $application->institution_department_id),
                securityBulkPaymentPayload($application, 'registration_fee_confirmed'),
            )
            ->assertForbidden();

        expect((bool) $application->fresh()->registration_fee_confirmed)->toBeFalse();
    });

    it('only accepts known payment flags in bulk payment updates', function () {
        $application = createVerifiedStudentApplication('SEC-BULK-'.Str::upper(Str::random(4)));

        $this->actingAs(securityUserWith(['root:manage']))
            ->postJson(
                route('students.bulk-update-payment-statuses', $application->institution_department_id),
                securityBulkPaymentPayload($application, 'workflow_step_id'),
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('field_to_update');
    });

    it('only lets applicants upload proof of payment for their own application, as documents or images', function () {
        $application = createVerifiedStudentApplication('SEC-POP-'.Str::upper(Str::random(4)));
        $owner = $application->student->user;
        $owner->givePermissionTo('manageOwnStudentApplicationDetails:students');
        // Same tenant, so the tenant scope doesn't hide the application and the ownership rule is what refuses it.
        $otherApplicant = User::factory()->create(['tenant_id' => $application->tenant_id]);
        $otherApplicant->givePermissionTo('manageOwnStudentApplicationDetails:students');
        $type = POPTypeEnum::cases()[0]->value;

        $this->actingAs($otherApplicant)
            ->postJson(route('students.upload-proof-of-payment', $application), [
                'type' => $type,
                'proof_of_payment' => UploadedFile::fake()->create('proof.pdf', 120, 'application/pdf'),
            ])
            ->assertForbidden();

        $this->actingAs($owner)
            ->postJson(route('students.upload-proof-of-payment', $application), [
                'type' => $type,
                'proof_of_payment' => UploadedFile::fake()->create('shell.php', 1, 'application/x-php'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('proof_of_payment');
    });
});

it('keeps existing account passwords when the users seeder runs', function () {
    $existing = User::factory()->create(['email' => 'support@hrepoly.ac.zw']);
    $originalHash = $existing->fresh()->password;

    (new UsersTableSeeder)->run();

    expect($existing->fresh()->password)->toBe($originalHash)
        ->and($existing->fresh()->hasRole(RoleEnum::IT_SUPPORT_TECHNICIAN->name()))->toBeTrue()
        ->and(User::query()->where('email', 'tkumvekera@hrepoly.ac.zw')->value('password'))->not->toBeEmpty();
});

describe('public APIs', function () {
    it('serves the public staff directory without personal identifiers', function () {
        $tenant = Tenant::query()->firstOrFail();
        $staff = Staff::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => User::factory()->create(['tenant_id' => $tenant->id])->id,
            'title_id' => Title::query()->firstOrCreate(['name' => 'Mr'])->id,
            'gender_id' => Gender::query()->firstOrCreate(['title' => 'Male'])->id,
            'marital_status_id' => MaritalStatus::query()->firstOrCreate(['title' => 'Single'])->id,
        ]);
        $staff->forceFill([
            'id_number' => '63-7654321Z00',
            'passport_number' => 'PNSEC12345',
            'date_of_birth' => '1985-05-05',
        ])->save();

        $response = $this->getJson(route('v1.staff.index'))->assertOk();

        $entry = collect($response->json('data'))->firstWhere('id', $staff->id);

        expect($entry)->not->toBeNull();

        foreach (['idNumber', 'passportNumber', 'dateOfBirth', 'workPermitNumber', 'race', 'religion', 'employeeNumber', 'maritalStatus'] as $key) {
            expect($entry['attributes'])->not->toHaveKey($key);
        }

        expect($response->getContent())
            ->not->toContain('63-7654321Z00')
            ->not->toContain('PNSEC12345');
    });

    it('does not route write operations on public lookup APIs', function () {
        expect($this->postJson('/api/v1/intake-periods', ['name' => 'Probe'])->status())->toBeIn([404, 405])
            ->and($this->deleteJson('/api/v1/countries/1')->status())->toBeIn([404, 405]);

        $this->getJson('/api/v1/rbac/roles')->assertUnauthorized();
    });

    it('limits student API records to the owning student', function () {
        $own = createVerifiedStudentApplication('SEC-OWN-'.Str::upper(Str::random(4)));
        $other = createVerifiedStudentApplication('SEC-OTH-'.Str::upper(Str::random(4)));
        $owner = $own->student->user;
        $owner->givePermissionTo('manageOwnStudentPersonalDetails:students');

        Sanctum::actingAs($owner);

        $this->getJson(route('v1.students.personal', $other->student))->assertForbidden();
        $this->getJson(route('v1.students.next-of-kins', $other->student))->assertForbidden();
        $this->getJson(route('v1.students.contacts', $own->student))->assertOk();
        $this->getJson(route('v1.students.index'))->assertForbidden();
    });
});

describe('API authentication', function () {
    it('refuses tokens for inactive accounts', function () {
        $user = User::factory()->create([
            'password' => 'Secret#12345',
            'status_id' => StatusEnum::INACTIVE->id(),
        ]);

        $this->postJson(route('v1.auth.login'), ['email' => $user->email, 'password' => 'Secret#12345'])
            ->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.accountInactive', true)
            ->assertJsonPath('data.token', null);
    });

    it('throttles repeated failed logins', function () {
        $user = User::factory()->create();

        foreach (range(1, 5) as $attempt) {
            $this->postJson(route('v1.auth.login'), ['email' => $user->email, 'password' => 'wrong-password'])
                ->assertOk()
                ->assertJsonPath('data.invalidCredentials', true);
        }

        $this->postJson(route('v1.auth.login'), ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertStatus(429);
    });
});

describe('offer letters', function () {
    it('sends guests with unsigned links to the login page', function () {
        $application = createVerifiedStudentApplication('SEC-OL-'.Str::upper(Str::random(4)));

        $this->get(route('documents.offer-letter', ['student_application' => $application->id]))
            ->assertRedirect(route('login'));
    });

    it('does not accept a signed link reused for a different application', function () {
        $signedFor = createVerifiedStudentApplication('SEC-OLA-'.Str::upper(Str::random(4)));
        $target = createVerifiedStudentApplication('SEC-OLB-'.Str::upper(Str::random(4)));

        $signedUrl = app(StudentOfferLetterService::class)->signedDownloadUrl($signedFor->id);
        $tamperedUrl = str_replace('/offer-letter/'.$signedFor->id.'?', '/offer-letter/'.$target->id.'?', $signedUrl);

        expect($tamperedUrl)->not->toBe($signedUrl);

        $this->get($tamperedUrl)->assertRedirect(route('login'));
    });

    it('forbids students from opening another student offer letter', function () {
        $own = createVerifiedStudentApplication('SEC-OLO-'.Str::upper(Str::random(4)));
        $other = createVerifiedStudentApplication('SEC-OLX-'.Str::upper(Str::random(4)));

        $this->actingAs($own->student->user)
            ->get(route('documents.offer-letter', ['student_application' => $other->id]))
            ->assertForbidden();
    });

    it('emails an expiring signed download link', function () {
        $application = createVerifiedStudentApplication('SEC-OLM-'.Str::upper(Str::random(4)));

        $html = (new VerifiedStudentsOfferLetterMail('Security Student', (string) $application->id))->render();

        expect($html)->toContain('signature=')->toContain('expires=');
    });
});

it('only starts impersonation through POST', function () {
    $root = securityUserWith(['root:manage']);
    $target = User::factory()->create();

    $this->actingAs($root)
        ->get('/impersonate/take/'.$target->id)
        ->assertMethodNotAllowed();
});

it('hides registration confirmation pages from other visitors', function () {
    $user = User::factory()->create();

    $this->get(route('portal.confirmation', $user))->assertNotFound();

    $this->actingAs(User::factory()->create())
        ->get(route('portal.confirmation', $user))
        ->assertNotFound();
});

it('forbids students from deleting another student sponsor', function () {
    $own = createVerifiedStudentApplication('SEC-SPA-'.Str::upper(Str::random(4)));
    $other = createVerifiedStudentApplication('SEC-SPB-'.Str::upper(Str::random(4)));
    $sponsor = Sponsor::query()->create([
        'tenant_id' => $own->tenant_id,
        'student_id' => $own->student_id,
        'name' => 'Security Sponsor',
    ]);
    $intruder = $other->student->user;
    $intruder->givePermissionTo('manageOwnStudentSponsorDetails:students');

    $this->actingAs($intruder)
        ->deleteJson(route('sponsors.destroy', $sponsor))
        ->assertForbidden();

    expect(Sponsor::query()->whereKey($sponsor->id)->exists())->toBeTrue();
});

it('does not sign in inactive accounts on the web', function () {
    $user = User::factory()->create([
        'password' => 'Secret#12345',
        'status_id' => StatusEnum::INACTIVE->id(),
    ]);

    $this->post('/login', ['email' => $user->email, 'password' => 'Secret#12345'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});
