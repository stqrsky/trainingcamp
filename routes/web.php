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
Route::post('/login',  function () {
    return redirect()->route('home');
})->name('login.post');
Route::get('/signup',  function () {
    return view('backend.signup', ['title' => 'Sign Up']);
})->name('signup');
Route::post('/signup', function () {
    return redirect()->route('user.setting');
})->name('signup.post');


Route::group(['prefix' => 'user'], function () {
    Route::get('setting', function () {
        return view('frontend.users.createprofile');
    })->name('user.setting');
    Route::post('setting', function () {
        return redirect()->route('home');
    })->name('user.setting.post');
    Route::get('athletes',  function () {
        return view('frontend.athletes.athletes');
    })->name('user.athletes');
    Route::get('athletes/create', function () {
        return view('frontend.athletes.createathlete');
    })->name('user.athletes.create');
    Route::post('athletes/create', function () {
        return redirect()->route('user.athletes');
    })->name('user.athletes.create.post');
    Route::get('athletes/{id}', function ($id) {
        return view('frontend.athletes.editathlete', compact('id'));
    })->name('user.detail');
    Route::put('athletes/{id}', function () {
        return view('frontend.athletes.createathlete');
    })->name('user.detail.update');
    Route::get('profile', function () {
        return view('frontend.users.profile');
    })->name('user.profile');
    Route::get('profile/setting', function () {
        return view('frontend.users.profilesetting');
    })->name('user.profile.setting');
});

Route::group(['prefix' => 'notification'], function () {
    Route::get('create', function () {
        return view('frontend.notifications.create');
    })->name('notification.create');
    Route::post('create', function () {
        return redirect()->route('home');
    })->name('notification.create.post');
    Route::get('edit/{id}', function () {
        return view('frontend.notifications.edit');
    })->name('notification.edit');
    Route::put('edit/{id}', function () {
        return redirect()->route('home');
    })->name('notification.edit.put');
    Route::delete('delete/{id}', function () {
        return redirect()->route('home');
    })->name('notification.delete');
});

Route::group(['prefix' => 'schedules'], function () {
    Route::get('', function () {
        return view('frontend.schedules.schedules');
    })->name('schedules');
});
