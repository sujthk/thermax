@extends('layouts.app') 

@section('styles')	
	

@endsection

@section('content')
	<div class="main-body">
	    <div class="page-wrapper">
	        <div class="page-header">
	            <div class="page-header-title">
	                <h4>Edit Metallurgy Calculator</h4>
	            </div>
	            <div class="page-header-breadcrumb">
	                <ul class="breadcrumb-title">
	                    <li class="breadcrumb-item">
	                        <a href="{{ url('dashboard') }}">
	                            <i class="icofont icofont-home"></i>
	                        </a>
	                    </li>
	                    <li class="breadcrumb-item"><a href="{{ url('tube-metallurgy/calculators') }}">Metallurgy Calculator</a>
	                    </li>
	                    <li class="breadcrumb-item"><a href="#!">Edit Metallurgy Calculator</a>
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
		                            <h5>Edit Metallurgy Calculator</h5>
                            	</div>
	                        </div>
	                        <form id="edit_default_calculator" method="post" action="{{ url('tube-metallurgy/calculators/edit',[$metallurgy_calculator->id,$tube_type]) }}" enctype="multipart/form-data">
                				{{ csrf_field() }}
		                        <div class="card-block">
		                        	<div class="form-group row">
		                        	    <label class="col-sm-4 col-form-label">Name</label>
		                        	    <div class="col-sm-6">
		                        	        <input id="name" name="name" type="text" readonly value="{{ $metallurgy_calculator->name }}" required class="form-control">
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">    
		                        	    <label class="col-sm-4 col-form-label">Model</label>
		                        	    <div class="col-sm-6">
		                        	        <input id="model" name="model" type="number" readonly value="{{ $metallurgy_calculator->model }}" required class="form-control">
		                        	    </div>
		                        	</div>
	                        		<div class="">
	                        			@if($tube_type == 'eva')
			                            	<h5>Evaporator Options</h5>
			                            @elseif($tube_type == 'abs')	
			                            	<h5>Absorber Options</h5>
			                            @else
			                            	<h5>Condenser Options</h5>
			                            @endif	
	                            	</div>
	                            	<br>
		                        	@foreach ($metallurgy_values as $metallurgy_value)
		                        	    <div class="form-group row">
	                        	            <label class="col-sm-4 col-form-label">Label</label>
	                        	            <div class="col-sm-6">
	                        	                <input id="name" name="labels[]" type="text" value="{{ $metallurgy_value['name'] }}" required class="form-control">
	                        	            </div>
	                        	        </div>
	                        	        <div class="form-group row">    
	                        	            <label class="col-sm-4 col-form-label">Value</label>
	                        	            <div class="col-sm-6">
	                        	                <input id="model" name="values[]" type="number" value="{{ $metallurgy_value['value'] }}" required class="form-control">
	                        	            </div>
		                        	    </div>
		                        	    <br>
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