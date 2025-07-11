<?php

use App\Http\Controllers\Api\V1\Portal\PortalController;

Route::prefix('v1/portal')->middleware('auth:sanctum')->group(function () {
    Route::get('personal', [PortalController::class, 'personal'])->name('v1.portal.personal');
    Route::get('contacts', [PortalController::class, 'contacts'])->name('v1.portal.contacts');
    Route::get('addresses', [PortalController::class, 'addresses'])->name('v1.portal.addresses');
    Route::get('sponsors', [PortalController::class, 'sponsors'])->name('v1.portal.sponsors');
});
