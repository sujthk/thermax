
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
	                <h4>Time Line</h4>
	            </div>
	            <div class="page-header-breadcrumb">
	                <ul class="breadcrumb-title">
	                    <li class="breadcrumb-item">
	                        <a href="{{ url('dashboard') }}">
	                            <i class="icofont icofont-home"></i>
	                        </a>
	                    </li>
	                    <li class="breadcrumb-item"><a href="#!">Time Line</a>
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
		                            <h5>Add Time Line</h5>
		                            <button class="btn btn-primary btn-sm " data-toggle="modal" data-target="#calculator_key">New Time Line +</button>
                            	</div>
	                        </div>
	                        <div class="card-block">
	                            <div class="dt-responsive table-responsive">
	                                <table id="simpletable" class="table table-striped table-bordered nowrap">
	                                    <thead>
	                                        <tr>
	                                        	<th>S.No</th>
	                                        	<th>Image</th>
	                                            <th style="width: 8%">Title</th>
	                                            <th >Description</th>
	                                            <th style="width: 8%">Action</th>
	                                        </tr>
	                                    </thead>
	                                    <tbody>
	                                    	@php ($i=1)
	                                    	@foreach ($time_lines as $time_line) 
	                                    		<tr>
	                                    			<td>{{ $i }}</td>
	                                    		    
	                                    		    <td> <img src="{{$time_line->image_path}}" alt="" width="30" height="30"></td>
	                                    		    <td>{{ $time_line->name }}</td>
	                                    		   <td>

	                                    		   	{{str_limit($time_line->description, 90)  }}</td>
		                                            <td>
		                                                <a href="#" data-toggle="modal" data-target="#timeline{{$time_line->id}}" class="btn btn-primary btn-sm">Edit</a>
		                                                <button onclick="deleteData({{ $time_line->id }})" class="btn btn-danger btn-sm">Delete</button>
		                                               
	
		                                            </td> 
								 	<div class="modal fade" id="timeline{{$time_line->id}}" tabindex="-1">
									    <div class="modal-dialog" role="document">
									        <div class="modal-content">
									            <div class="modal-header">
									                <h5 class="modal-title">Edit Time Line</h5>
									                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
									                    <span aria-hidden="true">&times;</span>
									                </button>
									            </div>
									            <form id="add_note" method="post" action="{{ url('/time_line/edit',[$time_line->id]) }}" enctype="multipart/form-data">
									            	<div class="modal-body p-b-0">
									                	{{ csrf_field() }}
									                    <div class="row">
									                        <div class="col-sm-12">
									                            <label class="col-form-label">Title</label> 
									                            <div class="input-group">
									                                <input type="text" class="form-control" name="name" required="" placeholder="Title" value="{{$time_line->name}}" >
									                            </div>
									                            <label class="col-form-label">Image</label> 
									                            <div class="input-group">
									                                <input type="file" class="form-control" name="image" required >
									                            </div>
									                            <label class="col-form-label">Url</label> 
									                            <div class="input-group">
									                                <input type="text" class="form-control" name="url_link" required="" placeholder="Url" value="{{$time_line->url_link}}" >
									                            </div>
									                             <label class="col-form-label">Description</label> 
									                        	<div class="input-group">
								                               
								                                <textarea class="form-control" required="" rows="10" name="description">{{$time_line->description}}</textarea>
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
	                <h5 class="modal-title">Add Time Line</h5>
	                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
	                    <span aria-hidden="true">&times;</span>
	                </button>
	            </div>
	            <form id="add_note" method="post" action="{{ url('/time_line/add') }}" enctype="multipart/form-data">
	            	<div class="modal-body p-b-0">
	                
	                	{{ csrf_field() }}
	                    <div class="row">
	                        <div class="col-sm-12">
	                  
	                                <label class="col-form-label">Title</label>
	                      
	                            <div class="input-group">
	                                <input type="text" class="form-control" name="name" required placeholder="title">
	                            </div>
	                            <label class="col-form-label">Image</label> 
	                            <div class="input-group">
	                                <input type="file" class="form-control" name="image" required >
	                            </div>
	                            <label class="col-form-label">Url</label> 
	                            <div class="input-group">
	                                <input type="text" class="form-control" name="url_link" required="" placeholder="Url" >
	                            </div>
	                           
	                            <label class="col-form-label">Description</label> 
	                               
	                           
	                             <div class="input-group">
	                               
	                                <textarea class="form-control" required="" name="description" rows="6"></textarea>
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
	 <script>
        function deleteData(id){
            var csrf_token=$('meta[name="csrf_token"]').attr('content');
            Swal.fire({
               	  title: 'Are you sure?',
				  text: "You won't be able to revert this!",
				  icon: 'warning',
				  showCancelButton: true,
				  confirmButtonColor: '#3085d6',
				  cancelButtonColor: '#d33',
				  confirmButtonText: 'Yes, delete it!'
            })
            .then((result) => {
                if (result.value){
                    $.ajax({
                        url : "{{ url('/time_line/destroy')}}" + '/' + id,
                        type : "get",
                        data : {'_token' :csrf_token},
                        success: function(data){

                            location.reload();

                        },
                        error : function(){
                            Swal.fire({
                                title: 'Opps...',
                                text : data.message,
                                type : 'error',
                                timer : '1500'
                            })
                        }
                    })
                } else {
                	Swal.fire("Your file is safe!");
                }
            });
        }
    </script>

@endsection