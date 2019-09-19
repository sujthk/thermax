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
	        .report-table .table>thead>tr>th{
	        	background: #676767;
	        	color: #fff;
	        }

	        .report-table .table th{
	        	background: #676767;
	        	color: #fff;
	        }

	         .report-table .table td{
	        	border:1px solid #676767;
	        	
	        }
	        .report-top{
	        	margin-bottom:0;
	        }
	        .report-end h4{
	            padding: .75rem;
	            background: #676767;
	            color: #fff;
	            font-size: 18px;
	        }
	        .report-end{
	        	border:1px solid #676767;
	        }
	        .report-end p{
	        	padding: 0 .75rem;
	        	margin-bottom: 0;
	        }
	    </style>
	</head>

	<body class="fix-menu">
		<section class="report-table">
			<!-- Container-fluid starts -->
			<div class="container-fluid">
				<div class="row">
					<div class="col-sm-12">
						<!-- Authentication card start -->
		      			<div class="table-responsive">
		      			    <table class="table table-bordered">
		      			  		<thead class="thead-dark">
		      			    		<tr>
										<th scope="col">Client : {{ $name }}</th>
										<th scope="col">Version : 5.1.2.0</th>     
		  			    			</tr>
		      			     		<tr>
		      			      			<th scope="col">Enquiry : {{ $phone }}</th>
		      			      			<th scope="col">Date : {{ date('m/d/Y, h:i A') }}</th>     
		      			    		</tr>
		  			     			<tr>
		      			      			<th scope="col">Project : {{ $project }}</th>
		      			      			<th scope="col">Model : {{ $calculation_values['ModelName'] }}</th>     
		      			    		</tr>
		      			  		</thead>  
		      				</table>
		      			</div>
		      			<div class="table-responsive">
		      				<table class="table table-bordered report-top">
		      			  		<thead>
								<tr>
									<th scope="col">Sr.</th>
									<th scope="col">Description</th>
									<th scope="col">Unit</th>
									<th scope="col"> Cooling Mode</th>      
								</tr>

								<tr>
									<th scope="col"></th>
									<th scope="col"> Capacity(+/-3%)</th>
									<th scope="col"> TR </th>
									<th scope="col"> {{ $calculation_values['TON'] }}</th>      
								</tr>
								<tr>
									<td colspan="3"></td>
								</tr>
		      			  		</thead>
			      			  	<tbody>
									<tr>
										<th scope="col"> A  </th>
										<th scope="col"> Chilled Water Circuit</th>
										<th scope="col"> </th>
										<th scope="col"> </th>      
									</tr>
									<tr>
										<td> 1 </td>
										<td> Chilled water flow</td>
										<td> m<sup>3 </sup>/hr</td>
										<td> {{ $calculation_values['ChilledWaterFlow'] }}</td>
									</tr>
									<tr>
										<td> 2 </td>
										<td> Chilled water inlet temperature</td>
										<td> <sup> 0 </sup> C</td>
										<td> {{ $calculation_values['TCHW11'] }}</td>
									</tr>
									<tr>
										<td> 3 </td> 
										<td> Chilled water outlet temperature</td>
										<td> <sup> 0 </sup> C</td>
										<td> {{ $calculation_values['TCHW12'] }} </td>
									</tr>
									<tr>     
										<td> 4 </td>
										<td> Evaporate passes</td>
										<td> No.</td>
										<td> {{ $calculation_values['EvaporatorPasses'] }}</td>
									</tr>
									<tr>     
										<td> 5 </td>
										<td> Chilled water circuit pressure loss </td>
										<td> mLC</td>
										<td> {{ $calculation_values['ChilledFrictionLoss'] }} </td>
									</tr>
									<tr>     
										<td> 6 </td>
										<td> Chilled water Connection diameter </td>
										<td> DN</td>
										<td> {{ $calculation_values['ChilledConnectionDiameter'] }} </td>
									</tr>
									<tr>     
										<td> 7 </td>
										<td> Glycol type </td>
										<td> </td>
										@if($calculation_values['GL'] == 1)
											<td> NA </td>
										@elseif($calculation_values['GL'] == 2)
											<td>Ethylene</td>
										@else
											<td>Proplylene</td>
										@endif			
									</tr>
									<tr>     
										<td> 8 </td>
										<td> Chilled water glycol %  </td>
										<td> %</td>
										<td> {{ $calculation_values['CHGLY'] }} </td>
									</tr>
									<tr>     
										<td> 9 </td>
										<td> Chilled water fouling factor </td>
										<td> m<sup>2 </sup>/hr<sup>o </sup><br>C/kcal</td>
										@if($calculation_values['TUU'] == "standard")
											<td> {{ $calculation_values['TUU'] }} </td>
										@else
											<td> {{ $calculation_values['FFCHW1'] }} </td>
										@endif	
									</tr>
									<tr>     
										<td> 10 </td>
										<td> Maximum working pressure </td>
										<td> kg/cm<sup>2</sup>(g)</td>
										<td>  {{ $calculation_values['m_maxCHWWorkPressure'] }}</td>
									</tr>
									<tr>
										<th scope="col"> B  </th>
										<th scope="col"> Cooling Water Circuit</th>
										<th scope="col"> </th>
										<th scope="col"> </th>      
									</tr>
									<tr>
										<td> 1 </td>
										<td> Cooling water flow</td>
										<td> m<sup>3 </sup>/hr</td>
										<td> {{ $calculation_values['GCW'] }}</td>
									</tr>
									<tr>
										<td> 2 </td>
										<td> Cooling water inlet temperature</td>
										<td> <sup> 0 </sup> C</td>
										<td> {{ $calculation_values['TCW11'] }}</td>
									</tr>
									<tr>
										<td> 3 </td>
										<td> Cooling water outlet temperature</td>
										<td> <sup> 0 </sup> C</td>
										<td> {{ $calculation_values['CoolingWaterOutTemperature'] }}</td>
									</tr>
									<tr>
										<td> 4 </td> 
										<td> Absorber / Condenser passes</td>
										<td> No.</td>
										<td> {{ $calculation_values['AbsorberPasses'] }}/{{ $calculation_values['CondenserPasses'] }} </td>
									</tr>
									<tr>     
										<td> 5 </td>
										<td> Cooling water Bypass Flow</td>
										<td>m<sup>3 </sup>/hr </td>
										@if(empty($calculation_values['BypassFlow']))
											<td> - </td>
										@else
											<td>{{ $calculation_values['BypassFlow'] }}</td>
										@endif	
									</tr>
									<tr>     
										<td> 6 </td>
										<td> Cooling water circuit pressure loss </td>
										<td> mLC</td>
										<td> {{ $calculation_values['CoolingFrictionLoss'] }} </td>
									</tr>
									<tr>     
										<td> 7 </td>
										<td> Cooling water Connection diameter </td>
										<td> DN</td>
										<td> {{ $calculation_values['CoolingConnectionDiameter'] }} </td>
									</tr>
									<tr>     
										<td> 8 </td>
										<td> Glycol type </td>
										<td> </td>
										@if($calculation_values['GL'] == 1)
											<td> NA </td>
										@elseif($calculation_values['GL'] == 2)
											<td>Ethylene</td>
										@else
											<td>Proplylene</td>
										@endif			
									</tr>
									<tr>     
										<td> 9 </td>
										<td> Cooling water glycol %  </td>
										<td> %</td>
										<td> {{ $calculation_values['COGLY'] }} </td>
									</tr>
									<tr>     
										<td> 10 </td>
										<td> Cooling water fouling factor </td>
										<td> m<sup>2 </sup>/hr<sup>o </sup><br>C/kcal</td>
										@if($calculation_values['TUU'] == "standard")
											<td> {{ $calculation_values['TUU'] }} </td>
										@else
											<td> {{ $calculation_values['FFCOW1'] }} </td>
										@endif
									</tr>
									<tr>     
										<td> 11 </td>
										<td> Maximum working pressure </td>
										<td> kg/cm<sup>2</sup>(g)</td>
										<td> {{ $calculation_values['m_maxCOWWorkPressure'] }} </td>
									</tr>
									<tr>
										<th scope="col"> C </th>
										<th scope="col"> Steam Circuit</th>
										<th scope="col"> </th>
										<th scope="col"> </th>      
									</tr>
									<tr>
										<td> 1 </td>
										<td> Steam pressure</td>
										<td> kg/cm<sup>2 </sup>(g)</td>
										<td> {{ $calculation_values['PST1'] }}</td>
									</tr>
									<tr>
										<td> 2 </td>
										<td> Steam Consumption(+/-3%)</td>
										<td> kg/hr</td>
										<td> {{ $calculation_values['SteamConsumption'] }}</td>
									</tr>
									<tr>
										<td> 3 </td>
										<td> Condensate drain temperature</td>
										<td> <sup> 0 </sup> C</td>
										<td> {{ $calculation_values['m_dMinCondensateDrainTemperature'] }} - {{ $calculation_values['m_dMaxCondensateDrainTemperature'] }} </td>
									</tr>
									<tr>
										<td> 4 </td> 
										<td> Condensate drain pressure</td>
										<td> kg/cm<sup> 2 </sup> (g)</td>
										<td> {{ $calculation_values['m_dCondensateDrainPressure'] }} </td>
									</tr>
									<tr>     
										<td> 5 </td>
										<td> Connection - Inlet diameter</td>
										<td> DN</td>
										<td> {{ $calculation_values['SteamConnectionDiameter'] }}</td>
									</tr>
									<tr>     
										<td> 6 </td>
										<td> Connection - Drain diameter </td>
										<td> DN</td>
										<td> {{ $calculation_values['SteamDrainDiameter'] }} </td>
									</tr>
									<tr>     
										<td> 7 </td>
										<td> Design Pressure </td>
										<td> kg/cm<sup> 2 </sup> (g)</td>
										<td> {{ $calculation_values['m_DesignPressure'] }} </td>
									</tr>
									<tr>
										<th scope="col"> D  </th>
										<th scope="col"> Electrical Data</th>
										<th scope="col"> </th>
										<th scope="col"> </th>      
									</tr>
									<tr>
										<td> 1 </td>
										<td> Power supply</td>
										<td> </td>
										<td> {{ $calculation_values['PowerSupply'] }}</td>
									</tr>
									<tr>
										<td> 2 </td>
										<td> Power consumption</td>
										<td>kVA</td>
										<td> {{ round($calculation_values['TotalPowerConsumption'],1) }}</td>
									</tr>
									<tr>
										<td> 3 </td>
										<td> Absorbent pump rating</td>
										<td> kW (A)</td>
										<td> {{ $calculation_values['AbsorbentPumpMotorKW'] }}( {{$calculation_values['AbsorbentPumpMotorAmp']}}) </td>
									</tr>
									<tr>
										<td> 4 </td> 
										<td> Refrigerant pump rating</td>
										<td> kW (A)</td>
										<td> {{ $calculation_values['RefrigerantPumpMotorKW']}}({{$calculation_values['RefrigerantPumpMotorAmp']}}) </td>
									</tr>
									<tr>     
										<td> 5 </td>
										<td> Vacuum pump rating</td>
										<td> kW (A)</td>
										<td> {{ $calculation_values['PurgePumpMotorKW']}}({{$calculation_values['PurgePumpMotorAmp']}}) </td>
									</tr>
									<tr>
										<th scope="col"> E  </th>
										<th scope="col"> Physical Data</th>
										<th scope="col"> </th>
										<th scope="col"> </th>      
									</tr>
									<tr>
										<td> 1 </td>
										<td> Length</td>
										<td> mm</td>
										<td> {{ $calculation_values['Length'] }}</td>
									</tr>
									<tr>
										<td> 2 </td>
										<td> width</td>
										<td> mm</td>
										<td> {{ $calculation_values['Width'] }}</td>
									</tr>
									<tr>
										<td> 3 </td>
										<td> Height</td>
										<td> mm</td>
										<td> {{ $calculation_values['Height'] }} </td>
									</tr>
									<tr>
										<td> 4 </td> 
										<td> Operating weight</td>
										<td> ton</td>
										<td> {{ $calculation_values['OperatingWeight'] }}</td>
									</tr>
									<tr>     
										<td> 5 </td>
										<td> Shipping weight</td>
										<td> ton</td>
										<td> {{ $calculation_values['MaxShippingWeight'] }} </td>
									</tr>
									<tr>     
										<td> 6 </td>
										<td> Flooded weight </td>
										<td> ton</td>
										<td> {{ $calculation_values['FloodedWeight'] }} </td>
									</tr>
									<tr>     
										<td> 7 </td>
										<td> Dry weight </td>
										<td> ton</td>
										<td> {{ $calculation_values['DryWeight'] }} </td>
									</tr>
									<tr>     
										<td> 8 </td>
										<td> Tube cleaning space (any one side length-wise) </td>
										<td> mm</td>
										<td> {{ $calculation_values['ClearanceForTubeRemoval'] }} </td>
									</tr>
									<tr>
										<th scope="col"> F  </th>
										<th scope="col"> Tube Metallurgy</th>
										<th scope="col"> </th>
										<th scope="col"> </th>      
									</tr>
									<tr>
										<td> 1 </td>
										<td> Evaporator tube material</td>
										<td> </td>
										<td> {{ $evaporator_name }}</td>
									</tr>
									<tr>
										<td> 2 </td>
										<td> Absorber tube material</td>
										<td> </td>
										<td> {{ $absorber_name }}</td>
									</tr>
									<tr>
										<td> 3 </td>
										<td> Condenser tube material</td>
										<td> </td>
										<td> {{ $condenser_name }} </td>
									</tr>
									@if(!$calculation_values['isStandard'] || $calculation_values['isStandard'] != 'true')
										<tr>
											<td> 4 </td> 
											<td> Evaporator tube thickness</td>
											<td> mm</td>
											<td> {{ $calculation_values['TU3'] }}</td>
										</tr>
										<tr>     
											<td> 5 </td>
											<td> Absorber tube thickness</td>
											<td> mm</td>
											<td> {{ $calculation_values['TU6'] }} </td>
										</tr>
										<tr>     
											<td> 6 </td>
											<td> Condenser tube thickness </td>
											<td> mm</td>
											<td> {{ $calculation_values['TV6'] }} </td>
										</tr>
									@endif	
									<tr>
										<th scope="col"> G </th>
										<th scope="col"> Low Temperature Heat exchanger Type</th>
										<th scope="col"> </th>
										<th scope="col">{{ $calculation_values['HHType'] }} </th>      
									</tr>				
								</tbody>

		      				</table>
		      				<div class="report-end">
								<h4> Caption Notes: </h4>
								@foreach($calculation_values['notes'] as $note)
									<p>{{ $note }}</p>
								@endforeach
							</div>
		      			</div>
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
