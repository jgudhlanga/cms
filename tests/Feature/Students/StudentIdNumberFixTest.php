<?php

use App\Enums\Shared\IdTypeEnum;
use App\Models\Shared\IdType;
use App\Models\Users\User;
use Illuminate\Support\Str;

it('exposes invalid id flags on student show for zimbabwean national ids', function (): void {
    $program = createVerifiedStudentApplication('ID-FLAGS-'.strtoupper(Str::random(4)));
    $student = $program->student;

    $zimIdType = IdType::query()->firstOrCreate(
        ['name' => IdTypeEnum::ZIMBABWEAN_ID_NUMBER->value],
        ['description' => IdTypeEnum::ZIMBABWEAN_ID_NUMBER->description(), 'is_default' => true],
    );

    $student->update([
        'id_type_id' => $zimIdType->id,
        'id_number' => '631234567N63',
    ]);

    $admin = User::factory()->create(['tenant_id' => $student->tenant_id]);
    $admin->givePermissionTo(['view:students', 'viewAny:students']);

    $this->actingAs($admin)
        ->get(route('students.show', $student))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('students/Show')
            ->where('student.attributes.idNumberValid', false)
            ->where('student.attributes.suggestedIdNumber', '63-1234567N63')
            ->where('student.attributes.idNumberRectificationStatus', 'ready_to_fix')
            ->where('student.attributes.idNumberConflict', null));
});

it('exposes duplicate merge status when suggested id is already taken', function (): void {
    $program = createVerifiedStudentApplication('ID-DUP-'.strtoupper(Str::random(4)));
    $student = $program->student;

    $zimIdType = IdType::query()->firstOrCreate(
        ['name' => IdTypeEnum::ZIMBABWEAN_ID_NUMBER->value],
        ['description' => IdTypeEnum::ZIMBABWEAN_ID_NUMBER->description(), 'is_default' => true],
    );

    $otherProgram = createVerifiedStudentApplication('ID-TAKEN-'.strtoupper(Str::random(4)));
    $otherProgram->student->update([
        'id_type_id' => $zimIdType->id,
        'id_number' => '63-1234567N63',
    ]);

    $student->update([
        'id_type_id' => $zimIdType->id,
        'id_number' => '631234567N63',
    ]);

    $admin = User::factory()->create(['tenant_id' => $student->tenant_id]);
    $admin->givePermissionTo(['view:students', 'viewAny:students']);

    $this->actingAs($admin)
        ->get(route('students.show', $student))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('students/Show')
            ->where('student.attributes.idNumberValid', false)
            ->where('student.attributes.suggestedIdNumber', '63-1234567N63')
            ->where('student.attributes.idNumberRectificationStatus', 'duplicate_merge')
            ->where('student.attributes.idNumberConflict.conflictingStudentId', $otherProgram->student->id)
            ->missing('student.attributes.idNumberConflict.mergePreviewUrl'));
});

it('includes merge preview url for root managers when suggested id conflicts', function (): void {
    $program = createVerifiedStudentApplication('ID-ROOT-'.strtoupper(Str::random(4)));
    $student = $program->student;

    $zimIdType = IdType::query()->firstOrCreate(
        ['name' => IdTypeEnum::ZIMBABWEAN_ID_NUMBER->value],
        ['description' => IdTypeEnum::ZIMBABWEAN_ID_NUMBER->description(), 'is_default' => true],
    );

    $otherProgram = createVerifiedStudentApplication('ID-ROOT-T-'.strtoupper(Str::random(4)));
    $otherProgram->student->update([
        'id_type_id' => $zimIdType->id,
        'id_number' => '63-1234567N63',
    ]);

    $student->update([
        'id_type_id' => $zimIdType->id,
        'id_number' => '631234567N63',
    ]);

    $admin = User::factory()->create(['tenant_id' => $student->tenant_id]);
    $admin->givePermissionTo(['view:students', 'viewAny:students', 'root:manage']);

    $this->actingAs($admin)
        ->get(route('students.show', $student))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('students/Show')
            ->where('student.attributes.idNumberRectificationStatus', 'duplicate_merge')
            ->where('student.attributes.idNumberConflict.conflictingStudentId', $otherProgram->student->id)
            ->has('student.attributes.idNumberConflict.mergePreviewUrl'));
});

it('suggests hyphenated format when id number uses underscores', function (): void {
    $program = createVerifiedStudentApplication('ID-UNDERSCORE-'.strtoupper(Str::random(4)));
    $student = $program->student;

    $zimIdType = IdType::query()->firstOrCreate(
        ['name' => IdTypeEnum::ZIMBABWEAN_ID_NUMBER->value],
        ['description' => IdTypeEnum::ZIMBABWEAN_ID_NUMBER->description(), 'is_default' => true],
    );

    $student->update([
        'id_type_id' => $zimIdType->id,
        'id_number' => '75_2015378T80',
    ]);

    $admin = User::factory()->create(['tenant_id' => $student->tenant_id]);
    $admin->givePermissionTo(['view:students', 'viewAny:students']);

    $this->actingAs($admin)
        ->get(route('students.show', $student))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('students/Show')
            ->where('student.attributes.idNumberValid', false)
            ->where('student.attributes.suggestedIdNumber', '75-2015378T80'));
});

