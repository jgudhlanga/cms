<?php

use App\Http\Controllers\Api\V1\Students\StudentClearanceController;
use App\Http\Controllers\Api\V1\Students\StudentController;
use App\Http\Controllers\Api\V1\Students\StudentExamResultController;

Route::prefix('v1/students')->middleware('auth:sanctum')->group(function () {
    Route::get('personal/{student}', [StudentController::class, 'personal'])->name('v1.students.personal');
    Route::get('contacts/{student}', [StudentController::class, 'contacts'])->name('v1.students.contacts');
    Route::get('addresses/{student}', [StudentController::class, 'addresses'])->name('v1.students.addresses');
    Route::get('sponsors/{student}', [StudentController::class, 'sponsors'])->name('v1.students.sponsors');
    Route::get('next-of-kins/{student}', [StudentController::class, 'nextOfKin'])->name('v1.students.next-of-kins');
    Route::get('programs/{student}', [StudentController::class, 'programs'])->name('v1.students.programs');
    Route::get('{student}/student-enrolements', [StudentController::class, 'studentEnrolements'])->name('v1.students.student-enrolements');
    Route::get('{student}/clearance', [StudentClearanceController::class, 'show'])->name('v1.students.clearance.show');
    Route::put('{student}/clearance', [StudentClearanceController::class, 'update'])->name('v1.students.clearance.update');
    Route::get('{student}/exam-results', [StudentExamResultController::class, 'index'])->name('v1.students.exam-results.index');
    Route::post('{student}/exam-results/lookup', [StudentExamResultController::class, 'lookup'])->name('v1.students.exam-results.lookup');
    Route::get('{student}/exam-results/{studentExamResult}', [StudentExamResultController::class, 'show'])->name('v1.students.exam-results.show');
});
Route::middleware('auth:sanctum')->group(function () {
    Route::get('students/stats', [StudentController::class, 'stats'])->name('v1.students.stats');
    Route::apiResource('students', StudentController::class)->names('v1.students');
});
