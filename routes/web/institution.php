<?php

use App\Http\Controllers\Institution\CourseController;
use App\Http\Controllers\Institution\DepartmentController;
use App\Http\Controllers\Institution\DivisionController;
use App\Http\Controllers\Institution\GradeController;
use App\Http\Controllers\Institution\LevelController;
use App\Http\Controllers\Institution\ModeOfStudyController;
use App\Http\Controllers\Institution\SubjectController;
use App\Http\Controllers\Settings\InstitutionController;
use Illuminate\Support\Facades\Route;


Route::prefix('institution-setup')->middleware('auth')->group(function () {
    Route::get('/', InstitutionController::class)->name('institution-setup.index');
    # ==================================== COURSES ======================================================
    Route::put('courses/{course}/restore', [CourseController::class, 'restore'])->name('courses.restore');
    Route::delete('courses/{course}/force-delete', [CourseController::class, 'forceDelete'])->name('courses.force-delete');
    Route::resource('courses', CourseController::class)->names('courses');
    # ==================================== DEPARTMENTS ======================================================
    Route::put('departments/{department}/restore', [DepartmentController::class, 'restore'])->name('departments.restore');
    Route::delete('departments/{department}/force-delete', [DepartmentController::class, 'forceDelete'])->name('departments.force-delete');
    Route::resource('departments', DepartmentController::class)->names('departments');
    # ==================================== DIVISIONS ======================================================
    Route::put('divisions/{division}/restore', [DivisionController::class, 'restore'])->name('divisions.restore');
    Route::delete('divisions/{division}/force-delete', [DivisionController::class, 'forceDelete'])->name('divisions.force-delete');
    Route::resource('divisions', DivisionController::class)->names('divisions');
    # ==================================== GRADES ======================================================
    Route::put('grades/{grade}/restore', [GradeController::class, 'restore'])->name('grades.restore');
    Route::delete('grades/{grade}/force-delete', [GradeController::class, 'forceDelete'])->name('grades.force-delete');
    Route::resource('grades', GradeController::class)->names('grades');
    # ==================================== LEVELS ======================================================
    Route::put('levels/{level}/restore', [LevelController::class, 'restore'])->name('levels.restore');
    Route::delete('levels/{level}/force-delete', [LevelController::class, 'forceDelete'])->name('levels.force-delete');
    Route::resource('levels', LevelController::class)->names('levels');
    # ==================================== MODES OF STUDY ======================================================
    Route::put('mode-of-studies/{mode_of_study}/restore', [ModeOfStudyController::class, 'restore'])->name('mode-of-studies.restore');
    Route::delete('mode-of-studies/{mode_of_study}/force-delete', [ModeOfStudyController::class, 'forceDelete'])->name('mode-of-studies.force-delete');
    Route::resource('mode-of-studies', ModeOfStudyController::class)->names('mode-of-studies');
    # ==================================== SUBJECTS ======================================================
    Route::put('subjects/{subject}/restore', [SubjectController::class, 'restore'])->name('subjects.restore');
    Route::delete('subjects/{subject}/force-delete', [SubjectController::class, 'forceDelete'])->name('subjects.force-delete');
    Route::resource('subjects', SubjectController::class)->names('subjects');
});
