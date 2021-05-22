@extends('layouts.app') 

@section('styles')	


@endsection

@section('content')
	<div class="main-body">
	    <div class="page-wrapper">
	        <!-- <div class="page-header">
	            <div class="page-header-title">
	                <h4>Add Unit Set</h4>
	            </div>
	            <div class="page-header-breadcrumb">
	                <ul class="breadcrumb-title">
	                    <li class="breadcrumb-item">
	                        <a href="{{ url('dashboard') }}">
	                            <i class="icofont icofont-home"></i>
	                        </a>
	                    </li>
	                    <li class="breadcrumb-item"><a href="{{ url('unit-sets') }}">Unit Sets</a>
	                    </li>
	                    <li class="breadcrumb-item"><a href="#!">Add Unit Set</a>
	                    </li>
	                </ul>
	            </div>
	        </div> -->
	        <div class="page-body">
	            <div class="row">
	                <div class="col-sm-8">
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
		                            <h5>Add Unit Set</h5>
		                            <div class="pull-left" style="float: right;">
									<a href="{{ url()->previous() }}" class="btn btn-info" title="Back" >Back</a>
									</div>
                            	</div>
	                        </div>
	                        <form id="add_unit_set" method="post" action="{{ url('unit-sets/add') }}" enctype="multipart/form-data">
                				{{ csrf_field() }}
		                        <div class="card-block">
		                        	<div class="form-group row">
		                        	    <label class="col-sm-4 col-form-label">Name</label>
		                        	    <div class="col-sm-6">
		                        	        <input id="name" name="name" type="text" value="{{ old('name') }}" required class="form-control">
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-4 col-form-label">TemperatureUnit</label>
		                        	    <div class="col-sm-6">
		                        	        <select name="TemperatureUnit" id="TemperatureUnit" required class="form-control">
                                                <option selected value="Centigrade">Centigrade</option>
                                                <option value="Fahrenheit">Fahrenheit</option>
                                            </select>
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-4 col-form-label">LengthUnit</label>
		                        	    <div class="col-sm-6">
		                        	        <select name="LengthUnit" id="LengthUnit" required class="form-control">
                                                <option selected value="Millimeter">Millimeter</option>
                                                <option value="Inch">Inch</option>
                                            </select>
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-4 col-form-label">WeightUnit</label>
		                        	    <div class="col-sm-6">
		                        	        <select name="WeightUnit" id="WeightUnit" required class="form-control">
                                                <option selected value="Kilogram">Kilogram</option>
                                                <option value="Ton">Ton</option>
                                                <option value="Pound">lbs</option>
                                            </select>
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-4 col-form-label">PressureUnit</label>
		                        	    <div class="col-sm-6">
		                        	        <select name="PressureUnit" id="PressureUnit" required class="form-control">
                                                <option selected value="KgPerCmSq">KgPerCmSq</option>
                                                <option value="KgPerCmSqGauge">KgPerCmSqGauge</option>
                                                <option value="Bar">Bar</option>
                                                <option value="BarGauge">BarGauge</option>
                                                <option value="mLC">mLC</option>
                                                <option value="mWC">mWC</option>
                                                <option value="mmWC">mmWC</option>
                                                <option value="ftLC">ftLC</option>
                                                <option value="ftWC">ftWC</option>
                                                <option value="psi">psi</option>
                                                <option value="psig">psig</option>
                                                <option value="kiloPascal">kiloPascal</option>
                                                <option value="kiloPascalGauge">kiloPascalGauge</option>
                                            </select>
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-4 col-form-label">PressureDropUnit</label>
		                        	    <div class="col-sm-6">
		                        	        <select name="PressureDropUnit" id="PressureDropUnit" required class="form-control">
                                                <option selected value="KgPerCmSq">KgPerCmSq</option>
                                                <option value="KgPerCmSqGauge">KgPerCmSqGauge</option>
                                                <option value="Bar">Bar</option>
                                                <option value="BarGauge">BarGauge</option>
                                                <option value="mLC">mLC</option>
                                                <option value="mWC">mWC</option>
                                                <option value="mmWC">mmWC</option>
                                                <option value="ftLC">ftLC</option>
                                                <option value="ftWC">ftWC</option>
                                                <option value="psi">psi</option>
                                                <option value="psig">psig</option>
                                                <option value="kiloPascal">kiloPascal</option>
                                                <option value="kiloPascalGauge">kiloPascalGauge</option>
                                            </select>
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-4 col-form-label">FurnacePressureDropUnit</label>
		                        	    <div class="col-sm-6">
		                        	        <select name="FurnacePressureDropUnit" id="FurnacePressureDropUnit" required class="form-control">
                                                <option selected value="KgPerCmSq">KgPerCmSq</option>
                                                <option value="KgPerCmSqGauge">KgPerCmSqGauge</option>
                                                <option value="Bar">Bar</option>
                                                <option value="BarGauge">BarGauge</option>
                                                <option value="mLC">mLC</option>
                                                <option value="mWC">mWC</option>
                                                <option value="mmWC">mmWC</option>
                                                <option value="ftLC">ftLC</option>
                                                <option value="ftWC">ftWC</option>
                                                <option value="psi">psi</option>
                                                <option value="psig">psig</option>
                                                <option value="kiloPascal">kiloPascal</option>
                                                <option value="kiloPascalGauge">kiloPascalGauge</option>
                                            </select>
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-4 col-form-label">WorkPressureUnit</label>
		                        	    <div class="col-sm-6">
		                        	        <select name="WorkPressureUnit" id="WorkPressureUnit" required class="form-control">
                                                <option selected value="KgPerCmSq">KgPerCmSq</option>
                                                <option value="KgPerCmSqGauge">KgPerCmSqGauge</option>
                                                <option value="Bar">Bar</option>
                                                <option value="BarGauge">BarGauge</option>
                                                <option value="mLC">mLC</option>
                                                <option value="mWC">mWC</option>
                                                <option value="mmWC">mmWC</option>
                                                <option value="ftLC">ftLC</option>
                                                <option value="ftWC">ftWC</option>
                                                <option value="psi">psi</option>
                                                <option value="psig">psig</option>
                                                <option value="kiloPascal">kiloPascal</option>
                                                <option value="kiloPascalGauge">kiloPascalGauge</option>
                                            </select>
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-4 col-form-label">AreaUnit</label>
		                        	    <div class="col-sm-6">
		                        	        <select name="AreaUnit" id="AreaUnit" required class="form-control">
                                                <option selected value="SquareMeter">SquareMeter</option>
                                                <option value="SquareFeet">SquareFeet</option>
                                            </select>
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-4 col-form-label">VolumeUnit</label>
		                        	    <div class="col-sm-6">
		                        	        <select name="VolumeUnit" id="VolumeUnit" required class="form-control">
                                                <option selected value="CubicMeter">CubicMeter</option>
                                                <option value="CubicFeet">CubicFeet</option>
                                            </select>
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-4 col-form-label">FlowRateUnit</label>
		                        	    <div class="col-sm-6">
		                        	        <select name="FlowRateUnit" id="FlowRateUnit" required class="form-control">
                                                <option selected value="CubicMeterPerHr">CubicMeterPerHr</option>
                                                <option value="CubicFeetPerHour">CubicFeetPerHour</option>
                                                <option value="GallonPerMin">GallonPerMin</option>
                                            </select>
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-4 col-form-label">NozzleDiameterUnit</label>
		                        	    <div class="col-sm-6">
		                        	        <select name="NozzleDiameterUnit" id="NozzleDiameterUnit" required class="form-control">
                                                <option selected value="DN">DN</option>
                                                <option value="NB">NB</option>
                                            </select>
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-4 col-form-label">CapacityUnit</label>
		                        	    <div class="col-sm-6">
		                        	        <select name="CapacityUnit" id="CapacityUnit" required class="form-control">
                                                <option selected value="TR">TR</option>
                                                <option value="kW">kW</option>
                                            </select>
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-4 col-form-label">FoulingFactorUnit</label>
		                        	    <div class="col-sm-6">
		                        	        <select name="FoulingFactorUnit" id="FoulingFactorUnit" required class="form-control">
                                                <option selected value="SquareMeterKperkW">SquareMeterKperkW</option>
                                                <option value="SquareMeterHrCperKcal">SquareMeterHrCperKcal</option>
                                                <option value="SquareFeetHrFperBTU">SquareFeetHrFperBTU</option>
                                            </select>
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-4 col-form-label">SteamConsumptionUnit</label>
		                        	    <div class="col-sm-6">
		                        	        <select name="SteamConsumptionUnit" id="SteamConsumptionUnit" required class="form-control">
                                                <option selected value="KilogramsPerHr">KilogramsPerHr</option>
                                                <option value="PoundsPerHour">PoundsPerHour</option>
                                            </select>
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-4 col-form-label">ExhaustGasFlowUnit</label>
		                        	    <div class="col-sm-6">
		                        	        <select name="ExhaustGasFlowUnit" id="ExhaustGasFlowUnit" required class="form-control">
                                                <option selected value="KilogramsPerHr">KilogramsPerHr</option>
                                                <option value="PoundsPerHour">PoundsPerHour</option>
                                            </select>
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-4 col-form-label">FuelConsumptionOilUnit</label>
		                        	    <div class="col-sm-6">
		                        	        <select name="FuelConsumptionOilUnit" id="FuelConsumptionOilUnit" required class="form-control">
                                                <option selected value="KilogramsPerHr">KilogramsPerHr</option>
                                                <option value="PoundsPerHour">PoundsPerHour</option>
                                            </select>
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-4 col-form-label">FuelConsumptionGasUnit</label>
		                        	    <div class="col-sm-6">
		                        	        <select name="FuelConsumptionGasUnit" id="FuelConsumptionGasUnit" required class="form-control">
                                                <option selected value="NCubicMeterPerHr">NCubicMeterPerHr</option>
                                                <option value="NCubicFeetPerHour">NCubicFeetPerHour</option>
                                            </select>
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-4 col-form-label">HeatUnit</label>
		                        	    <div class="col-sm-6">
		                        	        <select name="HeatUnit" id="HeatUnit" required class="form-control">
                                                <option selected value="kCPerHour">kCPerHour</option>
                                                <option value="KWatt">KWatt</option>
                                                <option value="MBTUPerHour">MBTUPerHour</option>
                                            </select>
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-4 col-form-label">CalorificValueGasUnit</label>
		                        	    <div class="col-sm-6">
		                        	        <select name="CalorificValueGasUnit" id="CalorificValueGasUnit" required class="form-control">
                                                <option selected value="kCPerNcubicmetre">kCPerNcubicmetre</option>
                                                <option value="BTUPerNcubicfeet">BTUPerNcubicfeet</option>
                                                <option value="kJPerNcubicmetre">kJPerNcubicmetre</option>
                                            </select>
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-4 col-form-label">CalorificValueOilUnit</label>
		                        	    <div class="col-sm-6">
		                        	        <select name="CalorificValueOilUnit" id="CalorificValueOilUnit" required class="form-control">
                                                <option selected value="kCPerKilogram">kCPerKilogram</option>
                                                <option value="BTUPerPound">BTUPerPound</option>
                                                <option value="kJPerKilogram">kJPerKilogram</option>
                                            </select>
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-4 col-form-label">AllWorkPrHWUnit</label>
		                        	    <div class="col-sm-6">
		                        	        <select name="AllWorkPrHWUnit" id="AllWorkPrHWUnit" required class="form-control">
                                                <option selected value="KgPerCmSqGauge">KgPerCmSqGauge</option>
                                                <option value="psiGauge">psiGauge</option>
                                                <option value="kiloPascalGauge">kiloPascalGauge</option>
                                            </select>
		                        	    </div>
		                        	</div>
		                        	<div class="form-group row">
		                        	    <label class="col-sm-4 col-form-label">HeatCapacityUnit</label>
		                        	    <div class="col-sm-6">
		                        	        <select name="HeatCapacityUnit" id="HeatCapacityUnit" required class="form-control">
                                                <option selected value="kcalperkgdegC">kcalperkgdegC</option>
                                                <option value="kJouleperkgdegC">kJouleperkgdegC</option>
                                                <option value="BTUperpounddegF">BTUperpounddegF</option>
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
	
@endsection