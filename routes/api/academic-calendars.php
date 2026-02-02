<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1/institution')->middleware('auth:sanctum')->group(function () {

});
