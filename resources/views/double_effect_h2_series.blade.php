 @extends('layouts.app') 

 @section('styles')	
 <!-- Data Table Css -->
 <meta name="csrf-token" content="{{ csrf_token() }}" />
 <style type="text/css">
 	.emsg{
 		color: red;
 	}
 	.hidden {
 		display: none;
 	}
 	.border-red{
 		border-color: #bc291a !important;
 	}

 	.max-calculator .form-control, .max-calculator select.form-control{
 		height: 26px !important;
 		padding: 3px .5rem;
 	}
 	.max-calculator{
 		background: #fff;
 	}

 	.max-calculator .card-header{
 		margin-bottom: 5px;
 		padding: 8px;
 	}
 	.max-calculator .card-header h5{
 		font-size: 15px;
 		font-weight: 700;
 	}
 	.modal-scrol{
 		overflow-y: scroll;
 		max-height: 180px;
 	}
 	.contact-submit{
 		margin-bottom: 10px;
 		cursor: pointer;
 	}

 	.model-two input::placeholder{
 		color: #fff;
 	}

 	.summary-head{
 		padding: 2rem 0 0 0;
 	}
 	.model-two h5.modal-title{
 		color: #fff;
 	}
 	.model-one h5.modal-title{
 		color: #fff;
 	}
 	.model-open{
 		overflow: hidden;
 	}
 	.modal{
 		overflow-x: hidden;
 		overflow-y: scroll;
 	}
 </style>
 @endsection

 @section('content')
 <div class="main-body">
 	<div class="page-wrapper max-calculator">
 		<div class="page-header">
 			<div class="page-header-title">
 				<h4>Double Effect H2 Series</h4>
 			</div>
 			<div class="page-header-breadcrumb">
 				<ul class="breadcrumb-title">
 					<li class="breadcrumb-item">
 						<a href="{{ url('dashboard') }}">
 							<i class="icofont icofont-home"></i>
 						</a>
 					</li>
 					<li class=""><a href="#!">Double Effect H2 Series</a>
 					</li>
 				</ul>
 			</div>
 		</div>
 		<div class="page-body">
 			<form id="double_steam_s2" method="post" enctype="multipart/form-data">
 				{{ csrf_field() }}
 				<div class="row">	
 					<div class="col-md-6">
 						<!-- Basic Form Inputs card start -->
 						<div class="">
 							<div class="modl-title">
 								<div class="row">
 									<div class="col-lg-4">
 										<label>Model</label>
 									</div>
 									<div class="col-lg-5">
 										<select name="model_number" id="model_number" class="form-control" onchange="updateModelValues('model_number')" >
 											<option  value="130">S2 C3</option>
 											<option value="160">S2 C4</option>
 											<option value="210">S2 D1</option>
 											<option value="250">S2 D2</option>
 											<option value="310">S2 D3</option>
 											<option value="350">S2 D4</option>
 											<option value="410">S2 E1</option>
 											<option value="470">S2 E2</option>
 											<option value="530">S2 E3</option>
 											<option value="580">S2 E4</option>
 											<option value="630">S2 E5</option>
 											<option value="710">S2 E6</option>
 											<option value="760">S2 F1</option>
 											<option value="810">S2 F2</option>
 											<option value="900">S2 F3</option>
 											<option value="1010">S2 G1</option>
 											<option value="1130">S2 G2</option>
 											<option value="1260">S2 G3</option>
 											<option value="1380">S2 G4</option>
 											<option value="1560">S2 G5</option>
 											<option value="1690">S2 G6</option>
 											<option value="1890">S2 H1</option>
 											<option value="2130">S2 H2</option>
 											<option value="2270">S2 J1</option>
 											<option value="2560">S2 J2</option>
 										</select>
 									</div>
 									<div class="col-lg-3">
 										<label id="model_name"></label>
 									</div>

 									<div class="col-lg-4">
 										<label>Capacity</label></div>
 										<div class="col-lg-5">
 											<input id="capacity" name="capacity" type="text" value="" onchange="updateModelValues('capacity')" class="form-control">

 											<span class="messages emsg hidden" id="capacity_error"><p class="text-danger error">Please Enter a Valid Capacity</p></span>
 										</div>
 										<div class="col-lg-3">
 											<label>({{ $units_data[$unit_set->CapacityUnit] }})</label>
 										</div>
 									</div>

 								</div>
 								<div class="card-header">
 									<h5>Chilled Water</h5>
 								</div>
 								<div class="">
 									<div class="row">
 										<div class="col-lg-4">
 											<label>Water In</label>
 										</div>
 										<div class="col-lg-5">
 											<input type="text" id="chilled_water_in" name="chilled_water_in" onchange="updateModelValues('chilled_water_in')" value="" class="form-control">

 											<span class="messages emsg hidden" id="chilled_water_in_error"><p class="text-danger error">Please Enter a Valid Chilled Water In</p></span>
 										</div>
 										<div class="col-lg-3">
 											<label>({{ $units_data[$unit_set->TemperatureUnit] }})</label>
 										</div>
 									</div>
 									<div class="row">
 										<div class="col-lg-4">
 											<label>Water Out (min <span id="min_chilled_water_out">0</span>)</label>
 										</div>
 										<div class="col-lg-5">
 											<input type="text" class="form-control" id="chilled_water_out" name="chilled_water_out" onchange="updateModelValues('chilled_water_out')" value="">

 											<span class="messages emsg hidden" id="chilled_water_out_error"><p class="text-danger error">Please Enter a Valid Chilled Water Out</p></span>
 										</div>
 										<div class="col-lg-3">
 											<label>({{ $units_data[$unit_set->TemperatureUnit] }})</label>
 										</div>
 									</div>

 								</div>
 							</div>    
 						</div> 
 						<div class="col-md-6">
 							<div class="">
 								<div class="card-header">
 									<h5>Tube Metallurgy</h5>
 								</div>
 								<div class="">
 									<div class="row">                                		
 										<div class="form-radio col-12">
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
 										<div class="col-md-6">                                      
 											<label>Material</label>
 										</div>
 										<div class="col-md-6">      
 											<label>Thickness ({{ $units_data[$unit_set->LengthUnit] }})</label>
 										</div>
 									</div>
 									<div class="row">
 										<div class="col-lg-4">
 											<label class="">Evaporator</label>
 										</div>
 										<div class="col-lg-4">
 											<select name="evaporator_material" id="evaporator_material" onchange="updateModelValues('evaporator_tube_type');" class="form-control metallurgy_standard">
 												@foreach($evaporator_options as $evaporator_option)
 												<option value="{{ $evaporator_option->value }}">{{ $evaporator_option->metallurgy->display_name }}</option>
 												@endforeach
 											</select>
 										</div>
 										<div class="col-lg-4">
 											<input type="text" name="evaporator_thickness" id="evaporator_thickness" onchange="updateModelValues('evaporator_thickness')" value="" class="form-control metallurgy_standard">

 											<span class="messages emsg hidden" id="evaporator_thickness_error"><p class="text-danger error">Please Enter a Valid Evaporator Thickness</p></span>
 											<span class="metallurgy_standard_span" id="evaporator_range"></span>
 										</div>

 										<div class="col-lg-4">
 											<label class=" col-form-label">Absorber</label>
 										</div>
 										<div class="col-lg-4">
 											<select name="absorber_material" id="absorber_material" onchange="updateModelValues('absorber_tube_type');" class="form-control metallurgy_standard">
 												@foreach($absorber_options as $absorber_option)
 												<option value="{{ $absorber_option->value }}">{{ $absorber_option->metallurgy->display_name }}</option>
 												@endforeach
 											</select>
 										</div>
 										<div class="col-lg-4">
 											<input type="text" name="absorber_thickness" id="absorber_thickness" onchange="updateModelValues('absorber_thickness')" value="" class="form-control metallurgy_standard">

 											<span class="messages emsg hidden" id="absorber_thickness_error"><p class="text-danger error">Please Enter a Valid Absorber Thickness</p></span>
 											<span class="metallurgy_standard_span" id="absorber_range"></span>
 										</div>

 										<div class="col-lg-4">
 											<label class=" col-form-label">Condenser</label>
 										</div>
 										<div class="col-lg-4">
 											<select name="condenser_material" id="condenser_material" onchange="updateModelValues('condenser_tube_type');" class="form-control metallurgy_standard">
 												@foreach($condenser_options as $condenser_option)
 												<option value="{{ $condenser_option->value }}">{{ $condenser_option->metallurgy->display_name }}</option>
 												@endforeach
 											</select>
 										</div>
 										<div class="col-lg-4">
 											<input type="text" name="condenser_thickness" id="condenser_thickness"  onchange="updateModelValues('condenser_thickness')" value="" class="form-control metallurgy_standard">
 											<span class="messages emsg hidden" id="condenser_thickness_error"><p class="text-danger error">Please Enter a Valid Condenser Thickness</p></span>
 											<span class="metallurgy_standard_span" id="condenser_range"></span>
 										</div>

 									</div>    	
 								</div>
 							</div>    
 						</div>
 						<div class="col-md-6">
 							<div class="">
 								<div class="card-header">
 									<h5>Cooling Water</h5>
 								</div>
 								<div class="">
 									<div class="row">
 										<div class="col-lg-4">
 											<label>Water In (<span id="cooling_water_in_range" ></span>)</label>
 										</div>
 										<div class="col-lg-5">
 											<input type="text" value="" onchange="updateModelValues('cooling_water_in')" name="cooling_water_in" id="cooling_water_in" class="form-control">

 											<span class="messages emsg hidden" id="cooling_water_in_error"><p class="text-danger error">Please Enter a Valid Cooling Water In</p></span>
 										</div>
 										<div class="col-lg-3">
 											<label>({{ $units_data[$unit_set->TemperatureUnit] }})</label>
 										</div>
 									</div>
 									<div class="row">
 										<div class="col-lg-4">
 											<label>Water Flow</label>
 										</div>
 										<div class="col-lg-5">
 											<input type="text" name="cooling_water_flow" onchange="updateModelValues('cooling_water_flow')" id="cooling_water_flow" value="" class="form-control">

 											<span class="messages emsg hidden" id="cooling_water_flow_error"><p class="text-danger error">Please Enter a Valid Cooling Water Flow</p></span>
 										</div>
 										<div class="col-lg-3">
 											<label>({{ $units_data[$unit_set->FlowRateUnit] }})</label>
 										</div>
 									</div>
 									<div class="row">
 										<label class="col-sm-12">Available Range(s) : <span id="cooling_water_ranges"></span></label>
 									</div>
 								</div>
 							</div>    
 						</div>
 						<div class="col-md-6">
 							<div class="">
 								<div class="card-header">
 									<h5>Fouling Factor</h5>
 								</div>
 								<div class="">
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
 													<i class="helper"></i>ARI
 												</label>
 											</div>
 										</div>    
 									</div>
 									<div class="row">                                    	
 										<div class="col-lg-4 checkbox-fade fade-in-primary">
 											<label>
 												<input type="checkbox" class="fouling_standard" name="fouling_chilled_water" id="fouling_chilled_water" value="">
 												<span class="cr">
 													<i class="cr-icon icofont icofont-ui-check txt-primary"></i>
 												</span><span>Chilled Water</span><br><span id="fouling_chilled_min" ></span>
 											</label>
 										</div>

 										<div class="col-lg-4">
 											<input type="text" name="fouling_chilled_value" id="fouling_chilled_value" onchange="updateModelValues('fouling_chilled_value')"	 class="form-control fouling_standard">

 											<span class="messages emsg hidden" id="fouling_chilled_value_error"><p class="text-danger error">Please Enter a Valid Fouling Chilled Water</p></span>
 										</div>
 										<div class="col-lg-4">
 											<label>({{ $units_data[$unit_set->FoulingFactorUnit] }})</label>
 										</div> 
 									</div>
 									<div class="row">                                    	
 										<div class="col-lg-4 checkbox-fade fade-in-primary">
 											<label>
 												<input type="checkbox" class="fouling_standard" name="fouling_cooling_water" id="fouling_cooling_water" value="">
 												<span class="cr">
 													<i class="cr-icon icofont icofont-ui-check txt-primary"></i>
 												</span><span>Cooling Water</span><br><span id="fouling_cooling_min" ></span>
 											</label>
 										</div>

 										<div class="col-lg-4">
 											<input type="text" name="fouling_cooling_value" id="fouling_cooling_value" onchange="updateModelValues('fouling_cooling_value')" class="form-control fouling_standard">

 											<span class="messages emsg hidden" id="fouling_cooling_value_error"><p class="text-danger error">Please Enter a Valid Fouling Cooling Water</p></span>
 										</div>
 										<div class="col-lg-4">
 											<label>({{ $units_data[$unit_set->FoulingFactorUnit] }})</label>
 										</div>
 									</div>   	
 								</div>
 							</div>    
 						</div>
 						<div class="col-md-6">
 							<div class="">
 								<div class="card-header">
 									<h5>Glycol Content % (By Vol)</h5>
 								</div>
 								<div class="">
 									<div class="row">                                		
 										<div class="col-12 form-radio">

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
 									</div>
 									<div class="row">
 										<div class="col-lg-4">
 											<label>Chilled Water </label>
 										</div>
 										<div class="col-md-5">
 											<input type="text" name="glycol_chilled_water" id="glycol_chilled_water" onchange="updateModelValues('glycol_chilled_water')" value="" class="form-control">

 											<span class="messages emsg hidden" id="glycol_chilled_water_error"><p class="text-danger error">Please Enter a Valid Glycol Chilled Water</p></span>
 										</div>
 									</div> 
 									<div class="row">
 										<div class="col-lg-4">
 											<label>Cooling Water </label>
 										</div>
 										<div class="col-md-5">
 											<input type="text" name="glycol_cooling_water" id="glycol_cooling_water" value="" onchange="updateModelValues('glycol_cooling_water')" class="form-control">

 											<span class="messages emsg hidden" id="glycol_cooling_water_error"><p class="text-danger error">Please Enter a Valid Glycol Cooling Water</p></span>
 										</div>
 									</div>   	
 								</div>
 							</div>    
 						</div>
 						<div class="col-md-6">
 							<div class="">
 								<div class="card-header">
 									<h5>Steam</h5>
 								</div>
 								<div class="">
 									<div class="row">
 										<div class="col-lg-4">
 											<label>Pressure : (<span id="steam_pressure_range"></span>)</label>
 										</div>
 										<div class="col-lg-4">
 											<input type="text" name="steam_pressure" id="steam_pressure" onchange="updateModelValues('steam_pressure')" value="" class="form-control">

 											<span class="messages emsg hidden" id="steam_pressure_error"><p class="text-danger error">Please Enter a Valid Steam Pressure</p></span>
 										</div>
 										<div class="col-lg-4">
 											<label>({{ $units_data[$unit_set->PressureUnit] }})</label>
 										</div>
 									</div>   	
 								</div>
 							</div>    
 						</div>
 						<div class="col-sm-12">    
 							<div class="row">		                    	
 								<div class="col-md-12 text-center">
 									<input type="submit" name="submit_value" value="Calculate" id="calculate_button" class="btn btn-primary m-b-0" >
 									<input type="button" name="reset" id="reset" value="Reset" class="btn btn-primary m-b-0">
 								</div>
 							</div>
 						</div>  
 					</div>      
 				</form>            
 			</div>
 		</div>
 	</div>



 	<!-- Modal one -->
 	<div class="modal fade model-one" id="exampleModalLong" tabindex="-1" role="dialog" aria-labelledby="exampleModalLongTitle" aria-hidden="true">
 		<div class="modal-dialog modal-dialog-centered" role="document">
 			<div class="modal-content">
 				<div class="modal-header">
 					<h5 class="modal-title" id="exampleModalLongTitle"> Notes </h5>
 					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
 						<span aria-hidden="true">&times;</span>
 					</button>
 				</div>
 				<div class="modal-body">
 					<div id="notes_div" class="modal-scrol">

 					</div>
 				</div>
 				<div class="modal-footer">
 					<button type="button" class="btn btn-secondary ok-snd" data-toggle="modal" data-target="#exampleModalLong1"> ok </button>       
 				</div>
 			</div>
 		</div>
 	</div>


 	<!-- Modal -->
 	<div class="modal fade model-two" id="exampleModalLong1" tabindex="-1" role="dialog" aria-labelledby="exampleModalLongTitle1" aria-hidden="true">
 		<div class="modal-dialog modal-dialog-centered" role="document">
 			<div class="modal-content">
 				<div class="modal-header">
 					<h5 class="modal-title" id="exampleModalLongTitle1"><span id="result_span"></span> Design</h5>
 					<button type="button" id="model2" class="close" data-dismiss="modal" aria-label="Close">
 						<span aria-hidden="true">&times;</span>
 					</button>
 				</div>
 				<div class="modal-body">
 					<div class="row">
 						<form id="report_form" method="post" enctype="multipart/form-data">
 							<div class="col-md-8">
 								<div class="row">
 									<div class="col-md-12">
 										<input type="text" class="form-control" required id="customer_name" placeholder="Customer" name="customer_name">
 									</div>
 									<div class="col-md-12">
 										<input type="text" class="form-control" required id="project" placeholder="Project" name="project">
 									</div>

 									<div class="col-md-12">
 										<input type="text" class="form-control" required id="phone" placeholder="Enquiry Number" name="phone">
 									</div>
 								</div>
 							</div>
 							<div class="col-md-4">
 								<div class="row">
 									<div class="col-12">
 										<input type="button" name="show_report" id="show_report" value="Show Report"  class="contact-submit">
 									</div>
 									<div class="col-12">
 										<input type="button" name="submit" id="save_word" value="Export to Word" class="contact-submit save_report"> 
 									</div>
 									<div class="col-12">
 										<input type="button" name="submit" id="save_pdf" value="Export to Pdf" class="contact-submit save_report">
 									</div>
 								</div>
 							</div>
 						</form>
 					</div>

 					<div class="summary-head">
 						<h4> Summary : </h4>
 					</div>
 					<div class="table-responsive">
 						<table class="table table-bordered">
 							<thead>
 								<tr>
 									<th scope="col">Item</th>
 									<th scope="col">Unit</th>
 									<th scope="col">Value</th>  
 								</tr>
 							</thead>
 							<tbody>
 								<tr>    
 									<td> Capacity</td>
 									<td> {{ $units_data[$unit_set->CapacityUnit] }}</td>
 									<td><span id="capacity_span"></span> </td>
 								</tr>
 								<tr>   
 									<td> Chilled water flow</td>
 									<td> {{ $units_data[$unit_set->FlowRateUnit] }}</td>
 									<td> <span id="chilled_water_flow_span"></span></td>
 								</tr>
 								<tr>   
 									<td> Chilled water inlet temperature</td>
 									<td> {{ $units_data[$unit_set->TemperatureUnit] }}</td>
 									<td> <span id="chilled_inlet_span"></span> </td>
 								</tr>
 								<tr>    
 									<td> Chilled water outlet temperature</td>
 									<td> {{ $units_data[$unit_set->TemperatureUnit] }}</td>
 									<td> <span id="chilled_outlet_span"></span> </td>
 								</tr>
 								<tr>     
 									<td> Evaporate passes</td>
 									<td> </td>
 									<td> <span id="evaporator_pass"></span> </td>
 								</tr>
 								<tr>     
 									<td> Chilled water circuit pressure loss </td>
 									<td> {{ $units_data[$unit_set->PressureDropUnit] }}</td>
 									<td> <span id="chilled_pressure_loss_span"></span> </td>
 								</tr>
 								<tr>     
 									<td> Cooling water flow </td>
 									<td> {{ $units_data[$unit_set->FlowRateUnit] }}</td>
 									<td> <span id="cooling_water_flow_span"></span> </td>
 								</tr>
 								<tr>     
 									<td> Cooling water inlet temperature </td>
 									<td> {{ $units_data[$unit_set->TemperatureUnit] }}</td>
 									<td> <span id="cooling_water_inlet_span"></span></td>
 								</tr>
 								<tr>     
 									<td> Cooling water outlet temperature </td>
 									<td> {{ $units_data[$unit_set->TemperatureUnit] }}</td>
 									<td> <span id="cooling_water_outlet_span"></span></td>
 								</tr>
 								<tr>     
 									<td> Absorber / Condenser Passes </td>
 									<td> </td>
 									<td> <span id="absorber_pass"></span> </td>
 								</tr>
 								<tr>     
 									<td> Cooling water circuit pressure loss </td>
 									<td> {{ $units_data[$unit_set->PressureDropUnit] }}</td>
 									<td> <span id="cooling_pressure_loss_span"></span> </td>
 								</tr>
 								<tr>     
 									<td> Steam pressure </td>
 									<td> {{ $units_data[$unit_set->PressureUnit] }}</td>
 									<td> <span id="steam_pressure_span"></span> </td>
 								</tr>
 								<tr>     
 									<td> Steam consumption </td>
 									<td> {{ $units_data[$unit_set->SteamConsumptionUnit] }}</td>
 									<td> <span id="steam_consumption_span"></span> </td>
 								</tr>
 							</tbody>
 						</table>
 					</div>
 				</div>
 			</div>      
 		</div>
 	</div>






 	@endsection

 	@section('scripts')	

 	<script>
 		$(document).ready(function(){


 			$(".ok-snd").click(function(){ 
 				$("#exampleModalLong").modal('hide'); 
 				$("body").addClass("model-open");
 				$('#exampleModalLong1').modal({
 					backdrop: 'static',
 					keyboard: false
 				});

 			});
 			$('#exampleModalLong, #exampleModalLong1').on('hide.bs.modal',function(e){
 				$('body').css('padding-right','0');
 			});

 //   $("#show_report").click(function(){ 
 //  		var wi = window.open();
 //  		var html = $('#exampleModalLong2').html();
 //  		$(wi.document.body).html(html);

	// });



});

