 @extends('layouts.app') 

 @section('styles')	
 <!-- Data Table Css -->
 <meta name="csrf-token" content="{{ csrf_token() }}" />
 <style type="text/css">
 	.emsg {
 		color: red;
 	}
 	.hidden {
 		display: none;
 	}

 	.hidden-div {
 		visibility: hidden;
 	}

 	.show-div {
 		visibility: visible;
 	}

 	.border-red {
 		border-color: #bc291a !important;
 	}

 	.max-calculator .form-control,
 	.max-calculator select.form-control {
 		height: 26px;
 		padding: 3px .5rem;
 	}

 	.max-calculator {
 		background: #fff;
 	}

 	.max-calculator .card-header {
 		margin-bottom: 5px;
 		padding: 8px;
 	}

 	.max-calculator .card-header h5 {
 		font-size: 11px;
 		font-weight: 700;
 	}

 	.modal-scrol {
 		overflow-y: scroll;
 		max-height: 180px;
 	}

 	.contact-submit {
 		margin-bottom: 10px;
 		cursor: pointer;
 	}

 	.model-two input::placeholder {
 		color: #fff;
 	}

 	.summary-head h4 {
 		font-size: 16px;
 		font-weight: 600;
 	}

 	.model-two h5.modal-title {
 		color: #fff;
 	}

 	.model-one h5.modal-title {
 		color: #fff;
 	}

 	.model-open {
 		overflow: hidden;
 	}

 	.modal {
 		overflow-x: hidden;
 		overflow-y: scroll;
 	}


 	.ss-steam-label p{
 		text-align: center;
 		margin: 0;
 	}
 	.ss-steam-label .page-wrapper {
 		padding: 20px 15px;
 	}

 	.ss-steam-label label { 		 
 		margin-bottom: 10%;
 		font-weight: 600;
 		color: #656565;
 	}

 	.ss-steam-label .page-wrapper .page-header {
 		margin-bottom: 0px;
 	}
 	.ss-steam-label .card-header{
		padding: 15px 0;
		margin-bottom: 4px;
		margin-top: 2px;
		background: #fff;
 	}

 	body.dark-layout .ss-steam-label .form-control{
 		background: #fff;
 		color: #333;
 		border: none;
 		border-bottom: 1px solid #c5c5c5;
 		border-radius: 1px;
 		 
 		text-align-last: center;
 	}
 	body.dark-layout .ss-steam-label select option{
 		text-align-last: center;
 	}

 	.ss-steam-label .form-control{
 		text-align: center;
 	}

 	.notes-content{
		padding: 15px; 
	}
	
	.notes-content td, .notes-content th{
		padding: 2px;
		font-size: 11px;
	}

	.contact-submit{
		padding: 4px 15px;
		background: #e10010;
		border: none;
		font-size: 11px;
		border-radius: 2px;
		color: #fff;
		transition: all .2s;
	}
	.contact-submit:hover{
		background: #333;
		transition: all .2s;
	}
	.contact-submit:focus{
		outline: 0;
		box-shadow: none;
	}

	.cal-rest{
		padding: 0px 0 0 0;
	}
	.padd-2{

	}
	.padd-mm{
		padding-top: 10px;
	}

	#scroll-right::-webkit-scrollbar-track
	{
		-webkit-box-shadow: inset 0 0 6px rgba(0,0,0,0.3);
		border-radius: 4px;
		background-color: #F5F5F5;
	}


	#scroll-right::-webkit-scrollbar
	{
		width: 8px;
		background-color: #F5F5F5;
	}

	#scroll-right::-webkit-scrollbar-thumb
	{
		border-radius: 4px;
		-webkit-box-shadow: inset 0 0 6px rgba(0,0,0,.3);
		background-color: #555;
	}
	.scrollbar-right
	{	
		height: 495px;
		width: 100%;
		background: #fff;
		overflow-y: auto;
		border: 3px solid #5d5d5d;
	}
	.force-overflow{
		min-height: 450px;
	} 


	.tooltip-inner {
		background-color: #fff; 
		color: #000;
		border: 1px solid #000;
		padding: 0 10px;
		margin: 0;
		font-size: 11px;
	}

	.margin-0{
		margin: 0;
	}
	.padding-0{
		padding: 0;
	}

	.box-color{
		border: 1px solid red !important;
	}
