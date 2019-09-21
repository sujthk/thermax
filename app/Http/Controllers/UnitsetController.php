<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\UnitSet;

class UnitsetController extends Controller
{
    public function getUnitsets(){

    	$unit_sets = UnitSet::all();


    	return view('unit_sets')->with('unit_sets',$unit_sets);
    }

    public function addUnitset(){

    	return view('unit_set_add');
    }

    public function postUnitset(Request $request){
		$this->validate($request, [
		    'name' => 'required',
            'TemperatureUnit' => 'required',
		    'LengthUnit' => 'required',
		    'WeightUnit' => 'required',
		    'PressureUnit' => 'required',
		    'PressureDropUnit' => 'required',
		    'FurnacePressureDropUnit' => 'required',
		    'WorkPressureUnit' => 'required',
		    'AreaUnit' => 'required',
		    'VolumeUnit' => 'required',
		    'FlowRateUnit' => 'required',
		    'NozzleDiameterUnit' => 'required',
		    'CapacityUnit' => 'required',
		    'FoulingFactorUnit' => 'required',
		    'SteamConsumptionUnit' => 'required',
		    'ExhaustGasFlowUnit' => 'required',
		    'FuelConsumptionOilUnit' => 'required',
		    'FuelConsumptionGasUnit' => 'required',
		    'HeatUnit' => 'required',
		    'CalorificValueGasUnit' => 'required',
		    'CalorificValueOilUnit' => 'required',
		    'AllWorkPrHWUnit' => 'required',
		    'HeatCapacityUnit' => 'required',
		]);



		$unit_set = new UnitSet;
		$unit_set->name = $request->name;
		$unit_set->TemperatureUnit = $request->TemperatureUnit;
		$unit_set->LengthUnit = $request->LengthUnit;
		$unit_set->WeightUnit = $request->WeightUnit;
		$unit_set->PressureUnit = $request->PressureUnit;
		$unit_set->PressureDropUnit = $request->PressureDropUnit;
		$unit_set->FurnacePressureDropUnit = $request->FurnacePressureDropUnit;
		$unit_set->WorkPressureUnit = $request->WorkPressureUnit;
		$unit_set->AreaUnit = $request->AreaUnit;
		$unit_set->VolumeUnit = $request->VolumeUnit;
		$unit_set->FlowRateUnit = $request->FlowRateUnit;
		$unit_set->NozzleDiameterUnit = $request->NozzleDiameterUnit;
		$unit_set->CapacityUnit = $request->CapacityUnit;
		$unit_set->FoulingFactorUnit = $request->FoulingFactorUnit;
		$unit_set->SteamConsumptionUnit = $request->SteamConsumptionUnit;
		$unit_set->ExhaustGasFlowUnit = $request->ExhaustGasFlowUnit;
		$unit_set->FuelConsumptionOilUnit = $request->FuelConsumptionOilUnit;
		$unit_set->FuelConsumptionGasUnit = $request->FuelConsumptionGasUnit;
		$unit_set->HeatUnit = $request->HeatUnit;
		$unit_set->CalorificValueGasUnit = $request->CalorificValueGasUnit;
		$unit_set->CalorificValueOilUnit = $request->CalorificValueOilUnit;
		$unit_set->AllWorkPrHWUnit = $request->AllWorkPrHWUnit;
		$unit_set->HeatCapacityUnit = $request->HeatCapacityUnit;
		$unit_set->save();

		return redirect('unit-sets')->with('message','Unit Set Added')
                        ->with('status','success');
    }

    public function editUnitset($unit_set_id){
    	$unit_set = UnitSet::find($unit_set_id);

    	return view('unit_set_edit')->with('unit_set',$unit_set);
    }

    public function updateUnitset(Request $request,$unit_set_id){
		$this->validate($request, [
		    'name' => 'required',
            'TemperatureUnit' => 'required',
		    'LengthUnit' => 'required',
		    'WeightUnit' => 'required',
		    'PressureUnit' => 'required',
		    'PressureDropUnit' => 'required',
		    'FurnacePressureDropUnit' => 'required',
		    'WorkPressureUnit' => 'required',
		    'AreaUnit' => 'required',
		    'VolumeUnit' => 'required',
		    'FlowRateUnit' => 'required',
		    'NozzleDiameterUnit' => 'required',
		    'CapacityUnit' => 'required',
		    'FoulingFactorUnit' => 'required',
		    'SteamConsumptionUnit' => 'required',
		    'ExhaustGasFlowUnit' => 'required',
		    'FuelConsumptionOilUnit' => 'required',
		    'FuelConsumptionGasUnit' => 'required',
		    'HeatUnit' => 'required',
		    'CalorificValueGasUnit' => 'required',
		    'CalorificValueOilUnit' => 'required',
		    'AllWorkPrHWUnit' => 'required',
		    'HeatCapacityUnit' => 'required',
		]);



		$unit_set = UnitSet::find($unit_set_id);
		$unit_set->name = $request->name;
		$unit_set->TemperatureUnit = $request->TemperatureUnit;
		$unit_set->LengthUnit = $request->LengthUnit;
		$unit_set->WeightUnit = $request->WeightUnit;
		$unit_set->PressureUnit = $request->PressureUnit;
		$unit_set->PressureDropUnit = $request->PressureDropUnit;
		$unit_set->FurnacePressureDropUnit = $request->FurnacePressureDropUnit;
		$unit_set->WorkPressureUnit = $request->WorkPressureUnit;
		$unit_set->AreaUnit = $request->AreaUnit;
		$unit_set->VolumeUnit = $request->VolumeUnit;
		$unit_set->FlowRateUnit = $request->FlowRateUnit;
		$unit_set->NozzleDiameterUnit = $request->NozzleDiameterUnit;
		$unit_set->CapacityUnit = $request->CapacityUnit;
		$unit_set->FoulingFactorUnit = $request->FoulingFactorUnit;
		$unit_set->SteamConsumptionUnit = $request->SteamConsumptionUnit;
		$unit_set->ExhaustGasFlowUnit = $request->ExhaustGasFlowUnit;
		$unit_set->FuelConsumptionOilUnit = $request->FuelConsumptionOilUnit;
		$unit_set->FuelConsumptionGasUnit = $request->FuelConsumptionGasUnit;
		$unit_set->HeatUnit = $request->HeatUnit;
		$unit_set->CalorificValueGasUnit = $request->CalorificValueGasUnit;
		$unit_set->CalorificValueOilUnit = $request->CalorificValueOilUnit;
		$unit_set->AllWorkPrHWUnit = $request->AllWorkPrHWUnit;
		$unit_set->HeatCapacityUnit = $request->HeatCapacityUnit;
		$unit_set->save();

		return redirect('unit-sets')->with('message','Unit Set Updated')
                        ->with('status','success');
    }
}