</script>


<script type="text/javascript">

	var model_values = {!! json_encode($default_values) !!};
	var evaporator_options = {!! json_encode($evaporator_options) !!};
	var absorber_options = {!! json_encode($absorber_options) !!};
	var condenser_options = {!! json_encode($condenser_options) !!};
	var chiller_metallurgy_options = {!! json_encode($chiller_metallurgy_options) !!};
	var changed_value = "";
	var calculation_values;
	var metallurgy_unit = "{!! $unit_set->LengthUnit !!}";
	$( document ).ready(function() {
		    // swal("Hello world!");
		    
		    loadDefaultValues();

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

	function inputValidation(value,validation_type,input_name){
			// console.log(value);
			var positive_decimal=/^(0|[1-9]\d*)(\.\d+)?$/;
			var negative_decimal=/^-?(0|[1-9]\d*)(\.\d+)?$/;
			var value_input = input_name.replace('_error', '')
			if(validation_type == "positive_decimals"){
				if (!value.match(positive_decimal)) {
                  // there is a mismatch, hence show the error message
                  $('#'+input_name).removeClass('hidden');
                  $('#'+input_name).show();
                  $('#'+value_input).focus();
              	}
              else{
                    // else, do not display message
                    $('#'+input_name).addClass('hidden');
                    return true;
                }
            }

            if(validation_type == "decimals"){
            	if (!value.match(negative_decimal)) {
                  // there is a mismatch, hence show the error message
                  $('.emsg').removeClass('hidden');
                  $('.emsg').show();
              }
              else{
                    // else, do not display message
                    $('.emsg').addClass('hidden');
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
        	$('#min_chilled_water_out').html(model_values.min_chilled_water_out);
        	var cooling_water_in_range = model_values.cooling_water_in_min_range+" - "+model_values.cooling_water_in_max_range;
        	$('#cooling_water_in_range').html(cooling_water_in_range);
        	$('#cooling_water_in').val(model_values.cooling_water_in);
        	$('#cooling_water_flow').val(model_values.cooling_water_flow);
        	var cooling_water_ranges = getCoolingWaterRanges(model_values.cooling_water_ranges);

        	$('#cooling_water_ranges').html(cooling_water_ranges);
			// $("#glycol_none").attr('disabled', model_values.glycol_none);
			$('#glycol_chilled_water').val(model_values.glycol_chilled_water);
			$('#glycol_cooling_water').val(model_values.glycol_cooling_water);
			
			$('#evaporator_thickness').val(model_values.evaporator_thickness);
			$('#absorber_thickness').val(model_values.absorber_thickness);
			$('#condenser_thickness').val(model_values.condenser_thickness);
			$("#evaporator_material").val(model_values.evaporator_material_value);
			$("#absorber_material").val(model_values.absorber_material_value);
			$("#condenser_material").val(model_values.condenser_material_value);
			$("#steam_pressure").val(model_values.steam_pressure);
			var steam_pressure_range = model_values.steam_pressure_min_range+" - "+model_values.steam_pressure_max_range;
			$('#steam_pressure_range').html(steam_pressure_range);
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

			}else{
				$("#tube_metallurgy_non_standard").prop('checked', true);
				// $("#tube_metallurgy_standard").prop('disabled', true);
				$(".metallurgy_standard").prop('disabled', false);
				var evaporator_range = "("+model_values.evaporator_thickness_min_range+" - "+model_values.evaporator_thickness_max_range+")";
				$("#evaporator_range").html(evaporator_range);
				var absorber_range = "("+model_values.absorber_thickness_min_range+" - "+model_values.absorber_thickness_max_range+")";
				$("#absorber_range").html(absorber_range);
				var condenser_range = "("+model_values.condenser_thickness_min_range+" - "+model_values.condenser_thickness_max_range+")";
				$("#condenser_range").html(condenser_range);
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
			} else {
				$(".metallurgy_standard").prop('disabled', false);
				var evaporator_range = "("+model_values.evaporator_thickness_min_range+" - "+model_values.evaporator_thickness_max_range+")";
				$("#evaporator_range").html(evaporator_range);
				var absorber_range = "("+model_values.absorber_thickness_min_range+" - "+model_values.absorber_thickness_max_range+")";
				$("#absorber_range").html(absorber_range);
				var condenser_range = "("+model_values.condenser_thickness_min_range+" - "+model_values.condenser_thickness_max_range+")";
				$("#condenser_range").html(condenser_range);
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
				$("#fouling_chilled_min").html("");
				$("#fouling_cooling_min").html("");
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
		    	$("#fouling_chilled_min").html(">"+model_values.fouling_non_chilled);
		    	$("#fouling_cooling_min").html(">"+model_values.fouling_non_cooling);

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
		    	$("#fouling_chilled_min").html(">"+model_values.fouling_ari_chilled);
		    	$("#fouling_cooling_min").html(">"+model_values.fouling_ari_cooling);
		    	$("#fouling_chilled_value").val(model_values.fouling_chilled_water_value);
		    	$("#fouling_cooling_value").val(model_values.fouling_cooling_water_value);
		    }
		}

		function getCoolingWaterRanges(cooling_water_ranges){
			var range_values = "";
			// console.log(cooling_water_ranges);
			if(!$.isArray(cooling_water_ranges)){
				var cooling_water_ranges = cooling_water_ranges.split(",");
			}
			
			for (var i = 0; i < cooling_water_ranges.length; i+=2) {
				range_values += "("+cooling_water_ranges[i]+" - "+cooling_water_ranges[i+1]+")<br>";
			}

			return range_values;
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
				validate = inputValidation(capacity,"positive_decimals","capacity_error");
				break;
				case 'chilled_water_in':
				model_values.chilled_water_in = $("#chilled_water_in").val();
				validate = inputValidation(model_values.chilled_water_in,"positive_decimals","chilled_water_in_error");
				break;	
				case 'chilled_water_out':
				model_values.chilled_water_out = $("#chilled_water_out").val();
				validate = inputValidation(model_values.chilled_water_out,"positive_decimals","chilled_water_out_error");
				break;
				case 'glycol_type_changed':
				validate = true;
				break;	
				case 'glycol_chilled_water':
				model_values.glycol_chilled_water = $("#glycol_chilled_water").val();
				validate = inputValidation(model_values.glycol_chilled_water,"positive_decimals","glycol_chilled_water_error");
				break;
				case 'glycol_cooling_water':
				model_values.glycol_cooling_water = $("#glycol_cooling_water").val();
				validate = inputValidation(model_values.glycol_cooling_water,"positive_decimals","glycol_cooling_water_error");
				break;
				case 'cooling_water_in':
				model_values.cooling_water_in = $("#cooling_water_in").val();
				validate = inputValidation(model_values.cooling_water_in,"positive_decimals","cooling_water_in_error");
				break;
				case 'cooling_water_flow':
				model_values.cooling_water_flow = $("#cooling_water_flow").val();
				validate = inputValidation(model_values.cooling_water_flow,"positive_decimals","cooling_water_flow_error");
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
		    		validate = inputValidation(model_values.evaporator_thickness,"positive_decimals","evaporator_thickness_error");
		    		break;
		    		case 'absorber_thickness':
		    		model_values.absorber_thickness = $("#absorber_thickness").val();
		    		validate = inputValidation(model_values.absorber_thickness,"positive_decimals","absorber_thickness_error");
		    		break;
		    		case 'condenser_thickness':
		    		model_values.condenser_thickness = $("#condenser_thickness").val();
		    		validate = inputValidation(model_values.condenser_thickness,"positive_decimals","condenser_thickness_error");
		    		break;	
		    		case 'fouling_chilled_value':
		    		model_values.fouling_chilled_water_value = $("#fouling_chilled_value").val();
		    		validate = inputValidation(model_values.fouling_chilled_water_value,"positive_decimals","fouling_chilled_value_error");
		    		break;	
		    		case 'fouling_cooling_value':
		    		model_values.fouling_cooling_water_value = $("#fouling_cooling_value").val();
		    		validate = inputValidation(model_values.fouling_cooling_water_value,"positive_decimals","fouling_cooling_value_error");
		    		break;	
		    		case 'steam_pressure':
		    		model_values.steam_pressure = $("#steam_pressure").val();
		    		validate = inputValidation(model_values.steam_pressure,"positive_decimals","steam_pressure_error");
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
				url: "{{ url('calculators/double-effect-s2/ajax-calculate') }}",
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
						
						swal(response.msg, "", "error").then((value) => {
							$('#'+changed_value).focus();
						});
						// console.log(changed_value);
						
					}					
				},
			});
		}

		$("#double_steam_s2").submit(function(event) {
			event.preventDefault();
			var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
	  		// console.log(model_values);
	  		$.ajax({
	  			type: "POST",
	  			url: "{{ url('calculators/double-effect-s2/submit-calculate') }}",
	  			data: { values : model_values,_token: CSRF_TOKEN},
	  			success: function(response){
	  				if(response.status){
	  					console.log(response.calculation_values);
	  					calculation_values = response.calculation_values;
	  					if(calculation_values.Result == "FAILED"){
	  						swal(calculation_values.Notes, "", "error");
	  					}
	  					else{
	  						var notes = calculation_values.notes;
	  						$( "#notes_div" ).html("");
	  						for (var i = 0; i < notes.length; i++) {
	  							$( "#notes_div" ).append("<p>"+notes[i]+"</p>");
	  						}

	  						$('#customer_name').val("");
	  						$('#project').val("");
	  						$('#phone').val("");

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
	  						$('#cooling_water_outlet_span').html(calculation_values.CoolingWaterOutTemperature);

	  						var absorber_condenser_pass = calculation_values.AbsorberPasses+"/"+calculation_values.CondenserPasses
	  						$('#absorber_pass').html(absorber_condenser_pass);
	  						$('#cooling_pressure_loss_span').html((calculation_values.CoolingFrictionLoss).toFixed(2));
	  						$('#steam_pressure_span').html(calculation_values.PST1);
	  						$('#steam_consumption_span').html((calculation_values.SteamConsumption).toFixed(2));
	  						$('#result_span').html(calculation_values.Result);


	  						// $('#exampleModalLong').modal('show');
	  						$('#exampleModalLong').modal({
	  							backdrop: 'static',
	  							keyboard: false
	  						});
	  					}

	  				}
	  				else{
	  					$("#calculate_button").prop('disabled', true);
	  					swal(response.msg, "", "error");
	  					
	  				}	

	  			},
	  		});
	  	});

		$( "#reset" ).click(function() {
			
			var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
			$.ajax({
				type: "POST",
				url: "{{ url('calculators/double-effect-s2/reset-calculate') }}",
				data: { model_number : model_values.model_number,_token: CSRF_TOKEN},
				success: function(response){
					if(response.status){
						
						$('.emsg').addClass('hidden');
						model_values = response.model_values;
						evaporator_options = response.evaporator_options;
						absorber_options = response.absorber_options;
						condenser_options = response.condenser_options;
						chiller_metallurgy_options = response.chiller_metallurgy_options;
						castToBoolean();
						console.log(model_values);
						loadDefaultValues();
						$('#capacity').focus();
						$("#calculate_button").prop('disabled', false);
						
					}
					else{
						swal("Sorry Something Went Wrong", "", "error");
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
					url: "{{ url('calculators/double-effect-s2/show-report') }}",
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


		$( ".save_report" ).click(function() {
			var name = $('#customer_name').val();
			var project = $('#project').val();
			var phone = $('#phone').val();
			var report_type = this.id;
			
			if(name == '' || project == '' || phone == ''){
				
				alert("Enter the details");
			}
			else{
				var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
				$.ajax({
					type: "POST",
					url: "{{ url('calculators/double-effect-s2/save-report') }}",
					data: { calculation_values : calculation_values,_token: CSRF_TOKEN,name: name,project: project,phone: phone,report_type: report_type},
					success: function(response){
						$("#exampleModalLong1").modal('toggle');
						console.log(response);	
						window.open(response.redirect_url, '_blank');

					},
				});
			}

		});
		function updateModelNumber(){
			
			model_values.model_number = $("#model_number").val();
			getModelName(model_values.model_number);
			var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
			$.ajax({
				type: "POST",
				url: "{{ url('calculators/double-effect-s2/model-number-calculate') }}",
				data: { model_number : model_values.model_number,model_values : model_values,_token: CSRF_TOKEN},
				success: function(response){
					if(response.status){
						
						$('.emsg').addClass('hidden');
						model_values = response.model_values;
						evaporator_options = response.evaporator_options;
						absorber_options = response.absorber_options;
						condenser_options = response.condenser_options;
						chiller_metallurgy_options = response.chiller_metallurgy_options;
						castToBoolean();
						console.log(model_values);
						loadDefaultValues();
						$('#capacity').focus();
						$("#calculate_button").prop('disabled', false);
						
					}
					else{
						swal("Sorry Something Went Wrong", "", "error");
					}					
				},
			});
		}



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

		function getModelName(model_number){

			switch(model_number){
				case '130':
				model_values.model_name = 'TAC S2 C3';
				break;
				case '160':
				model_values.model_name = 'TAC S2 C4';
				break;
				case '210':
				model_values.model_name = 'TAC S2 D1';
				break;
				case '250':
				model_values.model_name = 'TAC S2 D2';
				break;
				case '310':
				model_values.model_name = 'TAC S2 D3';
				break;
				case '350':
				model_values.model_name = 'TAC S2 D4';
				break;
				case '410':
				model_values.model_name = 'TAC S2 E1';
				break;
				case '470':
				model_values.model_name = 'TAC S2 E2';
				break;
				case '530':
				model_values.model_name = 'TAC S2 E3';
				break;
				case '580':
				model_values.model_name = 'TAC S2 E4';
				break;
				case '630':
				model_values.model_name = 'TAC S2 E5';
				break;
				case '710':
				model_values.model_name = 'TAC S2 E6';
				break;
				case '760':
				model_values.model_name = 'TAC S2 F1';
				break;
				case '810':
				model_values.model_name = 'TAC S2 F2';
				break;
				case '900':
				model_values.model_name = 'TAC S2 F3';
				break;
				case '1010':
				model_values.model_name = 'TAC S2 G1';
				break;
				case '1130':
				model_values.model_name = 'TAC S2 G2';
				break;
				case '1260':
				model_values.model_name = 'TAC S2 G3';
				break;
				case '1380':
				model_values.model_name = 'TAC S2 G4';
				break;
				case '1560':
				model_values.model_name = 'TAC S2 G5';
				break;
				case '1690':
				model_values.model_name = 'TAC S2 G6';
				break;
				case '1890':
				model_values.model_name = 'TAC S2 H1';
				break;
				case '2130':
				model_values.model_name = 'TAC S2 H2';
				break;
				case '2270':
				model_values.model_name = 'TAC S2 J1';
				break;
				case '2560':
				model_values.model_name = 'TAC S2 J2';
				break;
		
				default: 
				//return false;
 										
			}
		}

	</script>

	@endsection











