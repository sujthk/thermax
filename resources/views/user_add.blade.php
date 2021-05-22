@extends('layouts.app') 

@section('styles')	
<!-- Data Table Css -->
<style>
	#frmCheckPassword {
		border-top: #F0F0F0 2px solid;
		background: #808080;
		padding: 10px;
	}

	.demoInputBox {
		padding: 7px;
		border: #F0F0F0 1px solid;
		border-radius: 4px;
	}

	#password-strength-status {
		padding: 5px 10px;
		color: #FFFFFF;
		border-radius: 4px;
		margin-top: 5px;
	}

	.medium-password {
		background-color: #b7d60a;
		border: #BBB418 1px solid;
	}

	.weak-password {
		background-color: #ce1d14;
		border: #AA4502 1px solid;
	}

	.strong-password {
		background-color: #12CC1A;
		border: #0FA015 1px solid;
	}
</style>

@endsection

@section('content')
<div class="main-body">
	<div class="page-wrapper">
		<!-- <div class="page-header">
			<div class="page-header-title">
				<h4>Add User</h4>
			</div>
			<div class="page-header-breadcrumb">
				<ul class="breadcrumb-title">
					<li class="breadcrumb-item">
						<a href="{{ url('dashboard') }}">
							<i class="icofont icofont-home"></i>
						</a>
					</li>
					<li class="breadcrumb-item"><a href="{{ url('users') }}">users</a>
					</li>
					<li class="breadcrumb-item"><a href="#!">Add User</a>
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
								<h5>Add User</h5>
								<div class="pull-left" style="float: right;">
                                    <a href="{{ url()->previous() }}" class="btn btn-info" title="Back" >Back</a>
                                </div>
							</div>
						</div>
						<form id="add_user" method="post" action="{{ url('users/add') }}" enctype="multipart/form-data">
							{{ csrf_field() }}
							<div class="card-block">

								<div class="row">
									<div class="col-md-12">
										<div class="row">
										<div class="col-md-6">
											<div class="form-group row">
												<label class="col-sm-4 col-form-label">Name</label>
												<div class="col-sm-8">
													<input id="name" name="name" type="text" value="{{ old('name') }}" required class="form-control" placeholder="Enter your name">
												</div>
											</div>
											<div class="form-group row">
												<label class="col-sm-4 col-form-label">Username</label>
												<div class="col-sm-8">
													<input id="username" name="username" type="text" value="{{ old('username') }}" required class="form-control" placeholder="Enter your Email">
												</div>
											</div>
											<div class="form-group row">
												<label class="col-sm-4 col-form-label">Mobile Number</label>
												<div class="col-sm-8">
													<input id="mobile" name="mobile" type="number" value="{{ old('mobile') }}" class="form-control" placeholder="Enter your Mobile Number">
												</div>
											</div>
											<div class="form-group row">
												<label class="col-sm-4 col-form-label">Password</label>
												<div class="col-sm-8">
													<input id="password" name="password" type="text" value="{{ old('password') }}" onKeyUp="checkPasswordStrength();" required class="form-control">
													<div id="password-strength-status"></div>
												</div>
											</div>
											<div class="form-group row">
												<label class="col-sm-4 col-form-label">User Type</label>
												<div class="col-sm-8">
													<select name="user_type" id="user_type" required class="form-control">
														<option value="">-- User Type --</option>
														<option  value="ADMIN">ADMIN</option>
														<option value="THERMAX_USER">Thermax User</option>
														<option value="NON_THERMAX_USER">Non Thermax User</option>
													</select>
												</div>
											</div>
											<div class="form-group row">
												<label class="col-sm-4 col-form-label">Unit Set</label>
												<div class="col-sm-8">
													<select name="unit_set_id" id="unit_set_id" required class="form-control">
														<option value="">-- Unit Set --</option>
														@foreach ($unit_sets as $unit_set)
														<option value="{{ $unit_set->id }}">{{ $unit_set->name }}</option>
														@endforeach
													</select>
												</div>
											</div>
											<div class="form-group row">
												<label class="col-sm-4 col-form-label">Region Type</label>
												<div class="col-sm-8">
													<select name="region_type" id="region_type" required class="form-control">
														<option value="">-- Region Type --</option>
														<option  value="1">Domestic</option>
														<option value="2">USA</option>
														<option value="3">Europe</option>
														<option value="4">Both</option>
													</select>
												</div>
											</div>
											<div class="form-group row region" >
												<label class="col-sm-4 col-form-label">Regions</label>
												<div class="col-sm-8">
													<select name="region_id" id="region_id" required class="form-control" >
														<option value="">-- Regions --</option>
														@foreach ($regions as $region)
														<option  value="{{$region->id}}">{{$region->name}}</option>
														@endforeach
													</select>
												</div>
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group row">
												<label class="col-sm-3 col-form-label">Language</label>
												<div class="col-sm-8">
													<select name="language_id" id="language" required class="form-control">
														<option value="">-- Select Language --</option>
														@foreach ($languages as $language)
                                                            <option  value="{{$language->id}}">{{$language->name}}</option>
                                                        @endforeach
													</select>
												</div>
											</div>
											<div class="form-group row">
												<label class="col-sm-3 col-form-label">Unit Set Type</label>
												<div class="col-sm-8" style="padding: 10px;">
												
													<input type="radio" name="unitset_type" id="unitset_type"  value="0" checked="checked">
													 <i class="helper"></i>No &nbsp;&nbsp;
												
													<input type="radio" name="unitset_type" id="unitset_type" value="1">
													 <i class="helper"></i>Yes		
												</div>
											</div>
											<div class="form-group row">
												<label class="col-sm-3 col-form-label">Min Chilled Water Out</label>
												<div class="col-sm-8">
													<input id="min_chilled_water_out" name="min_chilled_water_out" type="text" value="{{ old('min_chilled_water_out') }}" required class="form-control" placeholder="Enter your Min Chilled Water Out value">
												</div>
											</div>

											<div class="form-group row group_calculator" style="display: none;" >
												<label class="col-sm-3 col-form-label">Group Calculators</label>
												<div class="col-sm-8">
													<select name="group_calculator_id" id="group_calculator_id"  class="form-control" >
														<option value="">-- Group Calculator --</option>
														@foreach ($group_calculators as $group_calculator)
														<option  value="{{$group_calculator->id}}">{{$group_calculator->name}}</option>
														@endforeach
													</select>
												</div>
											</div>
											<div class="form-group row Calculator" style="display: none;">
												<label class="col-sm-3 col-form-label">Calculators</label>
												<div class="col-sm-8 calculator-append">
													
												</div>
											</div>
										</div>
									</div>
									<div class="form-group row">
										<label class="col-sm-5"></label>
										<div class="col-sm-7">
											<input type="submit" name="submit_value" value="Submit" id="submit_button" class="btn btn-primary m-b-0">
										</div>
									</div>
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
	$( document ).ready(function() {
		$("#password").val("thermax@123");
		    // swal("Hello world!");
	});

	function checkPasswordStrength() {
		var number = /([0-9])/;
		var alphabets = /([a-zA-Z])/;
		var special_characters = /([~,!,@,#,$,%,^,&,*,-,_,+,=,?,>,<])/;
		var status = false;

		if ($('#password').val().length < 6) {
			$('#password-strength-status').removeClass();
			$('#password-strength-status').addClass('weak-password');
			$('#password-strength-status').html("Weak (should be atleast 6 characters.)");
			status = true;
		} else {
			if ($('#password').val().match(number) && $('#password').val().match(alphabets) && $('#password').val().match(special_characters)) {
				$('#password-strength-status').removeClass();
				$('#password-strength-status').addClass('strong-password');
				$('#password-strength-status').html("Strong");
				status = false;
			} else {
				$('#password-strength-status').removeClass();
				$('#password-strength-status').addClass('medium-password');
				$('#password-strength-status').html("Medium (should include alphabets, numbers and special characters.)");
				status = true;
			}
		}

		$("#submit_button").prop('disabled', status);
	}
</script>
<script>
	$(document).ready(function() {

		$("#user_type").click(function () {
			var user_value = $('#user_type').val();
	
			if(user_value == 'THERMAX_USER' || user_value == 'NON_THERMAX_USER')
			{
				$(".group_calculator").show();
			}
			else
			{
				$(".group_calculator").hide();
				$('.Calculator').hide();
				
			}
			
		});
	$(document).on('change', '#group_calculator_id', function() {

		var group_calculator_id = $('#group_calculator_id').val();
			var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
			$.ajax({
				type: "POST",
				url: "{{ url('/users/group_calculator/list') }}",
				data: { group_calculator_id : group_calculator_id,_token: CSRF_TOKEN},
				success: function(response){
					if(response.status){
						$('.Calculator').show();
						$('.calculator-append').html(response.content);
					}
					else{
						$('.Calculator').hide();
						Swal.fire('Please select Group Calculator');
					}					
				},
			});
		});
		});
</script>

@endsection