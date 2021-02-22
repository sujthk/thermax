<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\UnitSet;
use Log;

class UnitConversionController extends Controller
{
    public function formUnitConversion($chiller_values,$calculator_code){

        $unit_set_id = Auth::user()->unit_set_id;
    	$unit_set = UnitSet::find($unit_set_id);

        // Capacity Unit
        $chiller_values['capacity'] = $this->convertUICapacityUnit($chiller_values['capacity'],$unit_set->CapacityUnit);

        // TemperatureUnit
        $chiller_values['chilled_water_in'] = $this->convertUITemperatureUnit($chiller_values['chilled_water_in'],$unit_set->TemperatureUnit);
        $chiller_values['chilled_water_out'] = $this->convertUITemperatureUnit($chiller_values['chilled_water_out'],$unit_set->TemperatureUnit);
        $chiller_values['min_chilled_water_out'] = $this->convertUITemperatureUnit($chiller_values['min_chilled_water_out'],$unit_set->TemperatureUnit);
        $chiller_values['cooling_water_in'] = $this->convertUITemperatureUnit($chiller_values['cooling_water_in'],$unit_set->TemperatureUnit);
        $chiller_values['cooling_water_in_min_range'] = $this->convertUITemperatureUnit($chiller_values['cooling_water_in_min_range'],$unit_set->TemperatureUnit);
        $chiller_values['cooling_water_in_max_range'] = $this->convertUITemperatureUnit($chiller_values['cooling_water_in_max_range'],$unit_set->TemperatureUnit);
       if($calculator_code == "D_H2")
       {
           $chiller_values['hot_water_in'] = $this->convertUITemperatureUnit($chiller_values['hot_water_in'],$unit_set->TemperatureUnit);
           $chiller_values['hot_water_out'] = $this->convertUITemperatureUnit($chiller_values['hot_water_out'],$unit_set->TemperatureUnit);
           $chiller_values['min_hot_water_out'] = $this->convertUITemperatureUnit($chiller_values['min_hot_water_out'],$unit_set->TemperatureUnit);
           $chiller_values['min_hot_water_in'] = $this->convertUITemperatureUnit($chiller_values['min_hot_water_in'],$unit_set->TemperatureUnit); 
           $chiller_values['max_hot_water_in'] = $this->convertUITemperatureUnit($chiller_values['max_hot_water_in'],$unit_set->TemperatureUnit);
       }


        // FlowRateUnit 
        $chiller_values['cooling_water_flow'] = $this->convertUIFlowRateUnit($chiller_values['cooling_water_flow'],$unit_set->FlowRateUnit);
        $cooling_water_ranges = array();
        if(!is_array($chiller_values['cooling_water_ranges'])){
            $chiller_values['cooling_water_ranges'] = explode(",", $chiller_values['cooling_water_ranges']);
        }
        for ($i=0; $i < count($chiller_values['cooling_water_ranges']); $i++) { 
            $cooling_water_ranges[] = $this->convertUIFlowRateUnit($chiller_values['cooling_water_ranges'][$i],$unit_set->FlowRateUnit);
        }
        $chiller_values['cooling_water_ranges'] = $cooling_water_ranges;

        // LengthUnit
        $chiller_values['evaporator_thickness'] = $this->convertUILengthUnit($chiller_values['evaporator_thickness'],$unit_set->LengthUnit);
        $chiller_values['absorber_thickness'] = $this->convertUILengthUnit($chiller_values['absorber_thickness'],$unit_set->LengthUnit);
        $chiller_values['condenser_thickness'] = $this->convertUILengthUnit($chiller_values['condenser_thickness'],$unit_set->LengthUnit);
        $chiller_values['evaporator_thickness_min_range'] = $this->convertUILengthUnit($chiller_values['evaporator_thickness_min_range'],$unit_set->LengthUnit);
        $chiller_values['evaporator_thickness_max_range'] = $this->convertUILengthUnit($chiller_values['evaporator_thickness_max_range'],$unit_set->LengthUnit);
        $chiller_values['absorber_thickness_min_range'] = $this->convertUILengthUnit($chiller_values['absorber_thickness_min_range'],$unit_set->LengthUnit);
        $chiller_values['absorber_thickness_max_range'] = $this->convertUILengthUnit($chiller_values['absorber_thickness_max_range'],$unit_set->LengthUnit);
        $chiller_values['condenser_thickness_min_range'] = $this->convertUILengthUnit($chiller_values['condenser_thickness_min_range'],$unit_set->LengthUnit);
        $chiller_values['condenser_thickness_max_range'] = $this->convertUILengthUnit($chiller_values['condenser_thickness_max_range'],$unit_set->LengthUnit);


        // FoulingFactorUnit
        $chiller_values['fouling_non_chilled'] = $this->convertUIFoulingFactorUnit($chiller_values['fouling_non_chilled'],$unit_set->FoulingFactorUnit);
        $chiller_values['fouling_non_cooling'] = $this->convertUIFoulingFactorUnit($chiller_values['fouling_non_cooling'],$unit_set->FoulingFactorUnit);
        $chiller_values['fouling_ari_chilled'] = $this->convertUIFoulingFactorUnit($chiller_values['fouling_ari_chilled'],$unit_set->FoulingFactorUnit);
        $chiller_values['fouling_ari_cooling'] = $this->convertUIFoulingFactorUnit($chiller_values['fouling_ari_cooling'],$unit_set->FoulingFactorUnit);
        if(!empty($chiller_values['fouling_chilled_water_value'])){
            $chiller_values['fouling_chilled_water_value'] = $this->convertUIFoulingFactorUnit($chiller_values['fouling_chilled_water_value'],$unit_set->FoulingFactorUnit);
        }
        if(!empty($chiller_values['fouling_cooling_water_value'])){
            $chiller_values['fouling_cooling_water_value'] = $this->convertUIFoulingFactorUnit($chiller_values['fouling_cooling_water_value'],$unit_set->FoulingFactorUnit);
        }

        // PressureUnit
        if($calculator_code == "D_S2"){
            $chiller_values['steam_pressure'] = $this->convertUIPressureUnit($chiller_values['steam_pressure'],$unit_set->PressureUnit);
            $chiller_values['steam_pressure_min_range'] = $this->convertUIPressureUnit($chiller_values['steam_pressure_min_range'],$unit_set->PressureUnit);
            $chiller_values['steam_pressure_max_range'] = $this->convertUIPressureUnit($chiller_values['steam_pressure_max_range'],$unit_set->PressureUnit);

        }
       

        // AllWorkPrHWUnit
        if($calculator_code == "D_H2"){
            $chiller_values['all_work_pr_hw'] = $this->convertUIAllWorkPrHWUnit($chiller_values['all_work_pr_hw'],$unit_set->AllWorkPrHWUnit);
        }



    	return $chiller_values;

    }


