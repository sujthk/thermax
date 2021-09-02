@extends('layouts.app')

@section('styles')
<style type="text/css">
	.teimeline-header {
		text-align: center;
	}

	.teimeline-header img {
		height: 100vh;
		width: 100%;
	}

	.t-max-content p {
		text-align: justify;
		text-indent: 40px;
	}

	.t-max-content {
		width: 80%;
		margin-left: 10%;
	}
</style>
<!-- Horizontal-Timeline css -->
<link rel="stylesheet" type="text/css"
	href="{{asset('dark-assets/assets/pages/dashboard/horizontal-timeline/css/style.css')}}">
@endsection

@section('content')
<div class="main-body">
	<div class="page-wrapper">
		<!-- <div class="page-header">
			<div class="page-header-title">
				<h4>Dashboard</h4>
			</div>
			<div class="page-header-breadcrumb">
				<ul class="breadcrumb-title">
					<li class="breadcrumb-item">
						<a href="{{url('/dashboard')}}">
							<i class="icofont icofont-home"></i>
						</a>
					</li>
					<li class="breadcrumb-item"><a href="#!">Dasboard</a>
					</li>
					<li class="breadcrumb-item"><a href="#!">Home Dashboard</a>
					</li>
				</ul>
			</div>
		</div> -->
		<!-- Horizontal Timeline start -->
		<div class="col-md-12 p-0">
			<div class="col-sm-12 p-0">
				<!-- Bootstrap slider card start -->
				<div class="card">
					<div class="card-block">
						<div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel">
							<ol class="carousel-indicators">
								@php($i=1)
								@foreach($time_lines as $time_line)
								@if($i==1)
								<li data-target="#carouselExampleIndicators" data-slide-to="{{$time_line->id}}"
									class="active"></li>
								@else
								<li data-target="#carouselExampleIndicators" data-slide-to="{{$time_line->id}}"></li>
								@endif
								@php($i++)
								@endforeach

							</ol>

							<div class="carousel-inner" role="listbox">
								@php($j=1)
								@foreach($time_lines as $time_line)
								@if($j==1)
								<div class="carousel-item active">
									@else
									<div class="carousel-item ">
										@endif
										<div class="row">
											<div class="col-md-12">

												<div class="teimeline-header">
													<img src="{{$time_line->image_path}}" alt="Snow" class="img-fluid">
													<a href="{{$time_line->url_link}}" target="_blank">
														<h2>{{$time_line->name}}</h2>
													</a>

												</div>
												<div class="t-max-content">
													<p>
														{{$time_line->description}}

													</p>

												</div>
											</div>

										</div>

									</div>

									@php($j++)
									@endforeach
								</div>
								<a class="carousel-control-prev" href="#carouselExampleIndicators" role="button"
									data-slide="prev">
									<span class="carousel-control-prev-icon" aria-hidden="true"></span>
									<span class="sr-only">Previous</span>
								</a>
								<a class="carousel-control-next" href="#carouselExampleIndicators" role="button"
									data-slide="next">
									<span class="carousel-control-next-icon" aria-hidden="true"></span>
									<span class="sr-only">Next</span>
								</a>
							</div>
						</div>
					</div>
					<!-- Bootstrap slider card end -->
				</div>
			</div>
			<!-- Horizontal Timeline end -->
		</div>
	</div>
	@endsection

	@section('scripts')
	<script type="text/javascript">

	</script>
	<!-- Horizontal-Timeline js -->
	<script type="text/javascript" src="{{asset('dark-assets/assets/pages/dashboard/horizontal-timeline/js/main.js')}}">
	</script>
	<script type="text/javascript" src="{{asset('dark-assets/assets/pages/dashboard/project-dashboard.js')}}"></script>
	@endsection