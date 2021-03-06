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

		<style>


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
	        .cn {
	        	padding-left: 15px;
	        }
	        .pn {
	        	padding-left: 9px;
	        }
	        .dn {
	        	padding-left: 22px;
	        }
	        .mn {
	        	padding-left: 12px;
	        }
	        .optimal-r1 {
	        	text-align: center;
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
		      			    <table class="table table-bordered" style="width: 100%;">
		      			  		<thead class="thead-dark">
		      			    		<tr>
										<th scope="col" style="padding-right: 40px;">Client <span class="cn">:</span> {{ $name }}</th>
										<th scope="col">Version : 5.1.2.0</th>     
		  			    			</tr>
		      			     		<tr>
		      			      			<th scope="col">Enquiry : {{ $phone }}</th>
		      			      			<th scope="col">Date <span class="dn">:</span> {{ date('m/d/Y, h:i A') }}</th>     
		      			    		</tr>
		  			     			<tr>
		      			      			<th scope="col">Project <span class="pn">:</span> {{ $project }}</th>
		      			      			<th scope="col">Model <span class="mn">:</span> {{ $calculation_values['model_name'] }}</th>     
		      			    		</tr>
		      			  		</thead>  
		      				</table>
		      			</div>
		      			<div class="table-responsive">
		      				<table class="table table-bordered report-top">
		      			  		<thead>
								
		      			  		</thead>
			      			  	<tbody>
			      			  		<tr>
			      			  			<th scope="col">Sr.</th>
			      			  			<th scope="col">Description</th>
			      			  			<th class="optimal-r1" scope="col">Unit</th>
			      			  			<th  class="optimal-r1" scope="col"> Cooling Mode</th>      
			      			  		</tr>

			      			  		<tr>
			      			  			<th scope="col"></th>
			      			  			<th scope="col"> Capacity(+/-3%)</th>
			      			  			<th class="optimal-r1" scope="col"> {{ $units_data[$unit_set->CapacityUnit] }} </th>
			      			  			<th class="optimal-r1" scope="col"> {{ round($calculation_values['TON'],1) }}</th>      
			      			  		</tr>
			      			  		<tr>
			      			  			<td colspan="4"></td>
			      			  		</tr>
									<tr>
										<th scope="col"> A  </th>
										<th scope="col"> Chilled Water Circuit</th>
										<th scope="col"> </th>
										<th scope="col"> </th>      
									</tr>
									<tr>
										<td> 1 </td>
										<td> Chilled water flow</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->FlowRateUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['ChilledWaterFlow'],1) }}</td>
									</tr>
									<tr>
										<td> 2 </td>
										<td > Chilled water inlet temperature</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->TemperatureUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['TCHW11'],1) }}</td>
									</tr>
									<tr>
										<td> 3 </td> 
										<td> Chilled water outlet temperature</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->TemperatureUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['TCHW12'],1) }} </td>
									</tr>
									<tr>     
										<td> 4 </td>
										<td> Evaporate passes</td>
										<td class="optimal-r1"> No.</td>
										<td class="optimal-r1"> {{ $calculation_values['EvaporatorPasses'] }}</td>
									</tr>
									<tr>     
										<td> 5 </td>
										<td> Chilled water circuit pressure loss </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->PressureDropUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['ChilledFrictionLoss'],1) }} </td>
									</tr>
									<tr>     
										<td> 6 </td>
										<td> Chilled water Connection diameter </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->NozzleDiameterUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['ChilledConnectionDiameter'],1) }} </td>
									</tr>
									<tr>     
										<td> 7 </td>
										<td > Glycol type </td>
										<td > </td>
										@if(empty($calculation_values['CHGLY']) || $calculation_values['GL'] == 1)
											<td class="optimal-r1"> NA </td>
										@elseif($calculation_values['GL'] == 2)
											<td class="optimal-r1">Ethylene</td>
										@else
											<td class="optimal-r1">Proplylene</td>
										@endif			
									</tr>
									<tr>     
										<td> 8 </td>
										<td> Chilled water glycol %  </td>
										<td class="optimal-r1"> %</td>
										<td class="optimal-r1"> {{ $calculation_values['CHGLY'] }} </td>
									</tr>
									<tr>     
										<td> 9 </td>
										<td> Chilled water fouling factor </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->FoulingFactorUnit] }}</td>
										@if($calculation_values['TUU'] == "standard")
											<td class="optimal-r1"> {{ $calculation_values['TUU'] }} </td>
										@else
											<td class="optimal-r1"> {{ number_format($calculation_values['FFCHW1'], 5) }} </td>
										@endif	
									</tr>
									<tr>     
										<td> 10 </td>
										<td> Maximum working pressure </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->WorkPressureUnit] }}</td>
										<td class="optimal-r1">  {{ ceil($calculation_values['m_maxCHWWorkPressure']) }}</td>
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
										<td class="optimal-r1"> {{ $units_data[$unit_set->FlowRateUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['GCW'],1) }}</td>
									</tr>
									<tr>
										<td> 2 </td>
										<td> Cooling water inlet temperature</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->TemperatureUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['TCW11'],1) }}</td>
									</tr>
									<tr>
										<td> 3 </td>
										<td> Cooling water outlet temperature</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->TemperatureUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['CoolingWaterOutTemperature'],1) }}</td>
									</tr>
									<tr>
										<td> 4 </td> 
										<td> Absorber / Condenser passes</td>
										<td class="optimal-r1"> No.</td>
										<td class="optimal-r1"> {{ $calculation_values['AbsorberPasses'] }}/{{ $calculation_values['CondenserPasses'] }} </td>
									</tr>
									<tr>     
										<td> 5 </td>
										<td> Cooling water Bypass Flow</td>
										<td class="optimal-r1">{{ $units_data[$unit_set->FlowRateUnit] }} </td>
										@if(empty($calculation_values['BypassFlow']))
											<td class="optimal-r1"> - </td>
										@else
											<td class="optimal-r1">{{ round($calculation_values['BypassFlow'],1) }}</td>
										@endif	
									</tr>
									<tr>     
										<td> 6 </td>
										<td> Cooling water circuit pressure loss </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->PressureDropUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['CoolingFrictionLoss'],1) }} </td>
									</tr>
									<tr>     
										<td> 7 </td>
										<td> Cooling water Connection diameter </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->NozzleDiameterUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['CoolingConnectionDiameter'],1) }} </td>
									</tr>
									<tr>     
										<td> 8 </td>
										<td> Glycol type </td>
										<td > </td>
										@if(empty($calculation_values['COGLY']) || $calculation_values['GL'] == 1)
											<td class="optimal-r1"> NA </td>
										@elseif($calculation_values['GL'] == 2)
											<td class="optimal-r1">Ethylene</td>
										@else
											<td class="optimal-r1">Proplylene</td>
										@endif			
									</tr>
									<tr>     
										<td> 9 </td>
										<td> Cooling water glycol %  </td>
										<td class="optimal-r1"> %</td>
										<td class="optimal-r1"> {{ $calculation_values['COGLY'] }} </td>
									</tr>
									<tr>     
										<td> 10 </td>
										<td> Cooling water fouling factor </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->FoulingFactorUnit] }}</td>
										@if($calculation_values['TUU'] == "standard")
											<td class="optimal-r1"> {{ $calculation_values['TUU'] }} </td>
										@else
											<td class="optimal-r1"> {{ number_format($calculation_values['FFCOW1'], 5) }} 
											</td>
										@endif
									</tr>
									<tr>     
										<td> 11 </td>
										<td> Maximum working pressure </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->WorkPressureUnit] }}</td>
										<td class="optimal-r1"> {{ ceil($calculation_values['m_maxCOWWorkPressure']) }} </td>
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
										<td class="optimal-r1"> {{ $units_data[$unit_set->PressureUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['PST1'],1) }}</td>
									</tr>
									<tr>
										<td> 2 </td>
										<td> Steam Consumption(+/-3%)</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->SteamConsumptionUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['SteamConsumption'],1) }}</td>
									</tr>
									<tr>
										<td> 3 </td>
										<td> Condensate drain temperature</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->TemperatureUnit] }}</td>
										<td class="optimal-r1"> {{ ceil($calculation_values['m_dMinCondensateDrainTemperature']) }} - {{ ceil($calculation_values['m_dMaxCondensateDrainTemperature']) }} </td>
									</tr>
									<tr>
										<td> 4 </td> 
										<td> Condensate drain pressure</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->PressureUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['m_dCondensateDrainPressure'],1) }} </td>
									</tr>
									<tr>     
										<td> 5 </td>
										<td> Connection - Inlet diameter</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->NozzleDiameterUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['SteamConnectionDiameter'],1) }}</td>
									</tr>
									<tr>     
										<td> 6 </td>
										<td> Connection - Drain diameter </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->NozzleDiameterUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['SteamDrainDiameter'],1) }} </td>
									</tr>
									<tr>     
										<td> 7 </td>
										<td> Design Pressure </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->PressureUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['m_DesignPressure'],1) }} </td>
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
										<td class="optimal-r1"> {{ $calculation_values['PowerSupply'] }}</td>
									</tr>
									<tr>
										<td> 2 </td>
										<td> Power consumption</td>
										<td class="optimal-r1">kVA</td>
										<td class="optimal-r1"> {{ round($calculation_values['TotalPowerConsumption'],1) }}</td>
									</tr>
									<tr>
										<td> 3 </td>
										<td> Absorbent pump rating</td>
										<td class="optimal-r1"> kW (A)</td>
										<td class="optimal-r1"> {{ round($calculation_values['AbsorbentPumpMotorKW'],2) }}( {{ round($calculation_values['AbsorbentPumpMotorAmp'],2) }} ) </td>
									</tr>
									<tr>
										<td> 4 </td> 
										<td> Refrigerant pump rating</td>
										<td class="optimal-r1"> kW (A)</td>
										<td class="optimal-r1"> {{ round($calculation_values['RefrigerantPumpMotorKW'],2) }}( {{ round($calculation_values['RefrigerantPumpMotorAmp'],2) }}) </td>
									</tr>
									<tr>     
										<td> 5 </td>
										<td> Vacuum pump rating</td>
										<td class="optimal-r1"> kW (A)</td>
										<td class="optimal-r1"> {{ round($calculation_values['PurgePumpMotorKW'],2) }}({{ round($calculation_values['PurgePumpMotorAmp'],2) }}) </td>
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
										<td class="optimal-r1"> {{ $units_data[$unit_set->LengthUnit] }}</td>
										<td class="optimal-r1"> {{ ceil($calculation_values['Length']) }}</td>
									</tr>
									<tr>
										<td> 2 </td>
										<td> width</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->LengthUnit] }}</td>
										<td class="optimal-r1">  {{ ceil($calculation_values['Width']) }}</td>
									</tr>
									<tr>
										<td> 3 </td>
										<td> Height</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->LengthUnit] }}</td>
										<td class="optimal-r1"> {{ ceil($calculation_values['Height']) }} </td>
									</tr>
									<tr>
										<td> 4 </td> 
										<td> Operating weight</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->WeightUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['OperatingWeight'],1) }}</td>
									</tr>
									<tr>     
										<td> 5 </td>
										<td> Shipping weight</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->WeightUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['MaxShippingWeight'],1) }} </td>
									</tr>
									<tr>     
										<td> 6 </td>
										<td> Flooded weight </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->WeightUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['FloodedWeight'],1) }} </td>
									</tr>
									<tr>     
										<td> 7 </td>
										<td> Dry weight </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->WeightUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['DryWeight'],1) }} </td>
									</tr>
									<tr>     
										<td> 8 </td>
										<td> Tube cleaning space (any one side length-wise) </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->LengthUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['ClearanceForTubeRemoval'],1) }} </td>
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
										<td class="optimal-r1"> {{ $evaporator_name }}</td>
									</tr>
									<tr>
										<td> 2 </td>
										<td> Absorber tube material</td>
										<td> </td>
										<td class="optimal-r1"> {{ $absorber_name }}</td>
									</tr>
									<tr>
										<td> 3 </td>
										<td> Condenser tube material</td>
										<td> </td>
										<td class="optimal-r1"> {{ $condenser_name }} </td>
									</tr>
									@if(!$calculation_values['isStandard'] || $calculation_values['isStandard'] != 'true')
										<tr>
											<td> 4 </td> 
											<td> Evaporator tube thickness</td>
											<td class="optimal-r1"> {{ $units_data[$unit_set->LengthUnit] }}</td>
											<td class="optimal-r1"> {{ $calculation_values['TU3'] }}</td>
										</tr>
										<tr>     
											<td> 5 </td>
											<td> Absorber tube thickness</td>
											<td class="optimal-r1"> {{ $units_data[$unit_set->LengthUnit] }}</td>
											<td class="optimal-r1"> {{ $calculation_values['TU6'] }} </td>
										</tr>
										<tr>     
											<td> 6 </td>
											<td> Condenser tube thickness </td>
											<td class="optimal-r1"> {{ $units_data[$unit_set->LengthUnit] }}</td>
											<td class="optimal-r1"> {{ $calculation_values['TV6'] }} </td>
										</tr>
									@endif	
									<tr>
										<th scope="col"> G </th>
										<th scope="col"> Low Temperature Heat exchanger Type</th>
										<th scope="col"> </th>
										<th class="optimal-r1" scope="col">{{ $calculation_values['HHType'] }} </th>      
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

		<!-- Custom js -->
		<!-- <script type="text/javascript" src="{{asset('assets/js/script.js')}}"></script> -->
		<!---- color js --->
		<!-- <script type="text/javascript" src="{{asset('assets/js/common-pages.js')}}"></script> -->


	</body>

</html>
