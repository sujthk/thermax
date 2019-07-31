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
Route::get('/login', function () {
    return view('login');
})->name('login');


Route::post('/login', 'UserController@loginUser');
Route::post('/user-send-otp', 'UserController@sendUserOtp');


Route::group(['middleware' => ['auth','revalidate']], function(){

	Route::get('/dashboard', function () {
	    return view('dashboard');
	});
	Route::get('/logout', 'UserController@logoutUser');
	

	Route::get('/users', 'UserController@getUsers');
	Route::get('/users/add', 'UserController@addUser');
	Route::post('/users/add', 'UserController@postUser');
	Route::get('/users/status/{user_id}/{status}', 'UserController@changeUserStatus');
	Route::get('/users/edit/{user_id}', 'UserController@editUser');
	Route::post('/users/edit/{user_id}', 'UserController@updateUser');

	Route::get('/metallurgies', 'MetallurgyController@getMetallurgies');
	Route::get('/metallurgies/add', 'MetallurgyController@addMetallurgy');
	Route::post('/metallurgies/add', 'MetallurgyController@postMetallurgy');
	Route::get('/metallurgies/edit/{metallurgy_id}', 'MetallurgyController@editMetallurgy');
	Route::post('/metallurgies/edit/{metallurgy_id}', 'MetallurgyController@updateMetallurgy');

	Route::get('/calculators/double-effect-s2', 'DoubleSteamController@getDoubleEffectS2');
	Route::post('/calculators/double-effect-s2', 'DoubleSteamController@calculateDoubleEffectS2');
	Route::post('/calculators/double-effect-s2/ajax-calculate', 'DoubleSteamController@postAjaxDoubleEffectS2');


	Route::get('/default/calculators', 'DefaultCalculatorController@getCalculators');
	Route::get('/default/calculators/edit/{chiller_default_id}', 'DefaultCalculatorController@editCalculator');
	Route::post('/default/calculators/edit/{chiller_default_id}', 'DefaultCalculatorController@updateCalculator');

	Route::get('/tube-metallurgy/calculators', 'DefaultCalculatorController@getMetallurgyCalculators');
	Route::get('/tube-metallurgy/calculators/add', 'DefaultCalculatorController@addMetallurgyCalculator');
	Route::post('/tube-metallurgy/calculators/add', 'DefaultCalculatorController@postMetallurgyCalculator');
	Route::get('/tube-metallurgy/calculators/edit/{chiller_metallurgy_id}/{tube_type}', 'DefaultCalculatorController@editMetallurgyCalculator');
	Route::post('/tube-metallurgy/calculators/edit/{chiller_metallurgy_id}/{tube_type}', 'DefaultCalculatorController@updateMetallurgyCalculator');

});