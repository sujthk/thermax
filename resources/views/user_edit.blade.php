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
<div class="page-header">
	<div class="page-header-title">
		<h4>Edit User</h4>
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
			<li class="breadcrumb-item"><a href="#!">Edit User</a>
			</li>
		</ul>
	</div>
</div>
<div class="page-body">
	<div class="row">
		<div class="col-sm-12">
			<!-- Zero config.table start -->
			@if ($errors ->any())
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
						<h5>Edit User</h5>
						<div class="pull-left" style="float: right;">
                            <a href="{{ url()->previous() }}" class="btn btn-info" title="Back" >Back</a>
                        </div>
					</div>
				</div>
				<form id="add_user" method="post" action="{{ url('users/edit',[$user->id]) }}" enctype="multipart/form-data">
					{{ csrf_field() }}
					<div class="card-block">

						<div class="row">
							<div class="col-md-12">
								<div class="row">
									<div class="col-md-6">
										<div class="form-group row">
											<label class="col-sm-4 col-form-label">Name</label>
											<div class="col-sm-8">
												<input id="name" name="name" type="text" value="{{ $user->name }}" required class="form-control">

												<input id="user_id" name="user_id" type="hidden" value="{{ $user->id }}">
											</div>
										</div>
										<div class="form-group row">
											<label class="col-sm-4 col-form-label">Username</label>
											<div class="col-sm-8">
												<input id="username" name="username" type="text" value="{{ $user->username }}" required class="form-control">
											</div>
										</div>
										<div class="form-group row">
											<label class="col-sm-4 col-form-label">Mobile Number</label>
											<div class="col-sm-8">
												<input id="mobile" name="mobile" type="number" value="{{ $user->mobile }}" class="form-control" placeholder="Enter your Mobile Number">
											</div>
										</div>
										<div class="form-group row">
											<label class="col-sm-4 col-form-label">New Password</label>
											<div class="col-sm-8">
												<input id="password" name="password" type="text" value="" onKeyUp="checkPasswordStrength();" class="form-control">
												<div id="password-strength-status"></div>
											</div>
										</div>
										<div class="form-group row">
											<label class="col-sm-4 col-form-label">User Type</label>
											<div class="col-sm-8">
												<select name="user_type" id="user_type" required class="form-control">
													<option value="ADMIN"  {{ $user->user_type == 'ADMIN' ? 'selected' : '' }}>ADMIN</option>
													<option  value="THERMAX_USER" {{ $user->user_type == 'THERMAX_USER' ? 'selected' : '' }}>Thermax User</option>
													<option value="NON_THERMAX_USER" {{ $user->user_type == 'NON_THERMAX_USER' ? 'selected' : '' }} >Non Thermax User</option>
												</select>
											</div>
										</div>
										<div class="form-group row">
											<label class="col-sm-4 col-form-label">Unit Set</label>
											<div class="col-sm-8">
												<select name="unit_set_id" id="unit_set_id" required class="form-control">
													@foreach ($unit_sets as $unit_set)
													<option {{ $user->unit_set_id == $unit_set->id ? 'selected' : '' }} value="{{ $unit_set->id }}">{{ $unit_set->name }}</option>
													@endforeach
												</select>
											</div>
										</div>
										<div class="form-group row">
											<label class="col-sm-4 col-form-label">Region Type</label>
											<div class="col-sm-8">
												<select name="region_type" id="region_type" required class="form-control">
													<option value="">-- Region Type --</option>
													<option  value="1" {{ $user->region_type == 1 ? 'selected' : '' }} >Domestic</option>
													<option value="2" {{ $user->region_type == 2 ? 'selected' : '' }}>USA</option>
													<option value="3" {{ $user->region_type == 3 ? 'selected' : '' }}>Europe</option>
													<option  value="4" {{ $user->region_type == 4 ? 'selected' : '' }}>Both</option>
												</select>
											</div>
										</div>
										<div class="form-group row">
											<label class="col-sm-4 col-form-label">Regions</label>
											<div class="col-sm-8">
												<select name="region_id" id="region_id" required class="form-control">
                                                    @foreach ($regions as $region)
                                                        <option {{ $user->region_id == $region->id ? 'selected' : '' }} value="{{ $region->id }}">{{ $region->name }}</option>
                                                    @endforeach
												</select>
											</div>
										</div>

										<div class="form-group row region">
											<label class="col-sm-4 col-form-label"></label>
											<div class="col-sm-8">
												
											</div>
										</div>

										<div class="form-group row">
											<label class="col-sm-4 col-form-label">Language</label>
											<div class="col-sm-8">
												<select name="language_id" id="language" required class="form-control">
                                                    @foreach ($languages as $language)
                                                        <option {{ $user->language_id == $language->id ? 'selected' : '' }} value="{{ $language->id }}">{{ $language->name }}</option>
                                                    @endforeach
												</select>
											</div>
										</div>
										<div class="form-group row">
												<label class="col-sm-4 col-form-label">Unit Set Type</label>
												<div class="col-sm-8" style="padding: 10px;">
													<input type="radio" name="unitset_type" id="unitset_type"  value="0"  {{ $user->unitset_status == 0 ? 'checked' : '' }} >
													 <i class="helper"></i>No &nbsp;&nbsp;
												
													<input type="radio" name="unitset_type" id="unitset_type" value="1"  {{ $user->unitset_status == 1 ? 'checked' : '' }}>
													 <i class="helper"></i>Yes		
												</div>
										</div>
										<div class="form-group row">
												<label class="col-sm-4 col-form-label">Min Chilled Water Out</label>
												<div class="col-sm-8">
													<input id="min_chilled_water_out" name="min_chilled_water_out" type="text" value="{{$user->min_chilled_water_out}}" required class="form-control" placeholder="Enter your Max Chilled Water Out value">
												</div>
										</div>
										@if($user->group_calculator_id)
										<div class="form-group row group_calculator"  >
											<label class="col-sm-4 col-form-label">Group Calculators</label>
											<div class="col-sm-8">
												<select name="group_calculator_id" id="group_calculator_id"  class="form-control" >
													<option value="">-- Group Calculator --</option>
													@foreach ($group_calculators as $group_calculator)
													<option  value="{{$group_calculator->id}}" {{ $user->group_calculator_id == $group_calculator->id ? 'selected' : '' }} >{{$group_calculator->name}}</option>
													@endforeach
												</select>
											</div>
										</div>
										<div class="form-group row Calculator" >
											<label class="col-sm-4 col-form-label">Calculators</label>
											<div class="col-sm-8 calculator-append">
												
											</div>
										</div>
										@else
											<div class="form-group row group_calculator" style="display: none;" >
											<label class="col-sm-4 col-form-label">Group Calculators</label>
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
											<label class="col-sm-4 col-form-label">Calculators</label>
											<div class="col-sm-8 calculator-append">

											</div>
										</div>
										@endif
									</div>
								</div>

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


	function checkPasswordStrength() {
		var number = /([0-9])/;
		var alphabets = /([a-zA-Z])/;
		var special_characters = /([~,!,@,#,$,%,^,&,*,-,_,+,=,?,>,<])/;
		var status = false;

		if($('#password').val().length > 0){
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
		}
		else{
			$('#password-strength-status').removeClass();
			$('#password-strength-status').html("");
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
			getCalculatorList();
		});

		function getCalculatorList(){
			var group_calculator_id = $('#group_calculator_id').val();
			var user_id = $('#user_id').val();
			var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
			$.ajax({
				type: "POST",
				url: "{{ url('/users/group_calculator/list') }}",
				data: { group_calculator_id : group_calculator_id,user_id : user_id,_token: CSRF_TOKEN},
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
		}
		if($('#user_type').val() != 'ADMIN'){
			getCalculatorList();
		}
		
	});
</script>
@endsection