@extends('layouts.app') 
@section('styles')	
@endsection
@section('content')
	<div class="main-body">
	    <div class="page-wrapper">
	        <div class="page-header">
	            <div class="page-header-title">
	                <h4>Edit Group Calculation</h4>
	            </div>
	            <div class="page-header-breadcrumb">
	                <ul class="breadcrumb-title">
	                    <li class="breadcrumb-item">
	                        <a href="{{ url('dashboard') }}">
	                            <i class="icofont icofont-home"></i>
	                        </a>
	                    </li>
	                    <li class="breadcrumb-item"><a href="{{ url('tube-metallurgy/calculators') }}">Group Calculation</a>
	                    </li>
	                    <li class="breadcrumb-item"><a href="#!">Edit Group Calculation</a>
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
		                            <h5>Edit Group Calculator</h5>
                            	</div>
	                        </div>
	                        <form id="edit_default_calculator" method="post" action="{{ url('/group-calcluation/update',[$group_calculation->id]) }}" enctype="multipart/form-data">
                				{{ csrf_field() }}
		                        <div class="card-block">
		                        	<div class="form-group row">
		                        	    <label class="col-sm-3 col-form-label">Name</label>
		                        	    <div class="col-sm-8">
		                        	        <input id="name" name="name" type="text" value="{{ $group_calculation->name }}" required class="form-control">
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Calculators</label>
                                        <div class="col-sm-8">
                                            @foreach ($calculators as $calculator)  
                                    			@if (in_array($calculator->id, $selected_calculators)) 
                                    			<input type="checkbox"  name="calculators[]" value="{{ $calculator->id }}" checked="">&nbsp;&nbsp;{{ ucwords($calculator->name) }}<br>
                                    			@else   
												<input type="checkbox"  name="calculators[]" value="{{ $calculator->id }}">&nbsp;&nbsp;{{ ucwords($calculator->name) }}<br>
												@endif
											@endforeach
                                        </div>
                                    </div>
                                    <div class="form-group ">
	                                   
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