    public function calculationUnitConversion($chiller_values,$calculator_code){

        $unit_set_id = Auth::user()->unit_set_id;
    	$unit_set = UnitSet::find($unit_set_id);

        // CapacityUnit
        $chiller_values['capacity'] = $this->convertCalculationCapacityUnit($chiller_values['capacity'],$unit_set->CapacityUnit);

        // TemperatureUnit
        $chiller_values['chilled_water_in'] = $this->convertCalculationTemperatureUnit($chiller_values['chilled_water_in'],$unit_set->TemperatureUnit);
        $chiller_values['chilled_water_out'] = $this->convertCalculationTemperatureUnit($chiller_values['chilled_water_out'],$unit_set->TemperatureUnit);
        $chiller_values['min_chilled_water_out'] = $this->convertCalculationTemperatureUnit($chiller_values['min_chilled_water_out'],$unit_set->TemperatureUnit);
        $chiller_values['cooling_water_in'] = $this->convertCalculationTemperatureUnit($chiller_values['cooling_water_in'],$unit_set->TemperatureUnit);
        $chiller_values['cooling_water_in_min_range'] = $this->convertCalculationTemperatureUnit($chiller_values['cooling_water_in_min_range'],$unit_set->TemperatureUnit);
        $chiller_values['cooling_water_in_max_range'] = $this->convertCalculationTemperatureUnit($chiller_values['cooling_water_in_max_range'],$unit_set->TemperatureUnit);

        if($calculator_code == "D_H2")
       {
           $chiller_values['hot_water_in'] = $this->convertCalculationTemperatureUnit($chiller_values['hot_water_in'],$unit_set->TemperatureUnit);
           $chiller_values['hot_water_out'] = $this->convertCalculationTemperatureUnit($chiller_values['hot_water_out'],$unit_set->TemperatureUnit);
           $chiller_values['min_hot_water_out'] = $this->convertCalculationTemperatureUnit($chiller_values['min_hot_water_out'],$unit_set->TemperatureUnit);
           $chiller_values['min_hot_water_in'] = $this->convertCalculationTemperatureUnit($chiller_values['min_hot_water_in'],$unit_set->TemperatureUnit); 
           $chiller_values['max_hot_water_in'] = $this->convertCalculationTemperatureUnit($chiller_values['max_hot_water_in'],$unit_set->TemperatureUnit);
       }


        // FlowRateUnit
        $chiller_values['cooling_water_flow'] = $this->convertCalculationFlowRateUnit($chiller_values['cooling_water_flow'],$unit_set->FlowRateUnit);
        $cooling_water_ranges = array();
        if(!is_array($chiller_values['cooling_water_ranges'])){
            $chiller_values['cooling_water_ranges'] = explode(",", $chiller_values['cooling_water_ranges']);
        }
        for ($i=0; $i < count($chiller_values['cooling_water_ranges']); $i++) { 
            $cooling_water_ranges[] = $this->convertCalculationFlowRateUnit($chiller_values['cooling_water_ranges'][$i],$unit_set->FlowRateUnit);
        }
        $chiller_values['cooling_water_ranges'] = $cooling_water_ranges;


        // LengthUnit
        $chiller_values['evaporator_thickness'] = $this->convertCalculationLengthUnit($chiller_values['evaporator_thickness'],$unit_set->LengthUnit);
        $chiller_values['absorber_thickness'] = $this->convertCalculationLengthUnit($chiller_values['absorber_thickness'],$unit_set->LengthUnit);
        $chiller_values['condenser_thickness'] = $this->convertCalculationLengthUnit($chiller_values['condenser_thickness'],$unit_set->LengthUnit);
        $chiller_values['evaporator_thickness_min_range'] = $this->convertCalculationLengthUnit($chiller_values['evaporator_thickness_min_range'],$unit_set->LengthUnit);
        $chiller_values['evaporator_thickness_max_range'] = $this->convertCalculationLengthUnit($chiller_values['evaporator_thickness_max_range'],$unit_set->LengthUnit);
        $chiller_values['absorber_thickness_min_range'] = $this->convertCalculationLengthUnit($chiller_values['absorber_thickness_min_range'],$unit_set->LengthUnit);
        $chiller_values['absorber_thickness_max_range'] = $this->convertCalculationLengthUnit($chiller_values['absorber_thickness_max_range'],$unit_set->LengthUnit);
        $chiller_values['condenser_thickness_min_range'] = $this->convertCalculationLengthUnit($chiller_values['condenser_thickness_min_range'],$unit_set->LengthUnit);
        $chiller_values['condenser_thickness_max_range'] = $this->convertCalculationLengthUnit($chiller_values['condenser_thickness_max_range'],$unit_set->LengthUnit);

        // FoulingFactorUnit
        $chiller_values['fouling_non_chilled'] = $this->convertCalculationFoulingFactorUnit($chiller_values['fouling_non_chilled'],$unit_set->FoulingFactorUnit);
        $chiller_values['fouling_non_cooling'] = $this->convertCalculationFoulingFactorUnit($chiller_values['fouling_non_cooling'],$unit_set->FoulingFactorUnit);
        $chiller_values['fouling_ari_chilled'] = $this->convertCalculationFoulingFactorUnit($chiller_values['fouling_ari_chilled'],$unit_set->FoulingFactorUnit);
        $chiller_values['fouling_ari_cooling'] = $this->convertCalculationFoulingFactorUnit($chiller_values['fouling_ari_cooling'],$unit_set->FoulingFactorUnit);
        if(!empty($chiller_values['fouling_chilled_water_value'])){
            $chiller_values['fouling_chilled_water_value'] = $this->convertCalculationFoulingFactorUnit($chiller_values['fouling_chilled_water_value'],$unit_set->FoulingFactorUnit);
        }
        if(!empty($chiller_values['fouling_cooling_water_value'])){
            $chiller_values['fouling_cooling_water_value'] = $this->convertCalculationFoulingFactorUnit($chiller_values['fouling_cooling_water_value'],$unit_set->FoulingFactorUnit);
        }


        // PressureUnit
        if($calculator_code == "D_S2"){
            $chiller_values['steam_pressure'] = $this->convertCalculationPressureUnit($chiller_values['steam_pressure'],$unit_set->PressureUnit);
            $chiller_values['steam_pressure_min_range'] = $this->convertCalculationPressureUnit($chiller_values['steam_pressure_min_range'],$unit_set->PressureUnit);
            $chiller_values['steam_pressure_max_range'] = $this->convertCalculationPressureUnit($chiller_values['steam_pressure_max_range'],$unit_set->PressureUnit);
        }
        


        // AllWorkPrHWUnit
        if($calculator_code == "D_H2"){
            $chiller_values['all_work_pr_hw'] = $this->convertCalculationAllWorkPrHWUnit($chiller_values['all_work_pr_hw'],$unit_set->AllWorkPrHWUnit);
        }
        

    	return $chiller_values;

    }


