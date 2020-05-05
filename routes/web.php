<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('frontend.home');
})->name('home');
Route::get('/login', function () {
    return view('backend.login', ['title' => 'Sign In']);
})->name('login');
Route::get('/signup', function () {
    return view('backend.signup', ['title' => 'Sign Up']);
})->name('signup');




// Route::resource('', 'Controller');
