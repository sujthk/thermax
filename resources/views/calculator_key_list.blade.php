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
	                <h4>Calculator Keys</h4>
	            </div>
	            <div class="page-header-breadcrumb">
	                <ul class="breadcrumb-title">
	                    <li class="breadcrumb-item">
	                        <a href="{{ url('dashboard') }}">
	                            <i class="icofont icofont-home"></i>
	                        </a>
	                    </li>
	                    <li class="breadcrumb-item"><a href="#!">Calculator Keys</a>
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
	                    	  <div class="card-header">
	                        	<div class="">
		                            <h5>Add Calculator Key</h5>
		                            <button class="btn btn-primary btn-sm " data-toggle="modal" data-target="#calculator_key">New Calculator Key +</button>
                            	</div>
	                        </div>
	                        <div class="card-block">
	                            <div class="dt-responsive table-responsive">
	                                <table id="example" class="table table-striped table-bordered" >
	                                    <thead>
	                                        <tr>
	                                        	<th>S.No</th>
	                                            <th>Name</th>
	                                            <th style="width: 8%">Keys</th>
	                                            <th style="width: 8%">Action</th>
	                                        </tr>
	                                    </thead>
	                                    <tbody>
	                                    	@php ($i=1)
	                                    	@foreach ($calculator_keys as $calculator_key) 
	                                    		<tr>
	                                    			<td>{{ $i }}</td>
	                                    		    <td>{{ $calculator_key->name }}</td>
	                                    		    <td style="width: 8%">
	                                    		    	{{str_limit($calculator_key->keys, 95)  }} </td>

	                                    		   
		                                            <td>
		                                                <a href="#" data-toggle="modal" data-target="#calculator_key{{$calculator_key->id}}" class="btn btn-primary btn-sm">Edit</a>
	
		                                            </td> 
								 	<div class="modal fade" id="calculator_key{{$calculator_key->id}}" tabindex="-1">
									    <div class="modal-dialog" role="document">
									        <div class="modal-content">
									            <div class="modal-header">
									                <h5 class="modal-title">Edit Calculator Keys</h5>
									                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
									                    <span aria-hidden="true">&times;</span>
									                </button>
									            </div>
									            <form id="add_note" method="post" action="{{ url('/calculation-key/edit',[$calculator_key->id]) }}" enctype="multipart/form-data">
									            	<div class="modal-body p-b-0">
									                	{{ csrf_field() }}
									                    <div class="row">
									                        <div class="col-sm-12">
									                            <div class="input-group">
									                                <label class="col-form-label">Name</label> 
									                                <input type="text" class="form-control" name="name" readonly="" placeholder="Name" value="{{$calculator_key->name}}" >
									                            </div>
									                             <div class="input-group">
	                                <label class="col-form-label">Code</label> 
	                                <input type="text" class="form-control" name="code" readonly placeholder="Code" value="{{$calculator_key->code}}">
	                            </div>
									                            <div class="input-group">
									                                <label class="col-form-label">Keys</label> 
									                               <textarea class="form-control" name="keys" required  rows="15">{{$calculator_key->keys}}</textarea>
									                            </div>
									                        </div>
									                    </div>                      
										            </div>
										            <div class="modal-footer">
										                <button type="submit" class="btn btn-primary">Edit</button>
										            </div>
									            </form>
									        </div>
									    </div>
									</div>
	                                    		</tr>
	                                    		@php($i++)
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
	<div class="modal fade" id="calculator_key" tabindex="-1">
	    <div class="modal-dialog" role="document">
	        <div class="modal-content">
	            <div class="modal-header">
	                <h5 class="modal-title">Add Calculator Keys</h5>
	                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
	                    <span aria-hidden="true">&times;</span>
	                </button>
	            </div>
	            <form id="add_note" method="post" action="{{ url('/calculation-keys/add') }}" enctype="multipart/form-data">
	            	<div class="modal-body p-b-0">
	                
	                	{{ csrf_field() }}
	                    <div class="row">
	                        <div class="col-sm-12">
	                            <div class="input-group">
	                                <label class="col-form-label">Name</label> 
	                                <input type="text" class="form-control" name="name" required placeholder="Name">
	                            </div>
	                              <div class="input-group">
	                                <label class="col-form-label">Calculator</label> 
                                    <select name="code" id="code" class="form-control">
                                        @foreach ($calculators as $calculator)
                                            <option value="{{ $calculator->code }}">{{ $calculator->name }}</option>
                                        @endforeach
                                    
                                    </select>
	                            </div>
	                            <div class="input-group">
	                                <label class="col-form-label">Keys</label> 
	                               <textarea class="form-control" name="keys" required ></textarea>
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
<script type="text/javascript">
	$(document).ready(function() {
    $('#example').DataTable();
} );
</script>
	<script src="{{asset('bower_components/datatables.net/js/jquery.dataTables.min.js')}}"></script>
    <script src="{{asset('bower_components/datatables.net-buttons/js/dataTables.buttons.min.js')}}"></script>
	<script src="{{asset('bower_components/datatables.net-bs4/js/dataTables.bootstrap4.min.js')}}"></script>
	<script src="{{asset('bower_components/datatables.net-responsive/js/dataTables.responsive.min.js')}}"></script>
	<script src="{{asset('bower_components/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js')}}"></script>
	<script src="{{asset('assets/pages/data-table/js/data-table-custom.js')}}"></script>

@endsection