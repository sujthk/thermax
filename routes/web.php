<?php

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
    return view('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
});

Route::get('/users', function () {
    return view('users');
});

Route::get('/calculators/double-effect-s2', 'DoubleSteamController@getDoubleEffectS2');
Route::post('/calculators/double-effect-s2', 'DoubleSteamController@calculateDoubleEffectS2');
Route::get('/calculators/double-effect-s2/ajax-calculate', 'DoubleSteamController@postAjaxDoubleEffectS2');
