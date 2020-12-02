@extends('layouts.app') 

@section('styles')	
	 <meta name="csrf-token" content="{{ csrf_token() }}" />
@endsection

@section('content')
	<div class="main-body">
	    <div class="page-wrapper">
	        <div class="page-header">
	            <div class="page-header-title">
	                <h4>Auto Testing</h4>
	            </div>
	            <div class="page-header-breadcrumb">
	                <ul class="breadcrumb-title">
	                    <li class="breadcrumb-item">
	                        <a href="{{ url('dashboard') }}">
	                            <i class="icofont icofont-home"></i>
	                        </a>
	                    </li>
	                    <li class="breadcrumb-item"><a href="#!">Auto Testing</a>
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
		                            <h5>Auto Testing</h5>
		                            <form action="{{url('auto-testing/download')}}" method="POST" enctype="multipart/form-data">
						                {{ csrf_field() }}
						            	<input type="submit" name="download_excel" id="download_excel" style="display: none;" value="Download Excel" class="btn btn-primary m-b-0">
						            </form>
		                            
                            	</div>
	                        </div>
	                        <div class="card-block">
	                            <div class="dt-responsive table-responsive">
	                                <table id="simpletable" class="table table-striped table-bordered nowrap">
	                                    <thead>
	                                        <tr>
	                                        	<th>Model Name</th>
	                                            <th>Model</th>
	                                            <th>Result</th>
	                                        </tr>
	                                    </thead>
	                                    <tbody id="result_table">
	                                    	
	                                    </tbody>	
	                                </table>
	                            </div>
	                        </div>
	                    </div>

	                </div>
	            </div>
	        </div>
	    </div>
	</div>
	
@endsection
	
@section('scripts')	
	<script type="text/javascript">

		var datas = {!! json_encode($datas) !!};
		var code = "{!! $code !!}";
		var data_length = datas.length - 1;
		var current_index = 0;

		// console.log(datas);
		console.log(data_length);
		console.log(current_index);

		var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');

		
		// var myVar = setInterval(function(){
		// 	if(current_index >= data_length){
		// 		clearInterval(myVar);
		//  	}
		// 	console.log(datas[current_index]);
		// 	sendCalculationValues(datas[current_index]);
		// 	current_index++;
		// }, 3000);
		

		 $(window).on('load', function () {

		 	

		 	
			// $.each(datas, function(key,option) {
			// 	console.log(option);
				
				setTimeout(function(){
				  sendCalculationValues(datas[current_index]);
				}, 3000);
				
			// 	// $("#ajax-loader").hide();
				
			// });
		})


		function sendCalculationValues(values){
			values.calculator_code = code;
			var tr_row = '<tr><td>'+values.model_name+'</td><td>'+values.model_number+'</td>';
			var error = 1;
			$.ajax({
				type: "POST",
				async: false,
				url: "{{ url('auto-testing/calculator') }}",
				data: { _token: CSRF_TOKEN,code: code,calculator_input: values},
				 complete: function(){
					
				 },
				success: function(response){
					
					if(response.status){
						console.log(response.result);
						tr_row = tr_row+'<td>'+response.result.Result+'</td></tr>';
						error = 0;
					}
					else{
						console.log(response.msg);
					}	
							

				},
                error: function (jqXHR, status, err) {
                    swal("Sorry Something Went Wrong", "", "error");
                }
			});

			if(error){
				tr_row = tr_row+'<td>Error</td></tr>';
			}
			
			$('#result_table').append(tr_row);

			if(current_index < data_length){
				current_index++;
				setTimeout(function(){
				  sendCalculationValues(datas[current_index]);
				}, 2000);
								
		 	}
		 	else{
		 		$('#download_excel').show();
		 	}
		}
 

	</script>

@endsection