    public function reportUnitConversion($calculated_values,$calculator_code){

        $unit_set_id = Auth::user()->unit_set_id;
    	$unit_set = UnitSet::find($unit_set_id);

        // CapacityUnit
        $calculated_values['TON'] = $this->convertUICapacityUnit($calculated_values['TON'],$unit_set->CapacityUnit);

        // TemperatureUnit
        $calculated_values['TCHW11'] = $this->convertUITemperatureUnit($calculated_values['TCHW11'],$unit_set->TemperatureUnit);
        $calculated_values['TCHW12'] = $this->convertUITemperatureUnit($calculated_values['TCHW12'],$unit_set->TemperatureUnit);
        $calculated_values['TCW11'] = $this->convertUITemperatureUnit($calculated_values['TCW11'],$unit_set->TemperatureUnit);
        $calculated_values['CoolingWaterOutTemperature'] = $this->convertUITemperatureUnit($calculated_values['CoolingWaterOutTemperature'],$unit_set->TemperatureUnit);
        $calculated_values['m_dMinCondensateDrainTemperature'] = $this->convertUITemperatureUnit($calculated_values['m_dMinCondensateDrainTemperature'],$unit_set->TemperatureUnit);
        $calculated_values['m_dMaxCondensateDrainTemperature'] = $this->convertUITemperatureUnit($calculated_values['m_dMaxCondensateDrainTemperature'],$unit_set->TemperatureUnit);
        if($calculator_code == "D_H2")
        {
            $calculated_values['hot_water_in'] = $this->convertUITemperatureUnit($calculated_values['hot_water_in'],$unit_set->TemperatureUnit);
            $calculated_values['hot_water_out'] = $this->convertUITemperatureUnit($calculated_values['hot_water_out'],$unit_set->TemperatureUnit);
        }

        // FlowRateUnit
        $calculated_values['GCW'] = $this->convertUIFlowRateUnit($calculated_values['GCW'],$unit_set->FlowRateUnit);
        $calculated_values['ChilledWaterFlow'] = $this->convertUIFlowRateUnit($calculated_values['ChilledWaterFlow'],$unit_set->FlowRateUnit);
        $calculated_values['BypassFlow'] = $this->convertUIFlowRateUnit($calculated_values['BypassFlow'],$unit_set->FlowRateUnit);
        if($calculator_code == "D_H2")
        {
            $calculated_values['HotWaterFlow']= $this->convertUIFlowRateUnit($calculated_values['HotWaterFlow'],$unit_set->FlowRateUnit);
        }


        // LengthUnit
        $calculated_values['TU3'] = $this->convertUILengthUnit($calculated_values['TU3'],$unit_set->LengthUnit);
        $calculated_values['TU6'] = $this->convertUILengthUnit($calculated_values['TU6'],$unit_set->LengthUnit);
        $calculated_values['TV6'] = $this->convertUILengthUnit($calculated_values['TV6'],$unit_set->LengthUnit);
        $calculated_values['Length'] = $this->convertUILengthUnit($calculated_values['Length'],$unit_set->LengthUnit);
        $calculated_values['Width'] = $this->convertUILengthUnit($calculated_values['Width'],$unit_set->LengthUnit);
        $calculated_values['Height'] = $this->convertUILengthUnit($calculated_values['Height'],$unit_set->LengthUnit);
        $calculated_values['ClearanceForTubeRemoval'] = $this->convertUILengthUnit($calculated_values['ClearanceForTubeRemoval'],$unit_set->LengthUnit);

   
        // FoulingFactorUnit
        $calculated_values['FFCHW1'] = $this->convertUIFoulingFactorUnit($calculated_values['FFCHW1'],$unit_set->FoulingFactorUnit);
        $calculated_values['FFCOW1'] = $this->convertUIFoulingFactorUnit($calculated_values['FFCOW1'],$unit_set->FoulingFactorUnit);

        // PressureDropUnit
        $calculated_values['ChilledFrictionLoss'] = $this->convertUIPressureDropUnit($calculated_values['ChilledFrictionLoss'],$unit_set->PressureDropUnit);
        $calculated_values['CoolingFrictionLoss'] = $this->convertUIPressureDropUnit($calculated_values['CoolingFrictionLoss'],$unit_set->PressureDropUnit);
        if($calculator_code == "D_H2")
        {
            $calculated_values['HotWaterFrictionLoss'] = $this->convertUIPressureDropUnit($calculated_values['HotWaterFrictionLoss'],$unit_set->PressureDropUnit);
        }


        // PressureUnit and WorkPressureUnit
        $calculated_values['m_maxCHWWorkPressure'] = $this->convertUIPressureUnit($calculated_values['m_maxCHWWorkPressure'],$unit_set->WorkPressureUnit);
        $calculated_values['m_maxCOWWorkPressure'] = $this->convertUIPressureUnit($calculated_values['m_maxCOWWorkPressure'],$unit_set->WorkPressureUnit);
        $calculated_values['m_DesignPressure'] = $this->convertUIPressureUnit($calculated_values['m_DesignPressure'],$unit_set->PressureUnit);
        if($calculator_code == "D_S2")
        {
            $calculated_values['PST1'] = $this->convertUIPressureUnit($calculated_values['PST1'],$unit_set->PressureUnit);
            $calculated_values['m_dCondensateDrainPressure'] = $this->convertUIPressureUnit($calculated_values['m_dCondensateDrainPressure'],$unit_set->PressureUnit);
        }


        // NozzleDiameterUnit
        $calculated_values['ChilledConnectionDiameter'] = $this->convertUINozzleDiameterUnit($calculated_values['ChilledConnectionDiameter'],$unit_set->NozzleDiameterUnit); 
        $calculated_values['CoolingConnectionDiameter'] = $this->convertUINozzleDiameterUnit($calculated_values['CoolingConnectionDiameter'],$unit_set->NozzleDiameterUnit);
        $calculated_values['SteamConnectionDiameter'] = $this->convertUINozzleDiameterUnit($calculated_values['SteamConnectionDiameter'],$unit_set->NozzleDiameterUnit); 
        $calculated_values['SteamDrainDiameter'] = $this->convertUINozzleDiameterUnit($calculated_values['SteamDrainDiameter'],$unit_set->NozzleDiameterUnit);

        // SteamConsumptionUnit
        $calculated_values['SteamConsumption'] = $this->convertUISteamConsumptionUnit($calculated_values['SteamConsumption'],$unit_set->SteamConsumptionUnit);

        // WeightUnit
        $calculated_values['OperatingWeight'] = $this->convertUIWeightUnit($calculated_values['OperatingWeight'],$unit_set->WeightUnit);
        $calculated_values['MaxShippingWeight'] = $this->convertUIWeightUnit($calculated_values['MaxShippingWeight'],$unit_set->WeightUnit);
        $calculated_values['FloodedWeight'] = $this->convertUIWeightUnit($calculated_values['FloodedWeight'],$unit_set->WeightUnit);
        $calculated_values['DryWeight'] = $this->convertUIWeightUnit($calculated_values['DryWeight'],$unit_set->WeightUnit);

       

    	// if($unit_set->HeatUnit != 'kCPerHour'){
    		
    	// 	if ($unit_set->HeatUnit == 'KWatt')
     //        {
     //            // $calculated_values['AbsorbentPumpMotorKW'] = $calculated_values['AbsorbentPumpMotorKW'] / 859.845;   //SK 3600/4.1868
     //            // $calculated_values['RefrigerantPumpMotorKW'] = $calculated_values['RefrigerantPumpMotorKW'] / 859.845;
     //            // $calculated_values['PurgePumpMotorKW'] = $calculated_values['PurgePumpMotorKW'] / 859.845;

     //        }
     //        else if($unit_set->HeatUnit == 'MBTUPerHour')
     //        {
     //            // $calculated_values['AbsorbentPumpMotorKW'] = $calculated_values['AbsorbentPumpMotorKW'] * 3.96832 / 1000;
     //            // $calculated_values['RefrigerantPumpMotorKW'] = $calculated_values['RefrigerantPumpMotorKW'] * 3.96832 / 1000;
     //            // $calculated_values['PurgePumpMotorKW'] = $calculated_values['PurgePumpMotorKW'] * 3.96832 / 1000;

     //        }
    	// }

        
    	return $calculated_values;

    }


