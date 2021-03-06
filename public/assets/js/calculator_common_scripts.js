console.log("common scripts loaded");

function getBoolean(value){
	switch(value){
		case true:
		case "true":
		case 1:
		case "1":
		case "on":
		case "yes":
		case "TRUE":
		return true;  
		default: 
		return false;
	}
}


function metalluryRedClass(value){
	if(!value){
		$("#metallurgy_non_standard_label").addClass('red-check');
		$("#metallurgy_standard_label").removeClass('red-check');
		$(".metallurgy-disabled").removeClass('disable-style');
	}
	else{
		$("#metallurgy_non_standard_label").removeClass('red-check');
		$("#metallurgy_standard_label").addClass('red-check');
		$(".metallurgy-disabled").addClass('disable-style');
	}
}


function foulingFactorRedClass(value){
	if(value == 'ari'){
		$("#fouling_factor_ari_label").addClass('red-check');
		$("#fouling_factor_non_standard_label").removeClass('red-check');
		$("#fouling_factor_standard_label").removeClass('red-check');
		$(".fouling-factor-disabled").removeClass('disable-style');
	}
	else if(value == 'non_standard'){
		$("#fouling_factor_ari_label").removeClass('red-check');
		$("#fouling_factor_non_standard_label").addClass('red-check');
		$("#fouling_factor_standard_label").removeClass('red-check');
		$(".fouling-factor-disabled").removeClass('disable-style');
	}
	else{
		$("#fouling_factor_ari_label").removeClass('red-check');
		$("#fouling_factor_non_standard_label").removeClass('red-check');
		$("#fouling_factor_standard_label").addClass('red-check');
		$(".fouling-factor-disabled").addClass('disable-style');
	}
}

function glycolRedClass(value){
	if(value == '3'){
		$("#glycol_proplene_label").addClass('red-check');
		$("#glycol_ethylene_label").removeClass('red-check');
		$("#glycol_none_label").removeClass('red-check');
		$(".glycol-disabled").removeClass('disable-style');
	}
	else if(value == '2'){
		$("#glycol_proplene_label").removeClass('red-check');
		$("#glycol_ethylene_label").addClass('red-check');
		$("#glycol_none_label").removeClass('red-check');
		$(".glycol-disabled").removeClass('disable-style');
	}
	else{
		$("#glycol_proplene_label").removeClass('red-check');
		$("#glycol_ethylene_label").removeClass('red-check');
		$("#glycol_none_label").addClass('red-check');
		$(".glycol-disabled").addClass('disable-style');
	}
}

function regionTypeRedClass(value){
	if(value == '3'){
		$("#region_europe_label").addClass('red-check');
		$("#region_usa_label").removeClass('red-check');
		$("#region_domestic_label").removeClass('red-check');
	}
	else if(value == '2'){
		$("#region_europe_label").removeClass('red-check');
		$("#region_usa_label").addClass('red-check');
		$("#region_domestic_label").removeClass('red-check');
	}
	else{
		$("#region_europe_label").removeClass('red-check');
		$("#region_usa_label").removeClass('red-check');
		$("#region_domestic_label").addClass('red-check');
	}
}