</style>
@endsection
@section('content')
<div class="main-body ss-steam-label">
	<div class="page-wrapper max-calculator">
		<div class="page-header">
			<div class="page-body">
				<form id="double_steam_h2" method="post" enctype="multipart/form-data">
				{{ csrf_field() }}
					<div class="row">
						<div class="col-md-7 padd-2">
							<div class="row">
								<div class="col-md-6">
									<div class="page-header-title">
										<h4>Double Effect Direct Fired series</h4>
									</div>
								</div>

								<div class="form-radio col-6">
									<div class="row " id="region_list" style="display: none;">
										<div class="radio radio-inline">
											<label>
												<input type="radio" name="region_type" class="region_type" id="domestic" value="1">
												<i class="helper"></i> Domestic
											</label>
										</div>
										<div class="radio radio-inline">
											<label>
												<input type="radio" name="region_type" id="USA_type" value="2" class="region_type">
												<i class="helper"></i> USA
											</label>
										</div>
										<div class="radio radio-inline">
											<label>
												<input type="radio" name="region_type" id="Europe_type" value="3" class="region_type">
												<i class="helper"></i> Europe
											</label>
										</div>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-12">
									<div class="row mb-2">
										<div class="col-lg-4">
											<input type="text" class="form-control" required id="customer_name" placeholder="Customer Name" name="customer_name">
										</div>
										<div class="col-lg-4">
											<input type="text" class="form-control" required id="project" placeholder="Project Name" name="project">

										</div>                                          
										<div class="col-lg-4">        
											<input type="text" class="form-control" required id="phone" placeholder="Opportunity Number" name="phone">       
										</div>
									</div>
								</div>

								<div class="col-md-12">
									<!-- Basic Form Inputs card start -->
									<div class="">
										<div class="modl-title">
											<div class="row">
												<div class="col-lg-3">
													<label>{{ $language_datas['model'] }}</label>
												</div>
												<div class="col-lg-2">
													<select name="model_number" id="model_number" class="form-control" onchange="updateModelValues('model_number')">
														<option  value="130">G2 C3</option>
														<option value="160">G2 C4</option>
														<option value="210">G2 D1</option>
														<option value="250">G2 D2</option>
														<option value="310">G2 D3</option>
														<option value="350">G2 D4</option>
														<option value="410">G2 E1</option>
														<option value="470">G2 E2</option>
														<option value="530">G2 E3</option>
														<option value="580">G2 E4</option>
														<option value="630">G2 E5</option>
														<option value="710">G2 E6</option>
														<option value="760">G2 F1</option>
														<option value="810">G2 F2</option>
														<option value="900">G2 F3</option>
														<option value="1010">G2 G1</option>
														<option value="1130">G2 G2</option>
														<option value="1260">G2 G3</option>
														<option value="1380">G2 G4</option>
														<option value="1560">G2 G5</option>
														<option value="1690">G2 G6</option>
													</select>
												</div>
												<div class="col-lg-1">
													
												</div>

												<div class="col-lg-3">
													<label>{{ $language_datas['capacity'] }}</label>
												</div>
												<div class="col-lg-2">          
													<input id="capacity" name="capacity" type="text" value="" onchange="updateModelValues('capacity')" class="form-control">
												</div>
												<div class="col-lg-1">
													<label>({{ $units_data[$unit_set->CapacityUnit] }})</label>
												</div>
											</div>
										</div>
											
										<div class="">
											<div class="row">
												<div class="col-lg-3">
													<label>{{ $language_datas['chilled_water_in'] }}</label>
												</div>
												<div class="col-lg-2">
													<input type="text" id="chilled_water_in" name="chilled_water_in" onchange="updateModelValues('chilled_water_in')" value="" class="form-control">
												</div>
												<div class="col-lg-1">
													<label>({{ $units_data[$unit_set->TemperatureUnit] }})</label>
												</div>

												<div class="col-lg-3">
													<label>{{ $language_datas['chilled_water_out'] }}</label>
												</div>
												<div class="col-lg-2">
													<input type="text" class="form-control min_chilled_water_out" id="chilled_water_out" name="chilled_water_out" onchange="updateModelValues('chilled_water_out')" value="" data-placement="bottom" title="">
												</div>
												<div class="col-lg-1">
													<label>({{ $units_data[$unit_set->TemperatureUnit] }})</label>
												</div>
											</div>
										</div>
									</div>
								</div> 
							</div>   
							<div class="row">
								<div class="col-lg-3">
									<label>{{ $language_datas['cooling_water_in'] }}</label>
								</div>
								<div class="col-lg-2">
									<input type="text" value="" onchange="updateModelValues('cooling_water_in')" name="cooling_water_in" id="cooling_water_in" class="form-control cooling_water_in_range" data-toggle="tooltip" data-placement="bottom" data-original-title>
								</div>
								<div class="col-lg-1">
									<label>({{ $units_data[$unit_set->TemperatureUnit] }})</label>
								</div>
								
								<div class="col-lg-3">
									<label>{{ $language_datas['cooling_water_flow'] }}</label>
								</div>
								<div class="col-lg-2">

									<input type="text" name="cooling_water_flow" onchange="updateModelValues('cooling_water_flow')" id="cooling_water_flow" value="" class="form-control cooling_water_ranges " data-placement="bottom" data-original-title>
								</div>
								<div class="col-lg-1">
									<label>({{ $units_data[$unit_set->FlowRateUnit] }})</label>
								</div>
							</div>
							<div class="row">
								<div class="col-12">
									<!-- <label class="">Available Range(s) : <span id=""></span></label> -->
								</div>
							</div>

							<div class="row">
								<div class="col-md-6">
									<div class="card-header">
										<h5>{{ $language_datas['glycol_content'] }} % (By Vol)</h5>
									</div>
								</div>
								<div class="col-md-6 form-radio">
									<div class="radio radio-inline">
										<label>
											<input type="radio" name="glycol" value="1" id="glycol_none" checked="checked">
											<i class="helper"></i>None
										</label>
									</div>
									<div class="radio radio-inline">
										<label>
											<input type="radio" name="glycol" id="glycol_ethylene" value="2">
											<i class="helper"></i>Ethylene
										</label>
									</div>
									<div class="radio radio-inline">
										<label>
											<input type="radio" name="glycol" id="glycol_propylene" value="3">
											<i class="helper"></i>Propylene
										</label>
									</div>
								</div>

								<div class="col-md-12">
									<div class="row">
										<div class="col-md-3">
											<label>{{ $language_datas['chilled_water'] }} </label>
										</div>
										<div class="col-md-3">
											<input type="text" name="glycol_chilled_water" id="glycol_chilled_water" value="0" onchange="updateModelValues('glycol_chilled_water')" value="" class="form-control">
										</div>

										<div class="col-md-3">
											<label>{{ $language_datas['cooling_water'] }} </label>
										</div>
										<div class="col-md-3">
											<input type="text" name="glycol_cooling_water" id="glycol_cooling_water" value="0" onchange="updateModelValues('glycol_cooling_water')" class="form-control">
										</div>
									</div>
								</div>
							</div>

							<div class="row">
								<div class="col-md-6">                      
									<div class="card-header">
										<h5>{{ $language_datas['tube_metallurgy'] }}</h5>
									</div>
								</div>

								<div class="form-radio col-6">
									<div class="radio radio-inline">
										<label>
											<input type="radio" name="tube_metallurgy" id="tube_metallurgy_standard" value="standard" checked="checked">
											<i class="helper"></i>Standard
										</label>
									</div>
									<div class="radio radio-inline">
										<label>
											<input type="radio" name="tube_metallurgy" id="tube_metallurgy_non_standard" value="non_standard">
											<i class="helper"></i>Non Standard
										</label>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-lg-3">
									<p><label class=" col-form-label">{{ $language_datas['evaporator'] }}</label></p>
									<div class="row">
										<div class="col-lg-12">
											<select name="evaporator_material" id="evaporator_material" onchange="updateModelValues('evaporator_tube_type');" class="form-control metallurgy_standard">
												@foreach($evaporator_options as $evaporator_option)
												<option value="{{ $evaporator_option->value }}">{{ $evaporator_option->metallurgy->display_name }}</option>
												@endforeach
											</select>
										</div>
										<div class="col-lg-12 range-hide">
											<div class="row">
												<div class="col-md-8">
													<input type="text" name="evaporator_thickness" id="evaporator_thickness" onchange="updateModelValues('evaporator_thickness')" value="" class="form-control metallurgy_standard metallurgy_standard_span" data-placement="bottom" title="">
													<span class="" id="evaporator_range"></span>
												</div>
												<div class="col-lg-4">
													<label class="padd-mm"> ({{ $units_data[$unit_set->LengthUnit] }}) </label>
												</div>
											</div>
										</div>							
									</div>
								</div>                           

								<div class="col-lg-3">
									<p><label class=" col-form-label">{{ $language_datas['absorber'] }}</label></p>
									<div class="row">
										<div class="col-lg-12">								
											<select name="absorber_material" id="absorber_material" onchange="updateModelValues('absorber_tube_type');" class="form-control metallurgy_standard">
												@foreach($absorber_options as $absorber_option)
												<option value="{{ $absorber_option->value }}">{{ $absorber_option->metallurgy->display_name }}</option>
												@endforeach
											</select>
										</div>
										<div class="col-lg-12 range-hide">
											<div class="row">
												<div class="col-md-8">
													<input type="text" name="absorber_thickness" id="absorber_thickness" onchange="updateModelValues('absorber_thickness')" value="" class="form-control metallurgy_standard" data-placement="bottom" title="">
													<span class="metallurgy_standard_span" id="absorber_range"></span>
												</div>
												<div class="col-lg-4">
													<label class="padd-mm"> ({{ $units_data[$unit_set->LengthUnit] }}) </label>
												</div>
											</div>
										</div>
									</div>
								</div>


								<div class="col-lg-3">
									<p><label class=" col-form-label">{{ $language_datas['condenser'] }}</label></p>
									<div class="row">
										<div class="col-lg-12">		
											<select name="condenser_material" id="condenser_material" onchange="updateModelValues('condenser_tube_type');" class="form-control metallurgy_standard">
												@foreach($condenser_options as $condenser_option)
												<option value="{{ $condenser_option->value }}">{{ $condenser_option->metallurgy->display_name }}</option>
												@endforeach
											</select>
										</div>
										<div class="col-lg-12 range-hide">
											<div class="row">
												<div class="col-md-8">
													<input type="text" name="condenser_thickness" id="condenser_thickness" onchange="updateModelValues('condenser_thickness')" value="" class="form-control metallurgy_standard" data-placement="bottom" title="">
													<span class="metallurgy_standard_span" id="condenser_range"></span>
												</div>
												<div class="col-lg-4">
													<label class="padd-mm"> ({{ $units_data[$unit_set->LengthUnit] }}) </label>
												</div>
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-3">
								</div>
							</div>

							<div class="row">
								<div class="col-md-6">
									<div class="card-header">
										<h5>{{ $language_datas['fouling_factor'] }}</h5>
									</div>
								</div>
								<div class="col-md-6">
									<div class="row">
										<div class="form-radio col-12">
											<div class="radio radio-inline">
												<label>
													<input type="radio" name="fouling_factor" id="fouling_factor_standard" value="standard" checked="checked">
													<i class="helper"></i>Standard
												</label>
											</div>
											<div class="radio radio-inline">
												<label>
													<input type="radio" name="fouling_factor" id="fouling_factor_non_standard" value="non_standard">
													<i class="helper"></i>Non Standard
												</label>
											</div>
											<div class="radio radio-inline">
												<label>
													<input type="radio" name="fouling_factor" id="fouling_factor_ari" value="ari">
													<i class="helper"></i><span id="fouling_ari">ARI</span><span id="fouling_factor_ahri"></span>
												</label>
											</div>
										</div>
									</div>
								</div>
								<div class="col-md-12">
									<div class="row margin-0">
										<div class="col-lg-2 padding-0 checkbox-fade fade-in-primary">
											<label>
												<input type="checkbox" class="fouling_standard " name="fouling_chilled_water" id="fouling_chilled_water" value="" data-placement="bottom" title="">
												<span class="cr">
													<i class="cr-icon icofont icofont-ui-check txt-primary"></i>
												</span><span>{{ $language_datas['chilled_water'] }}</span><span id=""></span>
											</label>
										</div>

										<div class="col-lg-2">
											<input type="text" name="fouling_chilled_value" id="fouling_chilled_value" onchange="updateModelValues('fouling_chilled_value')" class="form-control fouling_standard fouling_chilled_min" data-placement="bottom" title="">
										</div>
										<div class="col-lg-2 padding-0">
											<label>({{ $units_data[$unit_set->FoulingFactorUnit] }})</label>
										</div>

										<div class="col-lg-2 padding-0 checkbox-fade fade-in-primary">
											<label>
												<input type="checkbox" class="fouling_standard" name="fouling_cooling_water" id="fouling_cooling_water" value="" data-placement="bottom" title="0.00005">
												<span class="cr">
													<i class="cr-icon icofont icofont-ui-check txt-primary"></i>
												</span><span>{{ $language_datas['cooling_water'] }}</span><span id=""></span>
											</label>
										</div>

										<div class="col-lg-2">
											<input type="text" name="fouling_cooling_value" id="fouling_cooling_value" onchange="updateModelValues('fouling_cooling_value')" class="form-control fouling_standard fouling_cooling_min" data-placement="bottom" title="">
										</div>
										<div class="col-lg-2 padding-0">
											<label>({{ $units_data[$unit_set->FoulingFactorUnit] }})</label>
										</div>
									</div>
								</div>
							</div>

							<div class="row">
								<div class="col-md-6">
									<div class="card-header">
										<h5>Fuel</h5>
									</div>
								</div>
								<div class="col-md-6">
									<div class="row">
										<div class="form-radio col-12">
											<div class="radio radio-inline">
												<label>
													<input type="radio" name="fuel_type" class="fuel_type" id="fuel_gross" value="Gross" >
													<i class="helper"></i>Gross CV
												</label>
												</div>
											<div class="radio radio-inline">
												<label>
													<input type="radio" name="fuel_type" class="fuel_type" id="fuel_normal" value="Normal" >
													<i class="helper"></i>Normal CV
												</label>
											</div>
										</div>
									</div>
								</div>
								<div class="col-md-12">
									<div class="row">
										<div class="form-radio col-lg-4">
											<div class="radio radio-inline">
												<label >
													<input type="radio" name="fuel_value_type" id="fuel_natural_gas" value="NaturalGas" class="fuel_value_type" >
														<i class="helper"></i>Natural Gas 
												</label>
											</div>
										</div>
										<div class="col-lg-2">
											<label>Cal.Value </label>
										</div>
										<div class="col-lg-4">
											<input type="text" name="calorific_value" id="calorific_value" onchange="updateModelValues('calorific_value')" value="" class="form-control">

											<span class="messages emsg hidden" id="calorific_value_error"><p class="text-danger error"></p></span>
										</div>
										<div class="col-lg-2">
											<label>({{ $units_data[$unit_set->TemperatureUnit] }})</label>
										</div>
									</div> 
								</div>
								<div class="col-md-12">
									<div class="row">
										<div class=" form-radio col-lg-4">
												<div class="radio radio-inline">
												<label>
													<input type="radio" name="fuel_value_type" class="fuel_value_type" id="fuel_hsd" value="HSD">
														<i class="helper"></i>HSD 
												</label>
												</div>	
										</div>
										<div class="col-lg-7">
											<div class="row fuel_cv_stds" style="display: none;">
											<div class="col-lg-4">
												<label>Std GCV </label>
											</div>
											<div class="col-lg-6">
												<span id="fuel_cv_std"></span>
												
											</div>
											<div class="col-lg-2">
												<label>({{ $units_data[$unit_set->TemperatureUnit] }})</label>
											</div>
											</div>
										</div>
									</div>
								</div>
								<div class="col-md-12">
									<div class="row">
										<div class="form-radio col-lg-4">
											<div class="radio radio-inline">
												<label>
													<input type="radio" name="fuel_value_type" id="fuel_sko" value="SKO" class="fuel_value_type">
														<i class="helper"></i>SKO  
												</label>
											</div>
										</div>
										<div class="col-lg-4">
											
										</div>
										<div class="col-lg-3">
											
										</div>
									</div>
								</div>
							</div>
							<div class="col-sm-12">
								<div class="row">
									<div class="col-md-12 text-center cal-rest">
										<input type="submit" name="submit_value" value="Calculate" id="calculate_button" class="btn btn-primary m-b-0">
										<input type="button" name="reset" id="reset" value="Reset" class="btn btn-primary m-b-0">
									</div>
								</div>
							</div>
						</div>

						<div class="col-md-5 padd-2">
							<div class="scrollbar-right" id="scroll-right">
								<div class="force-overflow"> 
									<div class="notes-content"  >
										<div id="errornotes" style="display: none;">
											<div class="summary-head">
												<h4> NOTES : </h4>
											</div>
											<span id="errormessage">

											</span>
										</div>              
										<div class="showreport" style="display: none;">                 
											<div class="summary-head" id="notes_head_div">
											</div>
											<div class="row">

												<div class="col-md-6">
													<button type="button" name="submit" id="save_word" value="Export to Word" class="contact-submit save_report">   <i class="fas fa-file-word"></i> Export to Word</button>
												</div>
												<div class="col-md-6">
													<button type="button" name="submit" id="save_pdf" value="Export to Pdf" class="contact-submit save_report">  <i class="fas fa-file-pdf"></i> Export to Pdf</button>
												</div>
											</div>                           
								  			
								 			<div id="notes_div" >

								 			</div>
								 			<div id="showreportlist">

								 			</div>
							 			</div>
							 		</div>
							 	</div>
							</div>                     
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<div class="ajax-loader" id="ajax-loader"  style="position: fixed; left: 50%; top: 50%; transform: translate(-50%, -50%); display: none;">
		<img src="{{asset('assets/pageloader.gif')}}" id="ajax-loader" class="img-responsive" />

</div>

@endsection

@section('scripts')	


@endsection