    public function convertUITemperatureUnit($value,$unit){
        if($unit == 'Fahrenheit'){
            $value = ($value * 9)/5 +32;
        }

        return $value;

    }

    public function convertCalculationTemperatureUnit($value,$unit){
        if($unit == 'Fahrenheit'){
            $value = ($value - 32)*5/9;
        }    

        return $value;

    }

    public function convertUICapacityUnit($value,$unit){
        if($unit == 'kW'){
            $value = ($value * 3.5169);
        }

        return round($value,2);

    }

    public function convertCalculationCapacityUnit($value,$unit){
        if($unit == 'kW'){
            $value = $value / 3.5169;
        }    

        return round($value,2);

    }

    public function convertUIFlowRateUnit($value,$unit){


        if($unit == 'CubicFeetPerHour'){
            $value = ($value * 35.3147);
        }

        if($unit == 'GallonPerMin'){
            $value = ($value * 264.172) / 60;
        }

        return round($value,2);

    }

    public function convertCalculationFlowRateUnit($value,$unit){
        
        if($unit == 'CubicFeetPerHour'){
            $value = ($value / 35.3147);
        }

        if($unit == 'GallonPerMin'){
            $value = ($value * 60) / 264.172;
        }

        return round($value,2);

    }

    public function convertUILengthUnit($value,$unit){
        
        if($unit == 'Inch'){
            $value = ($value * 0.0393700787);
        }

        return round($value,4);

    }

