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
 		height: 26px !important;
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
 		font-size: 15px;
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
 		font-size: 11px;
 		margin-bottom: 9px;
 		font-weight: 600;
 		color: #656565;
 	}

 	.ss-steam-label .page-wrapper .page-header {
 		margin-bottom: 0px;
 	}

 	.ss-steam-label .max-calculator .card-header h5 {
 		font-size: 11px;
 	}
 	.show-div{
 		font-size: 11px;
 	}

 	.ss-steam-label .card-header{
 		padding: 3px 10px;
 		margin-bottom: 4px;
 		margin-top: 2px;
 	}

 	body.dark-layout .ss-steam-label .form-control{
 		background: #fff;
 		color: #333;
 		border: none;
 		border-bottom: 1px solid #c5c5c5;
 		border-radius: 1px;
 		font-size: 11px;
 		text-align-last: center;
 	}
 	body.dark-layout .ss-steam-label select option{
 		text-align-last: center;
 	}

 	.ss-steam-label .form-control{
 		text-align: center;
 	}

 	.ss-steam-label .btn-primary{
 		padding: 4px 20px;
 		width: 100px;
 		font-size: 11px;
 	}

 	.notes-content{

 		padding: 15px; 
		/*
			max-height: 500px;              
			overflow: hidden;         
			*/
		}
	/*
		  .notes-content:hover{
			   overflow-y: auto;     
			  
		 }
		 */
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
		 	padding: 25px 0 0 0;
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
												<h4>Double Effect H2 Hot Water Fired series</h4>
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
											<div class="">

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
										</div>


										<div class="col-md-12">
											<!-- Basic Form Inputs card start -->
											<div class="">
												<div class="modl-title">
													<div class="row">
														<div class="col-lg-3">
															<label>Model</label>
														</div>
														<div class="col-lg-2">
															<select name="model_number" id="model_number" class="form-control" onchange="updateModelValues('model_number')">
																<option  value="130">H2 C3</option>
																<option value="160">H2 C4</option>
																<option value="210">H2 D1</option>
																<option value="250">H2 D2</option>
																<option value="310">H2 D3</option>
																<option value="350">H2 D4</option>
																<option value="410">H2 E1</option>
																<option value="470">H2 E2</option>
																<option value="530">H2 E3</option>
																<option value="580">H2 E4</option>
																<option value="630">H2 E5</option>
																<option value="710">H2 E6</option>
																<option value="760">H2 F1</option>
																<option value="810">H2 F2</option>
																<option value="900">H2 F3</option>
																<option value="1010">H2 G1</option>
																<option value="1130">H2 G2</option>
																<option value="1260">H2 G3</option>
																<option value="1380">H2 G4</option>
																<option value="1560">H2 G5</option>
																<option value="1690">H2 G6</option>
																<option value="1890">H2 H1</option>
																<option value="2130">H2 H2</option>
																<option value="2270">H2 J1</option>
																<option value="2560">H2 J2</option>
															</select>
														</div>
														<div class="col-lg-1">
															<!--                                                  <label id="model_name"></label> -->
														</div>

														<div class="col-lg-3">
															<label>Capacity</label></div>
															<div class="col-lg-2">          
																<input id="capacity" name="capacity" type="text" value="" onchange="updateModelValues('capacity')" class="form-control">

																<span class="messages emsg hidden" id="capacity_error">
																	<p class="text-danger error">Please Enter a Valid Capacity</p>
																</span>
															</div>
															<div class="col-lg-1">
																<label>({{ $units_data[$unit_set->CapacityUnit] }})</label>
															</div>
														</div>
													</div>
								<!--
									<div class="card-header">
										 <h5>Chilled Water</h5>
									</div>
								-->
								<div class="">
									<div class="row">
										<div class="col-lg-3">
											<label>Chilled Water In</label>
										</div>
										<div class="col-lg-2">
											<input type="text" id="chilled_water_in" name="chilled_water_in" onchange="updateModelValues('chilled_water_in')" value="" class="form-control">

											<span class="messages emsg hidden" id="chilled_water_in_error">
												<p class="text-danger error">Please Enter a Valid Chilled Water In</p>
											</span>
										</div>
										<div class="col-lg-1">
											<label>({{ $units_data[$unit_set->TemperatureUnit] }})</label>
										</div>

										<div class="col-lg-3">
											<label>Chilled Water Out </label>
										</div>
										<div class="col-lg-2">
											<input type="text" class="form-control min_chilled_water_out" id="chilled_water_out" name="chilled_water_out" onchange="updateModelValues('chilled_water_out')" value="" data-placement="bottom" title="">
											<span class="messages emsg hidden" id="chilled_water_out_error">
												<p class="text-danger error">Please Enter a Valid Chilled Water Out</p>
											</span>
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
							<label>Cooling Water In </label>
						</div>
						<div class="col-lg-2">
							<input type="text" value="" onchange="updateModelValues('cooling_water_in')" name="cooling_water_in" id="cooling_water_in" class="form-control cooling_water_in_range" data-toggle="tooltip" data-placement="bottom" data-original-title>

							<span class="messages emsg hidden" id="cooling_water_in_error">
								<p class="text-danger error">Please Enter a Valid Cooling Water In</p>
							</span>
						</div>
						<div class="col-lg-1">
							<label>({{ $units_data[$unit_set->TemperatureUnit] }})</label>
						</div>
						
						<div class="col-lg-3">
							<label>Cooling Water Flow</label>
						</div>
						<div class="col-lg-2">

							<input type="text" name="cooling_water_flow" onchange="updateModelValues('cooling_water_flow')" id="cooling_water_flow" value="" class="form-control cooling_water_ranges " data-placement="bottom" data-original-title>

							<span class="messages emsg hidden" id="cooling_water_flow_error">
								<p class="text-danger error">Please Enter a Valid Cooling Water Flow</p>
							</span>
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
								<h5>Glycol Content % (By Vol)</h5>
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
									<label>Chilled Water </label>
								</div>
								<div class="col-md-3">
									<input type="text" name="glycol_chilled_water" id="glycol_chilled_water" value="0" onchange="updateModelValues('glycol_chilled_water')" value="" class="form-control">

									<span class="messages emsg hidden" id="glycol_chilled_water_error">
										<p class="text-danger error">Please Enter a Valid Glycol Chilled Water</p>
									</span>
								</div>

								<div class="col-md-3">
									<label>Cooling Water </label>
								</div>
								<div class="col-md-3">
									<input type="text" name="glycol_cooling_water" id="glycol_cooling_water" value="0" onchange="updateModelValues('glycol_cooling_water')" class="form-control">

									<span class="messages emsg hidden" id="glycol_cooling_water_error">
										<p class="text-danger error">Please Enter a Valid Glycol Cooling Water</p>
									</span>
								</div>
							</div>
						</div>
					</div>

					<div class="row">
						<div class="col-md-6">                      
							<div class="card-header">
								<h5>Tube Metallurgy</h5>
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
							<p><label class=" col-form-label">Evaporator</label></p>
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

											<span class="messages emsg hidden" id="evaporator_thickness_error">
												<p class="text-danger error">Please Enter a Valid Evaporator Thickness</p>
											</span>
											<span class="" id="evaporator_range"></span>
										</div>
										<div class="col-lg-4">
											<label class="padd-mm"> (mm) </label>
										</div>
									</div>
								</div>							
							</div>
						</div>                           

						<div class="col-lg-3">
							<p><label class=" col-form-label">Absorber</label></p>
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

											<span class="messages emsg hidden" id="absorber_thickness_error">
												<p class="text-danger error">Please Enter a Valid Absorber Thickness</p>
											</span>
											<span class="metallurgy_standard_span" id="absorber_range"></span>
										</div>
										<div class="col-lg-4">
											<label class="padd-mm"> (mm) </label>
										</div>
									</div>
								</div>
							</div>
						</div>


						<div class="col-lg-3">
							<p> <label class=" col-form-label">Condenser</label></p>
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
											<span class="messages emsg hidden" id="condenser_thickness_error">
												<p class="text-danger error">Please Enter a Valid Condenser Thickness</p>
											</span>
											<span class="metallurgy_standard_span" id="condenser_range"></span>
										</div>
										<div class="col-lg-4">
											<label class="padd-mm"> (mm) </label>
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
								<h5>Fouling Factor</h5>
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
										</span><span>Chilled Water</span><span id=""></span>
									</label>
								</div>

								<div class="col-lg-2">
									<input type="text" name="fouling_chilled_value" id="fouling_chilled_value" onchange="updateModelValues('fouling_chilled_value')" class="form-control fouling_standard fouling_chilled_min" data-placement="bottom" title="">

									<span class="messages emsg hidden" id="fouling_chilled_value_error">
										<p class="text-danger error">Please Enter a Valid Fouling Chilled Water</p>
									</span>
								</div>
								<div class="col-lg-2 padding-0">
									<label>({{ $units_data[$unit_set->FoulingFactorUnit] }})</label>
								</div>

								<div class="col-lg-2 padding-0 checkbox-fade fade-in-primary">
									<label>
										<input type="checkbox" class="fouling_standard" name="fouling_cooling_water" id="fouling_cooling_water" value="" data-placement="bottom" title="0.00005">
										<span class="cr">
											<i class="cr-icon icofont icofont-ui-check txt-primary"></i>
										</span><span>Cooling Water</span><span id=""></span>
									</label>
								</div>

								<div class="col-lg-2">
									<input type="text" name="fouling_cooling_value" id="fouling_cooling_value" onchange="updateModelValues('fouling_cooling_value')" class="form-control fouling_standard fouling_cooling_min" data-placement="bottom" title="">

									<span class="messages emsg hidden" id="fouling_cooling_value_error">
										<p class="text-danger error">Please Enter a Valid Fouling Cooling Water</p>
									</span>
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
								<h5>Hot Water</h5>
							</div>
						</div>
						<div class="col-md-12">
							<div class="row">

								<div class="col-lg-2">
									<label>Water In </label>
								</div>
								<div class="col-lg-2">
									<input type="text" name="hot_water_in" id="hot_water_in" onchange="updateModelValues('hot_water_in')" value="" class="form-control hot_water_in_range" data-placement="bottom" data-original-title>

									<span class="messages emsg hidden" id="hot_water_in_error"><p class="text-danger error"></p></span>
								</div>
								<div class="col-lg-2">
									<label>({{ $units_data[$unit_set->TemperatureUnit] }})</label>
								</div>

								<div class="col-lg-2">
									<label>Water Out</label>
								</div>
								<div class="col-lg-2">
									<input type="text" name="hot_water_out" id="hot_water_out" onchange="updateModelValues('hot_water_out')" value="" class="form-control hot_water_out_range" data-placement="bottom" data-original-title>

									<span class="messages emsg hidden" id="hot_water_out_error"><p class="text-danger error">Please Enter a Valid Hot Water Out</p></span>
								</div>
								<div class="col-lg-2">
									<label>({{ $units_data[$unit_set->TemperatureUnit] }})</label>
								</div>
							</div> 
						</div>
						<div class="col-md-12">
							<div class="row">
								<div class="col-lg-6">
									<label>Maximum Working Pressure in Hot Water </label>
								</div>
								<div class="col-lg-4">
									<input type="text" name="all_work_pr_hw" id="all_work_pr_hw" onchange="updateModelValues('all_work_pr_hw')" value="" class="form-control" data-placement="bottom" data-original-title>

									<span class="messages emsg hidden" id="all_work_pr_hw_error"><p class="text-danger error"></p></span>
								</div>
								<div class="col-lg-2">
									<label>({{ $units_data[$unit_set->AllWorkPrHWUnit] }})</label>
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
						  			<!-- <div class="summary-head">
					 				<h4> Summary : </h4>
					 			</div> -->
					 			<div id="notes_div" >

					 			</div>
					 			<div id="showreportlist">

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

