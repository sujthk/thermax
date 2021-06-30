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

// Delete Routes strictly for developer access

// Route::get('/user/delete/{user_id}', 'DeleteController@deleteUser');
Route::get('/user/ldap', 'UserController@ldapUsers');
// Route::get('/viscocity/test', 'VamBaseController@EG_VISCOSITY');
Route::get('/word/template', 'ReportController@templateCheck');



Route::group(['middleware' => 'guest'], function(){

	Route::get('/', function () {
	    return view('login');
	});
	Route::get('/login', function () {
	    return view('login');
	})->name('login');

	Route::get('/forgot-password', function () 
	{
		return view('forgot_password');
	});
	Route::post('/forgot-password', 'UserController@forgotPassword');
	Route::post('/password-reset', 'UserController@resetAdminPassword');
	Route::get('/password_verification/{token}', 'UserController@verifyCustomerToken');

	Route::post('/login', 'UserController@loginUser');
	Route::post('/user-send-otp', 'UserController@sendUserOtp');

});

// Route::get('/data', 'DoubleSteamController@getChillerData');

Route::group(['middleware' => ['auth']], function(){
    Route::get('/calculators/double-effect-s2/download-report/{user_report_id}/{type}', 'DoubleSteamController@downloadReport')->name('download.report');
    Route::get('/calculators/double-effect-h2/download-report/{user_report_id}/{type}', 'DoubleH2SteamController@downloadReport')->name('download.report_h2');
    Route::get('/calculators/l5-series/download-report/{user_report_id}/{type}', 'L5SeriesController@downloadReport')->name('download.l5report');
    Route::get('/calculators/l1-series/download-report/{user_report_id}/{type}', 'L1SeriesController@downloadReport')->name('download.l1report');
    Route::get('/calculators/s1-series/download-report/{user_report_id}/{type}', 'S1SeriesController@downloadReport')->name('download.s1report');
    Route::get('/calculators/g2-series/download-report/{user_report_id}/{type}', 'DoubleG2SteamController@downloadReport')->name('download.g2report');
    Route::get('/calculators/e2-series/download-report/{user_report_id}/{type}', 'E2SeriesController@downloadReport')->name('download.e2report');
    Route::get('/calculators/h1-series/download-report/{user_report_id}/{type}', 'H1SeriesController@downloadReport')->name('download.h1report');

});	

