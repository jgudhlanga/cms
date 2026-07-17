<?php

use App\Http\Controllers\Examinations\ExaminationController;
use App\Http\Controllers\Examinations\ExaminationImportController;
use Illuminate\Support\Facades\Route;

Route::prefix('examinations')->middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/', [ExaminationController::class, 'index'])->name('examinations.index');
    Route::get('/candidates/{candidateNumber}', [ExaminationController::class, 'show'])
        ->where('candidateNumber', '[^/]+')
        ->name('examinations.candidates.show');

    Route::get('/import', [ExaminationImportController::class, 'create'])->name('examinations.import');
    Route::post('/import', [ExaminationImportController::class, 'store'])->name('examinations.import.store');

    Route::get('/imports', [ExaminationImportController::class, 'index'])->name('examinations.imports.index');
    Route::get('/imports/{examinationImport}', [ExaminationImportController::class, 'show'])->name('examinations.imports.show');
    Route::post('/imports/{examinationImport}/cancel', [ExaminationImportController::class, 'cancel'])->name('examinations.imports.cancel');
});
