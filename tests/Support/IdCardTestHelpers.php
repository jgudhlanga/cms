<?php

use App\Enums\Shared\IdTypeEnum;
use App\Enums\Shared\ModuleEnum;
use App\Models\Rbac\Module;
use App\Models\Shared\Gender;
use App\Models\Shared\IdType;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Students\Student;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use App\Services\Rbac\RbacModuleStateService;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Models\Permission;

function createIdCardStudent(array $overrides = []): array
{
    $tenant = Tenant::query()->first() ?? Tenant::factory()->create();
    $suffix = uniqid();

    $idType = IdType::query()->firstOrCreate(
        ['name' => IdTypeEnum::FOREIGN_PASSPORT_NUMBER->label()],
    );

    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $student = Student::query()->create(array_merge([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'title_id' => Title::query()->create(['name' => 'Mr IDC '.$suffix])->id,
        'gender_id' => Gender::query()->create(['title' => 'Male IDC '.$suffix])->id,
        'marital_status_id' => MaritalStatus::query()->create(['title' => 'Single IDC '.$suffix])->id,
        'id_type_id' => $idType->id,
        'passport_number' => 'P'.strtoupper(substr($suffix, -8)),
        'student_number' => 'H'.strtoupper(substr($suffix, -6)),
        'date_of_birth' => '2001-01-01',
    ], $overrides));

    return compact('tenant', 'student', 'user');
}

function attachIdCardPhoto(Student $student, string $filename = 'id-photo.jpg'): Media
{
    return $student
        ->addMedia(UploadedFile::fake()->image($filename, 400, 500))
        ->toMediaCollection(Student::ID_PHOTO_COLLECTION);
}

function createIdCardStaff(int $tenantId, array $permissions = ['viewAny:student-id-card-requests']): User
{
    $user = User::factory()->create(['tenant_id' => $tenantId]);

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
    }

    return $user;
}

function grantIdCardPortalPermission(User $user): User
{
    Permission::findOrCreate('manageOwnStudentPersonalDetails:students', 'web');
    $user->givePermissionTo('manageOwnStudentPersonalDetails:students');

    return $user;
}

function disableStudentIdsModule(): void
{
    Module::query()
        ->where('slug', ModuleEnum::STUDENT_IDS->slug())
        ->firstOrFail()
        ->update(['status' => false]);

    app(RbacModuleStateService::class)->clearCache();
}