Route::group(['middleware' => ['auth','revalidate']], function(){

	
	Route::get('/dashboard', 'UserController@getDashboard')->name('dashboard');
	Route::get('/logout', 'UserController@logoutUser')->name('logout');
	Route::get('/profile', 'UserController@getProfile')->name('profile');
	Route::post('/user_profile/edit/{user_id}', 'UserController@updateUserProfile');
	Route::post('/password_change', 'UserController@postPasswordChange');
	
	Route::group(['middleware' => ['adminmiddleware']], function(){
		Route::get('/users', 'UserController@getUsers')->name('users');
		Route::get('/users/add', 'UserController@addUser');
		Route::post('/users/add', 'UserController@postUser');
		Route::get('/users/status/{user_id}/{status}', 'UserController@changeUserStatus');
		Route::get('/users/edit/{user_id}', 'UserController@editUser');
		Route::post('/users/edit/{user_id}', 'UserController@updateUser');
		Route::post('/users/group_calculator/list', 'UserController@getGroupCalcluation');
		Route::get('/user-profile/view/{id}', 'UserController@getuserlist');
		Route::get('/user-profile/download/{id}', 'UserController@getUserReport');
		

		/*Regions*/
		Route::get('/region', 'RegionController@getRegion')->name('region');
		Route::post('/region/add', 'RegionController@postRegion');
		Route::post('/region/edit/{id}', 'RegionController@editRegion');
		/*End Region*/

		/*Time Line*/
		Route::get('/time-line', 'TimeLineController@getTimeLine')->name('time-line');
		Route::post('/time_line/add', 'TimeLineController@postTimeLine');
		Route::post('/time_line/edit/{id}', 'TimeLineController@editTimeLine');
		Route::get('/time_line/destroy/{id}', 'TimeLineController@destroy');
		/*End Time Line*/

		/*Group Calculations*/
		Route::get('/group-calcluation', 'GroupCalculatorController@getGroupCalcluation')->name('group-calcluation');
		Route::get('/group-calcluation/add', 'GroupCalculatorController@addGroupCalcluation');
		Route::post('/group-calcluation/install', 'GroupCalculatorController@postGroupCalcluation');
		Route::get('/group-calcluation/edit/{id}', 'GroupCalculatorController@editGroupCalcluation');

		Route::post('/group-calcluation/update/{id}', 'GroupCalculatorController@GroupCalcluationUpdate');
		/*End Group Calculations*/


		Route::get('/metallurgies', 'MetallurgyController@getMetallurgies')->name('metallurgies');
		Route::get('/metallurgies/add', 'MetallurgyController@addMetallurgy');
		Route::post('/metallurgies/add', 'MetallurgyController@postMetallurgy');
		Route::get('/metallurgies/edit/{metallurgy_id}', 'MetallurgyController@editMetallurgy');
		Route::post('/metallurgies/edit/{metallurgy_id}', 'MetallurgyController@updateMetallurgy');
		Route::get('/metallurgies/delete/{metallurgy_id}', 'MetallurgyController@deleteMetallurgy');

			Route::get('/default/calculator-list', 'DefaultCalculatorController@getCalculatorList')->name('default/calculator-list');
			Route::post('/default/calculator-list/edit/{calculator_id}', 'DefaultCalculatorController@updateChillerCalculator');


			Route::get('/default/calculators', 'DefaultCalculatorController@getCalculators')->name('default/calculators');
			Route::get('/default/calculators/edit/{chiller_default_id}', 'DefaultCalculatorController@editCalculator');
			Route::post('/default/calculators/edit/{chiller_default_id}', 'DefaultCalculatorController@updateCalculator');

			Route::get('/chiller/calculation-values', 'DefaultCalculatorController@getChillerCalculations')->name('chiller/calculation-values');
			Route::get('/chiller/calculation-values/edit/{chiller_calculation_value_id}', 'DefaultCalculatorController@editCalculatorValue');
			Route::post('/chiller/calculation-values/edit/{chiller_calculation_value_id}', 'DefaultCalculatorController@updateCalculatorValue');
		    Route::get('/chiller/calculation-values/delete/{chiller_calculation_value_id}', 'DefaultCalculatorController@deleteCalculatorValue');
			Route::post('importExport', 'DefaultCalculatorController@importExport');
			Route::post('importExcel', 'DefaultCalculatorController@importExcel');

			Route::get('/calculation-keys', 'DefaultCalculatorController@getCalculationKeys')->name('/calculation-keys');
			Route::post('/calculation-keys/add', 'DefaultCalculatorController@postCalculationKey');
			Route::post('/calculation-key/edit/{id}', 'DefaultCalculatorController@editCalculationKey');

			Route::get('/error-notes', 'DefaultCalculatorController@getErrorNotes')->name('error-notes');
			Route::post('/error-notes/edit/{error_notes_id}', 'DefaultCalculatorController@updateErrorNote');
			Route::post('/error-notes/add', 'DefaultCalculatorController@postErrorNote');
			Route::get('/error-notes/delete/{error_notes_id}', 'DefaultCalculatorController@DeleteErrorNote');
		    Route::post('/error-notes/export-excel', 'DefaultCalculatorController@exportErrorExcel');
		    Route::post('/error-notes/import-excel', 'DefaultCalculatorController@importErrorExcel');

			Route::get('/languages-notes', 'DefaultCalculatorController@getLanguageNotes')->name('language-notes');
			Route::post('/languages-notes/edit/{language_note_id}', 'DefaultCalculatorController@updateLanguageNote');
			Route::post('/languages-notes/add', 'DefaultCalculatorController@postLanguageNote');
		    Route::get('/languages-notes/delete/{language_note_id}', 'DefaultCalculatorController@DeleteLanguageNote');
		    Route::post('/languages-notes/export-excel', 'DefaultCalculatorController@exportLanguageExcel');
		    Route::post('/languages-notes/import-excel', 'DefaultCalculatorController@importLanguageExcel');

		    Route::get('/languages', 'DefaultCalculatorController@getLanguages')->name('languages');
		    Route::post('/languages/edit/{language_id}', 'DefaultCalculatorController@updateLanguage');
		    Route::post('/languages/add', 'DefaultCalculatorController@postLanguage');
		    Route::get('/languages/status/{language_id}/{status}', 'DefaultCalculatorController@changeLanguageStatus');

			Route::get('/tube-metallurgy/calculators', 'DefaultCalculatorController@getMetallurgyCalculators')->name('tube-metallurgy/calculators');
			Route::get('/tube-metallurgy/calculators/add', 'DefaultCalculatorController@addMetallurgyCalculator');
			Route::post('/tube-metallurgy/calculators/add', 'DefaultCalculatorController@postMetallurgyCalculator');
			Route::get('/tube-metallurgy/calculators/edit/{chiller_metallurgy_id}/{tube_type}', 'DefaultCalculatorController@editMetallurgyCalculator');
			Route::post('/tube-metallurgy/calculators/edit/{chiller_metallurgy_id}/{tube_type}', 'DefaultCalculatorController@updateMetallurgyCalculator');
			Route::get('/tube-metallurgy/calculators/delete/{chiller_metallurgy_id}', 'DefaultCalculatorController@deleteMetallurgyCalculator');

			Route::get('/auto-testing', 'CalculatorTestingController@getAutoTesting')->name('auto-testing');
			Route::post('auto-testing/export', 'CalculatorTestingController@exportCalculatorForamt');
		    Route::post('auto-testing/import', 'CalculatorTestingController@importCalculatorForamt');
		    Route::post('auto-testing/calculator', 'CalculatorTestingController@testingCalculator');
			Route::post('auto-testing/download', 'CalculatorTestingController@downloadTestedCalculator');
	});	
	// Route::get('/users', 'UserController@getUsers')->name('users');
	

	/*Double Effect S2 Serires*/
	Route::get('/calculators/double-effect-s2', 'DoubleSteamController@getDoubleEffectS2')->name('calculators/double-effect-s2');
	Route::post('/calculators/double-effect-s2/ajax-calculate', 'DoubleSteamController@postAjaxDoubleEffectS2');
	// Route::post('/calculators/double-effect-s2/ajax-calculate-region', 'DoubleSteamController@postAjaxDoubleEffectS2Region');
	Route::post('/calculators/double-effect-s2/submit-calculate', 'DoubleSteamController@postDoubleEffectS2');
	Route::post('/calculators/double-effect-s2/reset-calculate', 'DoubleSteamController@postResetDoubleEffectS2');
	//report
	// Route::post('/calculators/double-effect-s2/show-report', 'DoubleSteamController@postShowReport');
	Route::post('/calculators/double-effect-s2/save-report', 'DoubleSteamController@postSaveReport');
    
	/*End Double Effect S2 Serires*/

	/*Double Effect H2 Serires*/
	Route::get('/calculators/double-effect-h2', 'DoubleH2SteamController@getDoubleEffectH2')->name('calculators/double-effect-h2');
	Route::post('/calculators/double-effect-h2/ajax-calculate', 'DoubleH2SteamController@postAjaxDoubleEffectH2');
	Route::post('/calculators/double-effect-h2/submit-calculate', 'DoubleH2SteamController@postDoubleEffectH2');
	Route::post('/calculators/double-effect-h2/reset-calculate', 'DoubleH2SteamController@postResetDoubleEffectH2');
	Route::post('/calculators/double-effect-h2/save-report', 'DoubleH2SteamController@postSaveReport');
    
	/*End Double Effect H2 Serires*/

	


	Route::get('/unit-sets', 'UnitsetController@getUnitsets')->name('unit-sets');
	Route::get('/unit-sets/add', 'UnitsetController@addUnitset');
	Route::post('/unit-sets/add', 'UnitsetController@postUnitset');
	Route::get('/unit-sets/edit/{unit_set_id}', 'UnitsetController@editUnitset');
	Route::post('/unit-sets/edit/{unit_set_id}', 'UnitsetController@updateUnitset');
	Route::get('/unit-sets/status/{unit_set_id}/{status}', 'UnitsetController@changeUnitsetStatus');

	


    Route::get('/calculators/l5-series', 'L5SeriesController@getL5Series')->name('calculators/l5-series');
    Route::post('/calculators/l5-series/ajax-calculate', 'L5SeriesController@postAjaxL5');
    Route::post('/calculators/l5-series/submit-calculate', 'L5SeriesController@postL5');
    Route::post('/calculators/l5-series/save-report', 'L5SeriesController@postSaveReport');
    Route::post('/calculators/l5-series/reset-calculate', 'L5SeriesController@postResetL5');


    Route::get('/calculators/l1-series', 'L1SeriesController@getL1Series')->name('calculators/l1-series');
    Route::post('/calculators/l1-series/ajax-calculate', 'L1SeriesController@postAjaxL1');
    Route::post('/calculators/l1-series/reset-calculate', 'L1SeriesController@postResetL1');
    Route::post('/calculators/l1-series/submit-calculate', 'L1SeriesController@postL1');
    Route::post('/calculators/l1-series/save-report', 'L1SeriesController@postSaveReport');


    Route::get('/calculators/s1-series', 'S1SeriesController@getS1Series')->name('calculators/s1-series');
    Route::post('/calculators/s1-series/ajax-calculate', 'S1SeriesController@postAjaxS1');
    Route::post('/calculators/s1-series/reset-calculate', 'S1SeriesController@postResetS1');
    Route::post('/calculators/s1-series/submit-calculate', 'S1SeriesController@postS1');
    Route::post('/calculators/s1-series/save-report', 'S1SeriesController@postSaveReport');

    /*Double Effect G2 Serires*/
    Route::get('/calculators/double-effect-g2', 'DoubleG2SteamController@getDoubleEffectG2')->name('calculators/double-effect-g2');
    Route::post('/calculators/double-effect-g2/ajax-calculate', 'DoubleG2SteamController@postAjaxDoubleEffectG2');
    Route::post('/calculators/double-effect-g2/submit-calculate', 'DoubleG2SteamController@postDoubleEffectG2');
    Route::post('/calculators/double-effect-g2/reset-calculate', 'DoubleG2SteamController@postResetDoubleEffectG2');
    Route::post('/calculators/double-effect-g2/save-report', 'DoubleG2SteamController@postSaveReport');
    /*End Double Effect G2 Serires*/

    Route::get('/calculators/e2-series', 'E2SeriesController@getE2Series')->name('calculators/e2-series');
    Route::post('/calculators/e2-series/ajax-calculate', 'E2SeriesController@postAjaxE2');
    Route::post('/calculators/e2-series/reset-calculate', 'E2SeriesController@postResetE2');
    Route::post('/calculators/e2-series/submit-calculate', 'E2SeriesController@postE2');
    Route::post('/calculators/e2-series/save-report', 'E2SeriesController@postSaveReport');


    Route::get('/calculators/h1-series', 'H1SeriesController@getH1Series')->name('calculators/h1-series');
    Route::post('/calculators/h1-series/ajax-calculate', 'H1SeriesController@postAjaxH1');
    Route::post('/calculators/h1-series/reset-calculate', 'H1SeriesController@postResetH1');
    Route::post('/calculators/h1-series/submit-calculate', 'H1SeriesController@postH1');
    Route::post('/calculators/h1-series/save-report', 'H1SeriesController@postSaveReport');

    Route::get('/calculators/chiller-heater-s2-series', 'ChillerHeaterS2Controller@getCHS2Series')->name('calculators/chiller-heater-s2-series');
    Route::post('/calculators/chiller-heater-s2-series/ajax-calculate', 'ChillerHeaterS2Controller@postAjaxCHS2');
    Route::post('/calculators/chiller-heater-s2-series/reset-calculate', 'ChillerHeaterS2Controller@postResetCHS2');
    Route::post('/calculators/chiller-heater-s2-series/submit-calculate', 'ChillerHeaterS2Controller@postCHS2');

    Route::get('/calculators/chiller-heater-g2-series', 'ChillerHeaterG2Controller@getCHG2Series')->name('calculators/chiller-heater-g2-series');
    Route::post('/calculators/chiller-heater-g2-series/ajax-calculate', 'ChillerHeaterG2Controller@postAjaxCHG2');
    Route::post('/calculators/chiller-heater-g2-series/reset-calculate', 'ChillerHeaterG2Controller@postResetCHG2');

});