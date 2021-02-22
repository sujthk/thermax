@extends('layouts.app') 

@section('styles')	


@endsection

@section('content')
	<div class="main-body">
	    <div class="page-wrapper">
	        <div class="page-header">
	            <div class="page-header-title">
	                <h4>Add Metallurgy</h4>
	            </div>
	            <div class="page-header-breadcrumb">
	                <ul class="breadcrumb-title">
	                    <li class="breadcrumb-item">
	                        <a href="{{ url('dashboard') }}">
	                            <i class="icofont icofont-home"></i>
	                        </a>
	                    </li>
	                    <li class="breadcrumb-item"><a href="{{ url('metallurgies') }}">Metallurgies</a>
	                    </li>
	                    <li class="breadcrumb-item"><a href="#!">Add Metallurgy</a>
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
		                            <h5>Add Metallurgy</h5>
                            	</div>
	                        </div>
	                        <form id="add_user" method="post" action="{{ url('metallurgies/add') }}" enctype="multipart/form-data">
                				{{ csrf_field() }}
		                        <div class="card-block">
		                        	<div class="form-group row">
		                        	    <label class="col-sm-3 col-form-label">Name</label>
		                        	    <div class="col-sm-6">
		                        	        <input id="name" name="name" type="text" value="{{ old('name') }}" required class="form-control">
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-3 col-form-label">Display Name</label>
		                        	    <div class="col-sm-6">
		                        	        <input id="display_name" name="display_name" type="text" value="{{ old('display_name') }}" required class="form-control">
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-3 col-form-label">Default Thickness</label>
		                        	    <div class="col-sm-6">
		                        	        <input id="default_thickness" name="default_thickness" type="text" value="{{ old('default_thickness') }}" required class="form-control">
		                        	    </div>
		                        	</div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">OD (mm)</label>
                                        <div class="col-sm-6">
                                            <input id="ode" name="ode" type="text" value="{{ old('ode') }}" required class="form-control">
                                        </div>
                                    </div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-3 col-form-label">Min Thickness</label>
		                        	    <div class="col-sm-6">
		                        	        <input id="min_thickness" name="min_thickness" type="text" value="{{ old('min_thickness') }}" required class="form-control">
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-3 col-form-label">Max Thickness</label>
		                        	    <div class="col-sm-6">
		                        	        <input id="max_thickness" name="max_thickness" type="text" value="{{ old('max_thickness') }}" required class="form-control">
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-3 col-form-label">Eva Min Velocity</label>
		                        	    <div class="col-sm-6">
		                        	        <input id="eva_min_velocity" name="eva_min_velocity" type="text" value="{{ old('eva_min_velocity') }}" required class="form-control">
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-3 col-form-label">Eva Max Velocity</label>
		                        	    <div class="col-sm-6">
		                        	        <input id="eva_max_velocity" name="eva_max_velocity" type="text" value="{{ old('eva_max_velocity') }}" required class="form-control">
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-3 col-form-label">Abs Min Velocity</label>
		                        	    <div class="col-sm-6">
		                        	        <input id="abs_min_velocity" name="abs_min_velocity" type="text" value="{{ old('abs_min_velocity') }}" required class="form-control">
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-3 col-form-label">Abs Max Velocity</label>
		                        	    <div class="col-sm-6">
		                        	        <input id="abs_max_velocity" name="abs_max_velocity" type="text" value="{{ old('abs_max_velocity') }}" required class="form-control">
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-3 col-form-label">Con Min Velocity</label>
		                        	    <div class="col-sm-6">
		                        	        <input id="con_min_velocity" name="con_min_velocity" type="text" value="{{ old('con_min_velocity') }}" required class="form-control">
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-3 col-form-label">Con Max Velocity</label>
		                        	    <div class="col-sm-6">
		                        	        <input id="con_max_velocity" name="con_max_velocity" type="text" value="{{ old('con_max_velocity') }}" required class="form-control">
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