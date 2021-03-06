@extends('layouts.app') 

@section('styles')	
	

@endsection

@section('content')
	<div class="main-body">
	    <div class="page-wrapper">
	        <!-- <div class="page-header">
	            <div class="page-header-title">
	                <h4>Edit Calculator Values</h4>
	            </div>
	            <div class="page-header-breadcrumb">
	                <ul class="breadcrumb-title">
	                    <li class="breadcrumb-item">
	                        <a href="{{ url('dashboard') }}">
	                            <i class="icofont icofont-home"></i>
	                        </a>
	                    </li>
	                    <li class="breadcrumb-item"><a href="{{ url('chiller/calculation-values') }}">Calculators Values</a>
	                    </li>
	                    <li class="breadcrumb-item"><a href="#!">Edit Calculator Values</a>
	                    </li>
	                </ul>
	            </div>
	        </div> -->
	        <div class="page-body">
	            <div class="row">
	                <div class="col-sm-8">
	                    <!-- Zero config.table start -->
	                    @if ($errors->any())
	                        <div class="alert alert-danger">
	                            <ul>
	                                @foreach ($errors->all() as $error)
	                                    <li>{{ $error }}</li>
	                                @endforeach
	                            </ul>
	                        </div>
	                    @endif
	                    <div class="card">
	                        <div class="card-header">
	                        	<div class="">
		                            <h5>Edit Calculator Values</h5>
		                             <button class="btn btn-primary btn-sm " data-toggle="modal" data-target="#new_note">Add Calculator Values +</button>
                            	</div>
	                        </div>
	                        <form id="edit_default_calculator" method="post" action="{{ url('chiller/calculation-values/edit',[$chiller_calculation_value->id]) }}" enctype="multipart/form-data">
                				{{ csrf_field() }}
		                        <div class="card-block">
		                        	<div class="form-group row">
		                        	    <label class="col-sm-4 col-form-label">Name</label>
		                        	    <div class="col-sm-6">
		                        	        <input id="name" name="name" type="text" value="{{ $chiller_calculation_value->name }}" required class="form-control">
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-4 col-form-label">Model</label>
		                        	    <div class="col-sm-6">
		                        	        <input id="min_model" name="min_model" type="number" value="{{ $chiller_calculation_value->min_model }}" required class="form-control">
		                        	    </div>
		                        	</div>
		                        	<?php
			                        	function floattostr( $val )
			                        	{
			                        		return rtrim(rtrim(number_format($val, 8, ".", ""), '0'), '.');

			                        	}
		                        	?>
		                        	
		                        	@foreach ($calculation_value_keys as $calculation_value_key)
		                        		
		                        		@if(is_array($calculation_values[$calculation_value_key]))
		                        			@php($value = implode(",",$calculation_values[$calculation_value_key]))
		                        		@elseif (is_string($calculation_values[$calculation_value_key]))
		                        			@php($value = $calculation_values[$calculation_value_key])	
		                        		@elseif (is_float($calculation_values[$calculation_value_key]))
		                        			@php($value = floattostr($calculation_values[$calculation_value_key]))	
		                        		@elseif (is_bool($calculation_values[$calculation_value_key]))
		                        			@php($value = ($calculation_values[$calculation_value_key]) ? 'true' : 'false')	
		                        		@else
		                        			@php($value = $calculation_values[$calculation_value_key])	
		                        		@endif
		                        		<div id="add_calculation_div"></div> 
		                        	    <div class="form-group row" >
		                        	        <label class="col-sm-4 col-form-label">{{ $calculation_value_key }}</label>
		                        	        <div class="col-sm-6">
		                        	            <input id="model" name="calculation_values[{{ $calculation_value_key }}]" type="text" value="{{ $value }}" required class="form-control">
		                        	        </div>
		                        	    </div>

		                        	@endforeach

        		                    <div class="form-group row">
        	                            <label class="col-sm-5"></label>
        	                            <div class="col-sm-7">
        	                                <input type="submit" name="submit_value" value="Submit" id="submit_button" class="btn btn-primary m-b-0">
        	                            </div>
        	                        </div>
		                        </div>
		                    </form>    
	                    </div>
	                </div>
	            </div>
	        </div>
	    </div>
	</div>
		<div class="modal fade" id="new_note" tabindex="-1">
	    <div class="modal-dialog" role="document">
	        <div class="modal-content">
	            <div class="modal-header">
	                <h5 class="modal-title">Add Calculator Values</h5>
	                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
	                    <span aria-hidden="true">&times;</span>
	                </button>
	            </div>
            	<div class="modal-body p-b-0">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="input-group">
                                <label class="col-form-label">Name</label>
                                <input type="text" class="form-control" id="calculation_name" name="name" required placeholder="Name">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="input-group">
                                <label class="col-form-label">Value</label>
                                <input type="text" class="form-control" id="calculation_value" name="value" required placeholder="Value">
                            </div>
                        </div>
                    </div>                        
                
	            </div>
	            <div class="modal-footer">
	                <button type="button" id="add_fields" class="btn btn-primary">Add</button>
	            </div>
	        </div>
	    </div>
	</div>
@endsection
	
@section('scripts')	
	
<script type="text/javascript">
$(document).ready( function(){
    $('#add_fields').click( function(){
        add_inputs()
        $("#new_note").modal("hide");
    });
    
    $(document).on('click', '.remove_fields', function() {
        $(this).closest('.record').remove();
    });
    function add_inputs(){
        var calculation_name = $('#calculation_name').val();
        var calculation_value = $('#calculation_value').val();
        
        var html = '  <div class="form-group row" ><label class="col-sm-4 col-form-label">'+calculation_name+'</label><div class="col-sm-6"><input id="model" name="calculation_values[' + calculation_name +']" type="text" value='+calculation_value+' required class="form-control"></div></div>'
        
        $('#add_calculation_div').append(html);
       $('#calculation_name').val('');
       $('#calculation_value').val('');
        //$('#counter').val( counter + 1 );
    }
});
</script>
@endsection