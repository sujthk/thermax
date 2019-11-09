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
Route::get('/report', function () {
    return view('report');
});

Route::post('/login', 'UserController@loginUser');
Route::post('/user-send-otp', 'UserController@sendUserOtp');

Route::get('/data', 'DoubleSteamController@getChillerData');

Route::get('/calculators/double-effect-s2/download-report/{user_report_id}/{type}', 'DoubleSteamController@downloadReport')->name('download.report')->middleware('auth');

Route::group(['middleware' => ['auth','revalidate']], function(){

	Route::get('/dashboard', function () {
	    return view('dashboard');
	})->name('dashboard');
	Route::get('/logout', 'UserController@logoutUser');
	

	Route::get('/users', 'UserController@getUsers')->name('users');
	Route::get('/users/add', 'UserController@addUser');
	Route::post('/users/add', 'UserController@postUser');
	Route::get('/users/status/{user_id}/{status}', 'UserController@changeUserStatus');
	Route::get('/users/edit/{user_id}', 'UserController@editUser');
	Route::post('/users/edit/{user_id}', 'UserController@updateUser');

	Route::get('/profile', 'UserController@getProfile');
	Route::post('/user_profile/edit/{user_id}', 'UserController@updateUserProfile');
	Route::post('/password_change', 'UserController@postPasswordChange');
	/*Regions*/
	Route::get('/region', 'RegionController@getRegion')->name('region');
	Route::post('/region/add', 'RegionController@postRegion');
	Route::post('/region/edit/{id}', 'RegionController@editRegion');
	/*End Region*/
	Route::get('/metallurgies', 'MetallurgyController@getMetallurgies')->name('metallurgies');
	Route::get('/metallurgies/add', 'MetallurgyController@addMetallurgy');
	Route::post('/metallurgies/add', 'MetallurgyController@postMetallurgy');
	Route::get('/metallurgies/edit/{metallurgy_id}', 'MetallurgyController@editMetallurgy');
	Route::post('/metallurgies/edit/{metallurgy_id}', 'MetallurgyController@updateMetallurgy');
	Route::get('/metallurgies/delete/{metallurgy_id}', 'MetallurgyController@deleteMetallurgy');

	/*Double Effect S2 Serires*/
	Route::get('/calculators/double-effect-s2', 'DoubleSteamController@getDoubleEffectS2')->name('calculators/double-effect-s2');
	Route::post('/calculators/double-effect-s2', 'DoubleSteamController@calculateDoubleEffectS2');
	Route::post('/calculators/double-effect-s2/ajax-calculate', 'DoubleSteamController@postAjaxDoubleEffectS2');
	Route::post('/calculators/double-effect-s2/ajax-calculate-region', 'DoubleSteamController@postAjaxDoubleEffectS2Region');

	Route::post('/calculators/double-effect-s2/submit-calculate', 'DoubleSteamController@postDoubleEffectS2');
	Route::post('/calculators/double-effect-s2/reset-calculate', 'DoubleSteamController@postResetDoubleEffectS2');
	//report
	Route::post('/calculators/double-effect-s2/show-report', 'DoubleSteamController@postShowReport');
	Route::post('/calculators/double-effect-s2/save-report', 'DoubleSteamController@postSaveReport');
	/*End Double Effect S2 Serires*/

	/*Double Effect H2 Serires*/
	Route::get('/calculators/double-effect-h2', 'DoubleH2SteamController@getDoubleEffectH2')->name('calculators/double-effect-h2');
	Route::post('/calculators/double-effect-h2', 'DoubleH2SteamController@calculateDoubleEffectH2');
	Route::post('/calculators/double-effect-h2/ajax-calculate', 'DoubleH2SteamController@postAjaxDoubleEffectH2');
	Route::post('/calculators/double-effect-h2/submit-calculate', 'DoubleH2SteamController@postDoubleEffectH2');
	Route::post('/calculators/double-effect-h2/reset-calculate', 'DoubleH2SteamController@postResetDoubleEffectH2');
	//report
	Route::post('/calculators/double-effect-h2/show-report', 'DoubleH2SteamController@postShowReport');
	Route::post('/calculators/double-effect-h2/save-report', 'DoubleH2SteamController@postSaveReport');
	/*End Double Effect H2 Serires*/


	Route::get('/default/calculators', 'DefaultCalculatorController@getCalculators')->name('default/calculators');
	Route::get('/default/calculators/edit/{chiller_default_id}', 'DefaultCalculatorController@editCalculator');
	Route::post('/default/calculators/edit/{chiller_default_id}', 'DefaultCalculatorController@updateCalculator');

	Route::get('/chiller/calculation-values', 'DefaultCalculatorController@getChillerCalculations')->name('chiller/calculation-values');
	Route::get('/chiller/calculation-values/edit/{chiller_calculation_value_id}', 'DefaultCalculatorController@editCalculatorValue');
	Route::post('/chiller/calculation-values/edit/{chiller_calculation_value_id}', 'DefaultCalculatorController@updateCalculatorValue');
	Route::post('importExport', 'DefaultCalculatorController@importExport');
	Route::post('importExcel', 'DefaultCalculatorController@importExcel');

	Route::get('/calculation-keys', 'DefaultCalculatorController@getCalculationKeys')->name('/calculation-keys');
	Route::post('/calculation-keys/add', 'DefaultCalculatorController@postCalculationKey');
	Route::post('/calculation-key/edit/{id}', 'DefaultCalculatorController@editCalculationKey');
	Route::get('/error-notes', 'DefaultCalculatorController@getErrorNotes')->name('error-notes');
	Route::post('/error-notes/edit/{error_notes_id}', 'DefaultCalculatorController@updateErrorNote');
	Route::post('/error-notes/add', 'DefaultCalculatorController@postErrorNote');
	Route::get('/error-notes/delete/{error_notes_id}', 'DefaultCalculatorController@DeleteErrorNote');


	Route::get('/tube-metallurgy/calculators', 'DefaultCalculatorController@getMetallurgyCalculators')->name('tube-metallurgy/calculators');
	Route::get('/tube-metallurgy/calculators/add', 'DefaultCalculatorController@addMetallurgyCalculator');
	Route::post('/tube-metallurgy/calculators/add', 'DefaultCalculatorController@postMetallurgyCalculator');
	Route::get('/tube-metallurgy/calculators/edit/{chiller_metallurgy_id}/{tube_type}', 'DefaultCalculatorController@editMetallurgyCalculator');
	Route::post('/tube-metallurgy/calculators/edit/{chiller_metallurgy_id}/{tube_type}', 'DefaultCalculatorController@updateMetallurgyCalculator');
	Route::get('/tube-metallurgy/calculators/delete/{chiller_metallurgy_id}', 'DefaultCalculatorController@deleteMetallurgyCalculator');


	Route::get('/unit-sets', 'UnitsetController@getUnitsets')->name('unit-sets');
	Route::get('/unit-sets/add', 'UnitsetController@addUnitset');
	Route::post('/unit-sets/add', 'UnitsetController@postUnitset');
	Route::get('/unit-sets/edit/{unit_set_id}', 'UnitsetController@editUnitset');
	Route::post('/unit-sets/edit/{unit_set_id}', 'UnitsetController@updateUnitset');

});