it('exposes valid id flags when zimbabwean id is already correct', function (): void {
    $program = createVerifiedStudentApplication('ID-VALID-'.strtoupper(Str::random(4)));
    $student = $program->student;

    $zimIdType = IdType::query()->firstOrCreate(
        ['name' => IdTypeEnum::ZIMBABWEAN_ID_NUMBER->value],
        ['description' => IdTypeEnum::ZIMBABWEAN_ID_NUMBER->description(), 'is_default' => true],
    );

    $student->update([
        'id_type_id' => $zimIdType->id,
        'id_number' => '63-1234567N63',
    ]);

    $admin = User::factory()->create(['tenant_id' => $student->tenant_id]);
    $admin->givePermissionTo(['view:students', 'viewAny:students']);

    $this->actingAs($admin)
        ->get(route('students.show', $student))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('students/Show')
            ->where('student.attributes.idNumberValid', true)
            ->where('student.attributes.suggestedIdNumber', null)
            ->where('student.attributes.idNumberRectificationStatus', null)
            ->where('student.attributes.idNumberConflict', null));
});

it('allows admin with update:students to fix an invalid id number', function (): void {
    $program = createVerifiedStudentApplication('ID-FIX-ADM-'.strtoupper(Str::random(4)));
    $student = $program->student;
    $student->update(['id_number' => '631234567N63']);

    $admin = User::factory()->create(['tenant_id' => $student->tenant_id]);
    $admin->givePermissionTo('update:students');

    $this->actingAs($admin)
        ->patchJson(route('students.id-number.update', $student), [
            'id_number' => '63-1234567N63',
        ])
        ->assertOk();

    expect($student->fresh()->id_number)->toBe('63-1234567N63');
});

it('allows profile owner with personal details permission to fix their id number', function (): void {
    $program = createVerifiedStudentApplication('ID-FIX-OWN-'.strtoupper(Str::random(4)));
    $student = $program->student;
    $student->update(['id_number' => '631234567N63']);

    $owner = $student->user;
    $owner->givePermissionTo('manageOwnStudentPersonalDetails:students');

    $this->actingAs($owner)
        ->patchJson(route('students.id-number.update', $student), [
            'id_number' => '63-1234567N63',
        ])
        ->assertOk();

    expect($student->fresh()->id_number)->toBe('63-1234567N63');
});

it('forbids users without edit rights from fixing id numbers', function (): void {
    $program = createVerifiedStudentApplication('ID-FIX-DENY-'.strtoupper(Str::random(4)));
    $student = $program->student;
    $student->update(['id_number' => '631234567N63']);

    $viewer = User::factory()->create(['tenant_id' => $student->tenant_id]);
    $viewer->givePermissionTo(['view:students', 'viewAny:students']);

    $this->actingAs($viewer)
        ->patchJson(route('students.id-number.update', $student), [
            'id_number' => '63-1234567N63',
        ])
        ->assertForbidden();
});

it('rejects invalid id format on student id-number endpoint', function (): void {
    $program = createVerifiedStudentApplication('ID-FIX-INV-'.strtoupper(Str::random(4)));
    $student = $program->student;
    $student->update(['id_number' => 'invalid-id']);

    $admin = User::factory()->create(['tenant_id' => $student->tenant_id]);
    $admin->givePermissionTo('update:students');

    $this->actingAs($admin)
        ->patchJson(route('students.id-number.update', $student), [
            'id_number' => 'still-invalid',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['id_number']);
});

it('returns conflict when corrected student id is already taken', function (): void {
    $program = createVerifiedStudentApplication('ID-FIX-CON-'.strtoupper(Str::random(4)));
    $student = $program->student;
    $student->update(['id_number' => 'invalid-id']);

    $otherProgram = createVerifiedStudentApplication('ID-FIX-TAKEN-'.strtoupper(Str::random(4)));
    $otherProgram->student->update(['id_number' => '63-1234567N63']);

    $admin = User::factory()->create(['tenant_id' => $student->tenant_id]);
    $admin->givePermissionTo('update:students');

    $this->actingAs($admin)
        ->patchJson(route('students.id-number.update', $student), [
            'id_number' => '63-1234567N63',
        ])
        ->assertStatus(409)
        ->assertJsonPath('errors.id_number.0', __('trans.maintenance_faulty_data_id_conflict'));
});

it('exposes invalid id flags on portal dashboard for zimbabwean national ids', function (): void {
    $program = createVerifiedStudentApplication('ID-DASH-'.strtoupper(Str::random(4)));
    $student = $program->student;

    $zimIdType = IdType::query()->firstOrCreate(
        ['name' => IdTypeEnum::ZIMBABWEAN_ID_NUMBER->value],
        ['description' => IdTypeEnum::ZIMBABWEAN_ID_NUMBER->description(), 'is_default' => true],
    );

    $student->update([
        'id_type_id' => $zimIdType->id,
        'id_number' => '631234567N63',
    ]);

    $owner = $student->user;
    $owner->givePermissionTo([
        'viewOwnDashboard:students',
        'manageOwnStudentPersonalDetails:students',
    ]);

    $this->actingAs($owner)
        ->get(route('portal.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/student/Index')
            ->where('student.attributes.idNumberValid', false)
            ->where('student.attributes.suggestedIdNumber', '63-1234567N63')
            ->where('student.attributes.idNumberRectificationStatus', 'ready_to_fix'));
});
