<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\OrganisationController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::resource('users', UserController::class);
    Route::resource('organisations', OrganisationController::class);
    Route::resource('tickets', TicketController::class);
    Route::resource('tickets.comments', CommentController::class)
        ->only(['store', 'update', 'destroy'])
        ->shallow();
});

require __DIR__.'/settings.php';