    public function convertCalculationLengthUnit($value,$unit){
        if($unit == 'Inch'){
            $value = $value / 0.0393700787;
        }    

        return round($value,4);

    }

    public function convertUIFoulingFactorUnit($value,$unit){
         
        if($unit == 'SquareMeterKperkW'){
            $value = ($value * 860);
        }

        if($unit == 'SquareFeetHrFperBTU'){
            $value = ($value * 4.886);
        }

        return round($value,10);

    }

    public function convertCalculationFoulingFactorUnit($value,$unit){
        if($unit == 'SquareMeterKperkW'){
            $value = ($value / 860);
        }

        if($unit == 'SquareFeetHrFperBTU'){
            $value = ($value / 4.886);
        }

        return round($value,10);

    }


    public function convertUIPressureUnit($value,$unit){


        if($unit == 'BarGauge'){
            $value = ($value * 0.980665);
        }

        if($unit == 'psig'){
            $value = ($value * 14.223);
        }

        if($unit == 'kiloPascalGauge'){
            $value = ($value * 98.0665);
        }

        if($unit == 'KgPerCmSq'){
            $value = ($value + 1.03323);
        }

        if($unit == 'psi'){
            $value = (($value * 14.223) + 14.695);
        }

        if($unit == 'kiloPascal'){
            $value = (($value * 98.0665) + 101.325);
        }

        if($unit == 'mLC'){
            $value = (($value * 10) + 10.3323);
        }

        if($unit == 'ftLC'){
            $value = (($value * 32.8084) + 33.8985);
        }

        if($unit == 'ftWC'){
            $value = (($value * 32.8084) + 33.8985);
        }

        if($unit == 'mmWC'){
            $value = (($value * 10000) + 10332.3);
        }

        return round($value,2);

    }

