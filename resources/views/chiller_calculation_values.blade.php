@extends('layouts.app') 

@section('styles')	
	<!-- Data Table Css -->
	<link rel="stylesheet" type="text/css" href="{{asset('bower_components/datatables.net-bs4/css/dataTables.bootstrap4.min.css')}}">
	<link rel="stylesheet" type="text/css" href="{{asset('dark-assets/assets/pages/data-table/css/buttons.dataTables.min.css')}}">
	<link rel="stylesheet" type="text/css" href="{{asset('bower_components/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css')}}">
@endsection

@section('content')
	<div class="main-body">
	    <div class="page-wrapper">
	        <div class="page-header">
	            <div class="page-header-title">
	                <h4>Chiller Calculation Values</h4>
	            </div>
	            <div class="page-header-breadcrumb">
	                <ul class="breadcrumb-title">
	                    <li class="breadcrumb-item">
	                        <a href="{{ url('dashboard') }}">
	                            <i class="icofont icofont-home"></i>
	                        </a>
	                    </li>
	                    <li class="breadcrumb-item"><a href="#!">Chiller Calculation Values</a>
	                    </li>
	                </ul>
	            </div>
	        </div>
	        <div class="page-body">
	            <div class="row">
	                <div class="col-sm-12">
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
		                            <h5>Calculators</h5>
		                            <div class="card-body">
		                            	<div class="row">
		                            	     <div class="col-sm-6">
		                            		
        							            <form action="{{url('importExcel')}}" method="POST" enctype="multipart/form-data">
        							                {{ csrf_field() }}
        							                <input type="file" name="file" class="form-control" required="">
        							                <br>
        							                <button class="btn btn-success" type="submit">Import User Data</button>
        							               
        							            </form>
							                </div>
							            <div class="col-sm-6">
							            	<form action="{{url('importExport')}}" method="POST" enctype="multipart/form-data">
    							                {{ csrf_field() }}
    							            	<select name="code" id="code" class="form-control"  required="">
    							            		<option value="">---Select Calculator---</option>
    							            	@foreach($calculator_keys as $calculator_key)

     											<option  value="{{$calculator_key->code}}">{{$calculator_key->name}}</option>
     											@endforeach
     											</select><br>
    							            	 <button class="btn btn-warning" type="submit">Export User Data</button>
                                             </form>
							            </div>
							            </div>
							        </div>
                            	</div>
	                        </div>
	                        <div class="card-block">
	                            <div class="dt-responsive table-responsive">
	                                <table id="simpletable" class="table table-striped table-bordered nowrap">
	                                    <thead>
	                                        <tr>
                                                <th>Name</th>
	                                            <th>Code</th>
	                                            <th>Model</th>
	                                            <th style="width: 8%">Action</th>
	                                        </tr>
	                                    </thead>
	                                    <tbody>
	                                    	@foreach ($chiller_calculation_values as $chiller_calculation_value) 
	                                    		<tr>
                                                    <td>{{ $chiller_calculation_value->name }}</td>
	                                    		    <td>{{ $chiller_calculation_value->code }}</td>
	                                    		    <td>{{ $chiller_calculation_value->min_model }}</td> 
		                                            <td>
		                                                <!-- <a href="{{ url('chiller/calculation-values/edit',[$chiller_calculation_value->id]) }}" class="btn btn-primary btn-sm">Edit</a> -->
                                                        <a href="{{ url('chiller/calculation-values/delete',[$chiller_calculation_value->id]) }}" class="btn btn-primary btn-sm">Delete</a>
		                                            </td> 
	                                    		</tr>
	                                    	@endforeach
	                                    </tbody>	
	                                </table>
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
	<script src="{{asset('bower_components/datatables.net/js/jquery.dataTables.min.js')}}"></script>
    <script src="{{asset('bower_components/datatables.net-buttons/js/dataTables.buttons.min.js')}}"></script>
	<script src="{{asset('bower_components/datatables.net-bs4/js/dataTables.bootstrap4.min.js')}}"></script>
	<script src="{{asset('bower_components/datatables.net-responsive/js/dataTables.responsive.min.js')}}"></script>
	<script src="{{asset('bower_components/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js')}}"></script>
	<script src="{{asset('assets/pages/data-table/js/data-table-custom.js')}}"></script>

@endsection