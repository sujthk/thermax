@extends('layouts.app') 

@section('styles')	
	
@endsection

@section('content')
	<div class="main-body">
	    <div class="page-wrapper">
	        <div class="page-header">
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
		                            <h5>Export Testing Format</h5>
                                    <br>
                                    <br>
		                            <div class="card-body">
		                            	<div class="row">
								            <div class="col-sm-12">
								            	<form action="{{url('auto-testing/export')}}" method="POST" enctype="multipart/form-data">
									                {{ csrf_field() }}
									            	<select name="code" id="code" class="form-control"  required="">
									            		<option value="">---Select Calculator---</option>
										            	@foreach($calculator_keys as $calculator_key)

			 												<option  value="{{$calculator_key->code}}">{{$calculator_key->name}}</option>
			 											@endforeach
		 											</select>
		 											<br>
									            	 <button class="btn btn-primary m-b-0" type="submit">Export Testing Format</button>
									            </form>	 
								            </div>
							            </div>
							        </div>
                            	</div>
	                        </div>
	                    </div>

	                    <div class="card">
	                        <div class="card-header">
	                        	<div class="">
		                            <h5>Import Testing Format</h5>
                                    <br>
                                    <br>
		                            <div class="card-body">
		                            	<div class="row">
								            <div class="col-sm-12">
								            	<form action="{{url('auto-testing/import')}}" method="POST" enctype="multipart/form-data">
									                {{ csrf_field() }}
									            	<select name="code" id="code" class="form-control"  required="">
									            		<option value="">---Select Calculator---</option>
										            	@foreach($calculator_keys as $calculator_key)

			 												<option  value="{{$calculator_key->code}}">{{$calculator_key->name}}</option>
			 											@endforeach
		 											</select>
		 											<br>
		 											<input type="file" name="data_file" class="form-control" required="">
		 											<br>
									            	 <button class="btn btn-primary m-b-0" type="submit">Import Testing Format</button>
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
                    <div class="col-sm-6">
                        <div class="card">
                            <div class="card-header">
                                <div class="">
                                    <h5>Data Options</h5>
                                    <br>
                                    <br>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-sm-12">
                                                <label class="col-sm-12 col-form-label">1. Region Type - 1(Domestic), 2(USA), 3(Europe)</label> 
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-12">
                                                <label class="col-sm-12 col-form-label">2. Glycol Content - 1(None), 2(Ethylene), 3(Propylene)</label> 
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-12">
                                                <label class="col-sm-12 col-form-label">3. Metallurgy Standard - standard, non_standard</label> 
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-12">
                                                <label class="col-sm-12 col-form-label">4. Fouling Factor - standard, non_standard, ari</label> 
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