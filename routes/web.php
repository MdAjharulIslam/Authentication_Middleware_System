<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UserController;
use App\Http\Middleware\ValidUser;




Route::get('/', function () {
    return view('register');
})->name('register.form');

Route::post('/register', [UserController::class, 'register'])->name('register');

Route::get('loginPage', function () {
    return view('login');
})->name('loginPage');

 Route::post('login', [UserController::class, 'login'])->name('login');


Route::get('dashboardPage', function () {
    
    return view('dashboard');
})->name('dashboard')->middleware('user:admin');




Route::post('logout', [UserController::class, 'logout'])->name('logout');


//group middleware

// Route::middleware(ValidUser::class)->group(function(){
   

// Route::get('dashboardPage', function () {
//     return view('dashboard');
// })->name('dashboard');
// });



//session route

Route::get('see-session', [UserController::class, 'seeSession']);
Route::get('create-session', [UserController::class, 'createSession']);
Route::get('delete-session', [UserController::class, 'deleteSession']);