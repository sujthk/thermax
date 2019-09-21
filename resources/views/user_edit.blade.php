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
		                            <h5>Add User</h5>
                            	</div>
	                        </div>
	                        <form id="add_user" method="post" action="{{ url('users/edit',[$user->id]) }}" enctype="multipart/form-data">
                				{{ csrf_field() }}
		                        <div class="card-block">
		                        	<div class="form-group row">
		                        	    <label class="col-sm-2 col-form-label">Name</label>
		                        	    <div class="col-sm-6">
		                        	        <input id="name" name="name" type="text" value="{{ $user->name }}" required class="form-control">
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-2 col-form-label">Email</label>
		                        	    <div class="col-sm-6">
		                        	        <input id="email" name="email" type="email" value="{{ $user->email }}" required class="form-control">
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-2 col-form-label">New Password</label>
		                        	    <div class="col-sm-6">
		                        	        <input id="password" name="password" type="text" value="{{ old('password') }}" onKeyUp="checkPasswordStrength();" class="form-control">
		                        	        <div id="password-strength-status"></div>
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-2 col-form-label">User Type</label>
		                        	    <div class="col-sm-6">
                                            <select name="user_type" id="user_type" required class="form-control">
                                                <option {{ $user->user_type == 'ADMIN' ? 'selected' : '' }} value="ADMIN">ADMIN</option>
                                                <option {{ $user->user_type == 'THERMAX_USER' ? 'selected' : '' }} value="THERMAX_USER">Thermax User</option>
                                                <option {{ $user->user_type == 'NON_THERMAX_USER' ? 'selected' : '' }} value="NON_THERMAX_USER">Non Thermax User</option>
                                            </select>
                                        </div>
		                        	</div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-2 col-form-label">Unit Set</label>
		                        	    <div class="col-sm-6">
                                            <select name="unit_set_id" id="unit_set_id" required class="form-control">
                                                @foreach ($unit_sets as $unit_set)
                                                	<option {{ $user->unit_set_id == $unit_set->id ? 'selected' : '' }} value="{{ $unit_set->id }}">{{ $unit_set->name }}</option>
                                                @endforeach
                                            </select>
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

@endsection