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
                                                        <option value="60">G2 M1</option>
                                                        <option value="75">G2 M2</option>
                                                        <option value="90">G2 N1</option>
                                                        <option value="110">G2 N2</option>
                                                        <option value="150">G2 N3</option>
                                                        <option value="175">G2 N4</option>
                                                        <option value="210">G2 P1</option>
                                                        <option value="250">G2 P2</option>
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
													<input type="text" class="form-control min_chilled_water_out" id="chilled_water_out" name="chilled_water_out" onchange="updateModelValues('chilled_water_out')" value="" data-placement="bottom" >
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
									<input type="text" value="" onchange="updateModelValues('cooling_water_in')" name="cooling_water_in" id="cooling_water_in" class="form-control cooling_water_in_range" data-placement="bottom" data-original-title>
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
													<input type="text" name="evaporator_thickness" id="evaporator_thickness" onchange="updateModelValues('evaporator_thickness')" value="" class="form-control metallurgy_standard metallurgy_standard_span" data-placement="bottom" >
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
													<input type="text" name="absorber_thickness" id="absorber_thickness" onchange="updateModelValues('absorber_thickness')" value="" class="form-control metallurgy_standard" data-placement="bottom" >
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
													<input type="text" name="condenser_thickness" id="condenser_thickness" onchange="updateModelValues('condenser_thickness')" value="" class="form-control metallurgy_standard" data-placement="bottom" >
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
												<input type="checkbox" class="fouling_standard " name="fouling_chilled_water" id="fouling_chilled_water" value="" data-placement="bottom" >
												<span class="cr">
													<i class="cr-icon icofont icofont-ui-check txt-primary"></i>
												</span><span>{{ $language_datas['chilled_water'] }}</span><span id=""></span>
											</label>
										</div>

										<div class="col-lg-2">
											<input type="text" name="fouling_chilled_value" id="fouling_chilled_value" onchange="updateModelValues('fouling_chilled_value')" class="form-control fouling_standard fouling_chilled_min" data-placement="bottom" >
										</div>
										<div class="col-lg-2 padding-0">
											<label>({{ $units_data[$unit_set->FoulingFactorUnit] }})</label>
										</div>

										<div class="col-lg-2 padding-0 checkbox-fade fade-in-primary">
											<label>
												<input type="checkbox" class="fouling_standard" name="fouling_cooling_water" id="fouling_cooling_water" value="" data-placement="bottom" >
												<span class="cr">
													<i class="cr-icon icofont icofont-ui-check txt-primary"></i>
												</span><span>{{ $language_datas['cooling_water'] }}</span><span id=""></span>
											</label>
										</div>

										<div class="col-lg-2">
											<input type="text" name="fouling_cooling_value" id="fouling_cooling_value" onchange="updateModelValues('fouling_cooling_value')" class="form-control fouling_standard fouling_cooling_min" data-placement="bottom" >
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
										<h5>{{ $language_datas['fuel'] }}</h5>
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
														<i class="helper"></i> Natural Gas
												</label>
											</div>
										</div>
										<div class="col-lg-2">
											<label>{{ $language_datas['cal_value'] }} </label>
										</div>
										<div class="col-lg-4">
											<input type="text" name="calorific_value" id="calorific_value" onchange="updateModelValues('calorific_value')" value="" class="form-control">
										</div>
										<div class="col-lg-2">
											<label class="natural_gas_unit">({{ $units_data[$unit_set->CalorificValueGasUnit] }})</label>
                                            <label class="hsd_gas_unit">({{ $units_data[$unit_set->CalorificValueOilUnit] }})</label>
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
											<div class="row" id="fuel_cv_stds" style="display: none;">
											<div class="col-lg-4">
												<label>Std GCV </label>
											</div>
											<div class="col-lg-6">
												<span id="fuel_cv_std"></span>
												<input type="text" name="std_calorific_value" id="std_calorific_value" disabled value="" class="form-control">
												
											</div>
											<div class="col-lg-2">
												<label class="natural_gas_unit">({{ $units_data[$unit_set->CalorificValueGasUnit] }})</label>
                                                <label class="hsd_gas_unit">({{ $units_data[$unit_set->CalorificValueOilUnit] }})</label>
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
											<div id="notes_div" >

								 			</div>
											<div class="row">

												<div class="col-md-6">
													<button type="button" name="submit" id="save_word" value="Export to Word" class="contact-submit save_report">   <i class="fas fa-file-word"></i> Export to Word</button>
												</div>
												<div class="col-md-6">
													<button type="button" name="submit" id="save_pdf" value="Export to Pdf" class="contact-submit save_report">  <i class="fas fa-file-pdf"></i> Export to Pdf</button>
												</div>
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
    var save_report_url = "{{ url('calculators/double-effect-g2/save-report') }}";
    var reset_url = "{{ url('calculators/double-effect-g2/reset-calculate') }}";
    var submit_url = "{{ url('calculators/double-effect-g2/submit-calculate') }}";
    var send_values_url = "{{ url('calculators/double-effect-g2/ajax-calculate') }}";


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

        updateEvaporatorOptions(chiller_metallurgy_options.eva_default_value,model_values.evaporator_thickness_change);
        updateAbsorberOptions(chiller_metallurgy_options.abs_default_value,model_values.absorber_thickness_change);
        updateCondenserOptions(chiller_metallurgy_options.con_default_value,model_values.condenser_thickness_change);
        updateValues();
        updateFuelValues();
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
        $('#cooling_water_in').val(model_values.cooling_water_in);
        $('#cooling_water_flow').val(model_values.cooling_water_flow);
        var cooling_water_ranges = getCoolingWaterRanges(model_values.cooling_water_ranges);
        $('.cooling_water_ranges').attr('data-original-title',cooling_water_ranges);
        $('#glycol_chilled_water').val(model_values.glycol_chilled_water ?  model_values.glycol_chilled_water : 0);
        $('#glycol_cooling_water').val(model_values.glycol_cooling_water ?  model_values.glycol_cooling_water : 0);
        
        $('#evaporator_thickness').val(model_values.evaporator_thickness);
        $('#absorber_thickness').val(model_values.absorber_thickness);
        $('#condenser_thickness').val(model_values.condenser_thickness);
        $("#evaporator_material").val(model_values.evaporator_material_value);
        $("#absorber_material").val(model_values.absorber_material_value);
        $("#condenser_material").val(model_values.condenser_material_value);

        
   
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

    function updateFuelValues(){
        if(model_values.fuel_type == 'Normal'){
            $("#fuel_normal").prop('checked', true);
            $("#fuel_cv_stds").hide();
        }
        else{
            $("#fuel_gross").prop('checked', true);
            $("#fuel_cv_stds").show();
        }

        if(model_values.fuel_value_type == 'NaturalGas'){
            $("#fuel_natural_gas").prop('checked', true);
            model_values.calorific_value = model_values.normal_ng_calorific_value;

            if(model_values.fuel_type != 'Normal'){
	            model_values.std_calorific_value = model_values.gross_ng_calorific_value;
	        }

            $(".natural_gas_unit").show();
            $(".hsd_gas_unit").hide();
          
        }
        else if(model_values.fuel_value_type == 'HSD'){
            $("#fuel_hsd").prop('checked', true);
            model_values.calorific_value = model_values.normal_hsd_calorific_value;

            if(model_values.fuel_type != 'Normal'){
	            model_values.std_calorific_value = model_values.gross_hsd_calorific_value;
	        }

            $(".natural_gas_unit").hide();
            $(".hsd_gas_unit").show();
        }
        else{
            $("#fuel_sko").prop('checked', true);
            model_values.calorific_value = model_values.normal_sko_calorific_value;

            if(model_values.fuel_type != 'Normal'){
	            model_values.std_calorific_value = model_values.gross_sko_calorific_value;
	        }

            $(".natural_gas_unit").hide();
            $(".hsd_gas_unit").show();

        }

        $("#calorific_value").val(model_values.calorific_value);
        $("#std_calorific_value").val(model_values.std_calorific_value);

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

    $('input:radio[name="fuel_type"]').change(function() {
    	
        if ($(this).val() == 'Normal') {
        	model_values.fuel_type = 'Normal';   
        } else {
            model_values.fuel_type = 'Gross';
        }

        updateFuelValues();
    });

    $('input:radio[name="fuel_value_type"]').change(function() {
    	
        if ($(this).val() == 'NaturalGas') {
        	model_values.fuel_value_type = 'NaturalGas';   
        } 
        else if($(this).val() == 'HSD'){
        	model_values.fuel_value_type = 'HSD';
        }
        else {
            model_values.fuel_value_type = 'SKO';
        }

        updateFuelValues();
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

    
        $('input[type=radio][name=glycol]').change(function() {
            // alert(this.value);
            model_values.glycol_selected = this.value;
            updateModelValues('glycol_type_changed');
        });

        $('input:radio[name="region_type"]').change(function() {
            model_values.region_type = $(this).val();
            model_values.model_number = 60;
            
            sendResetValues(reset_url);
        });

        $('input[type=radio][name=tube_metallurgy]').change(function() {
            // alert(this.value);
            if(this.value == 'non_standard'){
                $("#tube_metallurgy_standard").prop('disabled', false);
                $(".range-hide").addClass('show-div');
                $(".metallurgy_standard").prop('disabled', false);
                var evaporator_range = "("+model_values.evaporator_thickness_min_range+" - "+model_values.evaporator_thickness_max_range+")";
                $('#evaporator_thickness').attr('data-original-title',evaporator_range);
                var absorber_range = "("+model_values.absorber_thickness_min_range+" - "+model_values.absorber_thickness_max_range+")";
                $("#absorber_thickness").attr('data-original-title',absorber_range);
                var condenser_range = "("+model_values.condenser_thickness_min_range+" - "+model_values.condenser_thickness_max_range+")";
                $("#condenser_thickness").attr('data-original-title',condenser_range);

                model_values.metallurgy_standard = false;
            }
            else{
                $(".metallurgy_standard").prop('disabled', true);
                $(".metallurgy_standard_span").html("");
                $(".range-hide").removeClass('show-div').addClass('hidden-div');

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
            validate = inputValidation(capacity,"positive_decimals","capacity_error","{!! $language_datas['valid_capacity'] !!}");
            break;
            case 'chilled_water_in':
            model_values.chilled_water_in = $("#chilled_water_in").val();
            validate = inputValidation(model_values.chilled_water_in,"positive_decimals","chilled_water_in_error","{!! $language_datas['valid_chilled_water_in'] !!}");
            break;  
            case 'chilled_water_out':
            model_values.chilled_water_out = $("#chilled_water_out").val();
            validate = inputValidation(model_values.chilled_water_out,"positive_decimals","chilled_water_out_error","{!! $language_datas['valid_chilled_water_out'] !!}");
            break;
            case 'glycol_type_changed':
            validate = true;
            break;  
            case 'glycol_chilled_water':
            model_values.glycol_chilled_water = $("#glycol_chilled_water").val();
            validate = inputValidation(model_values.glycol_chilled_water,"positive_decimals","glycol_chilled_water_error","{!! $language_datas['valid_glycol_chilled_water'] !!}");
            break;
            case 'glycol_cooling_water':
            model_values.glycol_cooling_water = $("#glycol_cooling_water").val();
            validate = inputValidation(model_values.glycol_cooling_water,"positive_decimals","glycol_cooling_water_error","{!! $language_datas['valid_glycol_cooling_water'] !!}");
            break;
            case 'cooling_water_in':
            model_values.cooling_water_in = $("#cooling_water_in").val();
            validate = inputValidation(model_values.cooling_water_in,"positive_decimals","cooling_water_in_error","{!! $language_datas['valid_cooling_water_in'] !!}");
            break;
            case 'cooling_water_flow':
            model_values.cooling_water_flow = $("#cooling_water_flow").val();
            validate = inputValidation(model_values.cooling_water_flow,"positive_decimals","cooling_water_flow_error","{!! $language_datas['valid_cooling_water_flow'] !!}");
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
            validate = inputValidation(model_values.evaporator_thickness,"positive_decimals","evaporator_thickness_error","{!! $language_datas['valid_evaporator_thickness'] !!}");
            break;
            case 'absorber_thickness':
            model_values.absorber_thickness = $("#absorber_thickness").val();
            validate = inputValidation(model_values.absorber_thickness,"positive_decimals","absorber_thickness_error","{!! $language_datas['valid_absorber_thickness'] !!}");
            break;
            case 'condenser_thickness':
            model_values.condenser_thickness = $("#condenser_thickness").val();
            validate = inputValidation(model_values.condenser_thickness,"positive_decimals","condenser_thickness_error","{!! $language_datas['valid_condenser_thickness'] !!}");
            break;  
            case 'fouling_chilled_value':
            model_values.fouling_chilled_water_value = $("#fouling_chilled_value").val();
            validate = inputValidation(model_values.fouling_chilled_water_value,"positive_decimals","fouling_chilled_value_error","{!! $language_datas['valid_fouling_chilled_water'] !!}");
            break;  
            case 'fouling_cooling_value':
            model_values.fouling_cooling_water_value = $("#fouling_cooling_value").val();
            validate = inputValidation(model_values.fouling_cooling_water_value,"positive_decimals","fouling_cooling_value_error","{!! $language_datas['valid_fouling_cooling_water'] !!}");
            break; 
            case 'calorific_value':
                model_values.calorific_value = $("#calorific_value").val();
                validate = inputValidation(model_values.calorific_value,"positive_decimals","calorific_value_error","{!! $language_datas['valid_calorific_value'] !!}");
            break;                                     
            default:
            // code block
        }
        changed_value = input_type;

        if(validate){
            sendValues(send_values_url);
        }
        else{
            $("#calculate_button").prop('disabled', true);
        }

    }

    $("#double_steam_h2").submit(function(event) {
        event.preventDefault();
        submitValues(submit_url);
    });

    $( "#reset" ).click(function() {
        // console.log(model_values.cooling_water_ranges);
        sendResetValues(reset_url);
    });

    function afterReset(){
        if(model_values.region_type == 2){
            //console.log("usa selected");
            model_values.fouling_chilled_water_value = model_values.fouling_ari_chilled
            model_values.fouling_cooling_water_value = model_values.fouling_ari_cooling
        }
        updateValues(); 
        updateFuelValues();
    }
        
    $( ".save_report" ).click(function() {
        saveReport(save_report_url,this.id);
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
        
    </script>
    <script type="text/javascript" src="{{asset('assets/js/calculator_common_scripts.js')}}"></script>  
    
    <script>
        $(document).ready(function(){
            $('.ss-steam-label input').tooltip();
        });
    </script>

@endsection











