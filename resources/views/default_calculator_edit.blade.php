@extends('layouts.app') 

@section('styles')	
	

@endsection

@section('content')
	<div class="main-body">
	    <div class="page-wrapper">
	        <div class="page-header">
	            <div class="page-header-title">
	                <h4>Edit Calculator Default Values</h4>
	            </div>
	            <div class="page-header-breadcrumb">
	                <ul class="breadcrumb-title">
	                    <li class="breadcrumb-item">
	                        <a href="{{ url('dashboard') }}">
	                            <i class="icofont icofont-home"></i>
	                        </a>
	                    </li>
	                    <li class="breadcrumb-item"><a href="{{ url('default/calculators') }}">Calculators Default Values</a>
	                    </li>
	                    <li class="breadcrumb-item"><a href="#!">Edit Calculator Default Values</a>
	                    </li>
	                </ul>
	            </div>
	        </div>
	        <div class="page-body">
	            <div class="row">
	                <div class="col-sm-6">
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
		                            <h5>Edit Calculator Default Values</h5>
                            	</div>
	                        </div>
	                        <form id="edit_default_calculator" method="post" action="{{ url('default/calculators/edit',[$default_calculator->id]) }}" enctype="multipart/form-data">
                				{{ csrf_field() }}
		                        <div class="card-block">
		                        	<div class="form-group row">
		                        	    <label class="col-sm-4 col-form-label">Name</label>
		                        	    <div class="col-sm-6">
		                        	        <input id="name" name="name" type="text" value="{{ $default_calculator->name }}" required class="form-control">
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-4 col-form-label">Model</label>
		                        	    <div class="col-sm-6">
		                        	        <input id="model" name="model" type="number" value="{{ $default_calculator->model }}" required class="form-control">
		                        	    </div>
		                        	</div>
		                        	<?php
			                        	function floattostr( $val )
			                        	{
			                        		return rtrim(rtrim(number_format($val, 8, ".", ""), '0'), '.');

			                        	}
		                        	?>
		                        	
		                        	@foreach ($default_value_keys as $default_value_key)
		                        		
		                        		@if(is_array($default_values[$default_value_key]))
		                        			@php($value = implode(",",$default_values[$default_value_key]))
		                        		@elseif (is_string($default_values[$default_value_key]))
		                        			@php($value = $default_values[$default_value_key])	
		                        		@elseif (is_float($default_values[$default_value_key]))
		                        			@php($value = floattostr($default_values[$default_value_key]))	
		                        		@elseif (is_bool($default_values[$default_value_key]))
		                        			@php($value = ($default_values[$default_value_key]) ? 'true' : 'false')	
		                        		@else
		                        			@php($value = $default_values[$default_value_key])	
		                        		@endif
		                        	    <div class="form-group row">
		                        	        <label class="col-sm-4 col-form-label">{{ $default_value_key }}</label>
		                        	        <div class="col-sm-6">
		                        	            <input id="model" name="default_values[{{ $default_value_key }}]" type="text" value="{{ $value }}" required class="form-control">
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
@endsection
	
@section('scripts')	
	

@endsection