@extends('layouts.app') 

@section('styles')	
	
@endsection

@section('content')
	<div class="main-body">
	    <div class="page-wrapper">
	        <!-- <div class="page-header">
	            <div class="page-header-title">
	                <h4>Chiller Calculation Testing</h4>
	            </div>
	            <div class="page-header-breadcrumb">
	                <ul class="breadcrumb-title">
	                    <li class="breadcrumb-item">
	                        <a href="{{ url('dashboard') }}">
	                            <i class="icofont icofont-home"></i>
	                        </a>
	                    </li>
	                    <li class="breadcrumb-item"><a href="#!">Chiller Calculation Testing</a>
	                    </li>
	                </ul>
	            </div>
	        </div> -->
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
	                        	<h5>Calculator Reports</h5>
	                        </div>
	                        <div class="card-header">
	                        	<div class="">
		                            <h5>Export Calculator Reports</h5>
                                    <br>
                                    <br>
		                            <div class="card-body">
		                            	<div class="row">
								            <div class="col-sm-12">
								            	<form action="{{url('calculator-reports')}}" method="POST" enctype="multipart/form-data">
									                {{ csrf_field() }}
									            	<select name="username" id="username" class="form-control"  required="">
									            		<option value="all">---Select All---</option>
										            	@foreach($users as $user)

			 												<option  value="{{$user->username}}">{{$user->name}} - {{ $user->username }}</option>
			 											@endforeach
		 											</select>
		 											<br>
									            	 <button class="btn btn-primary m-b-0" type="submit">Export Report</button>
									            </form>	 
								            </div>
							            </div>
							        </div>
                            	</div>
	                        </div>
	                    </div>

	                    <!-- Scroll - Vertical, Dynamic Height table end -->
	                    <!-- Language - Comma Decimal Place table end -->
	                </div>
	            </div>
	        </div>
	    </div>
	</div>
@endsection
	
@section('scripts')	
	

@endsection