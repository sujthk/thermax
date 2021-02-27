@extends('layouts.app') 

@section('styles')	


@endsection

@section('content')
	<div class="main-body">
	    <div class="page-wrapper">
	        <div class="page-header">
	            <div class="page-header-title">
	                <h4>Add Metallurgy Calculator</h4>
	            </div>
	            <div class="page-header-breadcrumb">
	                <ul class="breadcrumb-title">
	                    <li class="breadcrumb-item">
	                        <a href="{{ url('dashboard') }}">
	                            <i class="icofont icofont-home"></i>
	                        </a>
	                    </li>
	                    <li class="breadcrumb-item"><a href="{{ url('tube-metallurgy/calculators') }}">Metallurgy Calculators</a>
	                    </li>
	                    <li class="breadcrumb-item"><a href="#!">Add Metallurgy Calculator</a>
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
		                            <h5>Add Metallurgy Calculator</h5>
                            	</div>
	                        </div>
	                        <form id="add_user" method="post" action="{{ url('tube-metallurgy/calculators/add') }}" enctype="multipart/form-data">
                				{{ csrf_field() }}
		                        <div class="card-block">
		                        	<div class="form-group row">
		                        	    <label class="col-sm-3 col-form-label">Name</label>
		                        	    <div class="col-sm-6">
		                        	        <input id="name" name="name" type="text" value="{{ old('name') }}" required class="form-control">
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Code</label>
                                        <div class="col-sm-6">
                                            <select name="code" id="code" class="form-control">
                                                @foreach ($calculators as $calculator)
                                                    <option value="{{ $calculator->code }}">{{ $calculator->name }}</option>
                                                @endforeach
 
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group row">
		                        	    <label class="col-sm-3 col-form-label">Min Model</label>
		                        	    <div class="col-sm-6">
		                        	        <input id="min_model" name="min_model" type="number" value="{{ old('min_model') }}" required class="form-control">
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-3 col-form-label">Max Model</label>
		                        	    <div class="col-sm-6">
		                        	        <input id="max_model" name="max_model" type="number" value="{{ old('max_model') }}" required class="form-control">
		                        	    </div>
		                        	</div>
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