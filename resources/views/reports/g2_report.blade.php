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
			<div class="">
				<div class="row">
					<div class="col-sm-12">
						<!-- Authentication card start -->
		      			<!-- <div class="table table-responsive">
		      			    <table class="table table-bordered">
		      			  		<thead class="thead-dark">
		      			    		<tr>
										<th scope="col">Client : {{ $name  }}</th>
										<th scope="col">Version : 5.1.2.0</th>     
		  			    			</tr>
		      			     		<tr>
		      			      			<th scope="col">Enquiry : {{ $phone  }}</th>
		      			      			<th scope="col">Date : {{ date('m/d/Y, h:i A') }}</th>     
		      			    		</tr>
		  			     			<tr>
		      			      			<th scope="col">Project : {{ $project }}</th>
		      			      			<th scope="col">Model : {{ $calculation_values['model_name'] }}</th>     
		      			    		</tr>
		      			  		</thead>  
		      				</table>
		      			</div> -->
		      			
		      			<div class="table">
		      				<table class=" table-bordered report-top">
		      			  		<thead>
								<tr>
									
									<th scope="col">{{ $language_datas['description'] }}</th>
									<th class="optimal-r1" scope="col">{{ $language_datas['unit'] }}</th>
									<th class="optimal-r1" scope="col">{{ $language_datas['value'] }}</th>      
								</tr>

								<tr>
									
									<th  scope="col"> {{ $calculation_values['model_name'] }}</th>
									<th class="optimal-r1" scope="col"> {{ $units_data[$unit_set->CapacityUnit] }} </th>
									<th class="optimal-r1" scope="col"> {{ $calculation_values['TON'] }}</th>      
								</tr>
								<tr>
									<td colspan="3"></td>
								</tr>
		      			  		</thead>
			      			  	<tbody>
									<tr>
										
										<th scope="col"> {{ $language_datas['chilled_water_circuit'] }}</th>
										<th scope="col"> </th>
										<th scope="col"> </th>      
									</tr>
									<tr>
										
										<td> {{ $language_datas['chilled_water_flow'] }}</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->FlowRateUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['ChilledWaterFlow'],1) }}</td>
									</tr>
									<tr>
										
										<td> {{ $language_datas['chilled_inlet_temp'] }}</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->TemperatureUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['TCHW11'],1) }}</td>
									</tr>
									<tr>
										
										<td> {{ $language_datas['chilled_outlet_temp'] }}</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->TemperatureUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['TCHW12'],1) }} </td>
									</tr>
									<tr>     
										
										<td> {{ $language_datas['evaporate_pass'] }}</td>
										<td class="optimal-r1"> No.</td>
										<td class="optimal-r1"> {{ $calculation_values['EvaporatorPasses'] }}</td>
									</tr>
									<tr>     
										
										<td> {{ $language_datas['chilled_pressure_loss'] }}</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->PressureDropUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['ChilledFrictionLoss'],1) }} </td>
									</tr>
									<tr>     
										
										<td> {{ $language_datas['chilled_connection_diameter'] }}</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->NozzleDiameterUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['ChilledConnectionDiameter'],1) }} </td>
									</tr>
									<tr>     
										
										<td> {{ $language_datas['glycol_type'] }}</td>
										<td> </td>
										@if(empty($calculation_values['CHGLY']) || $calculation_values['GLL'] == 1)
											<td class="optimal-r1"> NA </td>
										@elseif($calculation_values['GLL'] == 2)
											<td class="optimal-r1">Ethylene</td>
										@else
											<td class="optimal-r1">Proplylene</td>
										@endif			
									</tr>
									<tr>     
										
										<td> {{ $language_datas['chilled_gylcol'] }} ( % ) </td>
										<td class="optimal-r1"> %</td>
										<td class="optimal-r1"> {{ $calculation_values['CHGLY'] }} </td>
									</tr>
									<tr>     
										
										<td> {{ $language_datas['chilled_fouling_factor'] }} </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->FoulingFactorUnit] }}</td>
										@if($calculation_values['TUU'] == "standard")
											<td class="optimal-r1"> {{ $calculation_values['TUU'] }} </td>
										@else
											<td class="optimal-r1"> {{ number_format($calculation_values['FFCHW1'], 5) }} </td>
										@endif	
									</tr>
									<tr>     
										<td> {{ $language_datas['max_working_pressure'] }}</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->WorkPressureUnit] }}</td>
										<td class="optimal-r1">  {{ ceil($calculation_values['m_maxCHWWorkPressure']) }}</td>
									</tr>
									<tr>
										
										<th scope="col"> {{ $language_datas['cooling_water_circuit'] }}</th>
										<th scope="col"> </th>
										<th scope="col"> </th>      
									</tr>
									<tr>
										
										<td> {{ $language_datas['cooling_water_flow'] }}</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->FlowRateUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['GCW'],1) }}</td>
									</tr>
									<tr>
										
										<td> {{ $language_datas['cooling_inlet_temp'] }}</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->TemperatureUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['TCW11'],1) }}</td>
									</tr>
									<tr>
										
										<td> {{ $language_datas['cooling_outlet_temp'] }}</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->TemperatureUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['CoolingWaterOutTemperature'],1) }}</td>
									</tr>
									<tr>
										
										<td>{{ $language_datas['absorber_condenser_pass'] }}</td>
										<td class="optimal-r1"> No.</td>
										<td class="optimal-r1"> {{ $calculation_values['AbsorberPasses'] }}/{{ $calculation_values['CondenserPasses'] }} </td>
									</tr>
									<tr>     
										
										<td> {{ $language_datas['cooling_bypass_flow'] }}</td>
										<td class="optimal-r1">{{ $units_data[$unit_set->FlowRateUnit] }} </td>
										@if(empty($calculation_values['BypassFlow']))
											<td class="optimal-r1"> - </td>
										@else
											<td class="optimal-r1">{{ round($calculation_values['BypassFlow'],1) }}</td>
										@endif	
									</tr>
									<tr>     
										
										<td> {{ $language_datas['cooling_pressure_loss'] }}</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->PressureDropUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['CoolingFrictionLoss'],1) }} </td>
									</tr>
									<tr>     
										
										<td> {{ $language_datas['cooling_connection_diameter'] }} </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->NozzleDiameterUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['CoolingConnectionDiameter'],1) }} </td>
									</tr>
									<tr>     
										
										<td> {{ $language_datas['glycol_type'] }} </td>
										<td> </td>
									
										@if(empty($calculation_values['COGLY']) || $calculation_values['GLL'] == 1)
											<td class="optimal-r1"> NA </td>
										@elseif($calculation_values['GLL'] == 2)
											<td class="optimal-r1">Ethylene</td>
										@else
											<td class="optimal-r1">Proplylene</td>
										@endif			
									</tr>
									<tr>     
										
										<td> {{ $language_datas['cooling_gylcol'] }} ( % )  </td>
										<td class="optimal-r1"> %</td>
										<td class="optimal-r1"> {{ $calculation_values['COGLY'] }} </td>
									</tr>
									<tr>     
										
										<td> {{ $language_datas['cooling_fouling_factor'] }} </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->FoulingFactorUnit] }}</td>
										@if($calculation_values['TUU'] == "standard")
											<td class="optimal-r1"> {{ $calculation_values['TUU'] }} </td>
										@else
											<td class="optimal-r1"> {{ number_format($calculation_values['FFCOW1'], 5) }} </td>
										@endif
									</tr>
									<tr>     
										
										<td> {{ $language_datas['max_working_pressure'] }}</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->WorkPressureUnit] }}</td>
										<td class="optimal-r1"> {{ ceil($calculation_values['m_maxCOWWorkPressure']) }} </td>
									</tr>
									<tr>
										
										<th scope="col"> {{ $language_datas['direct_fired_circuit'] }}</th>
										<th scope="col"> </th>
										<th scope="col"> </th>      
									</tr>
									<tr>
										<td> {{ $language_datas['fuel_type'] }}</td>
										<td class="optimal-r1"> Gas</td>
										<td class="optimal-r1"> {{ $calculation_values['CV'] }}</td>
									</tr>
                                    <tr>
                                        <td> {{ $language_datas['calorific_fuel_type'] }}</td>
                                        <td class="optimal-r1"> GCV</td>
                                        <td class="optimal-r1"> {{ $calculation_values['GCV'] }}</td>
                                    </tr>
                                    <tr>
                                        <td>{{ $language_datas['calorific_value'] }}</td>
                                        @if($calculation_values['GCV'] == 'NaturalGas')
                                            <td class="optimal-r1"> {{ $units_data[$unit_set->CalorificValueGasUnit] }}</td>
                                        @else
                                            <td class="optimal-r1"> {{ $units_data[$unit_set->CalorificValueOilUnit] }}</td>
                                        @endif
                                        
                                        <td class="optimal-r1"> {{ $calculation_values['RCV1'] }}</td>
                                    </tr>
                                    <tr>
                                        <td>{{ $language_datas['fuel_consumption'] }} ( + 3 %)</td>
                                        <td class="optimal-r1"> GCV</td>    
                                        <td class="optimal-r1"> {{ $calculation_values['FuelConsumption'] }}</td>
                                    </tr>
                                    <tr>
                                        <td>{{ $language_datas['exhaust_gas_duct_size'] }}</td>
                                        <td class="optimal-r1">{{ $units_data[$unit_set->NozzleDiameterUnit] }}</td>
                                        <td class="optimal-r1"> {{ $calculation_values['RCV1'] }}</td>
                                    </tr>
									<tr>
										
										<th scope="col"> {{ $language_datas['electrical_data'] }}</th>
										<th scope="col"> </th>
										<th scope="col"> </th>      
									</tr>
									<tr>
										
										<td> {{ $language_datas['power_supply'] }}</td>
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
										
										<td> {{ $language_datas['power_consumption'] }}</td>
										<td class="optimal-r1">kVA</td>
										<td class="optimal-r1"> {{ round($calculation_values['TotalPowerConsumption'],1) }}</td>
									</tr>
									<tr>
										
										<td> {{ $language_datas['absorbent_pump_rating'] }}</td>
										<td class="optimal-r1"> kW (A)</td>
                                        @if($calculation_values['region_type'] == 2)
                                            <td class="optimal-r1"> {{ round($calculation_values['USA_AbsorbentPumpMotorKW'],2) }}( {{ round($calculation_values['USA_AbsorbentPumpMotorAmp'],2) }} ) </td>
                                        @else
										  <td class="optimal-r1"> {{ round($calculation_values['AbsorbentPumpMotorKW'],2) }}( {{ round($calculation_values['AbsorbentPumpMotorAmp'],2) }} ) </td>
                                        @endif
									</tr>
									<tr>
										
										<td> {{ $language_datas['refrigerant_pump_rating'] }}</td>
										<td class="optimal-r1"> kW (A)</td>
                                         @if($calculation_values['region_type'] == 2)
                                            <td class="optimal-r1"> {{ round($calculation_values['USA_RefrigerantPumpMotorKW'],2) }}( {{ round($calculation_values['USA_RefrigerantPumpMotorAmp'],2) }}) </td>
                                         @else
										  <td class="optimal-r1"> {{ round($calculation_values['RefrigerantPumpMotorKW'],2) }}( {{ round($calculation_values['RefrigerantPumpMotorAmp'],2) }}) </td>
                                        @endif  
									</tr>
									<tr>     
									
										<td>{{ $language_datas['vaccum_pump_rating'] }}</td>
										<td class="optimal-r1"> kW (A)</td>
                                        @if($calculation_values['region_type'] == 2)
                                            <td class="optimal-r1"> {{ round($calculation_values['USA_PurgePumpMotorKW'],2) }}({{ round($calculation_values['USA_PurgePumpMotorAmp'],2) }}) </td>
                                        @else
										  <td class="optimal-r1"> {{ round($calculation_values['PurgePumpMotorKW'],2) }}({{ round($calculation_values['PurgePumpMotorAmp'],2) }}) </td>
                                        @endif
									</tr>
									@if($calculation_values['region_type'] == 2)
									<tr>     
										<td>MOP</td>
										<td> </td>
										<td class="optimal-r1"> {{ round($calculation_values['MOP'],2) }}</td>
									</tr>
									<tr>     
										<td> MCA</td>
										<td> </td>
										<td class="optimal-r1"> {{ round($calculation_values['MCA'],2) }} </td>
									</tr>
									@endif

									<tr>
										
										<th scope="col"> {{ $language_datas['physical_data'] }}</th>
										<th scope="col"> </th>
										<th scope="col"> </th>      
									</tr>
									<tr>
										
										<td> {{ $language_datas['length'] }} (Without Burner)</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->LengthUnit] }}</td>
										<td class="optimal-r1"> {{ ceil($calculation_values['Length']) }}</td>
									</tr>
									<tr>
										
										<td> {{ $language_datas['width'] }}</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->LengthUnit] }}</td>
										<td class="optimal-r1"> {{ ceil($calculation_values['Width']) }}</td>
									</tr>
									<tr>
										
										<td> {{ $language_datas['height'] }}</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->LengthUnit] }}</td>
										<td class="optimal-r1"> {{ ceil($calculation_values['Height']) }} </td>
									</tr>
									<tr>
										
										<td> {{ $language_datas['operating_weight'] }}</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->WeightUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['OperatingWeight'],1) }}</td>
									</tr>
									<tr>     
										
										<td> {{ $language_datas['shipping_weight'] }}</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->WeightUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['MaxShippingWeight'],1) }} </td>
									</tr>
									<tr>     
										
										<td> {{ $language_datas['flooded_weight'] }} </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->WeightUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['FloodedWeight'],1) }} </td>
									</tr>
									<tr>     
										
										<td> {{ $language_datas['dry_weight'] }}</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->WeightUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['DryWeight'],1) }} </td>
									</tr>
									<tr>     
										
										<td> {{ $language_datas['tube_clearing_space'] }} <br>({{ $language_datas['one_side_length_wise'] }}) </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->LengthUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['ClearanceForTubeRemoval'],1) }} </td>
									</tr>
									<tr>
										
										<th scope="col"> {{ $language_datas['tube_metallurgy'] }}</th>
										<th scope="col"> </th>
										<th scope="col"> </th>      
									</tr>
									<tr>
										
										<td> {{ $language_datas['evaporator_tube_material'] }}</td>
										<td> </td>
										<td class="optimal-r1"> {{ $evaporator_name }}</td>
									</tr>
									<tr>
										
										<td> {{ $language_datas['absorber_tube_material'] }}</td>
										<td> </td>
										<td class="optimal-r1"> {{ $absorber_name }}</td>
									</tr>
									<tr>
										
										<td> {{ $language_datas['condenser_tube_material'] }}</td>
										<td> </td>
										<td class="optimal-r1"> {{ $condenser_name }} </td>
									</tr>
									@if(!$calculation_values['isStandard'] || $calculation_values['isStandard'] != 'true')
										<tr>
											
											<td> {{ $language_datas['evaporator_tube_thickness'] }}</td>
											<td class="optimal-r1"> {{ $units_data[$unit_set->LengthUnit] }}</td>
											<td class="optimal-r1">  {{ $calculation_values['TU3'] }}</td>
										</tr>
										<tr>     
											
											<td> {{ $language_datas['absorber_tube_thickness'] }}</td>
											<td class="optimal-r1"> {{ $units_data[$unit_set->LengthUnit] }}</td>
											<td class="optimal-r1"> {{ $calculation_values['TU6'] }} </td>
										</tr>
										<tr>     
											
											<td> {{ $language_datas['condenser_tube_thickness'] }} </td>
											<td class="optimal-r1"> {{ $units_data[$unit_set->LengthUnit] }}</td>
											<td class="optimal-r1"> {{ $calculation_values['TV6'] }} </td>
										</tr>
									@endif	
									<tr>
										
										<th scope="col"> {{ $language_datas['low_temp_heat_exchange'] }}</th>
										<th scope="col"> </th>
										<th class="optimal-r1" scope="col">{{ $calculation_values['HHType'] }} </th>      
									</tr>				
								</tbody>

		      				</table><br>
		      				<!-- <div class="report-end">
								<h4> Caption Notes: </h4>
								@foreach($calculation_values['notes'] as $note)
									<p>{{ $note }}</p>
								@endforeach
							</div> -->
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
	</body>

</html>
