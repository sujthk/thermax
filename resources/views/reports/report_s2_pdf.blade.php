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
            @if($language == 2)

                @font-face {
                   font-family: SimHei;
                   src: url('{{base_path().'/public/'}}fonts/SimHei.ttf') format('truetype');
                }

               .font-class{
                  font-family: SimHei;
                }

            @endif    


	        .report-table .table>thead>tr>th{
	        	background: #676767;
	        	color: #fff;
	        }

            .dark-cell{
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
	        .caption-notes{
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
										<td class="dark-cell font-class" scope="col" style="padding-right: 40px;">{{ $language_datas['client'] }} <span class="cn">:</span> {{ $name }}</td>
										<td class="dark-cell font-class" scope="col">{{ $language_datas['version'] }} : 0.9 Dt: 24-May-2021</td>     
		  			    			</tr>
		      			     		<tr>
		      			      			<td class="dark-cell font-class" scope="col">{{ $language_datas['enquiry'] }} : {{ $phone }}</td>
		      			      			<td class="dark-cell font-class" scope="col">{{ $language_datas['date'] }} <span class="dn">:</span> {{ date('d-M-Y, H:i ') }}</td>     
		      			    		</tr>
		  			     			<tr>

		      			      			<td class="dark-cell font-class" scope="col">{{ $language_datas['project'] }} <span class="pn">:</span> {{ $project }}</td>
		      			      			<td class="dark-cell font-class" scope="col">{{ $language_datas['model'] }}<span class="mn">:</span> {{ $calculation_values['model_name'] }}</td>     
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
			      			  			<td class="dark-cell" scope="col">Sr.</td>
			      			  			<td class="dark-cell font-class" scope="col">{{ $language_datas['description'] }}</td>
			      			  			<td class="dark-cell font-class" class="optimal-r1" scope="col">{{ $language_datas['unit'] }}</td>
			      			  			<td class="dark-cell font-class"  class="optimal-r1" scope="col"> {{ $language_datas['cooling_mode'] }}</td>      
			      			  		</tr>

			      			  		<tr>
			      			  			<td class="dark-cell" scope="col"></td>
			      			  			<td class="dark-cell font-class" scope="col"> {{ $language_datas['capacity'] }}(+/-3%)</td>
			      			  			<td class="dark-cell" class="optimal-r1" scope="col"> {{ $units_data[$unit_set->CapacityUnit] }} </td>
			      			  			<td class="dark-cell" class="optimal-r1" scope="col"> {{ $calculation_values['TON'] }}</td>      
			      			  		</tr>
			      			  		<tr>
			      			  			<td colspan="4"></td>
			      			  		</tr>
									<tr>
										<td class="dark-cell" scope="col"> A  </td>
										<td class="dark-cell font-class" scope="col"> {{ $language_datas['chilled_water_circuit'] }}</td>
										<td class="dark-cell" scope="col"> </td>
										<td class="dark-cell" scope="col"> </td>      
									</tr>
									<tr>
										<td> 1 </td>
										<td class="font-class">{{ $language_datas['chilled_water_flow'] }}</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->FlowRateUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['ChilledWaterFlow'],1) }}</td>
									</tr>
									<tr>
										<td> 2 </td>
										<td class="font-class"> {{ $language_datas['chilled_inlet_temp'] }}</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->TemperatureUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['TCHW11'],1) }}</td>
									</tr>
									<tr>
										<td> 3 </td> 
										<td class="font-class"> {{ $language_datas['chilled_outlet_temp'] }}</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->TemperatureUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['TCHW12'],1) }} </td>
									</tr>
									<tr>     
										<td> 4 </td>
										<td class="font-class"> {{ $language_datas['evaporate_pass'] }}</td>
										<td class="optimal-r1"> No.</td>
										<td class="optimal-r1"> {{ $calculation_values['EvaporatorPasses'] }}</td>
									</tr>
									<tr>     
										<td> 5 </td>
										<td class="font-class"> {{ $language_datas['chilled_pressure_loss'] }} </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->PressureDropUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['ChilledFrictionLoss'],1) }} </td>
									</tr>
									<tr>     
										<td> 6 </td>
										<td class="font-class"> {{ $language_datas['chilled_connection_diameter'] }} </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->NozzleDiameterUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['ChilledConnectionDiameter'],1) }} </td>
									</tr>
									<tr>     
										<td> 7 </td>
										<td class="font-class"> {{ $language_datas['glycol_type'] }} </td>
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
										<td class="font-class"> {{ $language_datas['chilled_gylcol'] }} %  </td>
										<td class="optimal-r1"> %</td>
										<td class="optimal-r1"> {{ $calculation_values['CHGLY'] }} </td>
									</tr>
									<tr>     
										<td> 9 </td>
										<td class="font-class"> {{ $language_datas['chilled_fouling_factor'] }} </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->FoulingFactorUnit] }}</td>
										@if($calculation_values['TUU'] == "standard")
											<td class="optimal-r1"> {{ $calculation_values['TUU'] }} </td>
										@else
											<td class="optimal-r1"> {{ number_format($calculation_values['FFCHW1'], 5) }} </td>
										@endif	
									</tr>
									<tr>     
										<td> 10 </td>
										<td class="font-class"> {{ $language_datas['max_working_pressure'] }} </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->WorkPressureUnit] }}</td>
										<td class="optimal-r1">  {{ ceil($calculation_values['m_maxCHWWorkPressure']) }}</td>
									</tr>
									<tr>
										<td class="dark-cell" scope="col"> B  </td>
										<td class="dark-cell font-class" scope="col"> {{ $language_datas['cooling_water_circuit'] }}</td>
										<td class="dark-cell" scope="col"> </td>
										<td class="dark-cell" scope="col"> </td>      
									</tr>
									<tr>
										<td> 1 </td>
										<td class="font-class"> {{ $language_datas['cooling_water_flow'] }}</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->FlowRateUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['GCW'],1) }}</td>
									</tr>
									<tr>
										<td> 2 </td>
										<td class="font-class"> {{ $language_datas['cooling_inlet_temp'] }}</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->TemperatureUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['TCW11'],1) }}</td>
									</tr>
									<tr>
										<td> 3 </td>
										<td class="font-class"> {{ $language_datas['cooling_outlet_temp'] }}</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->TemperatureUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['CoolingWaterOutTemperature'],1) }}</td>
									</tr>
									<tr>
										<td> 4 </td> 
										<td class="font-class"> {{ $language_datas['absorber_condenser_pass'] }}</td>
										<td class="optimal-r1"> No.</td>
										<td class="optimal-r1"> {{ $calculation_values['AbsorberPasses'] }}/{{ $calculation_values['CondenserPasses'] }} </td>
									</tr>
									<tr>     
										<td> 5 </td>
										<td class="font-class"> {{ $language_datas['cooling_bypass_flow'] }}</td>
										<td class="optimal-r1">{{ $units_data[$unit_set->FlowRateUnit] }} </td>
										@if(empty($calculation_values['BypassFlow']))
											<td class="optimal-r1"> - </td>
										@else
											<td class="optimal-r1">{{ round($calculation_values['BypassFlow'],1) }}</td>
										@endif	
									</tr>
									<tr>     
										<td> 6 </td>
										<td class="font-class"> {{ $language_datas['cooling_pressure_loss'] }} </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->PressureDropUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['CoolingFrictionLoss'],1) }} </td>
									</tr>
									<tr>     
										<td> 7 </td>
										<td class="font-class"> {{ $language_datas['cooling_connection_diameter'] }} </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->NozzleDiameterUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['CoolingConnectionDiameter'],1) }} </td>
									</tr>
									<tr>     
										<td> 8 </td>
										<td class="font-class"> {{ $language_datas['glycol_type'] }} </td>
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
										<td class="font-class"> {{ $language_datas['cooling_gylcol'] }} %  </td>
										<td class="optimal-r1"> %</td>
										<td class="optimal-r1"> {{ $calculation_values['COGLY'] }} </td>
									</tr>
									<tr>     
										<td> 10 </td>
										<td class="font-class"> {{ $language_datas['cooling_fouling_factor'] }}</td>
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
										<td class="font-class"> {{ $language_datas['max_working_pressure'] }} </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->WorkPressureUnit] }}</td>
										<td class="optimal-r1"> {{ ceil($calculation_values['m_maxCOWWorkPressure']) }} </td>
									</tr>
									<tr>
										<td class="dark-cell" scope="col"> C </td>
										<td class="dark-cell font-class" scope="col"> {{ $language_datas['steam_circuit'] }}</td>
										<td class="dark-cell" scope="col"> </td>
										<td class="dark-cell" scope="col"> </td>      
									</tr>
									<tr>
										<td> 1 </td>
										<td class="font-class"> {{ $language_datas['steam_pressure'] }}</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->PressureUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['PST1'],1) }}</td>
									</tr>
									<tr>
										<td> 2 </td>
										<td class="font-class"> {{ $language_datas['steam_consumption'] }}(+/-3%)</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->SteamConsumptionUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['SteamConsumption'],1) }}</td>
									</tr>
									<tr>
										<td> 3 </td>
										<td class="font-class"> {{ $language_datas['condensate_drain_temperature'] }}</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->TemperatureUnit] }}</td>
										<td class="optimal-r1"> {{ ceil($calculation_values['m_dMinCondensateDrainTemperature']) }} - {{ ceil($calculation_values['m_dMaxCondensateDrainTemperature']) }} </td>
									</tr>
									<tr>
										<td> 4 </td> 
										<td class="font-class"> {{ $language_datas['condensate_drain_pressure'] }}</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->PressureUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['m_dCondensateDrainPressure'],1) }} </td>
									</tr>
									<tr>     
										<td> 5 </td>
										<td class="font-class"> {{ $language_datas['connection_inlet_dia'] }}</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->NozzleDiameterUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['SteamConnectionDiameter'],1) }}</td>
									</tr>
									<tr>     
										<td> 6 </td>
										<td class="font-class"> {{ $language_datas['connection_drain_dia'] }} </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->NozzleDiameterUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['SteamDrainDiameter'],1) }} </td>
									</tr>
									<tr>     
										<td> 7 </td>
										<td class="font-class"> {{ $language_datas['design_pressure'] }} </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->PressureUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['m_DesignPressure'],1) }} </td>
									</tr>
									<tr>
										<td class="dark-cell" scope="col"> D  </td>
										<td class="dark-cell font-class" scope="col"> {{ $language_datas['electrical_data'] }}</td>
										<td class="dark-cell" scope="col"> </td>
										<td class="dark-cell" scope="col"> </td>      
									</tr>
									<tr>
										<td> 1 </td>
										<td class="font-class"> {{ $language_datas['power_supply'] }}</td>
										<td> </td>
										<td class="optimal-r1"> {{ $calculation_values['PowerSupply'] }}</td>
									</tr>
									<tr>
										<td> 2 </td>
										<td class="font-class"> {{ $language_datas['power_consumption'] }}</td>
										<td class="optimal-r1">kVA</td>
										<td class="optimal-r1"> {{ round($calculation_values['TotalPowerConsumption'],1) }}</td>
									</tr>
									<tr>
										<td> 3 </td>
										<td class="font-class"> {{ $language_datas['absorbent_pump_rating'] }}</td>
										<td class="optimal-r1"> kW (A)</td>
										@if($calculation_values['region_type'] == 2)
                                            <td class="optimal-r1"> {{ round($calculation_values['USA_AbsorbentPumpMotorKW'],2) }}( {{ round($calculation_values['USA_AbsorbentPumpMotorAmp'],2) }} ) </td>
                                        @else
                                          <td class="optimal-r1"> {{ round($calculation_values['AbsorbentPumpMotorKW'],2) }}( {{ round($calculation_values['AbsorbentPumpMotorAmp'],2) }} ) </td>
                                        @endif
									</tr>
									<tr>
										<td> 4 </td> 
										<td class="font-class"> {{ $language_datas['refrigerant_pump_rating'] }}</td>
										<td class="optimal-r1"> kW (A)</td>
										@if($calculation_values['region_type'] == 2)
                                            <td class="optimal-r1"> {{ round($calculation_values['USA_RefrigerantPumpMotorKW'],2) }}( {{ round($calculation_values['USA_RefrigerantPumpMotorAmp'],2) }}) </td>
                                         @else
                                          <td class="optimal-r1"> {{ round($calculation_values['RefrigerantPumpMotorKW'],2) }}( {{ round($calculation_values['RefrigerantPumpMotorAmp'],2) }}) </td>
                                        @endif
									</tr>
									<tr>     
										<td> 5 </td>
										<td class="font-class"> {{ $language_datas['vaccum_pump_rating'] }}</td>
										<td class="optimal-r1"> kW (A)</td>
										@if($calculation_values['region_type'] == 2)
                                            <td class="optimal-r1"> {{ round($calculation_values['USA_PurgePumpMotorKW'],2) }}({{ round($calculation_values['USA_PurgePumpMotorAmp'],2) }}) </td>
                                        @else
                                          <td class="optimal-r1"> {{ round($calculation_values['PurgePumpMotorKW'],2) }}({{ round($calculation_values['PurgePumpMotorAmp'],2) }}) </td>
                                        @endif
									</tr>
                                    @if($calculation_values['region_type'] == 2)
                                        <tr>     
                                            <td> 6 </td>
                                            <td class="font-class"> MOP </td>
                                            <td class="optimal-r1">  </td>                                
                                            <td class="optimal-r1"> {{ round($calculation_values['MOP'],2) }}</td>
                                        </tr>
                                        <tr>     
                                            <td> 7 </td>
                                            <td class="font-class"> MCA </td>
                                            <td class="optimal-r1">  </td>                                
                                            <td class="optimal-r1"> {{ round($calculation_values['MCA'],2) }}</td>
                                        </tr>
                                    @endif
									<tr>
										<td class="dark-cell" scope="col"> E  </td>
										<td class="dark-cell font-class" scope="col"> {{ $language_datas['physical_data'] }}</td>
										<td class="dark-cell" scope="col"> </td>
										<td class="dark-cell" scope="col"> </td>      
									</tr>
									<tr>
										<td> 1 </td>
										<td class="font-class"> {{ $language_datas['length'] }}</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->LengthUnit] }}</td>
										<td class="optimal-r1"> {{ ceil($calculation_values['Length']) }}</td>
									</tr>
									<tr>
										<td> 2 </td>
										<td class="font-class"> {{ $language_datas['width'] }}</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->LengthUnit] }}</td>
										<td class="optimal-r1">  {{ ceil($calculation_values['Width']) }}</td>
									</tr>
									<tr>
										<td> 3 </td>
										<td class="font-class"> {{ $language_datas['height'] }}</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->LengthUnit] }}</td>
										<td class="optimal-r1"> {{ ceil($calculation_values['Height']) }} </td>
									</tr>
									<tr>
										<td> 4 </td> 
										<td class="font-class"> {{ $language_datas['operating_weight'] }}</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->WeightUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['OperatingWeight'],1) }}</td>
									</tr>
									<tr>     
										<td> 5 </td>
										<td class="font-class"> {{ $language_datas['shipping_weight'] }}</td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->WeightUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['MaxShippingWeight'],1) }} </td>
									</tr>
									<tr>     
										<td> 6 </td>
										<td class="font-class"> {{ $language_datas['flooded_weight'] }} </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->WeightUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['FloodedWeight'],1) }} </td>
									</tr>
									<tr>     
										<td> 7 </td>
										<td class="font-class"> {{ $language_datas['dry_weight'] }} </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->WeightUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['DryWeight'],1) }} </td>
									</tr>
									<tr>     
										<td> 8 </td>
										<td class="font-class"> {{ $language_datas['tube_clearing_space'] }} ({{ $language_datas['one_side_length_wise'] }}) </td>
										<td class="optimal-r1"> {{ $units_data[$unit_set->LengthUnit] }}</td>
										<td class="optimal-r1"> {{ round($calculation_values['ClearanceForTubeRemoval'],1) }} </td>
									</tr>
									<tr>
										<td class="dark-cell" scope="col"> F  </td>
										<td class="dark-cell font-class" scope="col"> {{ $language_datas['tube_metallurgy'] }}</td>
										<td class="dark-cell" scope="col"> </td>
										<td class="dark-cell" scope="col"> </td>      
									</tr>
									<tr>
										<td> 1 </td>
										<td class="font-class"> {{ $language_datas['evaporator_tube_material'] }}</td>
										<td> </td>
										<td class="optimal-r1"> {{ $evaporator_name }}</td>
									</tr>
									<tr>
										<td> 2 </td>
										<td class="font-class"> {{ $language_datas['absorber_tube_material'] }}</td>
										<td> </td>
										<td class="optimal-r1"> {{ $absorber_name }}</td>
									</tr>
									<tr>
										<td> 3 </td>
										<td class="font-class"> {{ $language_datas['condenser_tube_material'] }}</td>
										<td> </td>
										<td class="optimal-r1"> {{ $condenser_name }} </td>
									</tr>
									@if(!$calculation_values['isStandard'] || $calculation_values['isStandard'] != 'true')
										<tr>
											<td> 4 </td> 
											<td class="font-class"> {{ $language_datas['evaporator_tube_thickness'] }}</td>
											<td class="optimal-r1"> {{ $units_data[$unit_set->LengthUnit] }}</td>
											<td class="optimal-r1"> {{ $calculation_values['TU3'] }}</td>
										</tr>
										<tr>     
											<td> 5 </td>
											<td class="font-class"> {{ $language_datas['absorber_tube_thickness'] }}</td>
											<td class="optimal-r1"> {{ $units_data[$unit_set->LengthUnit] }}</td>
											<td class="optimal-r1"> {{ $calculation_values['TU6'] }} </td>
										</tr>
										<tr>     
											<td> 6 </td>
											<td class="font-class"> {{ $language_datas['condenser_tube_thickness'] }} </td>
											<td class="optimal-r1"> {{ $units_data[$unit_set->LengthUnit] }}</td>
											<td class="optimal-r1"> {{ $calculation_values['TV6'] }} </td>
										</tr>
									@endif	
									<tr>
										<td class="dark-cell" scope="col"> G </td>
										<td class="dark-cell font-class" scope="col"> {{ $language_datas['low_temp_heat_exchange'] }}</td>
										<td class="dark-cell" scope="col"> </td>
										<td class="dark-cell" class="optimal-r1" scope="col">{{ $calculation_values['HHType'] }} </td>      
									</tr>				
								</tbody>

		      				</table>
		      				<div class="report-end">
								<p class="caption-notes font-class"> {{ $language_datas['caption_notes'] }}: </p>
								@foreach($calculation_values['notes'] as $note)
									<p class="font-class">{{ $note }}</p>
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
