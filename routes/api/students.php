<?php

use App\Http\Controllers\Api\V1\Students\StudentController;

Route::prefix('v1/students')->middleware('auth:sanctum')->group(function () {
    Route::get('personal/{student}', [StudentController::class, 'personal'])->name('v1.students.personal');
    Route::get('contacts/{student}', [StudentController::class, 'contacts'])->name('v1.students.contacts');
    Route::get('addresses/{student}', [StudentController::class, 'addresses'])->name('v1.students.addresses');
    Route::get('sponsors/{student}', [StudentController::class, 'sponsors'])->name('v1.students.sponsors');
    Route::get('next-of-kins/{student}', [StudentController::class, 'nextOfKin'])->name('v1.students.next-of-kins');
    Route::get('programs/{student}', [StudentController::class, 'programs'])->name('v1.students.programs');
});
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('students', StudentController::class)->names('v1.students');
});