    public function convertCalculationPressureUnit($value,$unit){

        if($unit == 'BarGauge'){
            $value = ($value / 0.980665);
        }

        if($unit == 'psig'){
            $value = ($value / 14.223);
        }

        if($unit == 'kiloPascalGauge'){
            $value = ($value / 98.0665);
        }

        if($unit == 'KgPerCmSq'){
            $value = ($value - 1.03323);
        }

        if($unit == 'psi'){
            $value = (($value / 14.223) - 14.695);
        }

        if($unit == 'kiloPascal'){
            $value = (($value / 98.0665) - 101.325);
        }

        if($unit == 'mLC'){
            $value = (($value / 10) - 10.3323);
        }

        if($unit == 'ftLC'){
            $value = (($value / 32.8084) - 33.8985);
        }

        if($unit == 'ftWC'){
            $value = (($value / 32.8084) - 33.8985);
        }

        if($unit == 'mmWC'){
            $value = (($value / 10000) - 10332.3);
        }

        return round($value,2);

    }


    public function convertUIPressureDropUnit($value,$unit){

        if($unit == 'Bar') 
        {

            $value = $value / 10.1972; 
        }
        if($unit == 'ftLC') 
        {
            $value = $value * 3.28084;
        }
        if($unit == 'kiloPascal') 
        {
            $value = $value * 9.80665;
        }
        if ($unit == 'kiloPascalGauge')           //mk
        {
            $value = ($value * 9.80665) - 101.325;
        }
        if ($unit == 'KgPerCmSq')                 //mk
        {
            $value = $value / 10;
        }
        if ($unit == 'KgPerCmSqGauge')            //mk
        {

            $value = ($value / 10) - 1.03323;
        }
        if ($unit == 'psi')                       //mk
        {

            $value = $value * 1.42233;
        }
        if ($unit == 'psig')                      //mk
        {
            $value = ($value * 1.42233) - 14.695;
        }
        if ($unit == 'mmWC')                      //mk
        {
            $value = $value * 1000;
        }
        if ($unit == 'ftWC')                      //mk
        {

            $value = $value * 3.28084;
        }
        if ($unit == 'BarGauge')                  //mk
        {

           $value = ($value / 10.1972) - 1.01325;
        }


        return round($value,2);

    }

