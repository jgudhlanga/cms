<?php

use App\Http\Controllers\Teaching\ClassesController;
use App\Http\Controllers\Teaching\ModulesController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'redirect.student'])
    ->prefix('teaching')
    ->name('teaching.')
    ->group(function () {
        Route::get('classes', [ClassesController::class, 'index'])->name('classes.index');
        Route::get('classes/{academic_calendar_class}', [ClassesController::class, 'show'])->name('classes.show');
        Route::get('classes/{academic_calendar_class}/class-list/export', [ClassesController::class, 'exportClassList'])
            ->name('classes.class-list.export');
        Route::get(
            'classes/{academic_calendar_class}/modules/{course_syllabus_module}/marksheet',
            [ClassesController::class, 'marksheet'],
        )->name('classes.marksheet');
        Route::get(
            'classes/{academic_calendar_class}/modules/{course_syllabus_module}/marksheet/export',
            [ClassesController::class, 'exportMarksheet'],
        )->name('classes.marksheet.export');
        Route::get(
            'classes/{academic_calendar_class}/modules/{course_syllabus_module}/import',
            [ClassesController::class, 'import'],
        )->name('classes.import');
        Route::get(
            'classes/{academic_calendar_class}/modules/{course_syllabus_module}/import/template',
            [ClassesController::class, 'importTemplate'],
        )->name('classes.import.template');
        Route::post(
            'classes/{academic_calendar_class}/modules/{course_syllabus_module}/import/preview',
            [ClassesController::class, 'importPreview'],
        )->name('classes.import.preview');
        Route::post(
            'classes/{academic_calendar_class}/modules/{course_syllabus_module}/import/process',
            [ClassesController::class, 'importProcess'],
        )->name('classes.import.process');
        Route::get(
            'classes/{academic_calendar_class}/students/{student_enrolment}/course-work',
            [ClassesController::class, 'studentCourseWork'],
        )->name('classes.student-course-work');

        Route::get('modules', [ModulesController::class, 'index'])->name('modules.index');
        Route::get('modules/{course_syllabus_module}', [ModulesController::class, 'show'])->name('modules.show');
    });

Route::middleware(['auth', 'verified', 'redirect.student'])->group(function () {
    Route::redirect('lecturer/dashboard', '/dashboard');
    Route::redirect('lecturer/classes', '/teaching/classes');
    Route::redirect('lecturer/modules', '/teaching/modules');
});
