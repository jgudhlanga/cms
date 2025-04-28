<?php

use App\Http\Controllers\Institution\InstitutionController;
use App\Http\Controllers\Institution\InstitutionDepartmentController;
use Illuminate\Support\Facades\Route;


Route::prefix('institution')->middleware('auth')->group(function () {
    Route::get('/', InstitutionController::class)->name('institution.index');
    # ==================================== DEPARTMENTS ======================================================
    Route::put('departments/{department}/restore', [DInstitutionepartmentController::class, 'restore'])->name('departments.restore');
    Route::delete('departments/{department}/force-delete', [InstitutionDepartmentController::class, 'forceDelete'])->name('departments.force-delete');
    Route::resource('departments', InstitutionDepartmentController::class)->names('departments');

});

