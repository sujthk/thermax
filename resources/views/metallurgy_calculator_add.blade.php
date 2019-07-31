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
                                                <option value="D_S2">S2 Series</option>
                                                <option value="D_S2_SMALL">S2 Series - Small</option>
                                                <option value="D_S1">S1 Series</option>
                                                <option value="D_G2">Direct G2 Series</option>
                                                <option value="D_G2_SMALL">Direct G2 Series - Small</option>
                                                <option value="D_E2">E2 Series</option>
                                                <option value="D_H2">H2 Series</option>
                                                <option value="D_H1">H1 Series</option>
                                                <option value="D_L5">L5 Series</option>
                                                <option value="D_5G">5G Series</option>
                                                <option value="D_STEAM_FIRED">Steam Fired</option>
                                                <option value="D_DIRECT_FIRED">Direct Fired</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group row">
		                        	    <label class="col-sm-3 col-form-label">Model</label>
		                        	    <div class="col-sm-6">
		                        	        <input id="model" name="model" type="number" value="{{ old('model') }}" required class="form-control">
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