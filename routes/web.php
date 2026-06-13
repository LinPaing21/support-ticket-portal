<?php

use App\Http\Controllers\OrganisationController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::resource('users', UserController::class);
    Route::resource('organisations', OrganisationController::class);
});

require __DIR__.'/settings.php';