function getCoolingWaterRanges(cooling_water_ranges){
	var range_values = "";
	 // console.log(cooling_water_ranges);
	if(!$.isArray(cooling_water_ranges)){
		var cooling_water_ranges = cooling_water_ranges.split(",");
	}
	
	for (var i = 0; i < cooling_water_ranges.length; i+=2) {
		range_values += "("+cooling_water_ranges[i].toFixed(1)+" - "+cooling_water_ranges[i+1].toFixed(1)+") /";

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
			// model_values.evaporator_thickness = 0.8;
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

function saveReport(chiller_url,report_type){

	var name = $('#customer_name').val();
	var project = $('#project').val();
	var phone = $('#phone').val();
	// var report_type = this.id;
	
	if(name == '' || project == '' || phone == ''){

		Swal.fire("Enter the details", "", "error");
	}
	else{
		var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
		$.ajax({
			type: "POST",
			url: chiller_url,
			data: { calculation_values : calculation_values,_token: CSRF_TOKEN,name: name,project: project,phone: phone,report_type: report_type},
			success: function(response){
				//$("#exampleModalLong1").modal('toggle');
				// console.log(response);	
				window.open(response.redirect_url, '_blank');

			},
		});
	}
}

function showReport(chiller_url){
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
			url: chiller_url,
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
}

function sendResetValues(chiller_url){
	var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
	$.ajax({
	    type: "POST",
	    url: chiller_url,
	    data: { model_number : model_values.model_number,values : model_values,_token: CSRF_TOKEN},
	    success: function(response){
	    	$("#errormessage").html("-");
	        if(response.status){
	            
	            $('.emsg').addClass('hidden');
	            model_values = response.model_values;
	            evaporator_options = response.evaporator_options;
	            absorber_options = response.absorber_options;
	            condenser_options = response.condenser_options;
	            chiller_metallurgy_options = response.chiller_metallurgy_options;
	            castToBoolean();
	            	        
	            updateEvaporatorOptions(chiller_metallurgy_options.eva_default_value,model_values.evaporator_thickness_change);
	            updateAbsorberOptions(chiller_metallurgy_options.abs_default_value,model_values.absorber_thickness_change);
	            updateCondenserOptions(chiller_metallurgy_options.con_default_value,model_values.condenser_thickness_change);
	            afterReset();
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
	            //swal("Sorry Something Went Wrong", "", "error");
	        }                   
	    },
	});

}

function submitValues(chiller_url){
	var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
	// console.log(model_values);
	$("#ajax-loader").show();

	var name = $('#customer_name').val();
	var project = $('#project').val();
	var phone = $('#phone').val();

	$.ajax({
		type: "POST",
		url: chiller_url,
		data: { values : model_values,_token: CSRF_TOKEN,name: name,project: project,phone: phone},
		 complete: function(){
			$("#ajax-loader").hide();
		 },
		success: function(response){
			$("#ajax-loader").show();
			$("#errormessage").html("-");
			if(response.status){
				// console.log(response.calculation_values);
				calculation_values = response.calculation_values;
				if(calculation_values.Result == "FAILED"){
					$(".showreport").hide();
					$("#errornotes").show();
					$("#errormessage").html(calculation_values.Notes);
					//swal(calculation_values.Notes, "", "error");
				}
				else{
					var notes = calculation_values.selection_notes;
					$("#notes_div").html("");	
					for(var i = notes.length - 1; i >= 0; --i)
					{
						
						// if(!model_values.metallurgy_standard)
						// {
							// if (i < 1){
								$( "#notes_div" ).append("<p style='text-align: left;'>"+notes[i]+"</p>");
							// }
						// }
					}
				
					$("#notes_head_div").html('<h4>'+calculation_values.Result+'</h4>');	
					

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
				//swal(response.msg, "", "error");
			}	

		},
        error: function (jqXHR, status, err) {
            alert("Sorry Something Went Wrong", "", "error");
        },
	});
}

function sendValues(chiller_url){
// var form_values = $("#double_steam_s2").serialize();
	var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
	$.ajax({
		type: "POST",
		url: chiller_url,
		data: { values : model_values,_token: CSRF_TOKEN,changed_value: changed_value},
		success: function(response){
			
			$("#errormessage").html("-");
			if(response.status){
				$('#'+changed_value).removeClass("box-color");
				
				$("#calculate_button").prop('disabled', false);

				model_values = response.model_values;
				if(response.changed_value == 'model_number'){
					evaporator_options = response.evaporator_options;
					absorber_options = response.absorber_options;
					condenser_options = response.condenser_options;
					chiller_metallurgy_options = response.chiller_metallurgy_options;
				}
				
				castToBoolean();
				if(response.changed_value == 'model_number'){
					updateEvaporatorOptions(chiller_metallurgy_options.eva_default_value,model_values.evaporator_thickness_change);
					updateAbsorberOptions(chiller_metallurgy_options.abs_default_value,model_values.absorber_thickness_change);
					updateCondenserOptions(chiller_metallurgy_options.con_default_value,model_values.condenser_thickness_change);
				}	        
				
				updateValues();				
			}
			else{
				$("#calculate_button").prop('disabled', true);
				changed_value = response.changed_value
				$(".showreport").hide();
				$("#errornotes").show();
				$('#'+changed_value).addClass("box-color");
				$('#'+changed_value).focus();
				$("#errormessage").html(response.msg);

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

    if (typeof model_values.fouling_hot_water_checked !== 'undefined') {
	  model_values.fouling_hot_water_checked = getBoolean(model_values.fouling_hot_water_checked);
	}

	if (typeof model_values.fouling_hot_water_disabled !== 'undefined') {
	  model_values.fouling_hot_water_disabled = getBoolean(model_values.fouling_hot_water_disabled);
	}

	if (typeof model_values.fouling_hot_water_value_disabled !== 'undefined') {
	  model_values.fouling_hot_water_value_disabled = getBoolean(model_values.fouling_hot_water_value_disabled);
	}


}

function roundCommonValues(){
	

	model_values.capacity = parseFloat(model_values.capacity).toFixed(1);
	model_values.chilled_water_in = parseFloat(model_values.chilled_water_in).toFixed(1);
	model_values.chilled_water_out = parseFloat(model_values.chilled_water_out).toFixed(1);
	model_values.min_chilled_water_out = parseFloat(model_values.min_chilled_water_out).toFixed(1);
	model_values.cooling_water_in_min_range = parseFloat(model_values.cooling_water_in_min_range).toFixed(1);
	model_values.cooling_water_in_max_range = parseFloat(model_values.cooling_water_in_max_range).toFixed(1);
	model_values.cooling_water_in = parseFloat(model_values.cooling_water_in).toFixed(1);
	model_values.cooling_water_flow = parseFloat(model_values.cooling_water_flow).toFixed(1);
	model_values.glycol_min_chilled_water = parseFloat(model_values.glycol_min_chilled_water).toFixed(1);
	model_values.glycol_max_chilled_water = parseFloat(model_values.glycol_max_chilled_water).toFixed(1);
	model_values.glycol_min_cooling_water = parseFloat(model_values.glycol_min_cooling_water).toFixed(1);
	model_values.glycol_max_cooling_water = parseFloat(model_values.glycol_max_cooling_water).toFixed(1);
	model_values.glycol_max_cooling_water = parseFloat(model_values.glycol_max_cooling_water).toFixed(1);
	model_values.fouling_ari_chilled = parseFloat(model_values.fouling_ari_chilled.toFixed(8));
	model_values.fouling_ari_cooling = parseFloat(model_values.fouling_ari_cooling.toFixed(8));
	model_values.fouling_non_chilled = parseFloat(model_values.fouling_non_chilled.toFixed(8));
	model_values.fouling_non_cooling = parseFloat(model_values.fouling_non_cooling.toFixed(8));

}