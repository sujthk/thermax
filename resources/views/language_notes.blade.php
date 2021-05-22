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
	        <!-- <div class="page-header">
	            <div class="page-header-title">
	                <h4>Language Notes</h4>
	            </div>
	            <div class="page-header-breadcrumb">
	                <ul class="breadcrumb-title">
	                    <li class="breadcrumb-item">
	                        <a href="{{ url('dashboard') }}">
	                            <i class="icofont icofont-home"></i>
	                        </a>
	                    </li>
	                    <li class="breadcrumb-item"><a href="#!">Language Notes</a>
	                    </li>
	                </ul>
	            </div>
	        </div> -->
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
	                        <div style="display: none;" class="card-header">
	                        	<div class="">
		                            <h5>Language Notes</h5>
		                            <button class="btn btn-primary btn-sm " data-toggle="modal" data-target="#new_note">New Language Note +</button>
                            	</div>
	                        </div>
                            <div class="card-header">
                                <div class="">
                                    <h5>Language Notes</h5>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-sm-6">
                                                
                                                <form action="{{url('languages-notes/import-excel')}}" method="POST" enctype="multipart/form-data">
                                                    {{ csrf_field() }}
                                                    <input type="file" name="file" class="form-control" required="">
                                                    <br>
                                                    <button class="btn btn-success" type="submit">Import Language Data</button>
                                                   
                                                </form>
                                            </div>
                                            <div class="col-sm-6">
                                                <form action="{{url('languages-notes/export-excel')}}" method="POST" enctype="multipart/form-data">
                                                    {{ csrf_field() }}
                                                     <button class="btn btn-warning" type="submit">Export Language Data</button>
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
                                                <th>Key Name</th>
                                                <th>Language</th>
                                                <th>Value</th>
                                                <th style="width: 8%">Action</th>
                                            </tr>
	                                    </thead>
	                                    <tbody>
	                                    	@foreach ($language_values as $language_value) 
	                                    		<tr>
	                                    		    <td>{{ $language_value->language_key->name }}</td>
                                                    <td>{{ $language_value->language->name }}</td>  
                                                    <td>{{ $language_value->value }}</td>  
		                                            <td>
		                                                <button class="btn btn-primary btn-sm " data-toggle="modal" data-target="#edit_note{{ $language_value->id }}">Edit</button>
		                                                 <a href="{{ url('languages-notes/delete',[$language_value->id]) }}" class="btn btn-danger btn-sm">Delete</a>
                                                         
		                                                <div class="modal fade" id="edit_note{{ $language_value->id }}" tabindex="-1">
		                                                    <div class="modal-dialog" role="document">
		                                                        <div class="modal-content">
		                                                            <div class="modal-header">
		                                                                <h5 class="modal-title">Edit Language Note</h5>
		                                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
		                                                                    <span aria-hidden="true">&times;</span>
		                                                                </button>
		                                                            </div>
		                                                            <form id="add_user" method="post" action="{{ url('languages-notes/edit',[$language_value->id]) }}" enctype="multipart/form-data">
			                                                            <div class="modal-body p-b-0">
		                                                                	{{ csrf_field() }}
		                                                                    <div class="row">
                                                                                <div class="col-sm-8">
                                                                                    <div class="input-group">
                                                                                        <label class="col-form-label">Key Name</label>
                                                                                        <input type="text" class="form-control" name="note_name" value="{{ $language_value->language_key->name }}" readonly placeholder="Name">
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="row">
                                                                                <div class="col-sm-8">
                                                                                    <div class="input-group">
                                                                                        <label class="col-form-label">Language</label>
                                                                                        <input type="text" class="form-control" name="note_value" value="{{ $language_value->language->name }}" readonly placeholder="Value">
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="row">
                                                                                <div class="col-sm-8">
                                                                                    <div class="input-group">
                                                                                        <label class="col-form-label">Value</label>
                                                                                        <input type="text" class="form-control" name="key_value" value="{{ $language_value->value }}" required placeholder="Value">
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
	<div class="modal fade" id="new_note" tabindex="-1">
	    <div class="modal-dialog" role="document">
	        <div class="modal-content">
	            <div class="modal-header">
	                <h5 class="modal-title">Add Language Note</h5>
	                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
	                    <span aria-hidden="true">&times;</span>
	                </button>
	            </div>
	            <form id="add_note" method="post" action="{{ url('languages-notes/add') }}" enctype="multipart/form-data">
	            	<div class="modal-body p-b-0">
	                
	                	{{ csrf_field() }}
	                    <div class="row">
                            <div class="col-sm-12">
                                <div class="input-group">
                                    <label class="col-form-label">Key Name</label>
                                    <input type="text" class="form-control" name="note_name" required placeholder="Name">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="input-group">
                                    <label class="col-form-label">Language</label>
                                    <select name="language_id" id="language" required class="form-control">
                                        <option value="">-- Select Language --</option>
                                        @foreach ($languages as $language)
                                            <option  value="{{$language->id}}">{{$language->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="input-group">
                                    <label class="col-form-label">Value</label>
                                    <input type="text" class="form-control" name="key_value" required placeholder="Value">
                                </div>
                            </div>
                        </div>                          
	                
		            </div>
		            <div class="modal-footer">
		                <button type="submit" class="btn btn-primary">Add</button>
		            </div>
	            </form>
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