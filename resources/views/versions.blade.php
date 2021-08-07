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
		                            <h5>Add Version</h5>
		                            <button class="btn btn-primary btn-sm " data-toggle="modal" data-target="#new_note">New Version +</button>
                            	</div>
	                        </div>
	                        <div class="card-block">
	                            <div class="dt-responsive table-responsive">
	                                <table id="simpletable" class="table table-striped table-bordered nowrap">
	                                    <thead>
	                                        <tr>
                                                <th>Version</th>
                                                <th>Remark</th>
                                            </tr>
	                                    </thead>
	                                    <tbody>
	                                    	@foreach ($versions as $version) 
	                                    		<tr>
	                                    		    <td>{{ $version->version }}</td>
	                                    		    <td>{{ $version->remarks }}</td>
                                                    
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
	                <h5 class="modal-title">Add Version</h5>
	                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
	                    <span aria-hidden="true">&times;</span>
	                </button>
	            </div>
	            <form id="add_note" method="post" action="{{ url('versions/add') }}" enctype="multipart/form-data">
	            	<div class="modal-body p-b-0">
	                
	                	{{ csrf_field() }}
	                    <div class="row">
                            <div class="col-sm-12">
                                <div class="input-group">
                                    <label class="col-form-label">Version</label>
                                    <input type="text" class="form-control" name="version" required placeholder="Version">
                                </div>
                                <div class="input-group">
                                    <label class="col-form-label">Remarks</label>
                                    <input type="text" class="form-control" name="remarks" required placeholder="Remarks">
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