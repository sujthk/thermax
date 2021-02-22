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
		 padding: 8px 0;
		 background:none;
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
		 margin-bottom: 16%;
         font-weight: 600;
         color: #656565;
	 }

	 .ss-steam-label .page-wrapper .page-header {
		 margin-bottom: 0px;
	 }
	 
	 .ss-steam-label .card-header{
		 margin-bottom: 4px;
		 margin-top: 2px;
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
		 
	}
	 
	.contact-submit{
		padding: 4px 15px;
		background: #e10010;
		border: none;
		 
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
	.padd-2 input,select{
		 background:none;
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
		height: 89vh;
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
			<form id="l5_series" method="post" enctype="multipart/form-data">
				{{ csrf_field() }}
				<div class="row">
					<div class="col-md-7 padd-2">
					 	<div class="row">
						  	<div class="col-md-6">
								<div class="page-header-title">
								 <h4>L5 series</h4>
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
												 <label>{{ $language_datas['model'] }}</label>
											</div>
											<div class="col-lg-2">
												<select name="model_number" id="model_number" class="form-control" onchange="updateModelValues('model_number')">
													 <option value="60">L5 D3</option>

												</select>
											</div>
											<div class="col-lg-1">

											</div>

											<div class="col-lg-3">
												 <label>{{ $language_datas['capacity'] }}</label></div>
											<div class="col-lg-2">          
												<input id="capacity" name="capacity" type="text" value="" onchange="updateModelValues('capacity')" class="form-control">

												<!-- <span class="messages emsg hidden" id="capacity_error">
													 <p class="text-danger error">Please Enter a Valid Capacity</p>
												</span> -->
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

												<!-- <span class="messages emsg hidden" id="chilled_water_in_error">
													 <p class="text-danger error">Please Enter a Valid Chilled Water In</p>
												</span> -->
											</div>
											<div class="col-lg-1">
												<label>({{ $units_data[$unit_set->TemperatureUnit] }})</label>
											</div>
										 
											<div class="col-lg-3">
												<label>{{ $language_datas['chilled_water_out'] }} </label>
											</div>
											<div class="col-lg-2">
												<input type="text" class="form-control min_chilled_water_out" id="chilled_water_out" name="chilled_water_out" onchange="updateModelValues('chilled_water_out')" value="" data-placement="bottom" title="">
												<!-- <span class="messages emsg hidden" id="chilled_water_out_error">
													 <p class="text-danger error">Please Enter a Valid Chilled Water Out</p>
												 </span> -->
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
								<label>{{ $language_datas['cooling_water_in'] }} </label>
							</div>
							<div class="col-lg-2">
								 <input type="text" value="" onchange="updateModelValues('cooling_water_in')" name="cooling_water_in" id="cooling_water_in" class="form-control cooling_water_in_range" data-toggle="tooltip" data-placement="bottom" data-original-title>

								 <!-- <span class="messages emsg hidden" id="cooling_water_in_error">
									 <p class="text-danger error">Please Enter a Valid Cooling Water In</p>
								 </span> -->
							</div>
							<div class="col-lg-1">
								<label>({{ $units_data[$unit_set->TemperatureUnit] }})</label>
							</div>
						
							<div class="col-lg-3">
								<label>{{ $language_datas['cooling_water_flow'] }}</label>
							</div>
							<div class="col-lg-2">

								<input type="text" name="cooling_water_flow" onchange="updateModelValues('cooling_water_flow')" id="cooling_water_flow" value="" class="form-control cooling_water_ranges " data-placement="bottom" data-original-title>

								<!-- <span class="messages emsg hidden" id="cooling_water_flow_error">
									<p class="text-danger error">Please Enter a Valid Cooling Water Flow</p>
								</span> -->
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
									 <div class="col-md-2">
										 <label>{{ $language_datas['chilled_water'] }} </label>
									 </div>
									<div class="col-md-2">
										 <input type="text" name="glycol_chilled_water" id="glycol_chilled_water" value="0" onchange="updateModelValues('glycol_chilled_water')" value="" class="form-control">

										 <!-- <span class="messages emsg hidden" id="glycol_chilled_water_error">
											 <p class="text-danger error">Please Enter a Valid Glycol Chilled Water</p>
										 </span> -->
									</div>
							
									<div class="col-md-2">
										 <label>{{ $language_datas['cooling_water'] }} </label>
									</div>
									<div class="col-md-2">
										 <input type="text" name="glycol_cooling_water" id="glycol_cooling_water" value="0" onchange="updateModelValues('glycol_cooling_water')" class="form-control">

										 <!-- <span class="messages emsg hidden" id="glycol_cooling_water_error">
											 <p class="text-danger error">Please Enter a Valid Glycol Cooling Water</p>
										 </span> -->
									</div>
									<div class="col-md-2">
										 <label>{{ $language_datas['hot_water_glycol'] }} </label>
									</div>
									<div class="col-md-2">
										 <input type="text" name="hot_water_glycol" id="hot_water_glycol" value="0" onchange="updateModelValues('hot_water_glycol')" class="form-control">

										 <!-- <span class="messages emsg hidden" id="glycol_cooling_water_error">
											 <p class="text-danger error">Please Enter a Valid Glycol Cooling Water</p>
										 </span> -->
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

												<!-- <span class="messages emsg hidden" id="evaporator_thickness_error">
												 <p class="text-danger error">Please Enter a Valid Evaporator Thickness</p>
											 	</span> -->
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

												<!-- <span class="messages emsg hidden" id="absorber_thickness_error">
													 <p class="text-danger error">Please Enter a Valid Absorber Thickness</p>
												</span> -->
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
	                            <p> <label class=" col-form-label">{{ $language_datas['condenser'] }}</label></p>
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
												<!-- <span class="messages emsg hidden" id="condenser_thickness_error">
													 <p class="text-danger error">Please Enter a Valid Condenser Thickness</p>
												</span> -->
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
	                            <p> <label class=" col-form-label">{{ $language_datas['generator_tube'] }}</label></p>
								<div class="row">
									<div class="col-lg-12">		
										<select name="condenser_material" id="condenser_material" onchange="updateModelValues('condenser_tube_type');" class="form-control metallurgy_standard">
											 @foreach($condenser_options as $condenser_option)
											 <option value="{{ $condenser_option->value }}">{{ $condenser_option->metallurgy->display_name }}</option>
											 @endforeach
										</select>
									</div>
									
	                            </div>
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

									 	<!-- <span class="messages emsg hidden" id="fouling_chilled_value_error">
										 <p class="text-danger error">Please Enter a Valid Fouling Chilled Water</p>
									 	</span> -->
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

									 	<!-- <span class="messages emsg hidden" id="fouling_cooling_value_error">
										 <p class="text-danger error">Please Enter a Valid Fouling Cooling Water</p>
									 	</span> -->
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
									 <h5>{{ $language_datas['hot_water'] }}</h5>
								</div>
							</div>
							<div class="col-md-12">
								<div class="row">
									<div class="col-lg-2">
										 <label>{{ $language_datas['hot_water_flow'] }} : </label>
									</div>
									<div class="col-lg-2">
										<input type="text" name="hot_water_flow" id="hot_water_flow" onchange="updateModelValues('hot_water_flow')" value="" class="form-control hot_water_flow_range" data-placement="bottom" title="3.5-10">
									</div>
									<div class="col-lg-2">
										 <label>({{ $units_data[$unit_set->FlowRateUnit] }})</label>
									</div>

									<div class="col-lg-2">
										 <label>{{ $language_datas['hot_water_in'] }} : </label>
									</div>
									<div class="col-lg-2">
										<input type="text" name="hot_water_in" id="hot_water_in" onchange="updateModelValues('hot_water_in')" value="" class="form-control " data-placement="bottom" title="3.5-10">
									</div>
									<div class="col-lg-2">
										 <label>({{ $units_data[$unit_set->TemperatureUnit] }})</label>
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



@endsection











