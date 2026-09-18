<?php

use App\Http\Controllers\Console\ConsoleCommandController;
use Illuminate\Support\Facades\Route;

Route::prefix('console')->middleware('auth')->group(function () {
    Route::get('/', [ConsoleCommandController::class, 'index'])
        ->middleware('can:viewConsole')
        ->name('console.index');

    Route::get('runs/{run}/output', [ConsoleCommandController::class, 'output'])
        ->middleware('can:viewConsole')
        ->name('console.runs.output');

    Route::post('dispatch', [ConsoleCommandController::class, 'dispatchCommand'])
        ->middleware(['can:runConsoleCommands', 'throttle:console-dispatch'])
        ->name('console.dispatch');
});
