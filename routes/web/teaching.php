<?php

use App\Http\Controllers\AcademicCalendars\CourseWorkCaptureExtensionController;
use App\Http\Controllers\Teaching\ClassesController;
use App\Http\Controllers\Teaching\CourseWorkProgressController;
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
        )->middleware('throttle:30,1')->name('classes.import.template');
        Route::post(
            'classes/{academic_calendar_class}/modules/{course_syllabus_module}/import/preview',
            [ClassesController::class, 'importPreview'],
        )->middleware('throttle:10,1')->name('classes.import.preview');
        Route::post(
            'classes/{academic_calendar_class}/modules/{course_syllabus_module}/import/process',
            [ClassesController::class, 'importProcess'],
        )->middleware('throttle:10,1')->name('classes.import.process');
        Route::post(
            'classes/{academic_calendar_class}/modules/{course_syllabus_module}/extensions',
            [CourseWorkCaptureExtensionController::class, 'store'],
        )->middleware('throttle:10,1')->name('classes.extensions.store');

        Route::get('course-work-progress', [CourseWorkProgressController::class, 'index'])->name('course-work-progress.index');
        Route::get('course-work-progress/{class_config}', [CourseWorkProgressController::class, 'show'])->name('course-work-progress.show');
        Route::post('course-work-progress/{class_config}/reports', [CourseWorkProgressController::class, 'submit'])
            ->middleware('throttle:10,1')
            ->name('course-work-progress.reports.store');
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
