<?php

use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::view('/', 'welcome')->name('welcome');

/*
|--------------------------------------------------------------------------
| Guest Routes (Only accessible if NOT logged in)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::view('login', 'login')->name('login');
    Route::post('login', [LoginController::class, 'authenticate'])->name('login.attempt');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes (Only accessible if logged in)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');
});


