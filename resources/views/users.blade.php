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
	                <h4>Users</h4>
	            </div>
	            <div class="page-header-breadcrumb">
	                <ul class="breadcrumb-title">
	                    <li class="breadcrumb-item">
	                        <a href="{{ url('dashboard') }}">
	                            <i class="icofont icofont-home"></i>
	                        </a>
	                    </li>
	                    <li class="breadcrumb-item"><a href="#!">Users</a>
	                    </li>
	                </ul>
	            </div>
	        </div>
	        <div class="page-body">
	            <div class="row">
	                <div class="col-sm-12">
	                    <!-- Zero config.table start -->
	                    @if($errors->any())
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
		                            <h5>Users</h5>
		                            <a href="{{ url('users/add') }}" class="btn btn-sm btn-primary">New User +</a>
                            	</div>
	                        </div>
	                        <div class="card-block">
	                            <div class="dt-responsive table-responsive">
	                                <table id="simpletable" class="table table-striped table-bordered nowrap">
	                                    <thead>
	                                        <tr>
	                                            <th>Name</th>
	                                            <th>Email</th>
	                                            <th>User Type</th>
	                                            <th>Last Logged In</th>
	                                            <th style="width: 10%">Status</th>
	                                            <th style="width: 8%">Action</th>
	                                        </tr>
	                                    </thead>
	                                    <tbody>
	                                    	@foreach ($users as $user) 
	                                    		<tr>
	                                    		    <td>{{ $user->name }}</td>
	                                    		    <td>{{ $user->email }}</td>
	                                    		    <td>{{ $user->user_type }}</td>	
	                                    		    <td>{{ $user->last_logged_in }}</td>
	                                    		    <td>
		                                                @if ($user->status)
		                                                    <a href="{{ url('users/status',[$user->id]) }}/0" class="btn btn-success btn-sm">Active</a>
		                                                @else    
		                                                    <a href="{{ url('users/status',[$user->id]) }}/1" class="btn btn-danger btn-sm">Deactivate</a></td>
		                                                @endif 
		                                            </td> 
		                                            <td>
		                                            	 <a href="{{ url('user-profile/view',[$user->id]) }}" class="btn btn-info btn-sm">View</a>
		                                                <a href="{{ url('users/edit',[$user->id]) }}" class="btn btn-primary btn-sm">Edit</a>
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