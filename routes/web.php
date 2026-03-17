<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\RegisterController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::view('/', 'welcome')->name('welcome');

/*
|--------------------------------------------------------------------------
| Guest Routes (Only for users who ARE NOT logged in)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    // Login
    Route::view('login', 'auth.login')->name('login'); // Adjusted to standard auth folder
    Route::post('login', [LoginController::class, 'authenticate'])->name('login.attempt');

    // Registration (Moved inside Guest middleware)
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes (Only for users who ARE logged in)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    // Crucial Change: Point to the Controller, NOT the view directly
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('logout', [LoginController::class, 'logout'])->name('logout');
});
