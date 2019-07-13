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
	</head>

	<body class="fix-menu">
		<section class="login p-fixed d-flex text-center bg-primary common-img-bg">
			<!-- Container-fluid starts -->
			<div class="container-fluid">
				<div class="row">
					<div class="col-sm-12">
						<!-- Authentication card start -->
						<div class="login-card card-block auth-body">
							<form class="md-float-material" action="dashboard-analytics.html">
								<div class="text-center">
									<img src="{{asset('assets/images/thermax-logo.png')}}" alt="logo.png">
								</div>
								<div class="auth-box">
									<div class="row m-b-20">
										<div class="col-md-12">
											<h3 class="text-left txt-primary">Sign In</h3>
										</div>
									</div>
									<hr/>
									<div class="input-group">
										<input type="email" class="form-control" placeholder="Your Email Address">
										<span class="md-line"></span>
									</div>
									<div class="input-group">
										<input type="password" class="form-control" placeholder="Password">
										<span class="md-line"></span>
									</div>
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
											<a href="" class="text-right f-w-600 text-inverse"> Forgot Your Password?</a>
										</div>
									</div>
									<div class="row m-t-30">
										<div class="col-md-12">
											<!-- <button type="button" class="btn btn-primary btn-md btn-block waves-effect text-center m-b-20">Sign in</button> -->
											<a href="dashboard-analytics.html" class="tbtn btn-primary btn-md btn-block waves-effect text-center m-b-20"> Sign in</a>
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
	</body>

</html>
