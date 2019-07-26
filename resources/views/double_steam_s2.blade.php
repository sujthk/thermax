@extends('layouts.app') 

@section('styles')	
	<!-- Data Table Css -->
	<meta name="csrf-token" content="{{ csrf_token() }}" />
	<style type="text/css">
		.emsg{
		    color: red;
		}
		.hidden {
		     visibility:hidden;
		}
	</style>
@endsection

@section('content')
	<div class="main-body">
	    <div class="page-wrapper">
	        <div class="page-header">
	            <div class="page-header-title">
	                <h4>Double Steam S2</h4>
	            </div>
	            <div class="page-header-breadcrumb">
	                <ul class="breadcrumb-title">
	                    <li class="breadcrumb-item">
	                        <a href="{{ url('dashboard') }}">
	                            <i class="icofont icofont-home"></i>
	                        </a>
	                    </li>
	                    <li class="breadcrumb-item"><a href="#!">Double Steam S2</a>
	                    </li>
	                </ul>
	            </div>
	        </div>
	        <div class="page-body">
                <form id="double_steam_s2" method="post" action="{{ url('calculators/double-effect-s2') }}" enctype="multipart/form-data">
                	{{ csrf_field() }}
                	<div class="row">	
	                    <div class="col-sm-6">
	                        <!-- Basic Form Inputs card start -->
	                        <div class="card">
	                            <div class="card-block">
                                	<div class="form-group row">
                                        <label class="col-sm-2 col-form-label">Model</label>
                                        <div class="col-sm-6">
                                            <select name="model_number" id="model_number" class="form-control">
                                                <option value="130">S2 C3</option>
                                                <option value="160">S2 C4</option>
                                                <option value="210">S2 D1</option>
                                                <option value="250">S2 D2</option>
                                                <option value="310">S2 D3</option>
                                            </select>
                                        </div>
                                        <label class="col-sm-2 col-form-label" id="model_name"></label>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-2 col-form-label">Capacity</label>
                                        <div class="col-sm-6">
                                            <input id="capacity" name="capacity" type="text" value="" onchange="updateModelValues('capacity')" class="form-control number-validate">
                                            <p><span class="emsg hidden">Please Enter a Valid Capacity</span></p>
                                        </div>
                                        <label class="col-sm-2 col-form-label">(TR)</label>
                                    </div>
	                                
	                            </div>
	                            <div class="card-header">
	                                <h5>Chilled Water</h5>
	                            </div>
	                            <div class="card-block">
                                	<div class="form-group row">
                                        <label class="col-sm-2 col-form-label">Water In</label>
                                        <div class="col-sm-6">
                                            <input type="text" id="chilled_water_in" name="chilled_water_in" onchange="updateModelValues('chilledwaterin')" value="" class="form-control">
                                        </div>
                                        <label class="col-sm-2 col-form-label">(&#176;C)</label>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-2 col-form-label">Water Out (min <span id="min_chilled_water_out">0</span>)</label>
                                        <div class="col-sm-6">
                                            <input type="text" class="form-control" id="chilled_water_out" name="chilled_water_out" onchange="updateModelValues('chilledwaterout')" value="">
                                        </div>
                                        <label class="col-sm-2 col-form-label">(&#176;C)</label>
                                    </div>
	                                
	                            </div>
	                        </div>    
	                    </div> 
	                    <div class="col-sm-6">
	                        <div class="card">
	                            <div class="card-header">
	                                <h5>Tube Metallurgy</h5>
	                            </div>
	                            <div class="card-block">
                                	<div class="form-group row">
                                		<label class="col-sm-4 col-form-label"></label>
                                		<div class="form-radio">

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
                                    <div class="form-group row">
                                        <label class="col-sm-2 col-form-label"></label>
                                        <label class="col-sm-3 col-form-label">Material</label>
                                        <label class="col-sm-3 col-form-label">Thickness (mm)</label>
                                    </div>
	                                <div class="form-group row">
	                                	<div class="col-sm-2">
	                                		<label class=" col-form-label float-right">Evaporator</label>
	                                	</div>
	                                	<div class="col-sm-3">
	                                	    <select name="evaporator_material" id="evaporator_material" class="form-control metallurgy_standard">
	                                	    	@foreach($evaporator_options as $evaporator_option)
	                                	    		<option value="{{ $evaporator_option['value'] }}">{{ $evaporator_option['name'] }}</option>
	                                	    	@endforeach
	                                	    </select>
	                                	</div>
	                                	<div class="col-sm-3">
                                            <input type="text" name="evaporator_thickness" id="evaporator_thickness" value="" class="form-control metallurgy_standard">
                                        </div>
                                        <div class="col-sm-4">
                                        	<span class="metallurgy_standard_span" id="evaporator_range"></span>
                                        </div>	
	                            	</div> 
	                            	<div class="form-group row">
	                                	<div class="col-sm-2">
	                                		<label class=" col-form-label float-right">Absorber</label>
	                                	</div>
	                                	<div class="col-sm-3">
	                                	    <select name="absorber_material" id="absorber_material" class="form-control metallurgy_standard">
	                                	        @foreach($absorber_options as $absorber_option)
	                                	    		<option value="{{ $absorber_option['value'] }}">{{ $absorber_option['name'] }}</option>
	                                	    	@endforeach
	                                	    </select>
	                                	</div>
	                                	<div class="col-sm-3">
                                            <input type="text" name="absorber_thickness" id="absorber_thickness" value="" class="form-control metallurgy_standard">
                                        </div>
                                        <div class="col-sm-4">
                                        	<span class="metallurgy_standard_span" id="absorber_range"></span>
                                        </div>
	                            	</div> 
	                            	<div class="form-group row">
	                                	<div class="col-sm-2">
	                                		<label class=" col-form-label float-right">Condenser</label>
	                                	</div>
	                                	<div class="col-sm-3">
	                                	    <select name="condenser_material" id="condenser_material" class="form-control metallurgy_standard">
	                                	        @foreach($condenser_options as $condenser_option)
	                                	    		<option value="{{ $condenser_option['value'] }}">{{ $condenser_option['name'] }}</option>
	                                	    	@endforeach
	                                	    </select>
	                                	</div>
	                                	<div class="col-sm-3">
                                            <input type="text" name="condenser_thickness" id="condenser_thickness" value="" class="form-control metallurgy_standard">
                                        </div>
                                        <div class="col-sm-4">
                                        	<span class="metallurgy_standard_span" id="condenser_range"></span>
                                        </div>
	                            	</div>    	
	                            </div>
	                        </div>    
	                    </div>
	                    <div class="col-sm-6">
	                        <div class="card">
	                            <div class="card-header">
	                                <h5>Cooling Water</h5>
	                            </div>
	                            <div class="card-block">
                                	<div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Water In (<span id="cooling_water_in_range" ></span>)</label>
                                        <div class="col-sm-6">
                                            <input type="text" value="" name="cooling_water_in" id="cooling_water_in" class="form-control">
                                        </div>
                                        <label class="col-sm-2 col-form-label">(&#176;C)</label>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Water Flow</label>
                                        <div class="col-sm-6">
                                            <input type="text" name="cooling_water_flow" id="cooling_water_flow" value="" class="form-control">
                                        </div>
                                        <label class="col-sm-2 col-form-label">(m&#179;/hr)</label>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-12 col-form-label">Available Range(s) : <span id="cooling_water_ranges"></span></label>
                                    </div>
	                            </div>
	                        </div>    
	                    </div>
	                    <div class="col-sm-6">
	                        <div class="card">
	                            <div class="card-header">
	                                <h5>Fouling Factor</h5>
	                            </div>
	                            <div class="card-block">
                                	<div class="form-group row">
                                		<label class="col-sm-4 col-form-label"></label>
                                		<div class="form-radio">
	                                        <div class="radio radio-inline">
	                                            <label>
	                                                <input type="radio" name="fouling_factor" value="standard" checked="checked">
	                                                <i class="helper"></i>Standard
	                                            </label>
	                                        </div>
	                                        <div class="radio radio-inline">
	                                            <label>
	                                                <input type="radio" name="fouling_factor" value="non_standard">
	                                                <i class="helper"></i>Non Standard
	                                            </label>
	                                        </div>
	                                        <div class="radio radio-inline">
	                                            <label>
	                                                <input type="radio" name="fouling_factor" value="ari">
	                                                <i class="helper"></i>ARI
	                                            </label>
	                                        </div>
	                                    </div>    
                                    </div>
                                    <div class="form-group row">
                                    	<label class="col-sm-2 col-form-label"></label>
                                    	<div class="checkbox-fade fade-in-primary">
                                            <label>
                                                <input type="checkbox" class="fouling_standard" name="fouling_chilled_water" id="fouling_chilled_water" value="">
                                                <span class="cr">
                                    				<i class="cr-icon icofont icofont-ui-check txt-primary"></i>
                                    			</span><span>Chilled Water</span><br><span style="padding-left: 35px;" id="fouling_chilled_min" ></span>
                                            </label>
                                        </div>

                                        <div class="col-sm-4">
                                            <input type="text" name="fouling_chilled_value" id="fouling_chilled_value" class="form-control fouling_standard">
                                        </div>
                                        <label class="col-sm-2 col-form-label">(m&#179;hr &#176;C/kcal)</label>
                                    </div> 
                                    <div class="form-group row">
                                    	<label class="col-sm-2 col-form-label"></label>
                                    	<div class="checkbox-fade fade-in-primary">
                                            <label>
                                                <input type="checkbox" class="fouling_standard" name="fouling_cooling_water" id="fouling_cooling_water" value="">
                                                <span class="cr">
                                    				<i class="cr-icon icofont icofont-ui-check txt-primary"></i>
                                    			</span><span>Cooling Water</span><br><span style="padding-left: 35px;" id="fouling_cooling_min" ></span>
                                            </label>
                                        </div>

                                        <div class="col-sm-4">
                                            <input type="text" name="fouling_cooling_value" id="fouling_cooling_value" class="form-control fouling_standard">
                                        </div>
                                        <label class="col-sm-2 col-form-label">(m&#179;hr &#176;C/kcal)</label>
                                    </div>   	
	                            </div>
	                        </div>    
	                    </div>
	                    <div class="col-sm-6">
	                        <div class="card">
	                            <div class="card-header">
	                                <h5>Glycol Content % (By Vol)</h5>
	                            </div>
	                            <div class="card-block">
                                	<div class="form-group row">
                                		<label class="col-sm-2 col-form-label"></label>
                                		<div class="form-radio">

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
                                    <div class="form-group row">
                                        <label class="col-sm-2 col-form-label">Chilled Water </label>
                                        <div class="col-sm-6">
                                            <input type="text" name="glycol_chilled_water" id="glycol_chilled_water" onchange="updateModelValues('glycolchilledwater')" value="" class="form-control">
                                        </div>
                                    </div> 
                                    <div class="form-group row">
                                        <label class="col-sm-2 col-form-label">Cooling Water </label>
                                        <div class="col-sm-6">
                                            <input type="text" name="glycol_cooling_water" id="glycol_cooling_water" value="" onchange="updateModelValues('glycolcoolingwater')" class="form-control">
                                        </div>
                                    </div>   	
	                            </div>
	                        </div>    
	                    </div>
	                    <div class="col-sm-6">
	                        <div class="card">
	                            <div class="card-header">
	                                <h5>Steam</h5>
	                            </div>
	                            <div class="card-block">
                                    <div class="form-group row">
                                        <label class="col-sm-4 col-form-label">Pressure : (<span id="steam_pressure_range"></span>)</label>
                                        <div class="col-sm-4">
                                            <input type="text" name="steam_pressure" id="steam_pressure" value="" class="form-control">
                                        </div>
                                        <label class="col-sm-4 col-form-label">(kg/cm<sup>2</sup>(g))</label>
                                    </div>   	
	                            </div>
	                        </div>    
	                    </div>
		                <div class="col-sm-12">    
		                    <div class="form-group row">
	                            <label class="col-sm-5"></label>
	                            <div class="col-sm-7">
	                                <input type="submit" name="submit_value" value="Calculate" id="calculate_button" class="btn btn-primary m-b-0">
	                                <input type="button" name="reset" id="reset" value="Reset" class="btn btn-primary m-b-0">
	                            </div>
	                        </div>
		                </div>  
		            </div>      
                </form>            
            </div>
	    </div>
	</div>
@endsection
	
@section('scripts')	
	<script type="text/javascript">
		
		var model_values = {!! json_encode($default_values) !!};
		var changed_value = "";
		// console.log(model_values);
		$( document ).ready(function() {
		    updateValues();
		    // swal("Hello world!");
		    var positive_decimal=/^(0|[1-9]\d*)(\.\d+)?$/;
		    var negative_decimal=/^-?(0|[1-9]\d*)(\.\d+)?$/;
	        $('.number-validate').on('keypress keydown keyup',function(){
                if (!$(this).val().match(negative_decimal)) {
                  // there is a mismatch, hence show the error message
                     $('.emsg').removeClass('hidden');
                     $('.emsg').show();
                 }
               	else{
                    // else, do not display message
                    $('.emsg').addClass('hidden');
                }
            });
		});

		function updateValues() {
			
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
			if(model_values.glycol_none == 'true')
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
				$("#tube_metallurgy_standard").prop('disabled', true);
				$(".metallurgy_standard").prop('disabled', false);
		    	var evaporator_range = "("+model_values.evaporator_thickness_min_range+" - "+model_values.evaporator_thickness_max_range+")";
				$("#evaporator_range").html(evaporator_range);
				var absorber_range = "("+model_values.absorber_thickness_min_range+" - "+model_values.absorber_thickness_max_range+")";
				$("#absorber_range").html(absorber_range);
				var condenser_range = "("+model_values.condenser_thickness_min_range+" - "+model_values.condenser_thickness_max_range+")";
				$("#condenser_range").html(condenser_range);
			}

			if(model_values.calculate_option){
				$("#calculate_button").prop('disabled', false);
			}
			else{
				$("#calculate_button").prop('disabled', true);
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
	           $("#fouling_chilled_value").val(model_values.fouling_non_chilled);
	           $("#fouling_chilled_value").prop('disabled', false);
	        }
	        else{
	        	$("#fouling_chilled_value").val("");
	        	$("#fouling_chilled_value").prop('disabled', true);
	        }
	               
	    });

	    $('#fouling_cooling_water').change(function() {
	        if($(this).is(":checked")) {
	           $("#fouling_cooling_value").val(model_values.fouling_non_chilled);
	           $("#fouling_cooling_value").prop('disabled', false);
	        }
	        else{
	        	$("#fouling_cooling_value").val("");
	        	$("#fouling_cooling_value").prop('disabled', true);
	        }
	               
	    });

		$('input:radio[name="fouling_factor"]').change(function() {
			foulingFactor($(this).val());
	
		});

		function foulingFactor(value){
			if (value == 'standard') {
				$("#fouling_chilled_water").prop('checked', false);
		  		$("#fouling_cooling_water").prop('checked', false);
		  		$(".fouling_standard").prop('disabled', true);
		  		$("#fouling_chilled_min").html("");
		  		$("#fouling_cooling_min").html("");
		  		$("#fouling_chilled_value").val("");
		  		$("#fouling_cooling_value").val("");
			} else if (value == 'non_standard'){
		  		$("#fouling_chilled_water").prop('disabled', false);
		  		$("#fouling_cooling_water").prop('disabled', false);
		  		$("#fouling_chilled_water").prop('checked', false);
		  		$("#fouling_cooling_water").prop('checked', false);
				$("#fouling_chilled_value").prop('disabled', true);
		  		$("#fouling_cooling_value").prop('disabled', true);
		  		$("#fouling_chilled_min").html(">"+model_values.fouling_non_chilled);
		  		$("#fouling_cooling_min").html(">"+model_values.fouling_non_cooling);
		  		$("#fouling_chilled_value").val("");
		  		$("#fouling_cooling_value").val("");
			}
			else{
				$("#fouling_chilled_water").prop('disabled', true);
			  	$("#fouling_cooling_water").prop('disabled', true);
			  	$("#fouling_chilled_water").prop('checked', true);
			  	$("#fouling_cooling_water").prop('checked', true);
			  	$("#fouling_chilled_value").prop('disabled', false);
			  	$("#fouling_cooling_value").prop('disabled', false);
			  	$("#fouling_chilled_min").html(">"+model_values.fouling_ari_chilled);
		  		$("#fouling_cooling_min").html(">"+model_values.fouling_ari_cooling);
		  		$("#fouling_chilled_value").val(model_values.fouling_ari_chilled);
		  		$("#fouling_cooling_value").val(model_values.fouling_ari_cooling);
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


		$( "#double_steam_s2" ).submit(function(event) {
			  event.preventDefault();
			  var form_values = $(this).serialize();
			  console.log(form_values);
			  var submit_value = $("input[name=submit_value]").val();
			  console.log(submit_value);
		});

		$( "#reset" ).click(function() {
			

		   	
		});

		$('input[type=radio][name=glycol]').change(function() {
		    // alert(this.value);
		    model_values.glycol_selected = this.value;
		    updateModelValues('glycoltypechanged');
		});

		function updateModelValues(input_type){

			switch(input_type) {
			  	case 'model_number':
			    	model_values.model_number = $("#model_number").val();
			    	break;
			  	case 'capacity':
			    	model_values.capacity = $("#capacity").val();
			    	break;
			    case 'chilledwaterin':
			    	model_values.chilled_water_in = $("#chilled_water_in").val();
			    	break;	
			    case 'chilledwaterout':
			    	model_values.chilled_water_out = $("#chilled_water_out").val();
			    	break;
			    case 'glycoltypechanged':
			    	
			    	break;	
			    case 'glycolchilledwater':
			    	model_values.glycol_chilled_water = $("#glycol_chilled_water").val();
			    	break;
			    case 'glycolcoolingwater':
			    	model_values.glycol_cooling_water = $("#glycol_cooling_water").val();
			    	break;			

			  	default:
			    	// code block
			}
			changed_value = input_type;

			sendValues();
			

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
						updateValues();
					}
					else{
						$("#calculate_button").prop('disabled', true);
						// alert(response.msg);
						swal(response.msg, "", "error");
					}					
				},
			});
		}

	</script>

@endsection