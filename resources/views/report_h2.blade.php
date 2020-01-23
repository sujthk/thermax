<!DOCTYPE html>
<html lang="en">

	<head>
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
	        	width: 100%;
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
	        .optimal-r1 {
	        	text-align: center;
	        }
	    </style>
	</head>

	<body class="fix-menu">
		<section class="report-table">
			<!-- Container-fluid starts -->
				<div class="row">
					<div class="col-sm-12">
						<!-- Authentication card start -->
		      		
		      			<div class="table">
		      				<table class="table-bordered report-top">
		      			  		<thead>
								<tr>
									
									<th scope="col">Description</th>
									<th class="optimal-r1" scope="col">Unit</th>
									<th class="optimal-r1" scope="col"> Value</th>      
								</tr>

								<tr>
									
									<th scope="col"> Capacity(+/-3%)</th>
									<th class="optimal-r1" scope="col"> {{ $units_data[$unit_set->CapacityUnit] }} </th>
									<th class="optimal-r1" scope="col"> {{ $calculation_values['TON'] }}</th>      
								</tr>
								<tr>
									<td colspan="3"></td>
								</tr>
		      			  		</thead>
			      			  	<tbody>
									<tr>
										
										<th scope="col"> Chilled Water Circuit</th>
										<th scope="col"> </th>
										<th scope="col"> </th>      
									</tr>
									<tr>
										
										<td> Chilled water flow</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->FlowRateUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['ChilledWaterFlow'],1) }}</td>
									</tr>
									<tr>
										
										<td> Chilled water inlet temperature</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->TemperatureUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['TCHW11'],1) }}</td>
									</tr>
									<tr>
										
										<td> Chilled water outlet temperature</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->TemperatureUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['TCHW12'],1) }} </td>
									</tr>
									<tr>     
										
										<td> Evaporate passes</td>
										<td class="optimal-r1"> No.</td>
										<td class="optimal-r1"> {{ $calculation_values['EvaporatorPasses'] }}</td>
									</tr>
									<tr>     
										
										<td> Chilled water circuit pressure loss </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->PressureDropUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['ChilledFrictionLoss'],1) }} </td>
									</tr>
									<tr>     
										
										<td> Chilled water Connection diameter </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->NozzleDiameterUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['ChilledConnectionDiameter'],1) }} </td>
									</tr>
									<tr>     
										
										<td> Glycol type </td>
										<td> </td>
										@if(empty($calculation_values['CHGLY']) || $calculation_values['GL'] == 1)
											<td class="optimal-r1"> NA </td>
										@elseif($calculation_values['GL'] == 2)
											<td class="optimal-r1">Ethylene</td>
										@else
											<td class="optimal-r1">Proplylene</td>
										@endif			
									</tr>
									<tr>     
										
										<td> Chilled water glycol %  </td>
										<td class="optimal-r1"> %</td>
										<td class="optimal-r1"> {{ $calculation_values['CHGLY'] }} </td>
									</tr>
									<tr>     
										
										<td> Chilled water fouling factor </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->FoulingFactorUnit] }}</td>
										@if($calculation_values['TUU'] == "standard")
											<td class="optimal-r1"> {{ $calculation_values['TUU'] }} </td>
										@else
											<td class="optimal-r1"> {{ $calculation_values['FFCHW1'] }} </td>
										@endif	
									</tr>
									<tr>     
										
										<td> Maximum working pressure </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->WorkPressureUnit] }}</td>
										<td class="optimal-r1">  {{ ceil($calculation_values['m_maxCHWWorkPressure']) }}</td>
									</tr>
									<tr>
										
										<th scope="col"> Cooling Water Circuit</th>
										<th scope="col"> </th>
										<th scope="col"> </th>      
									</tr>
									<tr>
										
										<td> Cooling water flow</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->FlowRateUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['GCW'],1) }}</td>
									</tr>
									<tr>
										
										<td> Cooling water inlet temperature</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->TemperatureUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['TCW11'],1) }}</td>
									</tr>
									<tr>
										
										<td> Cooling water outlet temperature</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->TemperatureUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['CoolingWaterOutTemperature'],1) }}</td>
									</tr>
									<tr>
										
										<td> Absorber / Condenser passes</td>
										<td class="optimal-r1"> No.</td>
										<td class="optimal-r1"> {{ $calculation_values['AbsorberPasses'] }}/{{ $calculation_values['CondenserPasses'] }} </td>
									</tr>
									<tr>     
										
										<td> Cooling water Bypass Flow</td>
										<td class="optimal-r1">{{ $units_data[$unit_set->FlowRateUnit] }} </td>
										@if(empty($calculation_values['BypassFlow']))
											<td class="optimal-r1"> - </td>
										@else
											<td class="optimal-r1">{{ round($calculation_values['BypassFlow'],1) }}</td>
										@endif	
									</tr>
									<tr>     
										
										<td> Cooling water circuit pressure loss </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->PressureDropUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['CoolingFrictionLoss'],1) }} </td>
									</tr>
									<tr>     
										
										<td> Cooling water Connection diameter </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->NozzleDiameterUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['CoolingConnectionDiameter'],1) }} </td>
									</tr>
									<tr>     
										
										<td> Glycol type </td>
										<td> </td>
										@if(empty($calculation_values['COGLY']) || $calculation_values['GL'] == 1)
											<td class="optimal-r1"> NA </td>
										@elseif($calculation_values['GL'] == 2)
											<td class="optimal-r1">Ethylene</td>
										@else
											<td class="optimal-r1">Proplylene</td>
										@endif			
									</tr>
									<tr>     
										
										<td> Cooling water glycol %  </td>
										<td class="optimal-r1"> %</td>
										<td class="optimal-r1"> {{ $calculation_values['COGLY'] }} </td>
									</tr>
									<tr>     
										
										<td> Cooling water fouling factor </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->FoulingFactorUnit] }}</td>
										@if($calculation_values['TUU'] == "standard")
											<td class="optimal-r1"> {{ $calculation_values['TUU'] }} </td>
										@else
											<td class="optimal-r1"> {{ $calculation_values['FFCOW1'] }} </td>
										@endif
									</tr>
									<tr>     
										
										<td> Maximum working pressure </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->WorkPressureUnit] }}</td>
										<td class="optimal-r1">  {{ ceil($calculation_values['m_maxCOWWorkPressure']) }} </td>
									</tr>
									<tr>
										
										<th scope="col">Hot Water Circuit</th>
										<th scope="col"> </th>
										<th scope="col"> </th>      
									</tr>
									<tr>
										
										<td>Hot water flow(+/- 3%)</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->PressureUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['HotWaterFlow'],1) }}</td>
									</tr>
									<tr>
										
										<td> Hot water inlet temperature</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->TemperatureUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['hot_water_in'],1) }}</td>
									</tr>
									<tr>
										
										<td> Hot water outlet temperature</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->TemperatureUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['hot_water_out'],1) }} </td>
									</tr>
									<tr>
										
										<td> Generator passes</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->PressureUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['GeneratorPasses'],1) }} </td>
									</tr>
									<tr>     
										
										<td> Hot water circuit pressure loss</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->PressureDropUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['HotWaterFrictionLoss'],1) }}</td>
									</tr>
									<tr>     
										
										<td> Hot water connection diameter </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->NozzleDiameterUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['HotWaterConnectionDiameter'],1) }} </td>
									</tr>
									<tr>     
										
										<td>Maximum working pressure </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->WorkPressureUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['all_work_pr_hw'],1) }} </td>
									</tr>
									<tr>
										
										<th scope="col"> Electrical Data</th>
										<th scope="col"> </th>
										<th scope="col"> </th>      
									</tr>
									<tr>
										
										<td> Power supply</td>
										<td> </td>
										<td class="optimal-r1">
											@php($i=1)
										@foreach(explode(',', $calculation_values['PowerSupply']) as $PowerSupply) 
										    {{$PowerSupply}},
										     @if ($i == 2)
        										<br>
    										@endif
    										@php($i++)
										 @endforeach

										</td>
									</tr>
									<tr>
										
										<td> Power consumption</td>
										<td class="optimal-r1">kVA</td>
										<td class="optimal-r1"> {{ round($calculation_values['TotalPowerConsumption'],1) }}</td>
									</tr>
									<tr>
										
										<td> Absorbent pump rating</td>
										<td class="optimal-r1"> kW (A)</td>
										<td class="optimal-r1"> {{ round($calculation_values['AbsorbentPumpMotorKW'],2) }}( {{ round($calculation_values['AbsorbentPumpMotorAmp'],2) }} ) </td>
									</tr>
									<tr>
										
										<td> Refrigerant pump rating</td>
										<td class="optimal-r1"> kW (A)</td>
										<td class="optimal-r1"> {{ round($calculation_values['RefrigerantPumpMotorKW'],2) }}( {{ round($calculation_values['RefrigerantPumpMotorAmp'],2) }}) </td>
									</tr>
									<tr>     
										
										<td> Vacuum pump rating</td>
										<td class="optimal-r1"> kW (A)</td>
										<td class="optimal-r1"> {{ round($calculation_values['PurgePumpMotorKW'],2) }}({{ round($calculation_values['PurgePumpMotorAmp'],2) }}) </td>
									</tr>
									<tr>
										
										<th scope="col"> Physical Data</th>
										<th scope="col"> </th>
										<th scope="col"> </th>      
									</tr>
									<tr>
										
										<td> Length</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->LengthUnit] }}</td>
										<td class="optimal-r1"> {{ ceil($calculation_values['Length']) }}</td>
									</tr>
									<tr>
										
										<td> width</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->LengthUnit] }}</td>
										<td class="optimal-r1"> {{ ceil($calculation_values['Width']) }}</td>
									</tr>
									<tr>
										
										<td> Height</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->LengthUnit] }}</td>
										<td class="optimal-r1"> {{ ceil($calculation_values['Height']) }} </td>
									</tr>
									<tr>
										
										<td> Operating weight</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->WeightUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['OperatingWeight'],1) }}</td>
									</tr>
									<tr>     
										
										<td> Shipping weight</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->WeightUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['MaxShippingWeight'],1) }} </td>
									</tr>
									<tr>     
										
										<td> Flooded weight </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->WeightUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['FloodedWeight'],1) }} </td>
									</tr>
									<tr>     
										
										<td> Dry weight </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->WeightUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['DryWeight'],1) }} </td>
									</tr>
									<tr>     
										
										<td> Tube cleaning space <br>(any one side length-wise) </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->LengthUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['ClearanceForTubeRemoval'],1) }} </td>
									</tr>
									<tr>
										
										<th scope="col"> Tube Metallurgy</th>
										<th scope="col"> </th>
										<th scope="col"> </th>      
									</tr>
									<tr>
										
										<td> Evaporator tube material</td>
										<td> </td>
										<td class="optimal-r1"> {{ $evaporator_name }}</td>
									</tr>
									<tr>
										
										<td> Absorber tube material</td>
										<td> </td>
										<td class="optimal-r1"> {{ $absorber_name }}</td>
									</tr>
									<tr>
										
										<td> Condenser tube material</td>
										<td> </td>
										<td class="optimal-r1"> {{ $condenser_name }} </td>
									</tr>
									@if(!$calculation_values['isStandard'] || $calculation_values['isStandard'] != 'true')
										<tr>
											
											<td> Evaporator tube thickness</td>
											<td class="optimal-r1"> {{ $units_data[$unit_set->LengthUnit] }}</td>
											<td class="optimal-r1"> {{ $calculation_values['TU3'] }}</td>
										</tr>
										<tr>     
											
											<td> Absorber tube thickness</td>
											<td class="optimal-r1"> {{ $units_data[$unit_set->LengthUnit] }}</td>
											<td class="optimal-r1"> {{ $calculation_values['TU6'] }} </td>
										</tr>
										<tr>     
											
											<td> Condenser tube thickness </td>
											<td class="optimal-r1"> {{ $units_data[$unit_set->LengthUnit] }}</td>
											<td class="optimal-r1"> {{ $calculation_values['TV6'] }} </td>
										</tr>
									@endif	
									<tr>
										
										<th scope="col"> Low Temperature Heat exchanger Type</th>
										<th scope="col"> </th>
										<th class="optimal-r1" scope="col">{{ $calculation_values['HHType'] }} </th>      
									</tr>				
								</tbody>

		      				</table>
		      				
		      			</div>
		     		</div>
						<!-- Authentication card end -->
					<!-- end of col-sm-12 -->
				</div>
				<!-- end of row -->
			</div>
			<!-- end of container-fluid -->
		</section>

	</body>

</html>
