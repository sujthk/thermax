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
        $chiller_values['capacity'] = $this->convertCapacityUnit($chiller_values['capacity'],"TR",$unit_set->CapacityUnit);

        // TemperatureUnit
        $chiller_values['chilled_water_in'] = $this->convertTemperatureUnit($chiller_values['chilled_water_in'],"Centigrade",$unit_set->TemperatureUnit);
        $chiller_values['chilled_water_out'] = $this->convertTemperatureUnit($chiller_values['chilled_water_out'],"Centigrade",$unit_set->TemperatureUnit);
        $chiller_values['min_chilled_water_out'] = $this->convertTemperatureUnit($chiller_values['min_chilled_water_out'],"Centigrade",$unit_set->TemperatureUnit);
        $chiller_values['cooling_water_in'] = $this->convertTemperatureUnit($chiller_values['cooling_water_in'],"Centigrade",$unit_set->TemperatureUnit);
        $chiller_values['cooling_water_in_min_range'] = $this->convertTemperatureUnit($chiller_values['cooling_water_in_min_range'],"Centigrade",$unit_set->TemperatureUnit);
        $chiller_values['cooling_water_in_max_range'] = $this->convertTemperatureUnit($chiller_values['cooling_water_in_max_range'],"Centigrade",$unit_set->TemperatureUnit);
       if($calculator_code == "D_H2")
       {
           $chiller_values['hot_water_in'] = $this->convertTemperatureUnit($chiller_values['hot_water_in'],"Centigrade",$unit_set->TemperatureUnit);
           $chiller_values['hot_water_out'] = $this->convertTemperatureUnit($chiller_values['hot_water_out'],"Centigrade",$unit_set->TemperatureUnit);
           $chiller_values['min_hot_water_out'] = $this->convertTemperatureUnit($chiller_values['min_hot_water_out'],"Centigrade",$unit_set->TemperatureUnit);
           $chiller_values['min_hot_water_in'] = $this->convertTemperatureUnit($chiller_values['min_hot_water_in'],"Centigrade",$unit_set->TemperatureUnit); 
           $chiller_values['max_hot_water_in'] = $this->convertTemperatureUnit($chiller_values['max_hot_water_in'],"Centigrade",$unit_set->TemperatureUnit);
       }
       if($calculator_code == "L5" || $calculator_code == "L1"){
            $chiller_values['hot_water_in'] = $this->convertTemperatureUnit($chiller_values['hot_water_in'],"Centigrade",$unit_set->TemperatureUnit);
            $chiller_values['how_water_temp_min_range'] = $this->convertTemperatureUnit($chiller_values['how_water_temp_min_range'],"Centigrade",$unit_set->TemperatureUnit);
            $chiller_values['how_water_temp_max_range'] = $this->convertTemperatureUnit($chiller_values['how_water_temp_max_range'],"Centigrade",$unit_set->TemperatureUnit);
       }
       


        // FlowRateUnit 
        $chiller_values['cooling_water_flow'] = $this->convertFlowRateUnit($chiller_values['cooling_water_flow'],"CubicMeterPerHr",$unit_set->FlowRateUnit);
        $cooling_water_ranges = array();
        if(!is_array($chiller_values['cooling_water_ranges'])){
            $chiller_values['cooling_water_ranges'] = explode(",", $chiller_values['cooling_water_ranges']);
        }
        for ($i=0; $i < count($chiller_values['cooling_water_ranges']); $i++) { 
            $cooling_water_ranges[] = $this->convertFlowRateUnit($chiller_values['cooling_water_ranges'][$i],"CubicMeterPerHr",$unit_set->FlowRateUnit);
        }
        $chiller_values['cooling_water_ranges'] = $cooling_water_ranges;
        if($calculator_code == "L5" || $calculator_code == "L1"){
            $chiller_values['hot_water_flow'] = $this->convertFlowRateUnit($chiller_values['hot_water_flow'],"CubicMeterPerHr",$unit_set->FlowRateUnit);
        }




        // LengthUnit
        $chiller_values['evaporator_thickness'] = $this->convertLengthUnit($chiller_values['evaporator_thickness'],"Millimeter",$unit_set->LengthUnit);
        $chiller_values['absorber_thickness'] = $this->convertLengthUnit($chiller_values['absorber_thickness'],"Millimeter",$unit_set->LengthUnit);
        $chiller_values['condenser_thickness'] = $this->convertLengthUnit($chiller_values['condenser_thickness'],"Millimeter",$unit_set->LengthUnit);
        $chiller_values['evaporator_thickness_min_range'] = $this->convertLengthUnit($chiller_values['evaporator_thickness_min_range'],"Millimeter",$unit_set->LengthUnit);
        $chiller_values['evaporator_thickness_max_range'] = $this->convertLengthUnit($chiller_values['evaporator_thickness_max_range'],"Millimeter",$unit_set->LengthUnit);
        $chiller_values['absorber_thickness_min_range'] = $this->convertLengthUnit($chiller_values['absorber_thickness_min_range'],"Millimeter",$unit_set->LengthUnit);
        $chiller_values['absorber_thickness_max_range'] = $this->convertLengthUnit($chiller_values['absorber_thickness_max_range'],"Millimeter",$unit_set->LengthUnit);
        $chiller_values['condenser_thickness_min_range'] = $this->convertLengthUnit($chiller_values['condenser_thickness_min_range'],"Millimeter",$unit_set->LengthUnit);
        $chiller_values['condenser_thickness_max_range'] = $this->convertLengthUnit($chiller_values['condenser_thickness_max_range'],"Millimeter",$unit_set->LengthUnit);


        // FoulingFactorUnit
        $chiller_values['fouling_non_chilled'] = $this->convertFoulingFactorUnit($chiller_values['fouling_non_chilled'],"SquareMeterHrCperKcal",$unit_set->FoulingFactorUnit);
        $chiller_values['fouling_non_cooling'] = $this->convertFoulingFactorUnit($chiller_values['fouling_non_cooling'],"SquareMeterHrCperKcal",$unit_set->FoulingFactorUnit);
        $chiller_values['fouling_ari_chilled'] = $this->convertFoulingFactorUnit($chiller_values['fouling_ari_chilled'],"SquareMeterHrCperKcal",$unit_set->FoulingFactorUnit);
        $chiller_values['fouling_ari_cooling'] = $this->convertFoulingFactorUnit($chiller_values['fouling_ari_cooling'],"SquareMeterHrCperKcal",$unit_set->FoulingFactorUnit);
        if(!empty($chiller_values['fouling_chilled_water_value'])){
            $chiller_values['fouling_chilled_water_value'] = $this->convertFoulingFactorUnit($chiller_values['fouling_chilled_water_value'],"SquareMeterHrCperKcal",$unit_set->FoulingFactorUnit);
        }
        if(!empty($chiller_values['fouling_cooling_water_value'])){
            $chiller_values['fouling_cooling_water_value'] = $this->convertFoulingFactorUnit($chiller_values['fouling_cooling_water_value'],"SquareMeterHrCperKcal",$unit_set->FoulingFactorUnit);
        }
        if($calculator_code == "L5" || $calculator_code == "L1"){
            $chiller_values['fouling_non_hot'] = $this->convertFoulingFactorUnit($chiller_values['fouling_non_hot'],"SquareMeterHrCperKcal",$unit_set->FoulingFactorUnit);
        }

        // PressureUnit 
        if($calculator_code == "D_S2" || $calculator_code == "S1"){
            $chiller_values['steam_pressure'] = $this->convertPressureUnit($chiller_values['steam_pressure'],"KgPerCmSqGauge",$unit_set->PressureUnit);
            $chiller_values['steam_pressure_min_range'] = $this->convertPressureUnit($chiller_values['steam_pressure_min_range'],"KgPerCmSqGauge",$unit_set->PressureUnit);
            $chiller_values['steam_pressure_max_range'] = $this->convertPressureUnit($chiller_values['steam_pressure_max_range'],"KgPerCmSqGauge",$unit_set->PressureUnit);

        }
       

        // AllWorkPrHWUnit
        if($calculator_code == "D_H2"){
            $chiller_values['all_work_pr_hw'] = $this->convertAllWorkPrHWUnit($chiller_values['all_work_pr_hw'],"KgPerCmSqGauge",$unit_set->AllWorkPrHWUnit);
        }

        // CalorificValueGasUnit
        if($calculator_code == "D_G2"){
            if($chiller_values['fuel_value_type'] == 'NaturalGas'){
                $chiller_values['calorific_value'] = $this->convertCalorificValueGasUnit($chiller_values['calorific_value'],"kCPerNcubicmetre",$unit_set->CalorificValueGasUnit);
                $chiller_values['std_calorific_value'] = $this->convertCalorificValueGasUnit($chiller_values['std_calorific_value'],"kCPerNcubicmetre",$unit_set->CalorificValueGasUnit);
            }
             
             $chiller_values['normal_ng_calorific_value'] = $this->convertCalorificValueGasUnit($chiller_values['normal_ng_calorific_value'],"kCPerNcubicmetre",$unit_set->CalorificValueGasUnit);
             $chiller_values['gross_ng_calorific_value'] = $this->convertCalorificValueGasUnit($chiller_values['gross_ng_calorific_value'],"kCPerNcubicmetre",$unit_set->CalorificValueGasUnit);
             
        }

        // CalorificValueOilUnit
        if($calculator_code == "D_G2"){
            if($chiller_values['fuel_value_type'] != 'NaturalGas'){
                $chiller_values['calorific_value'] = $this->convertCalorificValueOilUnit($chiller_values['calorific_value'],"kCPerKilogram",$unit_set->CalorificValueOilUnit);
                $chiller_values['std_calorific_value'] = $this->convertCalorificValueOilUnit($chiller_values['std_calorific_value'],"kCPerKilogram",$unit_set->CalorificValueOilUnit);
            }
            $chiller_values['normal_hsd_calorific_value'] = $this->convertCalorificValueOilUnit($chiller_values['normal_hsd_calorific_value'],"kCPerKilogram",$unit_set->CalorificValueOilUnit);
            $chiller_values['gross_hsd_calorific_value'] = $this->convertCalorificValueOilUnit($chiller_values['gross_hsd_calorific_value'],"kCPerKilogram",$unit_set->CalorificValueOilUnit);
            $chiller_values['normal_sko_calorific_value'] = $this->convertCalorificValueOilUnit($chiller_values['normal_sko_calorific_value'],"kCPerKilogram",$unit_set->CalorificValueOilUnit);
            $chiller_values['gross_sko_calorific_value'] = $this->convertCalorificValueOilUnit($chiller_values['gross_sko_calorific_value'],"kCPerKilogram",$unit_set->CalorificValueOilUnit);
        }



    	return $chiller_values;

    }


    public function calculationUnitConversion($chiller_values,$calculator_code){

        $unit_set_id = Auth::user()->unit_set_id;
    	$unit_set = UnitSet::find($unit_set_id);

        // CapacityUnit
        $chiller_values['capacity'] = $this->convertCapacityUnit($chiller_values['capacity'],$unit_set->CapacityUnit,"TR");

        // TemperatureUnit
        $chiller_values['chilled_water_in'] = $this->convertTemperatureUnit($chiller_values['chilled_water_in'],$unit_set->TemperatureUnit,"Centigrade");
        $chiller_values['chilled_water_out'] = $this->convertTemperatureUnit($chiller_values['chilled_water_out'],$unit_set->TemperatureUnit,"Centigrade");
        $chiller_values['min_chilled_water_out'] = $this->convertTemperatureUnit($chiller_values['min_chilled_water_out'],$unit_set->TemperatureUnit,"Centigrade");
        $chiller_values['cooling_water_in'] = $this->convertTemperatureUnit($chiller_values['cooling_water_in'],$unit_set->TemperatureUnit,"Centigrade");
        $chiller_values['cooling_water_in_min_range'] = $this->convertTemperatureUnit($chiller_values['cooling_water_in_min_range'],$unit_set->TemperatureUnit,"Centigrade");
        $chiller_values['cooling_water_in_max_range'] = $this->convertTemperatureUnit($chiller_values['cooling_water_in_max_range'],$unit_set->TemperatureUnit,"Centigrade");
        if($calculator_code == "D_H2")
       {
           $chiller_values['hot_water_in'] = $this->convertTemperatureUnit($chiller_values['hot_water_in'],$unit_set->TemperatureUnit,"Centigrade");
           $chiller_values['hot_water_out'] = $this->convertTemperatureUnit($chiller_values['hot_water_out'],$unit_set->TemperatureUnit,"Centigrade");
           $chiller_values['min_hot_water_out'] = $this->convertTemperatureUnit($chiller_values['min_hot_water_out'],$unit_set->TemperatureUnit,"Centigrade");
           $chiller_values['min_hot_water_in'] = $this->convertTemperatureUnit($chiller_values['min_hot_water_in'],$unit_set->TemperatureUnit,"Centigrade"); 
           $chiller_values['max_hot_water_in'] = $this->convertTemperatureUnit($chiller_values['max_hot_water_in'],$unit_set->TemperatureUnit,"Centigrade");
       }
       if($calculator_code == "L5" || $calculator_code == "L1"){
            $chiller_values['hot_water_in'] = $this->convertTemperatureUnit($chiller_values['hot_water_in'],$unit_set->TemperatureUnit,"Centigrade");
            $chiller_values['how_water_temp_min_range'] = $this->convertTemperatureUnit($chiller_values['how_water_temp_min_range'],$unit_set->TemperatureUnit,"Centigrade");
            $chiller_values['how_water_temp_max_range'] = $this->convertTemperatureUnit($chiller_values['how_water_temp_max_range'],$unit_set->TemperatureUnit,"Centigrade");
       }
  

        // FlowRateUnit
        $chiller_values['cooling_water_flow'] = $this->convertFlowRateUnit($chiller_values['cooling_water_flow'],$unit_set->FlowRateUnit,"CubicMeterPerHr");
        $cooling_water_ranges = array();
        if(!is_array($chiller_values['cooling_water_ranges'])){
            $chiller_values['cooling_water_ranges'] = explode(",", $chiller_values['cooling_water_ranges']);
        }
        for ($i=0; $i < count($chiller_values['cooling_water_ranges']); $i++) { 
            $cooling_water_ranges[] = $this->convertFlowRateUnit($chiller_values['cooling_water_ranges'][$i],$unit_set->FlowRateUnit,"CubicMeterPerHr");
        }
        $chiller_values['cooling_water_ranges'] = $cooling_water_ranges;
        if($calculator_code == "L5" || $calculator_code == "L1"){
            $chiller_values['hot_water_flow'] = $this->convertFlowRateUnit($chiller_values['hot_water_flow'],$unit_set->FlowRateUnit,"CubicMeterPerHr");
        }


        // LengthUnit
        $chiller_values['evaporator_thickness'] = $this->convertLengthUnit($chiller_values['evaporator_thickness'],$unit_set->LengthUnit,"Millimeter");
        $chiller_values['absorber_thickness'] = $this->convertLengthUnit($chiller_values['absorber_thickness'],$unit_set->LengthUnit,"Millimeter");
        $chiller_values['condenser_thickness'] = $this->convertLengthUnit($chiller_values['condenser_thickness'],$unit_set->LengthUnit,"Millimeter");
        $chiller_values['evaporator_thickness_min_range'] = $this->convertLengthUnit($chiller_values['evaporator_thickness_min_range'],$unit_set->LengthUnit,"Millimeter");
        $chiller_values['evaporator_thickness_max_range'] = $this->convertLengthUnit($chiller_values['evaporator_thickness_max_range'],$unit_set->LengthUnit,"Millimeter");
        $chiller_values['absorber_thickness_min_range'] = $this->convertLengthUnit($chiller_values['absorber_thickness_min_range'],$unit_set->LengthUnit,"Millimeter");
        $chiller_values['absorber_thickness_max_range'] = $this->convertLengthUnit($chiller_values['absorber_thickness_max_range'],$unit_set->LengthUnit,"Millimeter");
        $chiller_values['condenser_thickness_min_range'] = $this->convertLengthUnit($chiller_values['condenser_thickness_min_range'],$unit_set->LengthUnit,"Millimeter");
        $chiller_values['condenser_thickness_max_range'] = $this->convertLengthUnit($chiller_values['condenser_thickness_max_range'],$unit_set->LengthUnit,"Millimeter");

        // FoulingFactorUnit
        $chiller_values['fouling_non_chilled'] = $this->convertFoulingFactorUnit($chiller_values['fouling_non_chilled'],$unit_set->FoulingFactorUnit,"SquareMeterHrCperKcal");
        $chiller_values['fouling_non_cooling'] = $this->convertFoulingFactorUnit($chiller_values['fouling_non_cooling'],$unit_set->FoulingFactorUnit,"SquareMeterHrCperKcal");
        $chiller_values['fouling_ari_chilled'] = $this->convertFoulingFactorUnit($chiller_values['fouling_ari_chilled'],$unit_set->FoulingFactorUnit,"SquareMeterHrCperKcal");
        $chiller_values['fouling_ari_cooling'] = $this->convertFoulingFactorUnit($chiller_values['fouling_ari_cooling'],$unit_set->FoulingFactorUnit,"SquareMeterHrCperKcal");
        if(!empty($chiller_values['fouling_chilled_water_value'])){
            $chiller_values['fouling_chilled_water_value'] = $this->convertFoulingFactorUnit($chiller_values['fouling_chilled_water_value'],$unit_set->FoulingFactorUnit,"SquareMeterHrCperKcal");
        }
        if(!empty($chiller_values['fouling_cooling_water_value'])){
            $chiller_values['fouling_cooling_water_value'] = $this->convertFoulingFactorUnit($chiller_values['fouling_cooling_water_value'],$unit_set->FoulingFactorUnit,"SquareMeterHrCperKcal");
        }
        if($calculator_code == "L5" || $calculator_code == "L1"){
            $chiller_values['fouling_non_hot'] = $this->convertFoulingFactorUnit($chiller_values['fouling_non_hot'],$unit_set->FoulingFactorUnit,"SquareMeterHrCperKcal");
            $chiller_values['fouling_hot_water_value'] = $this->convertFoulingFactorUnit($chiller_values['fouling_hot_water_value'],$unit_set->FoulingFactorUnit,"SquareMeterHrCperKcal");
        }


        // PressureUnit
        if($calculator_code == "D_S2" || $calculator_code == "S1"){
            $chiller_values['steam_pressure'] = $this->convertPressureUnit($chiller_values['steam_pressure'],$unit_set->PressureUnit,"KgPerCmSqGauge");
            $chiller_values['steam_pressure_min_range'] = $this->convertPressureUnit($chiller_values['steam_pressure_min_range'],$unit_set->PressureUnit,"KgPerCmSqGauge");
            $chiller_values['steam_pressure_max_range'] = $this->convertPressureUnit($chiller_values['steam_pressure_max_range'],$unit_set->PressureUnit,"KgPerCmSqGauge");
        }
        


        // AllWorkPrHWUnit
        if($calculator_code == "D_H2"){
            $chiller_values['all_work_pr_hw'] = $this->convertAllWorkPrHWUnit($chiller_values['all_work_pr_hw'],$unit_set->AllWorkPrHWUnit,"KgPerCmSqGauge");
        }

        // CalorificValueGasUnit
        if($calculator_code == "D_G2"){
            if($chiller_values['fuel_value_type'] == 'NaturalGas'){
                $chiller_values['calorific_value'] = $this->convertCalorificValueGasUnit($chiller_values['calorific_value'],$unit_set->CalorificValueGasUnit,"kCPerNcubicmetre");
            }      
        }

        // CalorificValueOilUnit
        if($calculator_code == "D_G2"){
            if($chiller_values['fuel_value_type'] != 'NaturalGas'){
                $chiller_values['calorific_value'] = $this->convertCalorificValueOilUnit($chiller_values['calorific_value'],$unit_set->CalorificValueOilUnit,"kCPerKilogram");
             }
        }
        

    	return $chiller_values;

    }


    public function reportUnitConversion($calculated_values,$calculator_code){

        $unit_set_id = Auth::user()->unit_set_id;
    	$unit_set = UnitSet::find($unit_set_id);

        // CapacityUnit
        $calculated_values['TON'] = $this->convertCapacityUnit($calculated_values['TON'],"TR",$unit_set->CapacityUnit);

        // TemperatureUnit
        $calculated_values['TCHW11'] = $this->convertTemperatureUnit($calculated_values['TCHW11'],"Centigrade",$unit_set->TemperatureUnit);
        $calculated_values['TCHW12'] = $this->convertTemperatureUnit($calculated_values['TCHW12'],"Centigrade",$unit_set->TemperatureUnit);
        $calculated_values['TCW11'] = $this->convertTemperatureUnit($calculated_values['TCW11'],"Centigrade",$unit_set->TemperatureUnit);
        $calculated_values['CoolingWaterOutTemperature'] = $this->convertTemperatureUnit($calculated_values['CoolingWaterOutTemperature'],"Centigrade",$unit_set->TemperatureUnit);
        if($calculator_code == "D_S2" || $calculator_code == "S1"){
            $calculated_values['m_dMinCondensateDrainTemperature'] = $this->convertTemperatureUnit($calculated_values['m_dMinCondensateDrainTemperature'],"Centigrade",$unit_set->TemperatureUnit);
            $calculated_values['m_dMaxCondensateDrainTemperature'] = $this->convertTemperatureUnit($calculated_values['m_dMaxCondensateDrainTemperature'],"Centigrade",$unit_set->TemperatureUnit);
        }
        if($calculator_code == "D_H2")
        {
            $calculated_values['hot_water_in'] = $this->convertTemperatureUnit($calculated_values['hot_water_in'],"Centigrade",$unit_set->TemperatureUnit);
            $calculated_values['hot_water_out'] = $this->convertTemperatureUnit($calculated_values['hot_water_out'],"Centigrade",$unit_set->TemperatureUnit);
        }
        if($calculator_code == "L5"){
            $calculated_values['THW1'] = $this->convertTemperatureUnit($calculated_values['THW1'],"Centigrade",$unit_set->TemperatureUnit);
            $calculated_values['THW4'] = $this->convertTemperatureUnit($calculated_values['THW4'],"Centigrade",$unit_set->TemperatureUnit);
        }
        if($calculator_code == "L1"){
            $calculated_values['THW1'] = $this->convertTemperatureUnit($calculated_values['THW1'],"Centigrade",$unit_set->TemperatureUnit);
            $calculated_values['THW2'] = $this->convertTemperatureUnit($calculated_values['THW2'],"Centigrade",$unit_set->TemperatureUnit);
        }

        // FlowRateUnit
        $calculated_values['GCW'] = $this->convertFlowRateUnit($calculated_values['GCW'],"CubicMeterPerHr",$unit_set->FlowRateUnit);
        $calculated_values['ChilledWaterFlow'] = $this->convertFlowRateUnit($calculated_values['ChilledWaterFlow'],"CubicMeterPerHr",$unit_set->FlowRateUnit);
        $calculated_values['BypassFlow'] = $this->convertFlowRateUnit($calculated_values['BypassFlow'],"CubicMeterPerHr",$unit_set->FlowRateUnit);
        if($calculator_code == "D_H2")
        {
            $calculated_values['HotWaterFlow']= $this->convertFlowRateUnit($calculated_values['HotWaterFlow'],"CubicMeterPerHr",$unit_set->FlowRateUnit);
        }
        if($calculator_code == "L5" || $calculator_code == "L1"){
            $calculated_values['GHOT']= $this->convertFlowRateUnit($calculated_values['GHOT'],"CubicMeterPerHr",$unit_set->FlowRateUnit);
        }


        // LengthUnit
        $calculated_values['TU3'] = $this->convertLengthUnit($calculated_values['TU3'],"Millimeter",$unit_set->LengthUnit);
        $calculated_values['TU6'] = $this->convertLengthUnit($calculated_values['TU6'],"Millimeter",$unit_set->LengthUnit);
        $calculated_values['TV6'] = $this->convertLengthUnit($calculated_values['TV6'],"Millimeter",$unit_set->LengthUnit);
        $calculated_values['Length'] = $this->convertLengthUnit($calculated_values['Length'],"Millimeter",$unit_set->LengthUnit);
        $calculated_values['Width'] = $this->convertLengthUnit($calculated_values['Width'],"Millimeter",$unit_set->LengthUnit);
        $calculated_values['Height'] = $this->convertLengthUnit($calculated_values['Height'],"Millimeter",$unit_set->LengthUnit);
        $calculated_values['ClearanceForTubeRemoval'] = $this->convertLengthUnit($calculated_values['ClearanceForTubeRemoval'],"Millimeter",$unit_set->LengthUnit);

   
        // FoulingFactorUnit
        $calculated_values['FFCHW1'] = $this->convertFoulingFactorUnit($calculated_values['FFCHW1'],"SquareMeterHrCperKcal",$unit_set->FoulingFactorUnit);
        $calculated_values['FFCOW1'] = $this->convertFoulingFactorUnit($calculated_values['FFCOW1'],"SquareMeterHrCperKcal",$unit_set->FoulingFactorUnit);
        if($calculator_code == "L5" || $calculator_code == "L1"){
            $calculated_values['FFHOW1'] = $this->convertFoulingFactorUnit($calculated_values['FFHOW1'],"SquareMeterHrCperKcal",$unit_set->FoulingFactorUnit);
        }



        // PressureDropUnit
        $calculated_values['ChilledFrictionLoss'] = $this->convertPressureUnit($calculated_values['ChilledFrictionLoss'],"mLC",$unit_set->PressureDropUnit);
        $calculated_values['CoolingFrictionLoss'] = $this->convertPressureUnit($calculated_values['CoolingFrictionLoss'],"mLC",$unit_set->PressureDropUnit);
        if($calculator_code == "D_H2")
        {
            $calculated_values['HotWaterFrictionLoss'] = $this->convertPressureUnit($calculated_values['HotWaterFrictionLoss'],"mLC",$unit_set->PressureDropUnit);
        }


        //WorkPressureUnit
        $calculated_values['m_maxCHWWorkPressure'] = $this->convertPressureUnit($calculated_values['m_maxCHWWorkPressure'],"KgPerCmSqGauge",$unit_set->WorkPressureUnit);
        $calculated_values['m_maxCOWWorkPressure'] = $this->convertPressureUnit($calculated_values['m_maxCOWWorkPressure'],"KgPerCmSqGauge",$unit_set->WorkPressureUnit);
        if($calculator_code == "L5"){
            $calculated_values['m_maxHWWorkPressure'] = $this->convertPressureUnit($calculated_values['m_maxHWWorkPressure'],"KgPerCmSqGauge",$unit_set->WorkPressureUnit);
        }        


        // PressureUnit
        if($calculator_code == "D_S2" || $calculator_code == "S1")
        {
            $calculated_values['PST1'] = $this->convertPressureUnit($calculated_values['PST1'],"KgPerCmSqGauge",$unit_set->PressureUnit);
            $calculated_values['m_dCondensateDrainPressure'] = $this->convertPressureUnit($calculated_values['m_dCondensateDrainPressure'],"KgPerCmSqGauge",$unit_set->PressureUnit);
            $calculated_values['m_DesignPressure'] = $this->convertPressureUnit($calculated_values['m_DesignPressure'],"KgPerCmSqGauge",$unit_set->PressureUnit);
        }


        // NozzleDiameterUnit
        $calculated_values['ChilledConnectionDiameter'] = $this->convertNozzleDiameterUnit($calculated_values['ChilledConnectionDiameter'],"DN",$unit_set->NozzleDiameterUnit); 
        $calculated_values['CoolingConnectionDiameter'] = $this->convertNozzleDiameterUnit($calculated_values['CoolingConnectionDiameter'],"DN",$unit_set->NozzleDiameterUnit); 
        if($calculator_code == "D_S2"){
            $calculated_values['SteamConnectionDiameter'] = $this->convertNozzleDiameterUnit($calculated_values['SteamConnectionDiameter'],"DN",$unit_set->NozzleDiameterUnit);
            $calculated_values['SteamDrainDiameter'] = $this->convertNozzleDiameterUnit($calculated_values['SteamDrainDiameter'],"DN",$unit_set->NozzleDiameterUnit);
        }
        if($calculator_code == "L5"){
            $calculated_values['GENNB'] = $this->convertNozzleDiameterUnit($calculated_values['GENNB'],"DN",$unit_set->NozzleDiameterUnit);
        }

        // SteamConsumptionUnit
        if($calculator_code == "D_S2" || $calculator_code == "S1"){
            $calculated_values['SteamConsumption'] = $this->convertSteamConsumptionUnit($calculated_values['SteamConsumption'],"KilogramsPerHr",$unit_set->SteamConsumptionUnit);
        }
        

        // WeightUnit
        $calculated_values['OperatingWeight'] = $this->convertWeightUnit($calculated_values['OperatingWeight'],"Ton",$unit_set->WeightUnit);
        $calculated_values['MaxShippingWeight'] = $this->convertWeightUnit($calculated_values['MaxShippingWeight'],"Ton",$unit_set->WeightUnit);
        $calculated_values['FloodedWeight'] = $this->convertWeightUnit($calculated_values['FloodedWeight'],"Ton",$unit_set->WeightUnit);
        $calculated_values['DryWeight'] = $this->convertWeightUnit($calculated_values['DryWeight'],"Ton",$unit_set->WeightUnit);

        

        // Heat Unit
        if($calculator_code == "D_G2"){
            $calculated_values['HeatRejected'] = $this->convertHeatUnit($calculated_values['HeatRejected'],"kCPerHour",$unit_set->HeatUnit);
        }

        // CalorificValueGasUnit
        if($calculator_code == "D_G2"){
            if($calculated_values['GCV'] == 'NaturalGas'){
                $calculated_values['RCV1'] = $this->convertCalorificValueGasUnit($calculated_values['RCV1'],"kCPerNcubicmetre",$unit_set->CalorificValueGasUnit);
            }      
        }

        // CalorificValueOilUnit
        if($calculator_code == "D_G2"){
            if($calculated_values['GCV'] != 'NaturalGas'){
                $calculated_values['RCV1'] = $this->convertCalorificValueOilUnit($calculated_values['RCV1'],"kCPerKilogram",$unit_set->CalorificValueOilUnit);
             }
        }

    	return $calculated_values;

    }


    public function convertTemperatureUnit($input_value, $from_unit, $to_unit) 
    {
  
        $input_value = floatval($input_value);
        if($from_unit != $to_unit)  
        {
            switch ($from_unit) 
            {
                case "Centigrade":
                    $input_value = (9 * $input_value)/5 + 32;
                    break;

                case "Fahrenheit":
                    $input_value = ($input_value - 32) * 5/9;
                    break;
            }
        }
        return round($input_value,2);
    }


    public function convertLengthUnit($input_value,  $from_unit, $to_unit) 
    {
        
        $input_value = floatval($input_value);
        if($from_unit != $to_unit)  
        {
            switch ($from_unit) 
            {
                case "Millimeter":
                    $input_value = $input_value * 0.0393700787;
                    break;

                case "Inch":
                    $input_value = $input_value / 0.0393700787;
                    break;
            }
        }
        return round($input_value,2);
    }


    public function convertAreaUnit($input_value,  $from_unit, $to_unit) 
    {
        
        $input_value = floatval($input_value);
        if($from_unit != $to_unit)  
        {
            switch ($from_unit) 
            {
                case "SquareMeter":
                    $input_value = $input_value * 10.763915051182416;
                    break;

                case "SquareFeet":
                    $input_value = $input_value / 10.763915051182416;
                    break;
            }
        }

        return round($input_value,2);
    }


    public function convertVolumeUnit($input_value,  $from_unit, $to_unit) 
    {
        
        $input_value = floatval($input_value);
        if($from_unit != $to_unit)  
        {
            switch ($from_unit) 
            {
                case "CubicMeter":
                    $input_value = $input_value * 35.3147;
                    break;

                case "CubicFeet":
                    $input_value = $input_value / 35.3147;
                    break;
            }
        }

        return round($input_value,2);
    }


    public function convertWeightUnit($input_value, $from_unit, $to_unit) 
    {
        
        $input_value = floatval($input_value);
        if($from_unit != $to_unit)  
        {
            switch ($from_unit) 
            {
                case "Kilogram":
                    if($to_unit == "Pound") 
                    {
                        $input_value = $input_value * 2.20462262;
                    }
                    else if ($to_unit == "Ton") 
                    {
                        $input_value = $input_value / 1000;
                    }
                    break;

                case "Ton":
                    if($to_unit == "Pound") 
                    {
                        $input_value = $input_value * 2.20462262 * 1000;
                    }
                    else if ($to_unit == "Kilogram") 
                    {
                        $input_value = $input_value * 1000;
                    }
                    break;

                case "Pound":
                    if ($to_unit == "Kilogram") 
                    {
                        $input_value = $input_value / 2.20462262;
                    }
                    else if ($to_unit == "Ton") 
                    {
                        $input_value = ($input_value / 2.20462262) / 1000;
                    }

                    break;
            }
        }

        return round($input_value,2);
    }


    public function convertPressureUnit($input_value, $from_unit, $to_unit) 
    {
        
        $input_value = floatval($input_value);
        if($from_unit != $to_unit)  
        {
            switch ($from_unit) 
            {
                case "KgPerCmSq":
                    if($to_unit == "Bar") 
                    {
                        $input_value = $input_value * 0.980665; //sk 26/3
                    }
                    else if ($to_unit == "BarGauge")
                    {
                        $input_value = ($input_value * 0.980665) - 1.01325;        //MK 
                    }
                    else if ($to_unit == "KgPerCmSqGauge")
                    {
                        $input_value = $input_value - 1.03323;                   //MK 
                    }
                    else if($to_unit == "psi") 
                    {
                        $input_value = $input_value * 14.223;
                    }
                    else if ($to_unit == "psig")
                    {
                        $input_value = ($input_value * 14.223) - 14.695;         //MK 
                    }
                    else if($to_unit == "kiloPascal") 
                    {
                        $input_value = $input_value * 98.0665;
                    }
                    else if ($to_unit == "kiloPascalGauge")
                    {
                        $input_value = ($input_value * 98.0665) - 101.325;        //MK 
                    }
                    break;

                case "KgPerCmSqGauge":
                    if($to_unit == "BarGauge") 
                    {
                        $input_value = $input_value * 0.980665;
                    }
                    else if($to_unit == "psig") 
                    {
                        $input_value = $input_value * 14.223;
                    }
                    else if ($to_unit == "kiloPascalGauge")    //sk 9/4/08
                    {
                        $input_value = $input_value * 98.0665;
                    }
                    else if ($to_unit == "KgPerCmSq")               //mk
                    {
                        $input_value = $input_value + 1.03323;
                    }
                    else if ($to_unit == "psi")                     //mk
                    {
                        $input_value = ($input_value * 14.223) + 14.695;
                    }
                    else if ($to_unit == "kiloPascal")              //mk
                    {
                        $input_value = ($input_value * 98.0665) + 101.325;
                    }
                    else if ($to_unit == "mLC")                     //mk
                    {
                        $input_value = ($input_value * 10) + 10.3323;
                    }
                    else if ($to_unit == "ftLC")                    //mk
                    {
                        $input_value = ($input_value * 32.8084) + 33.8985;
                    }
                    else if ($to_unit == "ftWC")                    //mk
                    {
                        $input_value = ($input_value * 32.8084) + 33.8985;
                    }
                    else if ($to_unit == "mmWC")                    //mk
                    {
                        $input_value = ($input_value * 10000) + 10332.3;
                    }
                    break;
                
                case "Bar":
                    if ($to_unit == "KgPerCmSq")
                    {
                        $input_value = $input_value * 1.01972;
                    }
                    else if ($to_unit == "kiloPascal")
                    {
                        $input_value = $input_value * 100;
                    }
                    else if ($to_unit == "psi")
                    {
                        $input_value = $input_value * 14.5038;
                    }
                    else if ($to_unit == "mLC")
                    {
                        $input_value = $input_value * 10.1972;
                    }
                    else if ($to_unit == "ftLC")
                    {
                        $input_value = $input_value * 33.4553;
                    }
                    else if ($to_unit == "mmWC")
                    {
                        $input_value = $input_value * (1000 * 10.1972);
                    }
                    else if ($to_unit == "ftWC")
                    {
                        $input_value = $input_value * 33.4553;
                    }
                    break;

                case "BarGauge":
                    if ($to_unit == "KgPerCmSqGauge")
                    {
                        $input_value = $input_value * 1.01972;
                    }
                    else if ($to_unit == "kiloPascalGauge")
                    {
                        $input_value = $input_value * 100;
                    }
                    else if ($to_unit == "psig")
                    {
                        $input_value = $input_value * 14.5038;
                    }
                    break;

                case "kiloPascal":
                    if ($to_unit == "KgPerCmSq")
                    {
                        $input_value = $input_value * 0.0101972;
                    }
                    else if ($to_unit == "Bar")
                    {
                        $input_value = $input_value / 100;
                    }
                    else if ($to_unit == "psi")
                    {
                        $input_value = $input_value * 0.145038;
                    }
                    else if ($to_unit == "mLC")
                    {
                        $input_value = $input_value * 0.101972;
                    }
                    else if ($to_unit == "ftLC")
                    {
                        $input_value = $input_value * 0.334553;
                    }
                    else if ($to_unit == "mmWC")
                    {
                        $input_value = $input_value * 101.972;
                    }
                    else if ($to_unit == "ftWC")
                    {
                        $input_value = $input_value * 0.334553;
                    }
                    break;

                case "kiloPascalGauge":
                    if ($to_unit == "KgPerCmSqGauge")
                    {
                        $input_value = $input_value * 0.0101972;
                    }
                    else if ($to_unit == "BarGauge")
                    {
                        $input_value = $input_value / 100;
                    }
                    else if ($to_unit == "psig")
                    {
                        $input_value = $input_value * 0.145038;
                    }
                    break;

                case "psi":
                    if ($to_unit == "KgPerCmSq")
                    {
                        $input_value = $input_value / 14.223;
                    }
                    else if ($to_unit == "Bar")
                    {
                        $input_value = $input_value / 14.5038;
                    }
                    else if ($to_unit == "kiloPascal")
                    {
                        $input_value = $input_value / 0.145038;
                    }
                    break;

                case "psig":
                    if ($to_unit == "KgPerCmSqGauge")
                    {
                        $input_value = $input_value / 14.223;
                    }
                    else if ($to_unit == "BarGauge")
                    {
                        $input_value = $input_value / 14.5038;
                    }
                    else if ($to_unit == "kiloPascalGauge")
                    {
                        $input_value = $input_value / 0.145038;
                    }
                    break;

                case "mLC":
                    if($to_unit == "Bar") 
                    {
                        $input_value = $input_value / 10.1972; //sk 26/3
                    }
                    else if($to_unit == "ftLC") 
                    {
                        $input_value = $input_value * 3.28084;
                    }
                    else if($to_unit == "kiloPascal") 
                    {
                        $input_value = $input_value * 9.80665;
                    }
                    else if ($to_unit == "kiloPascalGauge")           //mk
                    {
                        $input_value = ($input_value * 9.80665) - 101.325;
                    }
                    else if ($to_unit == "KgPerCmSq")                 //mk
                    {
                        $input_value = $input_value / 10;
                    }
                    else if ($to_unit == "KgPerCmSqGauge")            //mk
                    {
                        $input_value = ($input_value / 10) - 1.03323;
                    }
                    else if ($to_unit == "psi")                       //mk
                    {
                        $input_value = $input_value * 1.42233;
                    }
                    else if ($to_unit == "psig")                      //mk
                    {
                        $input_value = ($input_value * 1.42233) - 14.695;
                    }
                    else if ($to_unit == "mmWC")                      //mk
                    {
                        $input_value = $input_value * 1000;
                    }
                    else if ($to_unit == "ftWC")                      //mk
                    {
                        $input_value = $input_value * 3.28084;
                    }
                    else if ($to_unit == "BarGauge")                  //mk
                    {
                        $input_value = ($input_value / 10.1972) - 1.01325;
                    }
                    break;

                case "ftLC":
                    if ($to_unit == "Bar")
                    {
                        $input_value = $input_value / 33.4553;
                    }
                    else if ($to_unit == "mLC")
                    {
                        $input_value = $input_value / 3.28084;
                    }
                    else if ($to_unit == "kiloPascal")
                    {
                        $input_value = $input_value * 2.98907;
                    }
                    break;

                case "mmWC":
                    if($to_unit == "Bar") 
                    {
                        $input_value = $input_value / (1000*10.1972);//sk 26/3
                    }
                    else if($to_unit == "ftWC") 
                    {
                        $input_value = $input_value * 3.28084/1000;
                    }
                    else if($to_unit == "kiloPascal") 
                    {
                        $input_value = $input_value * 9.80665/1000;
                    }
                    break;

                case "ftWC":
                    if ($to_unit == "mmWC")
                    {
                        $input_value = $input_value * 304.8;
                    }
                    else if ($to_unit == "Bar")
                    {
                        $input_value = $input_value * 0.0298907;
                    }
                    else if ($to_unit == "kiloPascal")
                    {
                        $input_value = $input_value * 2.98907;
                    }
                    break;                      
            }
        }
        return round($input_value,2);
    }


    public function convertCapacityUnit($input_value, $from_unit, $to_unit) 
    {
        
        $input_value = floatval($input_value);
        if($from_unit != $to_unit)  
        {
            switch ($from_unit) 
            {
                case "kW":
                    $input_value = $input_value / 3.5169;    //SK 3024/859.845
                    break;

                case "TR":
                    $input_value = $input_value * 3.5169;
                    break;
            }
        }

        return round($input_value,2);
    }


    public function convertFlowRateUnit($input_value,  $from_unit, $to_unit) 
    {
        
        $input_value = floatval($input_value);
        if($from_unit != $to_unit)  
        {
            switch ($from_unit) 
            {
                case "CubicMeterPerHr":
                    if($to_unit == "CubicFeetPerHour")
                    {
                        //$input_value = $input_value * 0.5885; sk 26/3
                        $input_value = $input_value * 35.3147;
                    }
                    else if($to_unit == "GallonPerMin")
                    {
                        $input_value = $input_value * 264.172 / 60;
                    }
                    break;
                    
                case "CubicFeetPerHour":
                    if($to_unit == "CubicMeterPerHr")
                    {
                        //$input_value = $input_value / 0.5885;
                        $input_value = $input_value / 35.3147;
                    }
                    else if($to_unit == "GallonPerMin")
                    {
                        $input_value = $input_value * 7.48052/60;
                    }
                    break;

                 case "GallonPerMin":
                     if ($to_unit == "CubicMeterPerHr")
                     {
                         $input_value = $input_value * 60 / 264.172;
                     }
                     else if ($to_unit == "CubicFeetPerHour")
                     {
                         $input_value = $input_value * 60 / 7.48052;
                     }
                     break;
            }
        }

        return round($input_value,2);
    }


    public function convertFoulingFactorUnit($input_value, $from_unit, $to_unit) 
    {
        
        $input_value = floatval($input_value);
        if($from_unit != $to_unit)  
        {
            switch ($from_unit) 
            {
                case "SquareMeterHrCperKcal":
                    if($to_unit == "SquareFeetHrFperBTU") 
                    {
                        $input_value = $input_value * 4.886;
                    }
                    else if ($to_unit == "SquareMeterKperkW") 
                    {
                        $input_value = $input_value * 860;
                    }
                    break;

                case "SquareFeetHrFperBTU":
                    if($to_unit == "SquareMeterHrCperKcal") 
                    {
                        $input_value = $input_value / 4.886;
                    }
                    else if ($to_unit == "SquareMeterKperkW") 
                    {
                        // TODO provide SquareFeetHrFperBTU $to_unit SquareMeterKperkW conversion for fouling factor
                        //sk 26/3
                        $input_value = ($input_value * 860) / 4.886;
                    }
                    break;

                case "SquareMeterKperkW":
                    if($to_unit == "SquareMeterHrCperKcal") 
                    {
                        $input_value = $input_value / 860;
                    }
                    else if ($to_unit == "SquareFeetHrFperBTU") 
                    {
        
                        $input_value = ($input_value * 4.886) / 860;
                    }
                    break;
            }
        }

        return round($input_value,10);
    }

    public function convertSteamConsumptionUnit($input_value,  $from_unit, $to_unit) 
    {
        
        $input_value = floatval($input_value);
        if($from_unit != $to_unit)  
        {
            switch ($from_unit) 
            {
                case "KilogramsPerHr":
                    $input_value = $input_value * 2.20462262;
                    break;

                case "PoundsPerHour":
                    $input_value = $input_value / 2.20462262;
                    break;
            }
        }

        return round($input_value,2);
    }

    public function convertExhaustGasFlowUnit($input_value, $from_unit, $to_unit) 
    {
        
        $input_value = floatval($input_value);
        if($from_unit != $to_unit)  
        {
            switch ($from_unit) 
            {
                case "KilogramsPerHr":
                    $input_value = $input_value * 2.20462262;
                    break;

                case "PoundsPerHour":
                    $input_value = $input_value / 2.20462262;
                    break;
            }
        }

        return round($input_value,2);
    }

    public function convertFuelConsumptionOilUnit($input_value, $from_unit, $to_unit) 
    {
        
        $input_value = floatval($input_value);
        if($from_unit != $to_unit)  
        {
            switch ($from_unit) 
            {
                case "KilogramsPerHr":
                    $input_value = $input_value * 2.20462262;
                    break;

                case "PoundsPerHour":
                    $input_value = $input_value / 2.20462262;
                    break;
            }
        }

        return round($input_value,2);
    }

    public function convertFuelConsumptionGasUnit($input_value,  $from_unit, $to_unit) 
    {
        
        $input_value = floatval($input_value);
        if($from_unit != $to_unit)  
        {
            switch ($from_unit) 
            {
                case "NCubicMeterPerHr":
                    $input_value = $input_value * 35.3147;
                    break;

                case "NCubicFeetPerHour":
                    $input_value = $input_value / 35.3147;
                    break;
            }
        }

        return round($input_value,2);
    }

    public function convertHeatUnit($input_value,  $from_unit, $to_unit) 
    {
        
        $input_value = floatval($input_value);
        if($from_unit != $to_unit)  
        {
            switch ($from_unit) 
            {
                case "kCPerHour":
                    if ($to_unit == "KWatt")
                    {
                        $input_value = $input_value / 859.845;   //SK 3600/4.1868
                    }
                    else if($to_unit == "MBTUPerHour")
                    {
                        $input_value = $input_value * 3.96832 / 1000;
                    }
                    break;

                case "KWatt":
                    if ($to_unit == "kCPerHour")
                    {
                        $input_value = $input_value * 859.845;
                    }
                    else if ($to_unit == "MBTUPerHour")
                    {
                        $input_value = $input_value * 859.845 * 3.96832 / 1000;
                    }
                    break;

                case "MBTUPerHour":
                    if($to_unit == "kCPerHour")
                    {
                         $input_value = $input_value * 1000 / 3.96832;
                    }
                    else if($to_unit == "KWatt")
                    {
                         $input_value = $input_value * 1000 /3412.14;
                    }
                    break;
                    
            }
        }

        return round($input_value,2);
    }

    public function convertCalorificValueGasUnit($input_value,  $from_unit, $to_unit) 
    {
        
        $input_value = floatval($input_value);
        if($from_unit != $to_unit)  
        {
            switch ($from_unit) 
            {
                case "kCPerNcubicmetre":
                    if($to_unit == "BTUPerNcubicfeet") 
                    {
                        $input_value = $input_value * 0.11237;
                    }
                    else if ($to_unit == "kJPerNcubicmetre") 
                    {
                        $input_value = $input_value * 4.1868;
                    }
                    break;

                case "BTUPerNcubicfeet":
                    if($to_unit == "kCPerNcubicmetre") 
                    {
                        $input_value = $input_value / 0.11237;
                    }
                    else if ($to_unit == "kJPerNcubicmetre") 
                    {
                        $input_value = ($input_value / 0.11237) * 4.1868;
                    }
                    break;

                case "kJPerNcubicmetre":
                    if($to_unit == "kCPerNcubicmetre") 
                    {
                        $input_value = $input_value / 4.1868;
                    }
                    else if ($to_unit == "BTUPerNcubicfeet") 
                    {
                        $input_value = ($input_value / 4.1868) * 0.11237;
                    }
                    break;
            }
        }

        return round($input_value,2);
    }

    public function convertCalorificValueOilUnit($input_value,  $from_unit, $to_unit) 
    {
        
        $input_value = floatval($input_value);
        if($from_unit != $to_unit)  
        {
            switch ($from_unit) 
            {
                case "kCPerKilogram":
                    if($to_unit == "BTUPerPound") 
                    {
                        $input_value = $input_value * 1.8;
                    }
                    else if ($to_unit == "kJPerKilogram") 
                    {
                        $input_value = $input_value * 4.187;
                    }
                    break;

                case "BTUPerPound":
                    if($to_unit == "kCPerKilogram") 
                    {
                        $input_value = $input_value / 1.8;
                    }
                    else if ($to_unit == "kJPerKilogram") 
                    {
                        $input_value = ($input_value / 1.8) * 4.1868;
                    }
                    break;

                case "kJPerKilogram":
                    if($to_unit == "kCPerKilogram") 
                    {
                        $input_value = $input_value / 4.1868;
                    }
                    else if ($to_unit == "BTUPerPound") 
                    {
                        $input_value = ($input_value / 4.1868) * 1.8;
                    }
                    break;
            }
        }

        return round($input_value,2);
    }

    public function convertNozzleDiameterUnit($input_value,  $from_unit, $to_unit) 
    {
        
        $input_value = floatval($input_value);
        if($from_unit != $to_unit)  
        {
            switch ($from_unit) 
            {
                case "DN":
                    $input_value = $input_value / 25;
                    break;

                case "NB":
                    $input_value = $input_value * 25;
                    break;
            }
        }

        return round($input_value,2);
    }
    public function convertAllWorkPrHWUnit($input_value,  $from_unit, $to_unit)
    {
        
        if ($from_unit != $to_unit)
        {
            switch ($from_unit) 
            {
                case "KgPerCmSqGauge":
                    if($to_unit == "psiGauge") 
                    {
                        $input_value = $input_value * 14.2233; 
                    }
                    else if($to_unit == "kiloPascalGauge") 
                    {
                        $input_value = $input_value * 98.0665;
                    }
                    break;

                case "psiGauge":
                    if($to_unit == "kiloPascalGauge") 
                    {
                        $input_value = $input_value * 6.89476; 
                    }
                    else if ($to_unit == "KgPerCmSqGauge")
                    {
                        $input_value = $input_value / 14.2233;
                    }
                    break;

                case "kiloPascalGauge":
                    if ($to_unit == "psiGauge")
                    {
                        $input_value = $input_value / 6.89746; 
                    }
                    else if ($to_unit == "KgPerCmSqGauge")
                    {
                        $input_value = $input_value * 1.01972/100;
                    }
                    break;
            }
        }

        return round($input_value,2);
    }

    public function convertHeatCapacityUnit($input_value,  $from_unit, $to_unit)
    {
        
        if ($from_unit != $to_unit)
        {
            switch ($from_unit)
            {
                case "kcalperkgdegC":
                    if ($to_unit == "BTUperpounddegF")
                    {
                        $input_value = $input_value * 1;
                    }
                    else if ($to_unit == "kJouleperkgdegC")
                    {
                        $input_value = $input_value * 4.1868;
                    }
                    break;

                case "BTUperpounddegF":
                    if ($to_unit == "kcalperkgdegC")
                    {
                        $input_value = $input_value * 1;
                    }
                    else if ($to_unit == "kJouleperkgdegC")
                    {
                        $input_value = $input_value * 4.1868;
                    }
                    break;

                case "kJouleperkgdegC":
                    if ($to_unit == "BTUperpounddegF")
                    {
                        $input_value = $input_value / 4.1868;
                    }
                    else if ($to_unit == "kcalperkgdegC")
                    {
                        $input_value = $input_value / 4.1868;
                    }

                    break;
            }
        }

        return round($input_value,2);
    }


}
