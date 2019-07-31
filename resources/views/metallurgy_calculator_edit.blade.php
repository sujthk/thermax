@extends('layouts.app') 

@section('styles')	
	

@endsection

@section('content')
	<div class="main-body">
	    <div class="page-wrapper">
	        <div class="page-header">
	            <div class="page-header-title">
	                <h4>Edit Metallurgy Calculator</h4>
	            </div>
	            <div class="page-header-breadcrumb">
	                <ul class="breadcrumb-title">
	                    <li class="breadcrumb-item">
	                        <a href="{{ url('dashboard') }}">
	                            <i class="icofont icofont-home"></i>
	                        </a>
	                    </li>
	                    <li class="breadcrumb-item"><a href="{{ url('tube-metallurgy/calculators') }}">Metallurgy Calculator</a>
	                    </li>
	                    <li class="breadcrumb-item"><a href="#!">Edit Metallurgy Calculator</a>
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
		                            <h5>Edit Metallurgy Calculator</h5>
                            	</div>
	                        </div>
	                        <form id="edit_default_calculator" method="post" action="{{ url('tube-metallurgy/calculators/edit',[$metallurgy_calculator->id,$tube_type]) }}" enctype="multipart/form-data">
                				{{ csrf_field() }}
		                        <div class="card-block">
		                        	<div class="form-group row">
		                        	    <label class="col-sm-4 col-form-label">Name</label>
		                        	    <div class="col-sm-6">
		                        	        <input id="name" name="name" type="text" readonly value="{{ $metallurgy_calculator->name }}" required class="form-control">
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">    
		                        	    <label class="col-sm-4 col-form-label">Model</label>
		                        	    <div class="col-sm-6">
		                        	        <input id="model" name="model" type="number" readonly value="{{ $metallurgy_calculator->model }}" required class="form-control">
		                        	    </div>
		                        	</div>
	                        		
	                            	
		                        	<div class="form-group row">
                                        <label class="col-sm-4 col-form-label">Metallurgy</label>
                                        <div class="col-sm-6">
                                            <select name="metallurgy" id="metallurgy" class="form-control">
                                            	@foreach($metallurgies as $metallurgy)
                                            		@if ($loop->first)
                                        		        <option selected value="{{ $metallurgy->id }}">{{ $metallurgy->name }}</option>
                                        		    @else
                                        		   		<option value="{{ $metallurgy->id }}">{{ $metallurgy->name }}</option>  
                                        		    @endif
                                            		
                                            	@endforeach
                                            </select>
                                        </div>
                                        <div class="col-sm-2">
		 									<input type="button" class="btn btn-success" name="add_metallurgy" id="add_metallurgy" value="Add">
		 								</div>
                                    </div>
                                    <div class="">
	                        			@if($tube_type == 'eva')
			                            	<h5>Evaporator Options</h5>
			                            @elseif($tube_type == 'abs')	
			                            	<h5>Absorber Options</h5>
			                            @else
			                            	<h5>Condenser Options</h5>
			                            @endif	
	                            	</div>
	                            	<br>
                                    <table class="table table-striped table-bordered table-hover">
                                    	<thead>
                                    		<tr>
                                    			<th>Name</th>
                                    			<th>Value</th>
                                    			<th>Action </th>
                                    		</tr>
                                    	</thead>
                                    	<tbody id="unit">
		 									@php ($i = 1)
		 									@foreach ($metallurgy_values as $metallurgy_value) 
			 									<tr>
			 										<td><input  class='form-control' type='hidden' name='metallurgy[{{ $i }}][metallurgy_id]' id='old_metallurgy-{{ $i }}' value='{{ $metallurgy_value->metallurgy_id }}'/>
			 											<input  class='form-control' type='text' name='metallurgy[{{ $i }}][metallurgy_text]' id='metallurgy_text-{{ $i }}' value='{{ $metallurgy_value->metallurgy->name }}' required readonly/></td>
			 										<td ><input type='text' class='form-control' name='metallurgy[{{ $i }}][value]' value='{{ $metallurgy_value->value }}' required id='value-{{ $i }}'></td>
			 										<td><a class='btn btn-default remove' ><i class='icofont icofont-minus' style='color:red'></i></a></td>
			 									</tr>
			 									@php ($i++)	
											@endforeach

                                    	</tbody>
                                    	<input type="hidden" name="count" id="count" value="">
                                    </table>

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
	<script type="text/javascript">
	    $(document).ready(function () {
		    $('#add_metallurgy').click(function () {
		       var metallurgy_val = $("#metallurgy").val();
		       var metallurgy_text = $("#metallurgy option:selected").text();
		       add_unit(metallurgy_val,metallurgy_text);
		    });

		});	

	    var count = 1;
		function add_unit(metallurgy_val,metallurgy_text) {	
			if(metallurgy_val != '') {
				var row="<tr><td><input  class='form-control' type='hidden' name='metallurgy["+count+"][metallurgy_id]' id='metallurgy_id-"+count+"' value='"+metallurgy_val+"' required /><input  class='form-control' type='text' name='metallurgy["+count+"][metallurgy_text]' id='metallurgy_text-"+count+"' value='"+metallurgy_text+"' required readonly/></td><td ><input type='text' class='form-control' name='metallurgy["+count+"][value]' required id='value-"+count+"'></td><td><a class='btn btn-default remove' ><i class='icofont icofont-minus' style='color:red'></i></a></td></tr>";
				$("#unit").append(row);
				count++;
				document.getElementById('count').value=count;
				
			}else{
				swal('Please Enter Correct Value');
			}
		}
		$(function(){
			$(document).on('click', '.remove', function(e) {
				e.preventDefault();
				var trIndex = $(this).closest("tr").index();
			    $(this).closest("tr").remove();

			});
		});
	</script>

@endsection