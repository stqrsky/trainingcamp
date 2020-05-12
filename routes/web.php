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

Route::get('/', 'HomeController@index')->name('home');
Route::get('/login', 'UserController@login')->name('login');
Route::post('/login', 'UserController@loginUser')->name('login.post');
Route::get('/signup', 'UserController@register')->name('signup');
Route::post('/signup', 'UserController@registerUser')->name('signup.post');

Route::group(['prefix' => 'user'], function () {
    Route::get('setting', 'UserController@createProfile')->name('user.setting');
    Route::post('setting', 'UserController@createProfileUser')->name('user.setting.post');
    Route::get('athletes', 'UserController@getAthlete')->name('user.athletes');
    Route::get('athletes/create', 'UserController@createUser')->name('user.athletes.create');
    Route::post('athletes/create', 'UserController@createUserData')->name('user.athletes.create.post');
    Route::get('athletes/{id}', 'UserController@detailUser')->name('user.detail');
    Route::put('athletes/{id}', 'UserController@updateUser')->name('user.detail.update');
    Route::get('profile', 'UserController@profile')->name('user.profile');
    Route::get('profile/setting', 'UserController@profileSetting')->name('user.profile.setting');
    Route::get('profile/setting/account', 'UserController@accountSetting')->name('user.account.setting');
    Route::put('profile/setting/account', 'UserController@updateProfileAccount')->name('user.account.setting.put');
});

Route::group(['prefix' => 'notification'], function () {
    Route::get('create', 'NotificationController@create')->name('notification.create');
    Route::post('create', 'NotificationController@createNotification')->name('notification.create.post');
    Route::get('edit/{id}', 'NotificationController@edit')->name('notification.edit');
    Route::put('edit/{id}', 'NotificationController@editNotification')->name('notification.edit.put');
    Route::delete('delete/{id}', 'NotificationController@deleteNotification')->name('notification.delete');
});

Route::group(['prefix' => 'schedules'], function () {
    Route::get('', 'ScheduleController@index')->name('schedules');
});
