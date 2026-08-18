<?php

declare(strict_types=1);

use App\Enums\Shared\ModuleEnum;
use App\Models\Rbac\Module;
use App\Models\Students\Student;
use App\Services\Students\StudentIdCardPhotoService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('student can upload an id photo in gallery without creating a request', function () {
    Storage::fake('id-card-photos');
    ['user' => $user, 'student' => $student] = createIdCardStudent();
    grantIdCardPortalPermission($user);

    $this->actingAs($user)
        ->get(route('portal.gallery.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/student/gallery/Index')
            ->where('hasIdentity', true)
        );

    $this->actingAs($user)
        ->post(route('portal.gallery.id-photo'), [
            'photo' => UploadedFile::fake()->image('id-photo.jpg', 400, 500),
        ])
        ->assertRedirect();

    $student->refresh();

    expect($student->idCardRequests()->count())->toBe(0)
        ->and($student->latestIdPhoto())->not->toBeNull();

    $stem = app(StudentIdCardPhotoService::class)->identityStem($student);
    expect($stem)->not->toBeNull()
        ->and(Storage::disk('id-card-photos')->files())->not->toBeEmpty();
});

test('student cannot upload an id photo without an identity number', function () {
    ['user' => $user, 'student' => $student] = createIdCardStudent([
        'passport_number' => null,
        'id_number' => null,
    ]);
    grantIdCardPortalPermission($user);

    $this->actingAs($user)
        ->post(route('portal.gallery.id-photo'), [
            'photo' => UploadedFile::fake()->image('id-photo.jpg', 400, 500),
        ])
        ->assertStatus(422);

    expect($student->fresh()->latestIdPhoto())->toBeNull()
        ->and($student->idCardRequests()->count())->toBe(0);
});

test('student can add and delete extra gallery photos', function () {
    ['user' => $user, 'student' => $student] = createIdCardStudent();
    grantIdCardPortalPermission($user);

    $this->actingAs($user)
        ->post(route('portal.gallery.store'), [
            'photo' => UploadedFile::fake()->image('extra.jpg', 400, 500),
        ])
        ->assertRedirect();

    $media = $student->fresh()->getMedia(Student::GALLERY_COLLECTION)->first();
    expect($media)->not->toBeNull()
        ->and($student->idCardRequests()->count())->toBe(0);

    $this->actingAs($user)
        ->delete(route('portal.gallery.destroy', $media))
        ->assertRedirect();

    expect($student->fresh()->getMedia(Student::GALLERY_COLLECTION))->toHaveCount(0);
});

test('gallery photo is rejected when it is too small', function () {
    ['user' => $user] = createIdCardStudent();
    grantIdCardPortalPermission($user);

    $this->actingAs($user)
        ->post(route('portal.gallery.id-photo'), [
            'photo' => UploadedFile::fake()->image('tiny.jpg', 50, 50),
        ])
        ->assertSessionHasErrors('photo');
});

test('gallery photo is rejected when it is not an image', function () {
    ['user' => $user] = createIdCardStudent();
    grantIdCardPortalPermission($user);

    $this->actingAs($user)
        ->post(route('portal.gallery.id-photo'), [
            'photo' => UploadedFile::fake()->create('notes.pdf', 120, 'application/pdf'),
        ])
        ->assertSessionHasErrors('photo');
});

test('staff can upload an id photo on the student profile with permission', function () {
    Storage::fake('id-card-photos');
    ['tenant' => $tenant, 'student' => $student] = createIdCardStudent();
    $staff = createIdCardStaff((int) $tenant->id, ['uploadIdPhoto:students']);

    $this->actingAs($staff)
        ->post(route('students.id-photo.store', $student), [
            'photo' => UploadedFile::fake()->image('id-photo.jpg', 400, 500),
        ])
        ->assertRedirect();

    expect($student->fresh()->latestIdPhoto())->not->toBeNull()
        ->and($student->idCardRequests()->count())->toBe(0);
});

test('staff cannot upload an id photo without permission', function () {
    ['tenant' => $tenant, 'student' => $student] = createIdCardStudent();
    $staff = createIdCardStaff((int) $tenant->id, ['view:students']);

    $this->actingAs($staff)
        ->post(route('students.id-photo.store', $student), [
            'photo' => UploadedFile::fake()->image('id-photo.jpg', 400, 500),
        ])
        ->assertForbidden();
});

test('uploading a gallery photo does not submit an id card request even when a photo already exists', function () {
    ['user' => $user, 'student' => $student] = createIdCardStudent();
    grantIdCardPortalPermission($user);
    attachIdCardPhoto($student);

    $this->actingAs($user)
        ->post(route('portal.gallery.id-photo'), [
            'photo' => UploadedFile::fake()->image('replacement.jpg', 400, 500),
        ])
        ->assertRedirect();

    expect($student->idCardRequests()->count())->toBe(0);
});

test('gallery module is seeded and enabled', function () {
    $module = Module::query()->where('slug', ModuleEnum::GALLERY->slug())->first();

    expect($module)->not->toBeNull()
        ->and($module->title)->toBe(ModuleEnum::GALLERY->value)
        ->and((bool) $module->status)->toBeTrue();
});

test('student cannot open the gallery when the gallery module is disabled', function () {
    ['user' => $user] = createIdCardStudent();
    grantIdCardPortalPermission($user);
    disableGalleryModule();

    $this->actingAs($user)
        ->get(route('portal.gallery.index'))
        ->assertForbidden();
});

test('student can open the gallery when the student ids module is disabled', function () {
    ['user' => $user] = createIdCardStudent();
    grantIdCardPortalPermission($user);
    disableStudentIdsModule();

    $this->actingAs($user)
        ->get(route('portal.gallery.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('portal/student/gallery/Index'));
});

test('portal documents route redirects to gallery', function () {
    ['user' => $user] = createIdCardStudent();
    grantIdCardPortalPermission($user);

    $this->actingAs($user)
        ->get(route('portal.profile.documents'))
        ->assertRedirect(route('portal.gallery.index'));
});
