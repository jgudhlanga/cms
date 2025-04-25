<?php

use App\Http\Controllers\AddressTypes\AddressTypeController;
use App\Http\Controllers\Communications\CommunicationMethodController;
use App\Http\Controllers\Countries\CountryController;
use App\Http\Controllers\Genders\GenderController;
use App\Http\Controllers\Languages\LanguageController;
use App\Http\Controllers\Provinces\ProvinceController;
use App\Http\Controllers\Races\RaceController;
use App\Http\Controllers\Settings\InstitutionController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Statuses\StatusController;
use App\Http\Controllers\Titles\TitleController;
use Illuminate\Support\Facades\Route;

Route::prefix('settings')->middleware('auth')->group(function () {
	Route::get('/', SettingsController::class)->name('settings.index');
	# ==================================== COMMUNICATIONS ======================================================
	Route::put('communication-methods/{communication_method}/restore', [CommunicationMethodController::class, 'restore'])->name('communication-methods.restore');
	Route::delete('communication-methods/{communication_method}/force-delete', [CommunicationMethodController::class, 'forceDelete'])->name('communication-methods.force-delete');
	Route::resource('communication-methods', CommunicationMethodController::class)->names('communication-methods');
	# ==================================== COUNTRIES ======================================================
	Route::put('countries/{country}/restore', [CountryController::class, 'restore'])->name('countries.restore');
	Route::delete('countries/{country}/force-delete', [CountryController::class, 'forceDelete'])->name('countries.force-delete');
	Route::resource('countries', CountryController::class)->names('countries');
	# ==================================== GENDERS ======================================================
	Route::put('genders/{gender}/restore', [GenderController::class, 'restore'])->name('genders.restore');
	Route::delete('genders/{gender}/force-delete', [GenderController::class, 'forceDelete'])->name('genders.force-delete');
	Route::resource('genders', GenderController::class)->names('genders');
	# ==================================== LANGUAGES ======================================================
	Route::put('languages/{language}/restore', [LanguageController::class, 'restore'])->name('languages.restore');
	Route::delete('languages/{language}/force-delete', [LanguageController::class, 'forceDelete'])->name('languages.force-delete');
	Route::resource('languages', LanguageController::class)->names('languages');
	# ==================================== PROVINCES ======================================================
	Route::put('provinces/{province}/restore', [ProvinceController::class, 'restore'])->name('provinces.restore');
	Route::delete('provinces/{province}/force-delete', [ProvinceController::class, 'forceDelete'])->name('provinces.force-delete');
	Route::resource('provinces', ProvinceController::class)->names('provinces');
	# ==================================== RACES ======================================================
	Route::put('races/{race}/restore', [RaceController::class, 'restore'])->name('races.restore');
	Route::delete('races/{race}/force-delete', [RaceController::class, 'forceDelete'])->name('races.force-delete');
	Route::resource('races', RaceController::class)->names('races');
	# ==================================== STATUSES ======================================================
	Route::put('statuses/{status}/restore', [StatusController::class, 'restore'])->name('statuses.restore');
	Route::delete('statuses/{status}/force-delete', [StatusController::class, 'forceDelete'])->name('statuses.force-delete');
	Route::resource('statuses', StatusController::class)->names('statuses');

	# ==================================== TITLES ======================================================
	Route::put('titles/{title}/restore', [TitleController::class, 'restore'])->name('titles.restore');
	Route::delete('titles/{title}/force-delete', [TitleController::class, 'forceDelete'])->name('titles.force-delete');
	Route::resource('titles', TitleController::class)->names('titles');
	# ==================================== ADDRESS TYPES ======================================================
	Route::put('address-types/{address_type}/restore', [AddressTypeController::class, 'restore'])->name('address-types.restore');
	Route::delete('address-types/{address_type}/force-delete', [AddressTypeController::class, 'forceDelete'])->name('address-types.force-delete');
	Route::resource('address-types', AddressTypeController::class)->names('address-types');
});
Route::prefix('institution-setup')->middleware('auth')->group(function () {
    Route::get('/', InstitutionController::class)->name('institution-setup.index');
});
