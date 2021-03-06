<!DOCTYPE html>
<html lang="en">

<head>
	<title>Thermax</title>
	<link rel="stylesheet" type="text/css" href="{{asset('bower_components/bootstrap/dist/css/bootstrap.min.css')}}">
	<!-- HTML5 Shim and Respond.js IE9 support of HTML5 elements and media queries -->
	<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
	<!--[if lt IE 9]>
		  <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
		  <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
		  <![endif]-->
	<!-- Meta -->

	<style>
		@if($language==2) @font-face {
			font-family: SimHei;
			src: url('{{base_path().'/public/'}}fonts/SimHei.ttf') format('truetype');
		}

		.font-class {
			font-family: SimHei;
		}

		@endif .report-table .table>thead>tr>th {
			background: #e5e5e5;
			color: #000;
		}

		.dark-cell {
			background: #e5e5e5;
			color: #000;
		}

		.report-table .table th {
			background: #e5e5e5;
			color: #000;
		}

		.report-table .table td {
			border: 1px solid #242424;
			padding: 0 5px;

		}

		.report-top {
			margin-bottom: 0;
		}

		.caption-notes {
			padding: .75rem;
			background: #e5e5e5;
			color: #000;
			font-size: 18px;
		}

		.report-end {
			border: 1px solid #676767;
		}

		.report-end p {
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

		.report-top tbody tr .empty-space {
			padding: 2px 0 3px 0;
			border: 1px solid #000;
		}
	</style>
</head>

<body class="fix-menu">
	<section class="report-table">
		<!-- Container-fluid starts -->
		<div class="container-fluid">
			<div class="row">
				<div class="pdt-top">
					<div class="row">
						<div class="col-md-7">
							<div class="technical-title">
								<h5
									style="text-align: center; font-family: Arial, Helvetica, sans-serif; font-weight: bold; text-transform: uppercase; font-size: 15px;">
									Technical
									Specifications : Vapour Absorption Chiller</h5>
							</div>
						</div>
						<div class="col-md-5">
							<div class="thermax-circuit-logo" style="text-align: right;">
								<img class="profile-bg-img img-fluid"
									src="{{asset('assets/images/Thermax-logo-fin.png')}}" alt="bg-img"
									style="width: 50px;">
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-12">
					<!-- Authentication card start -->
					<div class="table-responsive">
						<table class="table table-bordered" style="width: 100%;">
							<thead class="thead-dark">
								<tr>
									<td style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; padding-left: 10px; width: 15%;"
										class="dark-cell font-class" scope="col">
										{{ $language_datas['client'] }} </td>
									<td style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; padding-left: 10px; width: 35%;"
										class="dark-cell font-class" scope="col">{{ $name }}</td>
									<td style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; padding-left: 10px; width: 16%;"
										class="dark-cell font-class" scope="col">{{ $language_datas['version'] }}</td>
									<td style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; padding-left: 10px;"
										class="dark-cell font-class" scope="col">{{ $calculation_values['version'] }}
										Dt:
										{{ $calculation_values['version_date'] }}</td>
								</tr>
								<tr>
									<td style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; padding-left: 10px;"
										class="dark-cell font-class" scope="col">{{ $language_datas['enquiry'] }} </td>
									<td style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; padding-left: 10px;"
										class="dark-cell font-class" scope="col">{{ $phone }}</td>
									<td style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; padding-left: 10px;"
										class="dark-cell font-class" scope="col">{{ $language_datas['date'] }} </td>
									<td style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; padding-left: 10px;"
										class="dark-cell font-class" scope="col">{{ date('d-M-Y, H:i ') }}</td>
								</tr>
								<tr>

									<td style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; padding-left: 10px;"
										class="dark-cell font-class" scope="col">{{ $language_datas['project'] }} </td>
									<td style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; padding-left: 10px;"
										class="dark-cell font-class" scope="col">{{ $project }}</td>
									<td class="dark-cell font-class" scope="col"
										style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; padding-left: 10px;">
										{{ $language_datas['model'] }} </td>
									<td style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; padding-left: 10px;"
										class="dark-cell font-class" scope="col">{{ $calculation_values['model_name'] }}
									</td>
								</tr>
							</thead>
						</table>
					</div>
					<div class="table-responsive">
						<table class="table table-bordered report-top" style="width: 100%;">
							<thead>

							</thead>
							<tbody>
								<tr>
									<td style="text-align: center; font-family: Arial, Helvetica, sans-serif; font-size: 14px;"
										class="dark-cell" scope="col">Sr.</td>
									<td style="font-family: Arial, Helvetica, sans-serif; font-size: 14px;"
										class="dark-cell font-class" scope="col">{{ $language_datas['description'] }}
									</td>
									<td style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="dark-cell font-class" class="optimal-r1" scope="col">
										{{ $language_datas['unit'] }}</td>
									<td style="text-align: center; font-family: Arial, Helvetica, sans-serif; font-size: 14px;"
										class="dark-cell font-class" class="optimal-r1" scope="col">
										{{ $language_datas['cooling_mode'] }}</td>
								</tr>

								<tr>
									<td class="dark-cell" scope="col"></td>
									<td style="font-family: Arial, Helvetica, sans-serif; font-size: 14px;"
										class="dark-cell font-class" scope="col">
										{{ $language_datas['capacity'] }}(+/-3%)</td>
									<td style="text-align: center; font-family: Arial, Helvetica, sans-serif; font-size: 14px;"
										class="dark-cell" class="optimal-r1" scope="col">
										{{ $units_data[$unit_set->CapacityUnit] }} </td>
									<td style="text-align: center; font-family: Arial, Helvetica, sans-serif; font-size: 14px;"
										class="dark-cell" class="optimal-r1" scope="col">
										{{ round($calculation_values['TON'],1) }}</td>
								</tr>
								<tr>
									<td style="padding: 11px;" class="empty-space" colspan="4"></td>
								</tr>
								<tr>
									<td style="text-align: center; font-family: Arial, Helvetica, sans-serif; font-weight: bold;
										font-size: 14px;" class="dark-cell" scope="col"> A </td>
									<td style="font-family: Arial, Helvetica, sans-serif; font-weight: bold;
										font-size: 14px;" class="dark-cell font-class" scope="col">
										{{ $language_datas['chilled_water_circuit'] }}</td>
									<td style="font-family: Arial, Helvetica, sans-serif; font-weight: bold;
										font-size: 14px;" class="dark-cell" scope="col"> </td>
									<td style="font-family: Arial, Helvetica, sans-serif; font-weight: bold;
										font-size: 14px;" class="dark-cell" scope="col"> </td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										1 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class">{{ $language_datas['chilled_water_flow'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $units_data[$unit_set->FlowRateUnit] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ round($calculation_values['ChilledWaterFlow'],1) }}</td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										2 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['chilled_inlet_temp'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $units_data[$unit_set->TemperatureUnit] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ round($calculation_values['TCHW11'],1) }}</td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										3 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['chilled_outlet_temp'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $units_data[$unit_set->TemperatureUnit] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ round($calculation_values['TCHW12'],1) }} </td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										4 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['evaporate_pass'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> No.</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $calculation_values['EvaporatorPasses'] }}</td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										5 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['chilled_pressure_loss'] }} </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $units_data[$unit_set->PressureDropUnit] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ round($calculation_values['ChilledFrictionLoss'],1) }}
									</td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										6 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['chilled_connection_diameter'] }} </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $units_data[$unit_set->NozzleDiameterUnit] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1">
										{{ round($calculation_values['ChilledConnectionDiameter'],1) }} </td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										7 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['glycol_type'] }} </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"> </td>
									@if(empty($calculation_values['CHGLY']) || $calculation_values['GL'] == 1)
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> NA </td>
									@elseif($calculation_values['GL'] == 2)
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1">Ethylene</td>
									@else
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1">Proplylene</td>
									@endif
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										8 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['chilled_gylcol'] }} % </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> %</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $calculation_values['CHGLY'] }} </td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										9 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['chilled_fouling_factor'] }} </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $units_data[$unit_set->FoulingFactorUnit] }}</td>
									@if($calculation_values['TUU'] == "standard")
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $calculation_values['TUU'] }} </td>
									@else
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ number_format($calculation_values['FFCHW1'], 5) }} </td>
									@endif
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										10 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['max_working_pressure'] }} </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $units_data[$unit_set->WorkPressureUnit] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ ceil($calculation_values['m_maxCHWWorkPressure']) }}</td>
								</tr>
								<tr>
									<td style="padding: 11px;" class="empty-space" colspan="4"></td>
								</tr>
								<tr>
									<td style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px; font-weight: bold;"
										class="dark-cell" scope="col"> B </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px; font-weight: bold;"
										class="dark-cell font-class" scope="col">
										{{ $language_datas['hot_water_circuit'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="dark-cell" scope="col"> </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="dark-cell" scope="col"> </td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										1 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['heat_input'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $units_data[$unit_set->HeatUnit] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ round($calculation_values['HeatInput'],1) }}</td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										2 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class">{{ $language_datas['hot_water_flow'] }}(+/- 3%)</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $units_data[$unit_set->FlowRateUnit] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ round($calculation_values['HotWaterFlow'],1) }}</td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										3 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['hot_water_in_temp'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $units_data[$unit_set->TemperatureUnit] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ round($calculation_values['THW1'],1) }}</td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										4 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['hot_water_out_temp'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $units_data[$unit_set->TemperatureUnit] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ round($calculation_values['THW2'],1) }} </td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										5 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['side_arm_passes'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> No.</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ round($calculation_values['TGP'],1) }} </td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										6 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['hot_water_pressure_loss'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $units_data[$unit_set->PressureDropUnit] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ round($calculation_values['HotWaterFrictionLoss'],1) }}
									</td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										7 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['hot_water_connection_dia'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $units_data[$unit_set->NozzleDiameterUnit] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1">
										{{ round($calculation_values['HotWaterConnectionDiameter'],1) }} </td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										8 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class">{{ $language_datas['max_working_pressure'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $units_data[$unit_set->WorkPressureUnit] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1">{{ round($calculation_values['m_maxHWWorkPressure'],1) }}
									</td>
								</tr>
								<tr>
									<td style="padding: 11px;" class="empty-space" colspan="4"></td>
								</tr>
								<tr>
									<td style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px; font-weight: bold;"
										class="dark-cell" scope="col"> C </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px; font-weight: bold;"
										class="dark-cell font-class" scope="col">
										{{ $language_datas['cooling_water_circuit'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="dark-cell" scope="col"> </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="dark-cell" scope="col"> </td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										1 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['heat_rejected'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $units_data[$unit_set->HeatUnit] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ round($calculation_values['HeatRejected'],1) }}</td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										2 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['cooling_water_flow'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $units_data[$unit_set->FlowRateUnit] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ round($calculation_values['GCW'],1) }}</td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										3 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['cooling_inlet_temp'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $units_data[$unit_set->TemperatureUnit] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ round($calculation_values['TCW11'],1) }}</td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										4 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['cooling_outlet_temp'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $units_data[$unit_set->TemperatureUnit] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1">
										{{ round($calculation_values['CoolingWaterOutTemperature'],1) }}</td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										5 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['absorber_condenser_pass'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> No.</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1">
										{{ $calculation_values['AbsorberPasses'] }}/{{ $calculation_values['CondenserPasses'] }}
									</td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										6 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['cooling_bypass_flow'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1">{{ $units_data[$unit_set->FlowRateUnit] }} </td>
									@if(empty($calculation_values['BypassFlow']))
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> - </td>
									@else
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1">{{ round($calculation_values['BypassFlow'],1) }}</td>
									@endif
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										7 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['cooling_pressure_loss'] }} </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $units_data[$unit_set->PressureDropUnit] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ round($calculation_values['CoolingFrictionLoss'],1) }}
									</td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										8 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['cooling_connection_diameter'] }} </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $units_data[$unit_set->NozzleDiameterUnit] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1">
										{{ round($calculation_values['CoolingConnectionDiameter'],1) }} </td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										9 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['glycol_type'] }} </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"> </td>
									@if(empty($calculation_values['COGLY']) || $calculation_values['GL'] == 1)
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> NA </td>
									@elseif($calculation_values['GL'] == 2)
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1">Ethylene</td>
									@else
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1">Proplylene</td>
									@endif
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										10 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['cooling_gylcol'] }} % </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> %</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $calculation_values['COGLY'] }} </td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										11 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['cooling_fouling_factor'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $units_data[$unit_set->FoulingFactorUnit] }}</td>
									@if($calculation_values['TUU'] == "standard")
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $calculation_values['TUU'] }} </td>
									@else
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ number_format($calculation_values['FFCOW1'], 5) }}
									</td>
									@endif
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										12 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['max_working_pressure'] }} </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $units_data[$unit_set->WorkPressureUnit] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ ceil($calculation_values['m_maxCOWWorkPressure']) }}
									</td>
								</tr>
								<tr>
									<td style="padding: 11px;" class="empty-space" colspan="4"></td>
								</tr>
								<tr>
									<td style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px; font-weight: bold;"
										class="dark-cell" scope="col"> D </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px; font-weight: bold;"
										class="dark-cell font-class" scope="col"> {{ $language_datas['steam_circuit'] }}
									</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="dark-cell" scope="col"> </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="dark-cell" scope="col"> </td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										1 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['heat_duty'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $units_data[$unit_set->HeatUnit] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ round($calculation_values['HEATCAP'],1) }}</td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										2 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['steam_pressure'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $units_data[$unit_set->PressureUnit] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ round($calculation_values['PST1'],1) }}</td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										3 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['steam_consumption'] }}(+/-3%)</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $units_data[$unit_set->SteamConsumptionUnit] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ round($calculation_values['SteamConsumption'],1) }}</td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										4 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['condensate_drain_temperature'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $units_data[$unit_set->TemperatureUnit] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1">
										{{ ceil($calculation_values['m_dMinCondensateDrainTemperature']) }} -
										{{ ceil($calculation_values['m_dMaxCondensateDrainTemperature']) }} </td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										5 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['condensate_drain_pressure'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $units_data[$unit_set->PressureUnit] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1">
										{{ round($calculation_values['m_dCondensateDrainPressure'],1) }} </td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										6 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['connection_inlet_dia'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $units_data[$unit_set->NozzleDiameterUnit] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1">
										{{ round($calculation_values['SteamConnectionDiameter'],1) }}</td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										7 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['connection_drain_dia'] }} </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $units_data[$unit_set->NozzleDiameterUnit] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ round($calculation_values['SteamDrainDiameter'],1) }}
									</td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										8 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['design_pressure'] }} </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $units_data[$unit_set->PressureUnit] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ round($calculation_values['m_DesignPressure'],1) }} </td>
								</tr>
								<tr>
									<td style="padding: 11px;" class="empty-space" colspan="4"></td>
								</tr>
								<tr>
									<td style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px; font-weight: bold;"
										class="dark-cell" scope="col"> E </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px; font-weight: bold;"
										class="dark-cell font-class" scope="col">
										{{ $language_datas['electrical_data'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="dark-cell" scope="col"> </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="dark-cell" scope="col"> </td>
								</tr>
								<tr>
									<td
										style="vertical-align: middle; text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										1 </td>
									<td style="vertical-align: middle; font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['power_supply'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"> </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $calculation_values['PowerSupply'] }}</td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										2 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['power_consumption'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1">kVA</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ round($calculation_values['TotalPowerConsumption'],1) }}
									</td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										3 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['absorbent_pump_rating'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> kW (A)</td>
									@if($calculation_values['region_type'] == 2)
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1">
										{{ round($calculation_values['USA_AbsorbentPumpMotorKW'],2) }}(
										{{ round($calculation_values['USA_AbsorbentPumpMotorAmp'],2) }} ) </td>
									@else
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ round($calculation_values['AbsorbentPumpMotorKW'],2) }}(
										{{ round($calculation_values['AbsorbentPumpMotorAmp'],2) }} ) </td>
									@endif
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										4 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['refrigerant_pump_rating'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> kW (A)</td>
									@if($calculation_values['region_type'] == 2)
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1">
										{{ round($calculation_values['USA_RefrigerantPumpMotorKW'],2) }}(
										{{ round($calculation_values['USA_RefrigerantPumpMotorAmp'],2) }}) </td>
									@else
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1">
										{{ round($calculation_values['RefrigerantPumpMotorKW'],2) }}(
										{{ round($calculation_values['RefrigerantPumpMotorAmp'],2) }}) </td>
									@endif
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										5 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['vaccum_pump_rating'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> kW (A)</td>
									@if($calculation_values['region_type'] == 2)
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1">
										{{ round($calculation_values['USA_PurgePumpMotorKW'],2) }}({{ round($calculation_values['USA_PurgePumpMotorAmp'],2) }})
									</td>
									@else
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1">
										{{ round($calculation_values['PurgePumpMotorKW'],2) }}({{ round($calculation_values['PurgePumpMotorAmp'],2) }})
									</td>
									@endif
								</tr>
								@if($calculation_values['region_type'] == 2)
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										6 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> MOP </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ round($calculation_values['MOP'],2) }}</td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										7 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> MCA </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ round($calculation_values['MCA'],2) }}</td>
								</tr>
								@endif
								<tr>
									<td style="padding: 11px;" class="empty-space" colspan="4"></td>
								</tr>
								<tr>
									<td style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px; font-weight: bold;"
										class="dark-cell" scope="col"> F </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px; font-weight: bold;"
										class="dark-cell font-class" scope="col"> {{ $language_datas['physical_data'] }}
									</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="dark-cell" scope="col"> </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="dark-cell" scope="col"> </td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										1 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['length'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $units_data[$unit_set->LengthUnit] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ ceil($calculation_values['Length']) }}</td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										2 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['width'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $units_data[$unit_set->LengthUnit] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ ceil($calculation_values['Width']) }}</td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										3 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['height'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $units_data[$unit_set->LengthUnit] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ ceil($calculation_values['Height']) }} </td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										4 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['operating_weight'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $units_data[$unit_set->WeightUnit] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ round($calculation_values['OperatingWeight'],1) }}</td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										5 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['shipping_weight'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $units_data[$unit_set->WeightUnit] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ round($calculation_values['MaxShippingWeight'],1) }}
									</td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										6 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['flooded_weight'] }} </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $units_data[$unit_set->WeightUnit] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ round($calculation_values['FloodedWeight'],1) }} </td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										7 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['dry_weight'] }} </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $units_data[$unit_set->WeightUnit] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ round($calculation_values['DryWeight'],1) }} </td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										8 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['tube_clearing_space'] }}
										({{ $language_datas['one_side_length_wise'] }}) </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $units_data[$unit_set->LengthUnit] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1">
										{{ round($calculation_values['ClearanceForTubeRemoval'],1) }} </td>
								</tr>
								<tr>
									<td style="padding: 11px;" class="empty-space" colspan="4"></td>
								</tr>
								<tr>
									<td style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px; font-weight: bold;"
										class="dark-cell" scope="col"> G </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px; font-weight: bold;"
										class="dark-cell font-class" scope="col">
										{{ $language_datas['tube_metallurgy'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="dark-cell" scope="col"> </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="dark-cell" scope="col"> </td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										1 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['evaporator_tube_material'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"> </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $evaporator_name }}</td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										2 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['absorber_tube_material'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"> </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $absorber_name }}</td>
								</tr>
								<tr>
									<td
										style="text-align: center; font-family:Arial, Helvetica, sans-serif; font-size: 14px;">
										3 </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="font-class"> {{ $language_datas['condenser_tube_material'] }}</td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"> </td>
									<td style="font-family:Arial, Helvetica, sans-serif; font-size: 14px;"
										class="optimal-r1"> {{ $condenser_name }} </td>
								</tr>

							</tbody>

						</table>
						<div class="report-end" style="margin-top: 20px;">
							<p style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; font-weight: bold;"
								class="caption-notes font-class"> {{ $language_datas['caption_notes'] }}: </p>
							@foreach($calculation_values['notes'] as $note)
							<p style="font-family: Arial, Helvetica, sans-serif; font-size: 14px;" class="font-class">
								{{ $note }}</p>
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