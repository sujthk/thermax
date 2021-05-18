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
	                <h4>Calculators</h4>
	            </div>
	            <div class="page-header-breadcrumb">
	                <ul class="breadcrumb-title">
	                    <li class="breadcrumb-item">
	                        <a href="{{ url('dashboard') }}">
	                            <i class="icofont icofont-home"></i>
	                        </a>
	                    </li>
	                    <li class="breadcrumb-item"><a href="#!">Calculators</a>
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
	                        <div class="card-block">
	                            <div class="dt-responsive table-responsive">
	                                <table id="simpletable" class="table table-striped table-bordered nowrap">
	                                    <thead>
	                                        <tr>
                                                <th>Name</th>
                                                <th>Image</th>
                                                <th>Display Name</th>
                                                <th style="width: 8%">Action</th>
                                            </tr>
	                                    </thead>
	                                    <tbody>
	                                    	@foreach ($calculators as $calculator) 
	                                    		<tr>
	                                    		    <td>{{ $calculator->name }}</td>
                                                    <td> <img src="{{$calculator->image_path}}" alt="" width="30" height="30"></td>  
                                                    <td>{{ $calculator->display_name }}</td>  
		                                            <td>
		                                                <button class="btn btn-primary btn-sm " data-toggle="modal" data-target="#edit_note{{ $calculator->id }}">Edit</button>
                                                         
		                                                <div class="modal fade" id="edit_note{{ $calculator->id }}" tabindex="-1">
		                                                    <div class="modal-dialog" role="document">
		                                                        <div class="modal-content">
		                                                            <div class="modal-header">
		                                                                <h5 class="modal-title">Edit Calculator</h5>
		                                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
		                                                                    <span aria-hidden="true">&times;</span>
		                                                                </button>
		                                                            </div>
		                                                            <form id="edit_calculator" method="post" action="{{ url('/default/calculator-list/edit',[$calculator->id]) }}" enctype="multipart/form-data">
			                                                            <div class="modal-body p-b-0">
		                                                                	{{ csrf_field() }}
		                                                                    <div class="row">
                                                                                <div class="col-sm-8">
                                                                                    <div class="input-group">
                                                                                        <label class="col-form-label">Image</label>
                                                                                        <input type="file"  class="form-control" placeholder=" Image" id="name" name="image">
                                                                                        <img src="{{$calculator->image_path}}" alt="" width="30" height="30">
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="row">
                                                                                <div class="col-sm-8">
                                                                                    <div class="input-group">
                                                                                        <label class="col-form-label">Display Name</label>
                                                                                        <input type="text" class="form-control" name="display_name" value="{{ $calculator->display_name }}" required placeholder="Value">
                                                                                    </div>
                                                                                </div>
                                                                            </div>                                                         
			                                                            </div>
			                                                            <div class="modal-footer">
			                                                                <button type="submit" class="btn btn-primary">Update</button>
			                                                            </div>

		                                                            </form>
		                                                        </div>
		                                                    </div>
		                                                </div>
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