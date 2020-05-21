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

Route::get('/', 'HomeController@index')->name('home')->middleware('auth:web');

Route::group(['middleware' => ['guest']], function () {
    Route::get('/login', 'UserController@login')->name('login');
    Route::post('/login', 'UserController@loginUser')->name('login.post');
    Route::get('/signup', 'UserController@register')->name('signup');
    Route::post('/signup', 'UserController@registerUser')->name('signup.post');
});

Route::group(['prefix' => 'user', 'middleware' => ['auth:web']], function () {
    Route::post('logout', 'UserController@logout')->name('user.logout');
    Route::get('setting', 'UserController@createProfile')->name('user.setting');
    Route::post('setting', 'UserController@createProfileUser')->name('user.setting.post');

    Route::get('athletes', 'TeamController@getUserTeam')->name('user.athletes');
    Route::get('athletes/create', 'TeamController@addUser')->name('user.athletes.create');
    Route::post('athletes/create', 'TeamController@createUser')->name('user.athletes.post');
    Route::get('athletes/edit/{id}', 'TeamController@editUser')->name('user.athletes.edit');
    Route::put('athletes/{id}', 'TeamController@updateUser')->name('user.athletes.update');
    Route::delete('athletes/{id}', 'TeamController@deleteUser')->name('user.athletes.delete');
    Route::get('athletes/{id}', 'TeamController@detailUser')->name('user.athletes.detail');

    Route::get('profile', 'UserController@profile')->name('user.profile');
    Route::get('profile/setting', 'UserController@profileSetting')->name('user.profile.setting');
    Route::put('profile/setting', 'UserController@updateProfile')->name('user.profile.setting.put');
    Route::get('profile/setting/account', 'UserController@accountSetting')->name('user.account.setting');
    Route::put('profile/setting/account', 'UserController@updateProfileAccount')->name('user.account.setting.put');
});

Route::resource('notification', 'NotificationController')->middleware('auth:web');

Route::resource('schedules', 'ScheduleController')->middleware('auth:web');
