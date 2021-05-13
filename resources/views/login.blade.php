<!DOCTYPE html>
<html lang="en">

	<head>
		<title>Thermax</title>
		<!-- HTML5 Shim and Respond.js IE9 support of HTML5 elements and media queries -->
		<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
		<!--[if lt IE 9]>
		  <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
		  <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
		  <![endif]-->
		<!-- Meta -->
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
		<!-- Favicon icon -->
		
		<link rel="icon" href="{{asset('assets/images/thermax-logo.png')}}" type="image/x-icon">
		<!-- Google font-->
		<link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700,800" rel="stylesheet">
		<!-- Required Fremwork -->
		<link rel="stylesheet" type="text/css" href="{{asset('bower_components/bootstrap/dist/css/bootstrap.min.css')}}">
		<!-- themify-icons line icon -->
		<link rel="stylesheet" type="text/css" href="{{asset('dark-assets/assets/icon/themify-icons/themify-icons.css')}}">
		<!-- ico font -->
		<link rel="stylesheet" type="text/css" href="{{asset('dark-assets/assets/icon/icofont/css/icofont.css')}}">
		<!-- Style.css -->
		<link rel="stylesheet" type="text/css" href="{{asset('dark-assets/assets/css/style.css')}}">
		<!-- color .css -->
		<link rel="stylesheet" type="text/css" href="{{asset('dark-assets/assets/css/color/color-1.css')}}" id="color"/>
		<meta name="csrf-token" content="{{ csrf_token() }}" />
		<style>


	        .weak-password {
	            background-color: #ce1d14;
	            border: #AA4502 1px solid;
	        }

	    </style>
	</head>

	<body class="fix-menu">
		<section class="login p-fixed d-flex text-center bg-primary common-img-bg">
			<!-- Container-fluid starts -->
			<div class="container-fluid">
				<div class="row">
					<div class="col-sm-12">
						<!-- Authentication card start -->
						<div class="login-card card-block auth-body">
							
							<form class="md-float-material" id="login_form" method="post" action="{{ url('login') }}">
								{{ csrf_field() }}
								<div class="text-center">
									<img src="{{asset('assets/images/thermax-logo.png')}}" alt="logo.png">
								</div>
								<div class="auth-box">
									<div class="row m-b-20">
										<div class="col-md-12">
											<h3 class="text-left txt-primary">Sign In</h3>
										</div>
									</div>
									@if ($errors->any())
									
									    <div class="alert alert-danger">
									        <ul>
									            @foreach ($errors->all() as $error)
									                <li>{{ $error }}</li>
									            @endforeach
									        </ul>
									    </div>
									@endif
									<hr/>
									<div class="input-group">
										<input type="text" class="form-control" name="username" id="username" value="" required placeholder="Your Username">
									</div>
									<div class="input-group">
										<input type="password" class="form-control" name="password" id="password" value="" required placeholder="Password">
										<span class="md-line"></span>
									</div>
									<div class="input-group otp_div" style="display: none;">
										<input type="text" class="form-control" name="otp" id="otp" value="" placeholder="Enter Otp">
										<span class="md-line"></span>
									</div>
									<div id="error-display"></div>
									<div class="row m-t-25 text-left">
										<div class="col-sm-6 col-xs-12">
											<div class="checkbox-fade fade-in-primary">
												<label>
													<!-- <input type="checkbox" value="">
													<span class="cr"><i class="cr-icon icofont icofont-ui-check txt-primary"></i></span>
													<span class="text-inverse">Remember me</span> -->
												</label>
											</div>
										</div>
										<div class="col-sm-6 col-xs-12 forgot-phone text-right">
											<!-- <a href="{{url('forgot-password')}}" class="text-right f-w-600 text-inverse"> Forgot Your Password?</a> -->
											<a href="#" onclick="forgetPassword();" class="text-right f-w-600 text-inverse"> Forgot Your Password?</a>
										</div>
									</div>
									<div class="row m-t-30">
										<div class="col-md-12">
											<!-- <button type="button" class="btn btn-primary btn-md btn-block waves-effect text-center m-b-20">Sign in</button> -->
											<input type="submit" name="submit_value" value="Sign in" id="submit_button" class="btn btn-primary btn-md btn-block waves-effect text-center m-b-20 disp">
											<input type="button" name="resend_otp" value="Resend Otp" id="resend_otp" style="display: none;" class="btn btn-primary btn-md btn-block waves-effect text-center m-b-20 disp">

										</div>
									</div>
									<hr/>
								</div>
							</form>
							<!-- end of form -->
						</div>
						<!-- Authentication card end -->
					</div>
					<!-- end of col-sm-12 -->
				</div>
				<!-- end of row -->
			</div>
			<!-- end of container-fluid -->
		</section>
		<!-- Warning Section Starts -->
		<!-- Warning Section Ends -->
		<!-- Required Jquery -->
		<script type="text/javascript" src="{{asset('bower_components/jquery/dist/jquery.min.js')}}"></script>
		<script type="text/javascript" src="{{asset('bower_components/jquery-ui/jquery-ui.min.js')}}"></script>
		<script type="text/javascript" src="{{asset('bower_components/tether/dist/js/tether.min.js')}}"></script>
		<script type="text/javascript" src="{{asset('bower_components/bootstrap/dist/js/bootstrap.min.js')}}"></script>
		<!-- jquery slimscroll js -->
		<script type="text/javascript" src="{{asset('bower_components/jquery-slimscroll/jquery.slimscroll.js')}}"></script>
		<!-- modernizr js -->
		<script type="text/javascript" src="{{asset('bower_components/modernizr/modernizr.js')}}"></script>
		<script type="text/javascript" src="{{asset('bower_components/modernizr/feature-detects/css-scrollbars.js')}}"></script>
		<!-- i18next.min.js -->
		<script type="text/javascript" src="{{asset('bower_components/i18next/i18next.min.js')}}"></script>
		<script type="text/javascript" src="{{asset('bower_components/i18next-xhr-backend/i18nextXHRBackend.min.js')}}"></script>
		<script type="text/javascript" src="{{asset('bower_components/i18next-browser-languagedetector/i18nextBrowserLanguageDetector.min.js')}}"></script>
		<script type="text/javascript" src="{{asset('bower_components/jquery-i18next/jquery-i18next.min.js')}}"></script>
		<!-- Custom js -->
		<!-- <script type="text/javascript" src="{{asset('assets/js/script.js')}}"></script> -->
		<!---- color js --->
		<!-- <script type="text/javascript" src="{{asset('assets/js/common-pages.js')}}"></script> -->

		<script type="text/javascript">
			var first_visit = 0;

			$( "#login_form" ).submit(function(event) {
				event.preventDefault();
				// if(first_visit == 0){
				// 	sendOtp();
				// 	$(".disp").prop('disabled', true);
				// 	first_visit = 1;
				// }
				// else{
				var username = $('#username').val();
				var password = $('#password').val();
				var otp = $('#otp').val();
				var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
			   	$.ajax({
					type: "POST",
					url: "{{ url('login') }}",
					data: { username : username,_token: CSRF_TOKEN,password: password,otp: otp},
					success: function(response){
						// console.log(response);
						if(response.status){
							 window.location = "{!! url('/dashboard') !!}";
						}
						else{
							$('#error-display').addClass('weak-password');
							$('#error-display').html(response.msg);
						}					
					},
				});
				// }
			});

			$("#resend_otp").click(function() {
				sendOtp();
				$(".disp").prop('disabled', true);
			});

			function forgetPassword(){
				alert("Kindly Contact Thermax Admin");
			}
			function sendOtp(){
				var username = $('#username').val();
				var password = $('#password').val();
				var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
			   	$.ajax({
					type: "POST",
					url: "{{ url('user-send-otp') }}",
					data: { username : username,_token: CSRF_TOKEN,password: password},
					success: function(response){
						// console.log(response);
						$(".disp").prop('disabled', false);
						if(response.status){
							$(".otp_div").show();
							$('#error-display').removeClass();
							$('#error-display').html("");
							$("#otp").prop('required', true);
							$("#resend_otp").show();
						}
						else{
							first_visit = 0;
							$('#error-display').addClass('weak-password');
							$('#error-display').html(response.msg);
						}					
					},
				});
			}


		</script>

	</body>

</html>
