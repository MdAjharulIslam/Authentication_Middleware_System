<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UserController;
use App\Http\Middleware\ValidUser;




Route::get('/', function () {
    return view('register');
})->name('register.form')->middleware(ValidUser::class);

Route::post('/register', [UserController::class, 'register'])->name('register');

Route::get('loginPage', function () {
    return view('login');
})->name('loginPage');

Route::post('login', [UserController::class, 'login'])->name('login');


Route::get('dashboardPage', function () {
    
    return view('dashboard');
})->name('dashboard');;

Route::post('logout', [UserController::class, 'logout'])->name('logout');