    public function convertCalculationPressureDropUnit($value,$unit){
        if($unit == 'Bar') 
        {

            $value = $value * 10.1972; 
        }
        if($unit == 'ftLC') 
        {
            $value = $value / 3.28084;
        }
        if($unit == 'kiloPascal') 
        {
            $value = $value / 9.80665;
        }
        if ($unit == 'kiloPascalGauge')           //mk
        {
            $value = ($value / 9.80665) + 101.325;
        }
        if ($unit == 'KgPerCmSq')                 //mk
        {
            $value = $value * 10;
        }
        if ($unit == 'KgPerCmSqGauge')            //mk
        {

            $value = ($value * 10) + 1.03323;
        }
        if ($unit == 'psi')                       //mk
        {

            $value = $value / 1.42233;
        }
        if ($unit == 'psig')                      //mk
        {
            $value = ($value / 1.42233) + 14.695;
        }
        if ($unit == 'mmWC')                      //mk
        {
            $value = $value / 1000;
        }
        if ($unit == 'ftWC')                      //mk
        {

            $value = $value / 3.28084;
        }
        if ($unit == 'BarGauge')                  //mk
        {

           $value = ($value * 10.1972) + 1.01325;
        }

        return round($value,2);

    }


    public function convertUINozzleDiameterUnit($value,$unit){
        
        if($unit == 'NB'){
            $value = ($value  / 25);
        }

        return round($value,2);

    }