<script type="text/javascript">

	var model_values = {!! json_encode($default_values) !!};
	var evaporator_options = {!! json_encode($evaporator_options) !!};
	var absorber_options = {!! json_encode($absorber_options) !!};
	var condenser_options = {!! json_encode($condenser_options) !!};
	var chiller_metallurgy_options = {!! json_encode($chiller_metallurgy_options) !!};
	var changed_value = "";
	var calculation_values;
	var metallurgy_unit = "{!! $unit_set->LengthUnit !!}";
	var region_user = model_values.region_type;
	if(region_user == 4){
		$("#region_list").show();
		$("#domestic").prop('checked', true);
		$("#export_type").prop('disabled', false);
		$("#regionlist").hide();
		model_values.region_type = 1 ;
	}
	else{
		$("#region_list").hide();
	}
	$( document ).ready(function() {
		// swal("Hello world!");
		loadDefaultValues();
		$('.menu-hide-click').trigger('click');
		// sendValues();
	});

	function loadDefaultValues(){
		model_values.evaporator_thickness_change = true;
		model_values.absorber_thickness_change = true;
		model_values.condenser_thickness_change = true;
		model_values.fouling_chilled_water_value = "";
		model_values.fouling_cooling_water_value = "";
		model_values.fouling_chilled_water_checked = false;
		model_values.fouling_cooling_water_checked = false;
		model_values.fouling_chilled_water_disabled = true;
		model_values.fouling_cooling_water_disabled = true;
		model_values.fouling_chilled_water_value_disabled = true;
		model_values.fouling_cooling_water_value_disabled = true;

		updateEvaporatorOptions(chiller_metallurgy_options.eva_default_value,model_values.evaporator_thickness_change);
		updateAbsorberOptions(chiller_metallurgy_options.abs_default_value,model_values.absorber_thickness_change);
		updateCondenserOptions(chiller_metallurgy_options.con_default_value,model_values.condenser_thickness_change);
		updateValues();
	}

	function inputValidation(value,validation_type,input_name,message){
			// console.log(value);
			var positive_decimal=/^(0|[1-9]\d*)(\.\d+)?$/;
			var negative_decimal=/^-?(0|[1-9]\d*)(\.\d+)?$/;
			var value_input = input_name.replace('_error', '')
			if(validation_type == "positive_decimals"){
				if (!value.match(positive_decimal)) {
                  // there is a mismatch, hence show the error message
                  $('#'+value_input).addClass("box-color");
                  $(".showreport").hide();
                  $("#errornotes").show();
                  $("#errormessage").html(message);
                  $('#'+value_input).focus();
              }
              else{
                    // else, do not display message
                    $("#errornotes").hide();
                    $('#'+value_input).removeClass("box-color");
                    return true;
                }
            }

            if(validation_type == "decimals"){
            	if (!value.match(negative_decimal)) {
                  // there is a mismatch, hence show the error message
                  $("#errornotes").show();
                  $('#'+value_input).addClass("box-color");
                  $("#errormessage").html(message);
              }
              else{
                    // else, do not display message
                    $("#errornotes").hide();
                    $('#'+value_input).removeClass("box-color");
                    return true;
                }
            }


            return false;
        }


        function updateValues() {

        	updateEvaporatorOptions(model_values.evaporator_material_value,model_values.evaporator_thickness_change);
        	updateAbsorberOptions(model_values.absorber_material_value,model_values.absorber_thickness_change);
        	updateCondenserOptions(model_values.condenser_material_value,model_values.condenser_thickness_change);

        	$("#model_number").val(model_values.model_number);
        	$('#capacity').val(model_values.capacity);
        	$('#model_name').html(model_values.model_name);
        	$('#chilled_water_in').val(model_values.chilled_water_in);
        	$('#chilled_water_out').val(model_values.chilled_water_out);
        	$('.min_chilled_water_out').attr('data-original-title',"min "+model_values.min_chilled_water_out);
        	var cooling_water_in_range = model_values.cooling_water_in_min_range+" - "+model_values.cooling_water_in_max_range;
        	$('.cooling_water_in_range').attr('data-original-title', cooling_water_in_range);
			//$('#cooling_water_in_range').attr(cooling_water_in_range);
			$('#cooling_water_in').val(model_values.cooling_water_in);
			$('#cooling_water_flow').val(model_values.cooling_water_flow);
			var cooling_water_ranges = getCoolingWaterRanges(model_values.cooling_water_ranges);

			$('.cooling_water_ranges').attr('data-original-title',cooling_water_ranges);
			// $("#glycol_none").attr('disabled', model_values.glycol_none);
			$('#glycol_chilled_water').val(model_values.glycol_chilled_water ?  model_values.glycol_chilled_water : 0);
			$('#glycol_cooling_water').val(model_values.glycol_cooling_water ?  model_values.glycol_cooling_water : 0);
			
			$('#evaporator_thickness').val(model_values.evaporator_thickness);
			$('#absorber_thickness').val(model_values.absorber_thickness);
			$('#condenser_thickness').val(model_values.condenser_thickness);
			$("#evaporator_material").val(model_values.evaporator_material_value);
			$("#absorber_material").val(model_values.absorber_material_value);
			$("#condenser_material").val(model_values.condenser_material_value);

			$("#hot_water_in").val(model_values.hot_water_in);
			$("#hot_water_out").val(model_values.hot_water_out);
			$("#all_work_pr_hw").val(model_values.all_work_pr_hw);
			var hot_water_in_range = model_values.min_hot_water_in+" - "+model_values.max_hot_water_in;
			$('.hot_water_in_range').attr('data-original-title',hot_water_in_range);
			$('.hot_water_out_range').attr('data-original-title',model_values.min_hot_water_out);
			
			// $("#tube_metallurgy").attr('disabled', model_values.glycol_none);
			if(model_values.glycol_none === 'true')
				$("#glycol_none").prop('disabled', true);
			else
				$("#glycol_none").prop('disabled', false);


			foulingFactor(model_values.fouling_factor);

			if(model_values.glycol_selected == 1){
				$("#glycol_none").prop('checked', true);
				$("#glycol_chilled_water").prop('disabled', true);
				$("#glycol_cooling_water").prop('disabled', true);

			}
			else if(model_values.glycol_selected == 2){
				$("#glycol_ethylene").prop('checked', true);
				$("#glycol_chilled_water").prop('disabled', false);
				$("#glycol_cooling_water").prop('disabled', false);
			}
			else{
				$("#glycol_propylene").prop('checked', true);
				$("#glycol_chilled_water").prop('disabled', false);
				$("#glycol_cooling_water").prop('disabled', false);

			}


			if(model_values.metallurgy_standard){
				$("#tube_metallurgy_standard").prop('checked', true);
				$(".metallurgy_standard").prop('disabled', true);
				$(".metallurgy_standard_span").html("");
				$(".range-hide").removeClass('show-div').addClass('hidden-div');

			}else{
				$(".range-hide").addClass('show-div');
				$("#tube_metallurgy_non_standard").prop('checked', true);

				if(model_values.tube_metallurgy_standard === 'false')
					$("#tube_metallurgy_standard").prop('disabled', true);
				else
					$("#tube_metallurgy_standard").prop('disabled', false);
				// $("#tube_metallurgy_standard").prop('disabled', true);
				$(".metallurgy_standard").prop('disabled', false);
				var evaporator_range = "("+model_values.evaporator_thickness_min_range+" - "+model_values.evaporator_thickness_max_range+")";

				$('#evaporator_thickness').attr('data-original-title',evaporator_range);

				var absorber_range = "("+model_values.absorber_thickness_min_range+" - "+model_values.absorber_thickness_max_range+")";
				$("#absorber_thickness").attr('data-original-title',absorber_range);
				var condenser_range = "("+model_values.condenser_thickness_min_range+" - "+model_values.condenser_thickness_max_range+")";
				$("#condenser_thickness").attr('data-original-title',condenser_range);
			}

		}

		$('input:radio[name="glycol"]').change(function() {
			if ($(this).val() == 'none') {
				$("#glycol_chilled_water").prop('disabled', true);
				$("#glycol_cooling_water").prop('disabled', true);
			} else {
		  		// $("#glycol_none").prop('checked', false);
		  		$("#glycol_ethylene").prop('checked', true);
		  		$("#glycol_chilled_water").prop('disabled', false);
		  		$("#glycol_cooling_water").prop('disabled', false);
		  	}
		  });

		$('input:radio[name="tube_metallurgy"]').change(function() {
			if ($(this).val() == 'standard') {
				$(".metallurgy_standard").prop('disabled', true);
				$(".metallurgy_standard_span").html("");
				$(".range-hide").removeClass('show-div').addClass('hidden-div');

			} else {
				$("#tube_metallurgy_standard").prop('disabled', false);
				$(".range-hide").addClass('show-div');
				$(".metallurgy_standard").prop('disabled', false);
				var evaporator_range = "("+model_values.evaporator_thickness_min_range+" - "+model_values.evaporator_thickness_max_range+")";
				$('#evaporator_thickness').attr('data-original-title',evaporator_range);
				var absorber_range = "("+model_values.absorber_thickness_min_range+" - "+model_values.absorber_thickness_max_range+")";
				$("#absorber_thickness").attr('data-original-title',absorber_range);
				var condenser_range = "("+model_values.condenser_thickness_min_range+" - "+model_values.condenser_thickness_max_range+")";
				$("#condenser_thickness").attr('data-original-title',condenser_range);
			}
		});

		$('#fouling_chilled_water').change(function() {
			if($(this).is(":checked")) {
				model_values.fouling_chilled_water_checked = true;
				model_values.fouling_chilled_water_value_disabled = false;
				model_values.fouling_chilled_water_value = model_values.fouling_non_chilled;
				$("#fouling_chilled_value").val(model_values.fouling_non_chilled);
				$("#fouling_chilled_value").prop('disabled', false);
			}
			else{
				model_values.fouling_chilled_water_checked = false;
				model_values.fouling_chilled_water_value_disabled = true;
				model_values.fouling_chilled_water_value = "";
				$("#fouling_chilled_value").val("");
				$("#fouling_chilled_value").prop('disabled', true);
			}

		});

		$('#fouling_cooling_water').change(function() {
			if($(this).is(":checked")) {
				model_values.fouling_cooling_water_checked = true;
				model_values.fouling_cooling_water_value_disabled = false;
				model_values.fouling_cooling_water_value = model_values.fouling_non_cooling;
				$("#fouling_cooling_value").val(model_values.fouling_non_cooling);
				$("#fouling_cooling_value").prop('disabled', false);
			}
			else{
				model_values.fouling_cooling_water_checked = false;
				model_values.fouling_cooling_water_value_disabled = true;
				model_values.fouling_cooling_water_value = "";
				$("#fouling_cooling_value").val("");
				$("#fouling_cooling_value").prop('disabled', true);
			}

		});

		$('input:radio[name="fouling_factor"]').change(function() {
			$("#calculate_button").prop('disabled', false);
			model_values.fouling_factor = $(this).val();
			if($(this).val() == 'ari'){
				model_values.fouling_chilled_water_value = model_values.fouling_ari_chilled;
				model_values.fouling_cooling_water_value = model_values.fouling_ari_cooling;
			}
			foulingFactor($(this).val());
		});

		function foulingFactor(value){
			$("#fouling_chilled_water").prop('checked', false);


			if (value == 'standard') {
				$("#fouling_factor_standard").prop('checked', true);
				$("#fouling_chilled_water").prop('checked', false);
				$("#fouling_cooling_water").prop('checked', false);
				$(".fouling_standard").prop('disabled', true);
				$(".fouling_chilled_min").attr('data-original-title',"");
				$(".fouling_cooling_min").attr('data-original-title',"");
				$("#fouling_chilled_value").val("");
				$("#fouling_cooling_value").val("");
				model_values.fouling_chilled_water_checked = false;
				model_values.fouling_cooling_water_checked = false;
				model_values.fouling_chilled_water_disabled = true;
				model_values.fouling_cooling_water_disabled = true;
				model_values.fouling_chilled_water_value = "";
				model_values.fouling_cooling_water_value = "";
			} else if (value == 'non_standard'){
				model_values.fouling_chilled_water_disabled = false;
				model_values.fouling_cooling_water_disabled = false;
		    	// model_values.fouling_chilled_water_value = "";
		    	// model_values.fouling_cooling_water_value = "";
		    	if(!model_values.fouling_chilled_water_checked){
		    		model_values.fouling_chilled_water_value = "";
		    	}

		    	if(!model_values.fouling_cooling_water_checked){
		    		model_values.fouling_cooling_water_value = "";
		    	}

		    	$("#fouling_factor_non_standard").prop('checked', true);
		    	$("#fouling_chilled_water").prop('disabled', model_values.fouling_chilled_water_disabled);
		    	$("#fouling_cooling_water").prop('disabled', model_values.fouling_cooling_water_disabled);
		    	$("#fouling_chilled_water").prop('checked', model_values.fouling_chilled_water_checked);
		    	$("#fouling_cooling_water").prop('checked', model_values.fouling_cooling_water_checked);
		    	$("#fouling_chilled_value").prop('disabled', model_values.fouling_chilled_water_value_disabled);
		    	$("#fouling_cooling_value").prop('disabled', model_values.fouling_cooling_water_value_disabled);
		    	$("#fouling_chilled_value").val(model_values.fouling_chilled_water_value);
		    	$("#fouling_cooling_value").val(model_values.fouling_cooling_water_value);
		    	$(".fouling_chilled_min").attr('data-original-title',">"+model_values.fouling_non_chilled);
		    	$(".fouling_cooling_min").attr('data-original-title',">"+model_values.fouling_non_cooling);

		    }
		    else{

		    	model_values.fouling_chilled_water_checked = false;
		    	model_values.fouling_cooling_water_checked = false;
		    	model_values.fouling_chilled_water_value_disabled = true;
		    	model_values.fouling_cooling_water_value_disabled = true;

		    	$("#fouling_factor_ari").prop('checked', true);
		    	$("#fouling_chilled_water").prop('disabled', true);
		    	$("#fouling_cooling_water").prop('disabled', true);
		    	$("#fouling_chilled_water").prop('checked', true);
		    	$("#fouling_cooling_water").prop('checked', true);
		    	$("#fouling_chilled_value").prop('disabled', false);
		    	$("#fouling_cooling_value").prop('disabled', false);
		    	$(".fouling_chilled_min").attr('data-original-title',">"+model_values.fouling_ari_chilled);

		    	$(".fouling_cooling_min").attr('data-original-title',">"+model_values.fouling_ari_cooling);
		    	$("#fouling_chilled_value").val(model_values.fouling_chilled_water_value);
		    	$("#fouling_cooling_value").val(model_values.fouling_cooling_water_value);
		    }
		}

		function getCoolingWaterRanges(cooling_water_ranges){
			var range_values = "";
			console.log(cooling_water_ranges);
			if(!$.isArray(cooling_water_ranges)){
				var cooling_water_ranges = cooling_water_ranges.split(",");
			}
			
			for (var i = 0; i < cooling_water_ranges.length; i+=2) {
				range_values += "("+cooling_water_ranges[i]+" - "+cooling_water_ranges[i+1]+") /";
			}

			return range_values.replace(/\/$/, "");
		}

		function updateEvaporatorOptions(value,thickness_change){
			$('#evaporator_material').empty();
			var $el = $("#evaporator_material");
			$el.empty(); // remove old options
			$.each(evaporator_options, function(key,option) {
				// console.log(option);
				$el.append($("<option></option>").attr("value", option.value).text(option.metallurgy.display_name));
				if(value == option.value){
					model_values.evaporator_material_value = value;
					
					if(thickness_change){

						if(metallurgy_unit != 'Millimeter'){
							model_values.evaporator_thickness_min_range = (option.metallurgy.min_thickness * 0.0393700787).toFixed(4);
							model_values.evaporator_thickness_max_range = (option.metallurgy.max_thickness * 0.0393700787).toFixed(4);
							model_values.evaporator_thickness = (option.metallurgy.default_thickness * 0.0393700787).toFixed(4);
						}
						else{
							model_values.evaporator_thickness_min_range = option.metallurgy.min_thickness;
							model_values.evaporator_thickness_max_range = option.metallurgy.max_thickness;
							model_values.evaporator_thickness = option.metallurgy.default_thickness;
						}

						
					}
				}
			});

			if(model_values.chilled_water_out < 3.499 && model_values.chilled_water_out > 0.99){
				if(model_values.glycol_chilled_water == 0 || model_values.glycol_chilled_water == null){
					model_values.evaporator_thickness = 0.8;
				}
			} 			
		}

		function updateAbsorberOptions(value,thickness_change){
			$('#absorber_material').empty();
			var $el = $("#absorber_material");
			$el.empty(); // remove old options
			$.each(absorber_options, function(key,option) {
				// console.log(option);
				$el.append($("<option></option>").attr("value", option.value).text(option.metallurgy.display_name));
				if(value == option.value){
					model_values.absorber_material_value = value;
					

					if(thickness_change){

						if(metallurgy_unit != 'Millimeter'){
							model_values.absorber_thickness_min_range = (option.metallurgy.min_thickness * 0.0393700787).toFixed(4);
							model_values.absorber_thickness_max_range = (option.metallurgy.max_thickness * 0.0393700787).toFixed(4);
							model_values.absorber_thickness = (option.metallurgy.default_thickness * 0.0393700787).toFixed(4);
						}
						else{
							model_values.absorber_thickness_min_range = option.metallurgy.min_thickness;
							model_values.absorber_thickness_max_range = option.metallurgy.max_thickness;
							model_values.absorber_thickness = option.metallurgy.default_thickness;
						}

						
					}
				}
			});
			
		}

		function updateCondenserOptions(value,thickness_change){
			$('#condenser_material').empty();
			var $el = $("#condenser_material");
			$el.empty(); // remove old options
			$.each(condenser_options, function(key,option) {
				$el.append($("<option></option>").attr("value", option.value).text(option.metallurgy.display_name));
				if(value == option.value){
					model_values.condenser_material_value = value;

					

					if(thickness_change){
						if(metallurgy_unit != 'Millimeter'){
							model_values.condenser_thickness_min_range = (option.metallurgy.min_thickness * 0.0393700787).toFixed(4);
							model_values.condenser_thickness_max_range = (option.metallurgy.max_thickness * 0.0393700787).toFixed(4);
							model_values.condenser_thickness = (option.metallurgy.default_thickness * 0.0393700787).toFixed(4);
						}
						else{
							model_values.condenser_thickness_min_range = option.metallurgy.min_thickness;
							model_values.condenser_thickness_max_range = option.metallurgy.max_thickness;
							model_values.condenser_thickness = option.metallurgy.default_thickness;
						}
						
					}
				}
			});
			
		}


		

		$('input[type=radio][name=glycol]').change(function() {
		    // alert(this.value);
		    model_values.glycol_selected = this.value;
		    updateModelValues('glycol_type_changed');
		});
		$('input:radio[name="region_type"]').change(function() {
			model_values.region_type = $(this).val();
			model_values.model_number = 130;
			
			sendRegionValues();
		});

		// $('#region').change(function() {

		//     model_values.region_name = $("#region").val();
		//     if($(this).val() == 'USA'){
		// 		$("#regionlist").show();
		// 		model_values.region_name = $("#region").val();
 	// 			$("#fouling_ari").html('');
		// 		$("#fouling_factor_ahri").html("AHRI");
		// 		model_values.fouling_factor ="ari";
		// 		model_values.fouling_chilled_water_value = model_values.fouling_ari_chilled;
		// 		model_values.fouling_cooling_water_value = model_values.fouling_ari_cooling;
		// 		foulingFactor('ari');
		// 	}
		// 	else
		// 	{
		// 		//$("#regionlist").hide();
		// 		model_values.region_name = $("#region").val();
		// 		$("#fouling_ari").html("ARI");
		// 		$("#fouling_factor_ahri").html('');
		// 		model_values.fouling_factor ="standard";
		// 		foulingFactor('standard');
		// 	}
		//     sendRegionValues();
		// });

		function sendRegionValues(){
			// var form_values = $("#double_steam_s2").serialize();
			var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
			$.ajax({
				type: "POST",
				url: "{{ url('calculators/double-effect-h2/ajax-calculate-region') }}",
				data: { values : model_values,_token: CSRF_TOKEN},
				success: function(response){
					if(response.status){
						console.log(response.model_values);
						$("#calculate_button").prop('disabled', false);
						model_values = response.model_values;
						castToBoolean();

						loadDefaultValues();
						onRegionChange();
						
					}
					else{
						$("#calculate_button").prop('disabled', true);
						// alert(response.msg);
						$(".showreport").hide();
						$("#errornotes").show();
						$("#errormessage").html(response.msg);
						// console.log(changed_value);
						
					}					
				},
			});
		}


		function onRegionChange(){


			if(model_values.region_type == 1){
				$("#regionlist").hide();
				model_values.region_name ='';
				$("#fouling_ari").html("ARI");
				$("#fouling_factor_ahri").html('');
				model_values.fouling_factor ="standard";
				foulingFactor('standard');
			}
			else
			{	
				if(model_values.region_type == 2){
					//$("#regionlist").show();
					//model_values.region_name = $("#region").val();
					$("#fouling_ari").html('');
					$("#fouling_factor_ahri").html("AHRI");
					model_values.fouling_factor ="ari";
					model_values.fouling_chilled_water_value = model_values.fouling_ari_chilled;
					model_values.fouling_cooling_water_value = model_values.fouling_ari_cooling;
					foulingFactor('ari');
				}
				else
				{
					//$("#regionlist").show();
					//model_values.region_name = $("#region").val();
					$("#fouling_ari").html("ARI");
					$("#fouling_factor_ahri").html('');
					model_values.fouling_factor ="standard";
					foulingFactor('standard');
				}
			}
		}

		$('input[type=radio][name=tube_metallurgy]').change(function() {
		    // alert(this.value);
		    if(this.value == 'non_standard'){
		    	model_values.metallurgy_standard = false;
		    }
		    else{
		    	model_values.metallurgy_standard = true;
		    	updateEvaporatorOptions(chiller_metallurgy_options.eva_default_value,true);
		    	updateAbsorberOptions(chiller_metallurgy_options.abs_default_value,true);
		    	updateCondenserOptions(chiller_metallurgy_options.con_default_value,true);
		    	updateValues();
		    }
		    
		});

		function updateModelValues(input_type){
			var validate = false;
			switch(input_type) {
				case 'model_number':
				model_values.model_number = $("#model_number").val();
				validate = true;
				break;
				case 'capacity':
				var capacity = $("#capacity").val();
				model_values.capacity = capacity;
				validate = inputValidation(capacity,"positive_decimals","capacity_error","Please Enter a Valid Capacity");
				break;
				case 'chilled_water_in':
				model_values.chilled_water_in = $("#chilled_water_in").val();
				validate = inputValidation(model_values.chilled_water_in,"positive_decimals","chilled_water_in_error","Please Enter a Valid Chilled Water In");
				break;	
				case 'chilled_water_out':
				model_values.chilled_water_out = $("#chilled_water_out").val();
				validate = inputValidation(model_values.chilled_water_out,"positive_decimals","chilled_water_out_error","Please Enter a Valid Chilled Water Out");
				break;
				case 'glycol_type_changed':
				validate = true;
				break;	
				case 'glycol_chilled_water':
				model_values.glycol_chilled_water = $("#glycol_chilled_water").val();
				validate = inputValidation(model_values.glycol_chilled_water,"positive_decimals","glycol_chilled_water_error","Please Enter a Valid Glycol Chilled Water");
				break;
				case 'glycol_cooling_water':
				model_values.glycol_cooling_water = $("#glycol_cooling_water").val();
				validate = inputValidation(model_values.glycol_cooling_water,"positive_decimals","glycol_cooling_water_error","Please Enter a Valid Glycol Cooling Water");
				break;
				case 'cooling_water_in':
				model_values.cooling_water_in = $("#cooling_water_in").val();
				validate = inputValidation(model_values.cooling_water_in,"positive_decimals","cooling_water_in_error","Please Enter a Valid Cooling Water In");
				break;
				case 'cooling_water_flow':
				model_values.cooling_water_flow = $("#cooling_water_flow").val();
				validate = inputValidation(model_values.cooling_water_flow,"positive_decimals","cooling_water_flow_error","Please Enter a Valid Cooling Water Flow");
				break;					
				case 'evaporator_tube_type':
				model_values.evaporator_material_value = $("#evaporator_material").val();
				updateEvaporatorOptions(model_values.evaporator_material_value,true);
					// model_values.evaporator_thickness_change = true;
					validate = true;
					break;					
					case 'absorber_tube_type':
					model_values.absorber_material_value = $("#absorber_material").val();
					updateAbsorberOptions(model_values.absorber_material_value,true);
					validate = true;
					break;	
					case 'condenser_tube_type':
					model_values.condenser_material_value = $("#condenser_material").val();
					updateCondenserOptions(model_values.condenser_material_value,true);
					validate = true;
					break;
					case 'evaporator_thickness':
					model_values.evaporator_thickness = $("#evaporator_thickness").val();
					validate = inputValidation(model_values.evaporator_thickness,"positive_decimals","evaporator_thickness_error","Please Enter a Valid Evaporator Thickness");
					break;
					case 'absorber_thickness':
					model_values.absorber_thickness = $("#absorber_thickness").val();
					validate = inputValidation(model_values.absorber_thickness,"positive_decimals","absorber_thickness_error","Please Enter a Valid Absorber Thickness");
					break;
					case 'condenser_thickness':
					model_values.condenser_thickness = $("#condenser_thickness").val();
					validate = inputValidation(model_values.condenser_thickness,"positive_decimals","condenser_thickness_error","Please Enter a Valid Condenser Thickness");
					break;	
					case 'fouling_chilled_value':
					model_values.fouling_chilled_water_value = $("#fouling_chilled_value").val();
					validate = inputValidation(model_values.fouling_chilled_water_value,"positive_decimals","fouling_chilled_value_error","Please Enter a Valid Fouling Chilled Water");
					break;	
					case 'fouling_cooling_value':
					model_values.fouling_cooling_water_value = $("#fouling_cooling_value").val();
					validate = inputValidation(model_values.fouling_cooling_water_value,"positive_decimals","fouling_cooling_value_error","Please Enter a Valid Fouling Cooling Water");
					break;
					case 'hot_water_in':
					model_values.hot_water_in = $("#hot_water_in").val();
					validate = inputValidation(model_values.hot_water_in,"positive_decimals","hot_water_in_error","Please Enter a Valid Hot Water In");
					break;
					case 'hot_water_out':
					model_values.hot_water_out = $("#hot_water_out").val();
					validate = inputValidation(model_values.hot_water_out,"positive_decimals","hot_water_out_error","Please Enter a Valid Hot Water Out");
					break;
					case 'all_work_pr_hw':
					model_values.all_work_pr_hw = $("#all_work_pr_hw").val();
					validate = inputValidation(model_values.all_work_pr_hw,"positive_decimals","all_work_pr_hw_error","Please Enter a Valid AllWorkPrHWUnit");
					break;										
					default:
			    	// code block
			    }
			    changed_value = input_type;

			    if(validate){
			    	sendValues();
			    }
			    else{
			    	$("#calculate_button").prop('disabled', true);
			    }

			}


			function sendValues(){
			// var form_values = $("#double_steam_s2").serialize();
			var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
			$.ajax({
				type: "POST",
				url: "{{ url('calculators/double-effect-h2/ajax-calculate') }}",
				data: { values : model_values,_token: CSRF_TOKEN,changed_value: changed_value},
				success: function(response){
					if(response.status){
						console.log(response.model_values);
						$("#calculate_button").prop('disabled', false);
						model_values = response.model_values;
						castToBoolean();
						updateValues();
						
					}
					else{
						$("#calculate_button").prop('disabled', true);
						// alert(response.msg);
						
						$(".showreport").hide();
						$("#errornotes").show();
						$('#'+changed_value).addClass("box-color");
						$('#'+changed_value).focus();
						$("#errormessage").html(response.msg);
						
						// console.log(changed_value);
						
					}					
				},
			});
		}

		$("#double_steam_h2").submit(function(event) {
			event.preventDefault();
			var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
	  		// console.log(model_values);
	  		$("#ajax-loader").show();
	  		var name = $('#customer_name').val();
	  		var project = $('#project').val();
	  		var phone = $('#phone').val();
	  		$.ajax({
	  			type: "POST",
	  			url: "{{ url('calculators/double-effect-h2/submit-calculate') }}",
	  			data: { values : model_values,_token: CSRF_TOKEN,name: name,project: project,phone: phone},
	  			complete: function(){
	  				$("#ajax-loader").hide();
	  			},
	  			success: function(response){
	  				$("#ajax-loader").show();
	  				if(response.status){
	  					console.log(response.calculation_values);
	  					calculation_values = response.calculation_values;
	  					if(calculation_values.Result == "FAILED"){
	  						$(".showreport").hide();
	  						$("#errornotes").show();
	  						$("#errormessage").html(calculation_values.Notes);
	  					}
	  					else{
	  						var notes = calculation_values.notes;
	  						$("#notes_div").html("");	
	  						for(var i = notes.length - 1; i >= 0; --i)
	  						{
	  							if(!model_values.metallurgy_standard)
	  							{
	  								if (i < 1){
	  									$( "#notes_div" ).append("<p>"+notes[i]+"</p>");
	  								}
	  							}
	  						}

	  						$("#notes_head_div").html('<h4>'+calculation_values.Result+'</h4>');

	  						$('#capacity_span').html(calculation_values.TON);
	  						$('#chilled_water_flow_span').html(calculation_values.ChilledWaterFlow);
	  						$('#chilled_inlet_span').html(calculation_values.TCHW11);
	  						$('#chilled_outlet_span').html(calculation_values.TCHW12);
	  						$('#evaporator_pass').html(calculation_values.EvaporatorPasses);
	  						$('#chilled_wa').html(calculation_values.EvaporatorPasses);
	  						$('#evaporator_pass').html(calculation_values.EvaporatorPasses);
	  						$('#chilled_pressure_loss_span').html((calculation_values.ChilledFrictionLoss).toFixed(2));
	  						$('#cooling_water_flow_span').html(calculation_values.GCW);
	  						$('#cooling_water_inlet_span').html(calculation_values.TCW11);
	  						$('#cooling_water_outlet_span').html(calculation_values.CoolingWaterOutTemperature.toFixed(2));

	  						var absorber_condenser_pass = calculation_values.AbsorberPasses+"/"+calculation_values.CondenserPasses
	  						$('#absorber_pass').html(absorber_condenser_pass);
	  						
	  						$('#cooling_pressure_loss_span').html((calculation_values.CoolingFrictionLoss).toFixed(2));
	  						$('#hot_water_flow_span').html((calculation_values.HotWaterFlow).toFixed(2));

	  						$('#hw_inlet_span').html((calculation_values.hot_water_in));
	  						$('#hw_outlet_span').html((calculation_values.hot_water_out));

	  						$('#generator_passes_span').html((calculation_values.GeneratorPasses));
	  						$('#hw_circuit_pressure_span').html((calculation_values.HotWaterFrictionLoss).toFixed(2));
	  						
	  						
	  						$('#result_span').html(calculation_values.Result);


	  						$("#showreportlist").html(response.report);	

	  						$("#errornotes").hide();
	  						$(".showreport").show();
	  					}
	  				}
	  				else{
	  					$(".showreport").hide();
	  					$("#errornotes").show();
	  					$("#errormessage").html(response.msg);
	  					$("#calculate_button").prop('disabled', true);
	  					
	  				}	
	  			},
	  		});
	  	});

		$( "#reset" ).click(function() {
			
			var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
			$.ajax({
				type: "POST",
				url: "{{ url('calculators/double-effect-h2/reset-calculate') }}",
				data: { model_number : model_values.model_number,values : model_values,_token: CSRF_TOKEN},
				success: function(response){
					if(response.status){
						
						$('.emsg').addClass('hidden');
						model_values = response.model_values;
						evaporator_options = response.evaporator_options;
						absorber_options = response.absorber_options;
						condenser_options = response.condenser_options;
						chiller_metallurgy_options = response.chiller_metallurgy_options;
						castToBoolean();
						
						if(model_values.region_type == 2){
							//console.log("usa selected");
							model_values.fouling_chilled_water_value = model_values.fouling_ari_chilled
							model_values.fouling_cooling_water_value = model_values.fouling_ari_cooling
						} 
						console.log(model_values);
						updateEvaporatorOptions(chiller_metallurgy_options.eva_default_value,model_values.evaporator_thickness_change);
						updateAbsorberOptions(chiller_metallurgy_options.abs_default_value,model_values.absorber_thickness_change);
						updateCondenserOptions(chiller_metallurgy_options.con_default_value,model_values.condenser_thickness_change);
						updateValues();
						$('#capacity').focus();
						$("#calculate_button").prop('disabled', false);
						$(".showreport").hide();
						$("#errornotes").hide();
						$('.box-color').removeClass("box-color");
						
					}
					else{
						$(".showreport").hide();
						$("#errornotes").show();
						$("#errormessage").html("Sorry Something Went Wrong");
					}					
				},
			});

		});

		$( "#show_report" ).click(function() {
			var name = $('#customer_name').val();
			var project = $('#project').val();
			var phone = $('#phone').val();

			if(name == '' || project == '' || phone == ''){
				
				alert("Enter the details");
			}
			else{
				var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
				$.ajax({
					type: "POST",
					url: "{{ url('calculators/double-effect-h2/show-report') }}",
					data: { calculation_values : calculation_values,_token: CSRF_TOKEN,name: name,project: project,phone: phone},
					success: function(response){
						// console.log(response);	
						// $("#exampleModalLong1").modal('toggle');
						// $("#model2").click()
						var wi = window.open();
						$(wi.document.body).html(response.report);					
					},
				});
			}

		});


		$(".save_report" ).click(function() {
			var name = $('#customer_name').val();
			var project = $('#project').val();
			var phone = $('#phone').val();
			var report_type = this.id;
			
			if(name == '' || project == '' || phone == ''){
				
				Swal.fire("Enter the details", "", "error");
			}
			else{
				var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
				$.ajax({
					type: "POST",
					url: "{{ url('calculators/double-effect-h2/save-report') }}",
					data: { calculation_values : calculation_values,_token: CSRF_TOKEN,name: name,project: project,phone: phone,report_type: report_type},
					success: function(response){
						//$("#exampleModalLong1").modal('toggle');
						console.log(response);	
						window.open(response.redirect_url, '_blank');

					},
				});
			}
		});
		function castToBoolean(){

			model_values.metallurgy_standard = getBoolean(model_values.metallurgy_standard);

			model_values.evaporator_thickness_change = getBoolean(model_values.evaporator_thickness_change);
			model_values.absorber_thickness_change = getBoolean(model_values.absorber_thickness_change);
			model_values.condenser_thickness_change = getBoolean(model_values.condenser_thickness_change);
			model_values.fouling_chilled_water_checked = getBoolean(model_values.fouling_chilled_water_checked);
			model_values.fouling_cooling_water_checked = getBoolean(model_values.fouling_cooling_water_checked);
			model_values.fouling_chilled_water_disabled = getBoolean(model_values.fouling_chilled_water_disabled);
			model_values.fouling_cooling_water_disabled = getBoolean(model_values.fouling_cooling_water_disabled);
			model_values.fouling_chilled_water_value_disabled = getBoolean(model_values.fouling_chilled_water_value_disabled);
			model_values.fouling_cooling_water_value_disabled = getBoolean(model_values.fouling_cooling_water_value_disabled);
		}
		function getBoolean(value){
			switch(value){
				case true:
				case "true":
				case 1:
				case "1":
				case "on":
				case "yes":
				return true;
				default: 
				return false;
			}
		}
	</script>
	<script>
		$(document).ready(function(){
			$('.ss-steam-label input').tooltip();
		});
	</script>
	@endsection











