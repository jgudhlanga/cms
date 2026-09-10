<?php

declare(strict_types=1);

use App\Enums\Rbac\RoleEnum;
use App\Models\Students\Student;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;

/*
 * Shared returning-student fixture.
 *
 * Previously declared in two test files, which made them impossible to load in the same run.
 */

function createReturningStudentUser(array $studentAttributes = []): array
{
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id, 'email_verified_at' => now()]);
    $user->assignRole(RoleEnum::STUDENT->name());
    $user->givePermissionTo('manageOwnStudentPersonalDetails:students');

    $student = Student::query()->create(array_merge([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'title_id' => DB::table('titles')->value('id') ?? DB::table('titles')->insertGetId([
            'name' => 'Mr', 'created_at' => now(), 'updated_at' => now(),
        ]),
        'gender_id' => DB::table('genders')->value('id') ?? DB::table('genders')->insertGetId([
            'title' => 'Male', 'created_at' => now(), 'updated_at' => now(),
        ]),
        'marital_status_id' => DB::table('marital_statuses')->value('id') ?? DB::table('marital_statuses')->insertGetId([
            'title' => 'Single', 'created_at' => now(), 'updated_at' => now(),
        ]),
        'id_type_id' => DB::table('id_types')->value('id') ?? DB::table('id_types')->insertGetId([
            'name' => 'National ID', 'created_at' => now(), 'updated_at' => now(),
        ]),
        'date_of_birth' => '2000-01-01',
        'id_number' => '55-'.uniqid().'C55',
        'student_number' => 'SN-'.uniqid(),
    ], $studentAttributes));

    return [$user, $student];
}