    public function convertCalculationNozzleDiameterUnit($value,$unit){
        if($unit == 'NB'){
            $value = $value * 25;
        }    

        return round($value,2);

    }

    public function convertUISteamConsumptionUnit($value,$unit){
        
        if($unit == 'PoundsPerHour'){
            $value = ($value * 2.20462262);
        }

        return round($value,2);

    }

    public function convertCalculationSteamConsumptionUnit($value,$unit){
        if($unit == 'PoundsPerHour'){
            $value = $value / 2.20462262;
        }    

        return round($value,2);

    }

    public function convertUIHeatUnit($value,$unit){
        
        if($unit == 'KWatt'){
            $value = ($value / 859.845);
        }

        if($unit == 'MBTUPerHour'){
            $value = ($value  * 3.96832) / 1000;
        }

        return round($value,2);

    }

    public function convertCalculationHeatUnit($value,$unit){
        if($unit == 'KWatt'){
            $value = $value * 859.845;
        }

        if($unit == 'MBTUPerHour'){
            $value = ($value  / 3.96832) * 1000;
        }    

        return round($value,2);

    }

    public function convertUIWeightUnit($value,$unit){
        
        if($unit == 'Pound'){
            $value = ($value * 2.20462262 * 1000);
        }

        if($unit == 'Kilogram'){
            $value = ($value  * 1000);
        }

        return round($value,2);

    }

    public function convertCalculationWeightUnit($value,$unit){
        if($unit == 'Pound'){
            $value = ($value / 2.20462262) / 1000;
        }

        if($unit == 'Kilogram'){
            $value = ($value  / 1000);
        }    

        return round($value,2);

    }

    public function convertUIAllWorkPrHWUnit($value,$unit){
        
        if($unit == 'psiGauge'){
            $value = ($value * 14.2233);
        }

        if($unit == 'kiloPascalGauge'){
            $value = ($value  * 98.0665);
        }

        return round($value,2);

    }

    public function convertCalculationAllWorkPrHWUnit($value,$unit){
        if($unit == 'psiGauge'){
            $value = ($value / 14.2233);
        }

        if($unit == 'kiloPascalGauge'){
            $value = ($value  / 98.0665);
        }    

        return round($value,2);

    }


}
