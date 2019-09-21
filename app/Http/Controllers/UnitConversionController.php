<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\UnitSet;
use Log;

class UnitConversionController extends Controller
{
    public function formUnitConversion($chiller_values){

        $unit_set_id = Auth::user()->unit_set_id;
    	$unit_set = UnitSet::find($unit_set_id);


    	if($unit_set->CapacityUnit != 'TR'){
    		$chiller_values['capacity'] = $chiller_values['capacity'] * 3.5169; 
    	}

    	if($unit_set->TemperatureUnit != 'Centigrade'){
    		$chiller_values['chilled_water_in'] = ($chiller_values['chilled_water_in'] * 9)/5 +32; 
    		$chiller_values['chilled_water_out'] = ($chiller_values['chilled_water_out'] * 9)/5 +32; 
    		$chiller_values['min_chilled_water_out'] = ($chiller_values['min_chilled_water_out'] * 9)/5 +32; 
    		$chiller_values['cooling_water_in'] = ($chiller_values['cooling_water_in'] * 9)/5 +32; 
    		$chiller_values['cooling_water_in_min_range'] = ($chiller_values['cooling_water_in_min_range'] * 9)/5 +32; 
    		$chiller_values['cooling_water_in_max_range'] = ($chiller_values['cooling_water_in_max_range'] * 9)/5 +32; 
    		
    	}

    	if($unit_set->FlowRateUnit != 'CubicMeterPerHr'){

    		$cooling_water_ranges = array();
    		if(!is_array($chiller_values['cooling_water_ranges'])){
    			$chiller_values['cooling_water_ranges'] = explode(",", $chiller_values['cooling_water_ranges']);
    		}

    		if($unit_set->FlowRateUnit == 'CubicFeetPerHour'){
    			$chiller_values['cooling_water_flow'] = round(($chiller_values['cooling_water_flow'] * 35.3147),2);
    			for ($i=0; $i < count($chiller_values['cooling_water_ranges']); $i++) { 
    				$cooling_water_ranges[] = round(($chiller_values['cooling_water_ranges'][$i] * 35.3147),2);
    			}
    			$chiller_values['cooling_water_ranges'] = $cooling_water_ranges;
    		}

    		if($unit_set->FlowRateUnit == 'GallonPerMin'){
    			$chiller_values['cooling_water_flow'] = round(($chiller_values['cooling_water_flow'] * 264.172 / 60),2);
    			for ($i=0; $i < count($chiller_values['cooling_water_ranges']); $i++) { 
    				$cooling_water_ranges[] = round(($chiller_values['cooling_water_ranges'][$i] * 264.172 / 60),2);
    			}
    			$chiller_values['cooling_water_ranges'] = $cooling_water_ranges;
    		}

    	}


    	if($unit_set->LengthUnit != 'Millimeter'){
    		$chiller_values['evaporator_thickness'] = round(($chiller_values['evaporator_thickness'] * 0.0393700787),4); 
    		$chiller_values['absorber_thickness'] = round(($chiller_values['absorber_thickness'] * 0.0393700787),4); 
    		$chiller_values['condenser_thickness'] = round(($chiller_values['condenser_thickness'] * 0.0393700787),4);  
    		$chiller_values['evaporator_thickness_min_range'] = round(($chiller_values['evaporator_thickness_min_range'] * 0.0393700787),4); 
    		$chiller_values['evaporator_thickness_max_range'] = round(($chiller_values['evaporator_thickness_max_range'] * 0.0393700787),4); 
    		$chiller_values['absorber_thickness_min_range'] = round(($chiller_values['absorber_thickness_min_range'] * 0.0393700787),4);
    		$chiller_values['absorber_thickness_max_range'] = round(($chiller_values['absorber_thickness_max_range'] * 0.0393700787),4); 
    		$chiller_values['condenser_thickness_min_range'] = round(($chiller_values['condenser_thickness_min_range'] * 0.0393700787),4); 
    		$chiller_values['condenser_thickness_max_range'] = round(($chiller_values['condenser_thickness_max_range'] * 0.0393700787),4);

    	}

    	if($unit_set->FoulingFactorUnit != 'SquareMeterHrCperKcal'){
    		if($unit_set->FoulingFactorUnit == 'SquareMeterKperkW'){
    			$chiller_values['fouling_non_chilled'] = round(($chiller_values['fouling_non_chilled'] * 860),10);
    			$chiller_values['fouling_non_cooling'] = round(($chiller_values['fouling_non_cooling'] * 860),10);
    			$chiller_values['fouling_ari_chilled'] = round(($chiller_values['fouling_ari_chilled'] * 860),10);
    			$chiller_values['fouling_ari_cooling'] = round(($chiller_values['fouling_ari_cooling'] * 860),10);
    			if(!empty($chiller_values['fouling_chilled_water_value'])){
    				$chiller_values['fouling_chilled_water_value'] = round(($chiller_values['fouling_chilled_water_value'] * 860),10);
    			}

    			if(!empty($chiller_values['fouling_cooling_water_value'])){
    				$chiller_values['fouling_cooling_water_value'] = round(($chiller_values['fouling_cooling_water_value'] * 860),10);
    			}
    		}

    		if($unit_set->FoulingFactorUnit == 'SquareFeetHrFperBTU'){
    			$chiller_values['fouling_non_chilled'] = round(($chiller_values['fouling_non_chilled'] * 4.886),10);
    			$chiller_values['fouling_non_cooling'] = round(($chiller_values['fouling_non_cooling'] * 4.886),10);
    			$chiller_values['fouling_ari_chilled'] = round(($chiller_values['fouling_ari_chilled'] * 4.886),10);
    			$chiller_values['fouling_ari_cooling'] = round(($chiller_values['fouling_ari_cooling'] * 4.886),10);
    			if(!empty($chiller_values['fouling_chilled_water_value'])){
    				$chiller_values['fouling_chilled_water_value'] = round(($chiller_values['fouling_chilled_water_value'] * 4.886),10);
    			}

    			if(!empty($chiller_values['fouling_cooling_water_value'])){
    				$chiller_values['fouling_cooling_water_value'] = round(($chiller_values['fouling_cooling_water_value'] * 4.886),10);
    			}
    		}
    	}


    	if($unit_set->PressureUnit != 'KgPerCmSqGauge'){
			if($unit_set->PressureUnit == 'BarGauge') 
			{
				$chiller_values['steam_pressure'] = round(($chiller_values['steam_pressure'] * 0.980665),2);
				$chiller_values['steam_pressure_min_range'] = round(($chiller_values['steam_pressure_min_range'] * 0.980665),2);
				$chiller_values['steam_pressure_max_range'] = round(($chiller_values['steam_pressure_max_range'] * 0.980665),2);
			}
			if($unit_set->PressureUnit == 'psig') 
			{
				$chiller_values['steam_pressure'] = round(($chiller_values['steam_pressure'] * 14.223),2);
				$chiller_values['steam_pressure_min_range'] = round(($chiller_values['steam_pressure_min_range'] * 14.223),2);
				$chiller_values['steam_pressure_max_range'] = round(($chiller_values['steam_pressure_max_range'] * 14.223),2);
			}
            if ($unit_set->PressureUnit == 'kiloPascalGauge')    //sk 9/4/08
            {
                $chiller_values['steam_pressure'] = round(($chiller_values['steam_pressure'] * 98.0665),2);
                $chiller_values['steam_pressure_min_range'] = round(($chiller_values['steam_pressure_min_range'] * 98.0665),2);
                $chiller_values['steam_pressure_max_range'] = round(($chiller_values['steam_pressure_max_range'] * 98.0665),2);
            }
            if ($unit_set->PressureUnit == 'KgPerCmSq')               //mk
            {
                $chiller_values['steam_pressure'] = round(($chiller_values['steam_pressure'] + 1.03323),2);
                $chiller_values['steam_pressure_min_range'] = round(($chiller_values['steam_pressure_min_range'] + 1.03323),2);
                $chiller_values['steam_pressure_max_range'] = round(($chiller_values['steam_pressure_max_range'] + 1.03323),2);
            }
            if ($unit_set->PressureUnit == 'psi')                     //mk
            {
                $chiller_values['steam_pressure'] = round((($chiller_values['steam_pressure'] * 14.223) + 14.695),2);
                $chiller_values['steam_pressure_min_range'] = round((($chiller_values['steam_pressure_min_range'] * 14.223) + 14.695),2);
                $chiller_values['steam_pressure_max_range'] = round((($chiller_values['steam_pressure_max_range'] * 14.223) + 14.695),2);
            }
            if ($unit_set->PressureUnit == 'kiloPascal')              //mk
            {
                $chiller_values['steam_pressure'] = round((($chiller_values['steam_pressure'] * 98.0665) + 101.325),2);
                $chiller_values['steam_pressure_min_range'] = round((($chiller_values['steam_pressure_min_range'] * 98.0665) + 101.325),2);
                $chiller_values['steam_pressure_max_range'] = round((($chiller_values['steam_pressure_max_range'] * 98.0665) + 101.325),2);
            }
            if ($unit_set->PressureUnit == 'mLC')                     //mk
            {
                $chiller_values['steam_pressure'] = round((($chiller_values['steam_pressure'] * 10) + 10.3323),2);
                $chiller_values['steam_pressure_min_range'] = round((($chiller_values['steam_pressure_min_range'] * 10) + 10.3323),2);
                $chiller_values['steam_pressure_max_range'] = round((($chiller_values['steam_pressure_max_range'] * 10) + 10.3323),2);
            }
            if ($unit_set->PressureUnit == 'ftLC')                    //mk
            {
                $chiller_values['steam_pressure'] = round((($chiller_values['steam_pressure'] * 32.8084) + 33.8985),2);
                $chiller_values['steam_pressure_min_range'] = round((($chiller_values['steam_pressure_min_range'] * 32.8084) + 33.8985),2);
                $chiller_values['steam_pressure_max_range'] = round((($chiller_values['steam_pressure_max_range'] * 32.8084) + 33.8985),2);
            }
            if ($unit_set->PressureUnit == 'ftWC')                    //mk
            {
                $chiller_values['steam_pressure'] = round((($chiller_values['steam_pressure'] * 32.8084) + 33.8985),2);
                $chiller_values['steam_pressure_min_range'] = round((($chiller_values['steam_pressure_min_range'] * 32.8084) + 33.8985),2);
                $chiller_values['steam_pressure_max_range'] = round((($chiller_values['steam_pressure_max_range'] * 32.8084) + 33.8985),2);
            }
            if ($unit_set->PressureUnit == 'mmWC')                    //mk
            {
                $chiller_values['steam_pressure'] = round((($chiller_values['steam_pressure'] * 10000) + 10332.3),2);
                $chiller_values['steam_pressure_min_range'] = round((($chiller_values['steam_pressure_min_range'] * 10000) + 10332.3),2);
                $chiller_values['steam_pressure_max_range'] = round((($chiller_values['steam_pressure_max_range'] * 10000) + 10332.3),2);
            }
    	}


    	return $chiller_values;

    }


    public function calculationUnitConversion($chiller_values){

        $unit_set_id = Auth::user()->unit_set_id;
    	$unit_set = UnitSet::find($unit_set_id);


    	if($unit_set->CapacityUnit != 'TR'){
    		$chiller_values['capacity'] = $chiller_values['capacity'] / 3.5169; 
    	}

    	if($unit_set->TemperatureUnit != 'Centigrade'){
    		$chiller_values['chilled_water_in'] = ($chiller_values['chilled_water_in'] - 32)*5/9; 
    		$chiller_values['chilled_water_out'] = ($chiller_values['chilled_water_out'] - 32)*5/9; 
    		$chiller_values['min_chilled_water_out'] = ($chiller_values['min_chilled_water_out'] - 32)*5/9; 
    		$chiller_values['cooling_water_in'] = ($chiller_values['cooling_water_in'] - 32)*5/9; 
    		$chiller_values['cooling_water_in_min_range'] = ($chiller_values['cooling_water_in_min_range'] - 32)*5/9; 
    		$chiller_values['cooling_water_in_max_range'] = ($chiller_values['cooling_water_in_max_range'] - 32)*5/9; 
    		
    	}

    	if($unit_set->FlowRateUnit != 'CubicMeterPerHr'){

    		$cooling_water_ranges = array();
    		if(!is_array($chiller_values['cooling_water_ranges'])){
    			$chiller_values['cooling_water_ranges'] = explode(",", $chiller_values['cooling_water_ranges']);
    		}

    		if($unit_set->FlowRateUnit == 'CubicFeetPerHour'){
    			$chiller_values['cooling_water_flow'] = $chiller_values['cooling_water_flow'] / 35.3147;
    			for ($i=0; $i < count($chiller_values['cooling_water_ranges']); $i++) { 
    				$cooling_water_ranges[] = $chiller_values['cooling_water_ranges'][$i] / 35.3147;
    			}
    			$chiller_values['cooling_water_ranges'] = $cooling_water_ranges;
    		}

    		if($unit_set->FlowRateUnit == 'GallonPerMin'){
    			$chiller_values['cooling_water_flow'] = $chiller_values['cooling_water_flow'] * 60 / 264.172;
    			for ($i=0; $i < count($chiller_values['cooling_water_ranges']); $i++) { 
    				$cooling_water_ranges[] = $chiller_values['cooling_water_ranges'][$i] * 60 / 264.172;
    			}
    			$chiller_values['cooling_water_ranges'] = $cooling_water_ranges;
    		}

    	}

    	if($unit_set->LengthUnit != 'Millimeter'){
    		$chiller_values['evaporator_thickness'] = $chiller_values['evaporator_thickness'] / 0.0393700787; 
    		$chiller_values['absorber_thickness'] = $chiller_values['absorber_thickness'] / 0.0393700787; 
    		$chiller_values['condenser_thickness'] = $chiller_values['condenser_thickness'] / 0.0393700787;
    		$chiller_values['evaporator_thickness_min_range'] = ($chiller_values['evaporator_thickness_min_range'] / 0.0393700787); 
    		$chiller_values['evaporator_thickness_max_range'] = ($chiller_values['evaporator_thickness_max_range'] / 0.0393700787); 
    		$chiller_values['absorber_thickness_min_range'] = ($chiller_values['absorber_thickness_min_range'] / 0.0393700787);
    		$chiller_values['absorber_thickness_max_range'] = ($chiller_values['absorber_thickness_max_range'] / 0.0393700787); 
    		$chiller_values['condenser_thickness_min_range'] = ($chiller_values['condenser_thickness_min_range'] / 0.0393700787); 
    		$chiller_values['condenser_thickness_max_range'] = ($chiller_values['condenser_thickness_max_range'] / 0.0393700787);

    	}

    	if($unit_set->FoulingFactorUnit != 'SquareMeterHrCperKcal'){
    		if($unit_set->FoulingFactorUnit == 'SquareMeterKperkW'){
    			$chiller_values['fouling_non_chilled'] = $chiller_values['fouling_non_chilled'] / 860;
    			$chiller_values['fouling_non_cooling'] = $chiller_values['fouling_non_cooling'] / 860;
    			$chiller_values['fouling_ari_chilled'] = $chiller_values['fouling_ari_chilled'] / 860;
    			$chiller_values['fouling_ari_cooling'] = $chiller_values['fouling_ari_cooling'] / 860;
    			if(!empty($chiller_values['fouling_chilled_water_value'])){
    				$chiller_values['fouling_chilled_water_value'] = $chiller_values['fouling_chilled_water_value'] / 860;
    			}

    			if(!empty($chiller_values['fouling_cooling_water_value'])){
    				$chiller_values['fouling_cooling_water_value'] = $chiller_values['fouling_cooling_water_value'] / 860;
    			}
    		}

    		if($unit_set->FoulingFactorUnit == 'SquareFeetHrFperBTU'){
    			$chiller_values['fouling_non_chilled'] = $chiller_values['fouling_non_chilled'] / 4.886;
    			$chiller_values['fouling_non_cooling'] = $chiller_values['fouling_non_cooling'] / 4.886;
    			$chiller_values['fouling_ari_chilled'] = $chiller_values['fouling_ari_chilled'] / 4.886;
    			$chiller_values['fouling_ari_cooling'] = $chiller_values['fouling_ari_cooling'] / 4.886;
    			if(!empty($chiller_values['fouling_chilled_water_value'])){
    				$chiller_values['fouling_chilled_water_value'] = $chiller_values['fouling_chilled_water_value'] / 4.886;
    			}

    			if(!empty($chiller_values['fouling_cooling_water_value'])){
    				$chiller_values['fouling_cooling_water_value'] = $chiller_values['fouling_cooling_water_value'] / 4.886;
    			}
    		}
    	}

    	if($unit_set->PressureUnit != 'KgPerCmSqGauge'){
			if($unit_set->PressureUnit == 'BarGauge') 
			{
				$chiller_values['steam_pressure'] = round(($chiller_values['steam_pressure'] / 0.980665),2);
				$chiller_values['steam_pressure_min_range'] = round(($chiller_values['steam_pressure_min_range'] / 0.980665),2);
				$chiller_values['steam_pressure_max_range'] = round(($chiller_values['steam_pressure_max_range'] / 0.980665),2);
			}
			if($unit_set->PressureUnit == 'psig') 
			{
				$chiller_values['steam_pressure'] = round(($chiller_values['steam_pressure'] / 14.223),2);
				$chiller_values['steam_pressure_min_range'] = round(($chiller_values['steam_pressure_min_range'] / 14.223),2);
				$chiller_values['steam_pressure_max_range'] = round(($chiller_values['steam_pressure_max_range'] / 14.223),2);
			}
            if ($unit_set->PressureUnit == 'kiloPascalGauge')    //sk 9/4/08
            {
                $chiller_values['steam_pressure'] = round(($chiller_values['steam_pressure'] / 98.0665),2);
                $chiller_values['steam_pressure_min_range'] = round(($chiller_values['steam_pressure_min_range'] / 98.0665),2);
                $chiller_values['steam_pressure_max_range'] = round(($chiller_values['steam_pressure_max_range'] / 98.0665),2);
            }
            if ($unit_set->PressureUnit == 'KgPerCmSq')               //mk
            {
                $chiller_values['steam_pressure'] = round(($chiller_values['steam_pressure'] - 1.03323),2);
                $chiller_values['steam_pressure_min_range'] = round(($chiller_values['steam_pressure_min_range'] - 1.03323),2);
                $chiller_values['steam_pressure_max_range'] = round(($chiller_values['steam_pressure_max_range'] - 1.03323),2);
            }
            if ($unit_set->PressureUnit == 'psi')                     //mk
            {
                $chiller_values['steam_pressure'] = round((($chiller_values['steam_pressure'] / 14.223) - 14.695),2);
                $chiller_values['steam_pressure_min_range'] = round((($chiller_values['steam_pressure_min_range'] / 14.223) - 14.695),2);
                $chiller_values['steam_pressure_max_range'] = round((($chiller_values['steam_pressure_max_range'] / 14.223) - 14.695),2);
            }
            if ($unit_set->PressureUnit == 'kiloPascal')              //mk
            {
                $chiller_values['steam_pressure'] = round((($chiller_values['steam_pressure'] * 98.0665) + 101.325),2);
                $chiller_values['steam_pressure_min_range'] = round((($chiller_values['steam_pressure_min_range'] / 98.0665) - 101.325),2);
                $chiller_values['steam_pressure_max_range'] = round((($chiller_values['steam_pressure_max_range'] / 98.0665) - 101.325),2);
            }
            if ($unit_set->PressureUnit == 'mLC')                     //mk
            {
                $chiller_values['steam_pressure'] = round((($chiller_values['steam_pressure'] / 10) - 10.3323),2);
                $chiller_values['steam_pressure_min_range'] = round((($chiller_values['steam_pressure_min_range'] / 10) - 10.3323),2);
                $chiller_values['steam_pressure_max_range'] = round((($chiller_values['steam_pressure_max_range'] / 10) - 10.3323),2);
            }
            if ($unit_set->PressureUnit == 'ftLC')                    //mk
            {
                $chiller_values['steam_pressure'] = round((($chiller_values['steam_pressure'] / 32.8084) - 33.8985),2);
                $chiller_values['steam_pressure_min_range'] = round((($chiller_values['steam_pressure_min_range'] / 32.8084) - 33.8985),2);
                $chiller_values['steam_pressure_max_range'] = round((($chiller_values['steam_pressure_max_range'] / 32.8084) - 33.8985),2);
            }
            if ($unit_set->PressureUnit == 'ftWC')                    //mk
            {
                $chiller_values['steam_pressure'] = round((($chiller_values['steam_pressure'] / 32.8084) - 33.8985),2);
                $chiller_values['steam_pressure_min_range'] = round((($chiller_values['steam_pressure_min_range'] / 32.8084) - 33.8985),2);
                $chiller_values['steam_pressure_max_range'] = round((($chiller_values['steam_pressure_max_range'] / 32.8084) - 33.8985),2);
            }
            if ($unit_set->PressureUnit == 'mmWC')                    //mk
            {
                $chiller_values['steam_pressure'] = round((($chiller_values['steam_pressure'] / 10000) - 10332.3),2);
                $chiller_values['steam_pressure_min_range'] = round((($chiller_values['steam_pressure_min_range'] / 10000) - 10332.3),2);
                $chiller_values['steam_pressure_max_range'] = round((($chiller_values['steam_pressure_max_range'] / 10000) - 10332.3),2);
            }
    	}


    	return $chiller_values;

    }


    public function reportUnitConversion($calculated_values){

        $unit_set_id = Auth::user()->unit_set_id;
    	$unit_set = UnitSet::find($unit_set_id);


    	if($unit_set->CapacityUnit != 'TR'){
    		$calculated_values['TON'] = $calculated_values['TON'] * 3.5169; 
    	}

    	if($unit_set->TemperatureUnit != 'Centigrade'){
    		$calculated_values['TCHW11'] = ($calculated_values['TCHW11'] * 9)/5 +32; 
    		$calculated_values['TCHW12'] = ($calculated_values['TCHW12'] * 9)/5 +32; 
    		$calculated_values['TCW11'] = ($calculated_values['TCW11'] * 9)/5 +32; 
    		$calculated_values['CoolingWaterOutTemperature'] = ($calculated_values['CoolingWaterOutTemperature'] * 9)/5 +32; 
    		$calculated_values['m_dMinCondensateDrainTemperature'] = ($calculated_values['m_dMinCondensateDrainTemperature'] * 9)/5 +32; 
    		$calculated_values['m_dMaxCondensateDrainTemperature'] = ($calculated_values['m_dMaxCondensateDrainTemperature'] * 9)/5 +32; 

    		
    	}

    	if($unit_set->FlowRateUnit != 'CubicMeterPerHr'){

    		if($unit_set->FlowRateUnit == 'CubicFeetPerHour'){
    			$calculated_values['GCW'] = $calculated_values['GCW'] * 35.3147;
    			$calculated_values['ChilledWaterFlow'] = round(($calculated_values['ChilledWaterFlow'] * 35.3147),2);
    			$calculated_values['BypassFlow'] = round(($calculated_values['BypassFlow'] * 35.3147),2);
    		}

    		if($unit_set->FlowRateUnit == 'GallonPerMin'){
    			$calculated_values['GCW'] = $calculated_values['GCW'] * 264.172 / 60;
    			$calculated_values['ChilledWaterFlow'] = round(($calculated_values['ChilledWaterFlow'] * 264.172 / 60),2);
    			$calculated_values['BypassFlow'] = round(($calculated_values['BypassFlow'] * 264.172 / 60),2);

    		}

    	}


    	if($unit_set->LengthUnit != 'Millimeter'){
    		$calculated_values['TU3'] = round(($calculated_values['TU3'] * 0.0393700787),4); 
    		$calculated_values['TU6'] = round(($calculated_values['TU6'] * 0.0393700787),4); 
    		$calculated_values['TV6'] = round(($calculated_values['TV6'] * 0.0393700787),4); 
    		$calculated_values['Length'] = round(($calculated_values['Length'] * 0.0393700787),4); 
    		$calculated_values['Width'] = round(($calculated_values['Width'] * 0.0393700787),4); 
    		$calculated_values['Height'] = round(($calculated_values['Height'] * 0.0393700787),4); 
    		$calculated_values['ClearanceForTubeRemoval'] = round(($calculated_values['ClearanceForTubeRemoval'] * 0.0393700787),4); 
    	}

    	if($unit_set->FoulingFactorUnit != 'SquareMeterHrCperKcal'){
    		if($unit_set->FoulingFactorUnit == 'SquareMeterKperkW'){
    			$calculated_values['FFCHW1'] = $calculated_values['FFCHW1'] * 860;
    			$calculated_values['FFCOW1'] = $calculated_values['FFCOW1'] * 860;
    		}

    		if($unit_set->FoulingFactorUnit == 'SquareFeetHrFperBTU'){
    			$calculated_values['FFCHW1'] = $calculated_values['FFCHW1'] * 4.886;
    			$calculated_values['FFCOW1'] = $calculated_values['FFCOW1'] * 4.886;

    		}
    	}

    	if($unit_set->PressureUnit != 'KgPerCmSqGauge'){
			if($unit_set->PressureUnit == 'BarGauge') 
			{
				$calculated_values['PST1'] = round(($calculated_values['PST1'] * 0.980665),2);
				$calculated_values['m_dCondensateDrainPressure'] = round(($calculated_values['m_dCondensateDrainPressure'] * 0.980665),2);
				$calculated_values['m_DesignPressure'] = round(($calculated_values['m_DesignPressure'] * 0.980665),2);

			}
			if($unit_set->PressureUnit == 'psig') 
			{
				$calculated_values['PST1'] = round(($calculated_values['PST1'] * 14.223),2);
				$calculated_values['m_dCondensateDrainPressure'] = round(($calculated_values['m_dCondensateDrainPressure'] * 14.223),2);
				$calculated_values['m_DesignPressure'] = round(($calculated_values['m_DesignPressure'] * 14.223),2);

			}
            if ($unit_set->PressureUnit == 'kiloPascalGauge')    //sk 9/4/08
            {
                $calculated_values['PST1'] = round(($calculated_values['PST1'] * 98.0665),2);
                $calculated_values['m_dCondensateDrainPressure'] = round(($calculated_values['m_dCondensateDrainPressure'] * 98.0665),2);
                $calculated_values['m_DesignPressure'] = round(($calculated_values['m_DesignPressure'] * 98.0665),2);

            }
            if ($unit_set->PressureUnit == 'KgPerCmSq')               //mk
            {
                $calculated_values['PST1'] = round(($calculated_values['PST1'] + 1.03323),2);
                $calculated_values['m_dCondensateDrainPressure'] = round(($calculated_values['m_dCondensateDrainPressure'] + 1.03323),2);
                $calculated_values['m_DesignPressure'] = round(($calculated_values['m_DesignPressure'] + 1.03323),2);

            }
            if ($unit_set->PressureUnit == 'psi')                     //mk
            {
                $calculated_values['PST1'] = round((($calculated_values['PST1'] * 14.223) + 14.695),2);
                $calculated_values['m_dCondensateDrainPressure'] = round((($calculated_values['m_dCondensateDrainPressure'] * 14.223) + 14.695),2);
                $calculated_values['m_DesignPressure'] = round((($calculated_values['m_DesignPressure'] * 14.223) + 14.695),2);

            }
            if ($unit_set->PressureUnit == 'kiloPascal')              //mk
            {
                $calculated_values['PST1'] = round((($calculated_values['PST1'] * 98.0665) + 101.325),2);
                $calculated_values['m_dCondensateDrainPressure'] = round((($calculated_values['m_dCondensateDrainPressure'] * 98.0665) + 101.325),2);
                $calculated_values['m_DesignPressure'] = round((($calculated_values['m_DesignPressure'] * 98.0665) + 101.325),2);

            }
            if ($unit_set->PressureUnit == 'mLC')                     //mk
            {
                $calculated_values['PST1'] = round((($calculated_values['PST1'] * 10) + 10.3323),2);
                $calculated_values['m_dCondensateDrainPressure'] = round((($calculated_values['m_dCondensateDrainPressure'] * 10) + 10.3323),2);
                $calculated_values['m_DesignPressure'] = round((($calculated_values['m_DesignPressure'] * 10) + 10.3323),2);

            }
            if ($unit_set->PressureUnit == 'ftLC')                    //mk
            {
                $calculated_values['PST1'] = round((($calculated_values['PST1'] * 32.8084) + 33.8985),2);
                $calculated_values['m_dCondensateDrainPressure'] = round((($calculated_values['m_dCondensateDrainPressure'] * 32.8084) + 33.8985),2);
                $calculated_values['m_DesignPressure'] = round((($calculated_values['m_DesignPressure'] * 32.8084) + 33.8985),2);

            }
            if ($unit_set->PressureUnit == 'ftWC')                    //mk
            {
                $calculated_values['PST1'] = round((($calculated_values['PST1'] * 32.8084) + 33.8985),2);
                $calculated_values['m_dCondensateDrainPressure'] = round((($calculated_values['m_dCondensateDrainPressure'] * 32.8084) + 33.8985),2);
                $calculated_values['m_DesignPressure'] = round((($calculated_values['m_DesignPressure'] * 32.8084) + 33.8985),2);

            }
            if ($unit_set->PressureUnit == 'mmWC')                    //mk
            {
                $calculated_values['PST1'] = round((($calculated_values['PST1'] * 10000) + 10332.3),2);
                $calculated_values['m_dCondensateDrainPressure'] = round((($calculated_values['m_dCondensateDrainPressure'] * 10000) + 10332.3),2);
                $calculated_values['m_DesignPressure'] = round((($calculated_values['m_DesignPressure'] * 10000) + 10332.3),2);
            }
    	}

    	if($unit_set->PressureDropUnit != 'mLC'){
			if($unit_set->PressureDropUnit == 'Bar') 
			{
				$calculated_values['ChilledFrictionLoss'] = $calculated_values['ChilledFrictionLoss'] / 10.1972; //sk 26/3
				$calculated_values['CoolingFrictionLoss'] = $calculated_values['CoolingFrictionLoss'] / 10.1972;
			}
			else if($unit_set->PressureDropUnit == 'ftLC') 
			{
				$calculated_values['ChilledFrictionLoss'] = $calculated_values['ChilledFrictionLoss'] * 3.28084;
				$calculated_values['CoolingFrictionLoss'] = $calculated_values['CoolingFrictionLoss'] * 3.28084;
			}
			else if($unit_set->PressureDropUnit == 'kiloPascal') 
			{
				$calculated_values['ChilledFrictionLoss'] = $calculated_values['ChilledFrictionLoss'] * 9.80665;
				$calculated_values['CoolingFrictionLoss'] = $calculated_values['CoolingFrictionLoss'] * 9.80665;
			}
            else if ($unit_set->PressureDropUnit == 'kiloPascalGauge')           //mk
            {
                $calculated_values['ChilledFrictionLoss'] = ($calculated_values['ChilledFrictionLoss'] * 9.80665) - 101.325;
                $calculated_values['CoolingFrictionLoss'] = ($calculated_values['CoolingFrictionLoss'] * 9.80665) - 101.325;
            }
            else if ($unit_set->PressureDropUnit == 'KgPerCmSq')                 //mk
            {
                $calculated_values['ChilledFrictionLoss'] = $calculated_values['ChilledFrictionLoss'] / 10;
                $calculated_values['CoolingFrictionLoss'] = $calculated_values['CoolingFrictionLoss'] / 10;
            }
            else if ($unit_set->PressureDropUnit == 'KgPerCmSqGauge')            //mk
            {
                $calculated_values['ChilledFrictionLoss'] = ($calculated_values['ChilledFrictionLoss'] / 10) - 1.03323;
                $calculated_values['CoolingFrictionLoss'] = ($calculated_values['CoolingFrictionLoss'] / 10) - 1.03323;
            }
            else if ($unit_set->PressureDropUnit == 'psi')                       //mk
            {
                $calculated_values['ChilledFrictionLoss'] = $calculated_values['ChilledFrictionLoss'] * 1.42233;
                $calculated_values['CoolingFrictionLoss'] = $calculated_values['CoolingFrictionLoss'] * 1.42233;
            }
            else if ($unit_set->PressureDropUnit == 'psig')                      //mk
            {
                $calculated_values['ChilledFrictionLoss'] = ($calculated_values['ChilledFrictionLoss'] * 1.42233) - 14.695;
                $calculated_values['CoolingFrictionLoss'] = ($calculated_values['CoolingFrictionLoss'] * 1.42233) - 14.695;
            }
            else if ($unit_set->PressureDropUnit == 'mmWC')                      //mk
            {
                $calculated_values['ChilledFrictionLoss'] = $calculated_values['ChilledFrictionLoss'] * 1000;
                $calculated_values['CoolingFrictionLoss'] = $calculated_values['CoolingFrictionLoss'] * 1000;
            }
            else if ($unit_set->PressureDropUnit == 'ftWC')                      //mk
            {
                $calculated_values['ChilledFrictionLoss'] = $calculated_values['ChilledFrictionLoss'] * 3.28084;
                $calculated_values['CoolingFrictionLoss'] = $calculated_values['CoolingFrictionLoss'] * 3.28084;
            }
            else if ($unit_set->PressureDropUnit == 'BarGauge')                  //mk
            {
                $calculated_values['ChilledFrictionLoss'] = ($calculated_values['ChilledFrictionLoss'] / 10.1972) - 1.01325;
                $calculated_values['CoolingFrictionLoss'] = ($calculated_values['CoolingFrictionLoss'] / 10.1972) - 1.01325;
            }
    	}


    	if($unit_set->WorkPressureUnit != 'KgPerCmSqGauge'){
			if($unit_set->WorkPressureUnit == 'BarGauge') 
			{
				$calculated_values['m_maxCHWWorkPressure'] = round(($calculated_values['m_maxCHWWorkPressure'] * 0.980665),2);
				$calculated_values['m_maxCOWWorkPressure'] = round(($calculated_values['m_maxCOWWorkPressure'] * 0.980665),2);

			}
			if($unit_set->WorkPressureUnit == 'psig') 
			{
				$calculated_values['m_maxCHWWorkPressure'] = round(($calculated_values['m_maxCHWWorkPressure'] * 14.223),2);
				$calculated_values['m_maxCOWWorkPressure'] = round(($calculated_values['m_maxCOWWorkPressure'] * 14.223),2);

			}
            if ($unit_set->WorkPressureUnit == 'kiloPascalGauge')    //sk 9/4/08
            {
                $calculated_values['m_maxCHWWorkPressure'] = round(($calculated_values['m_maxCHWWorkPressure'] * 98.0665),2);
                $calculated_values['m_maxCOWWorkPressure'] = round(($calculated_values['m_maxCOWWorkPressure'] * 98.0665),2);

            }
            if ($unit_set->WorkPressureUnit == 'KgPerCmSq')               //mk
            {
                $calculated_values['m_maxCHWWorkPressure'] = round(($calculated_values['m_maxCHWWorkPressure'] + 1.03323),2);
                $calculated_values['m_maxCOWWorkPressure'] = round(($calculated_values['m_maxCOWWorkPressure'] + 1.03323),2);

            }
            if ($unit_set->WorkPressureUnit == 'psi')                     //mk
            {
                $calculated_values['m_maxCHWWorkPressure'] = round((($calculated_values['m_maxCHWWorkPressure'] * 14.223) + 14.695),2);
                $calculated_values['m_maxCOWWorkPressure'] = round((($calculated_values['m_maxCOWWorkPressure'] * 14.223) + 14.695),2);

            }
            if ($unit_set->WorkPressureUnit == 'kiloPascal')              //mk
            {
                $calculated_values['m_maxCHWWorkPressure'] = round((($calculated_values['m_maxCHWWorkPressure'] * 98.0665) + 101.325),2);
                $calculated_values['m_maxCOWWorkPressure'] = round((($calculated_values['m_maxCOWWorkPressure'] * 98.0665) + 101.325),2);

            }
            if ($unit_set->WorkPressureUnit == 'mLC')                     //mk
            {
                $calculated_values['m_maxCHWWorkPressure'] = round((($calculated_values['m_maxCHWWorkPressure'] * 10) + 10.3323),2);
                $calculated_values['m_maxCOWWorkPressure'] = round((($calculated_values['m_maxCOWWorkPressure'] * 10) + 10.3323),2);

            }
            if ($unit_set->WorkPressureUnit == 'ftLC')                    //mk
            {
                $calculated_values['m_maxCHWWorkPressure'] = round((($calculated_values['m_maxCHWWorkPressure'] * 32.8084) + 33.8985),2);
                $calculated_values['m_maxCOWWorkPressure'] = round((($calculated_values['m_maxCOWWorkPressure'] * 32.8084) + 33.8985),2);

            }
            if ($unit_set->WorkPressureUnit == 'ftWC')                    //mk
            {
                $calculated_values['m_maxCHWWorkPressure'] = round((($calculated_values['m_maxCHWWorkPressure'] * 32.8084) + 33.8985),2);
                $calculated_values['m_maxCOWWorkPressure'] = round((($calculated_values['m_maxCOWWorkPressure'] * 32.8084) + 33.8985),2);

            }
            if ($unit_set->WorkPressureUnit == 'mmWC')                    //mk
            {
                $calculated_values['m_maxCHWWorkPressure'] = round((($calculated_values['m_maxCHWWorkPressure'] * 10000) + 10332.3),2);
                $calculated_values['m_maxCOWWorkPressure'] = round((($calculated_values['m_maxCOWWorkPressure'] * 10000) + 10332.3),2);
            }
    	}

    	if($unit_set->NozzleDiameterUnit != 'DN'){
    		$calculated_values['ChilledConnectionDiameter'] = $calculated_values['ChilledConnectionDiameter'] / 25; 
    		$calculated_values['CoolingConnectionDiameter'] = $calculated_values['CoolingConnectionDiameter'] / 25; 
    		$calculated_values['SteamConnectionDiameter'] = $calculated_values['SteamConnectionDiameter'] / 25; 
    		$calculated_values['SteamDrainDiameter'] = $calculated_values['SteamDrainDiameter'] / 25; 
    	}

    	if($unit_set->SteamConsumptionUnit != 'KilogramsPerHr'){
    		
    		$calculated_values['SteamConsumption'] = $calculated_values['SteamConsumption'] * 2.20462262; 
    	}

    	if($unit_set->HeatUnit != 'kCPerHour'){
    		
    		if ($unit_set->HeatUnit == 'KWatt')
            {
                $calculated_values['AbsorbentPumpMotorKW'] = $calculated_values['AbsorbentPumpMotorKW'] / 859.845;   //SK 3600/4.1868
                $calculated_values['RefrigerantPumpMotorKW'] = $calculated_values['RefrigerantPumpMotorKW'] / 859.845;
                $calculated_values['PurgePumpMotorKW'] = $calculated_values['PurgePumpMotorKW'] / 859.845;

            }
            else if($unit_set->HeatUnit == 'MBTUPerHour')
            {
                $calculated_values['AbsorbentPumpMotorKW'] = $calculated_values['AbsorbentPumpMotorKW'] * 3.96832 / 1000;
                $calculated_values['RefrigerantPumpMotorKW'] = $calculated_values['RefrigerantPumpMotorKW'] * 3.96832 / 1000;
                $calculated_values['PurgePumpMotorKW'] = $calculated_values['PurgePumpMotorKW'] * 3.96832 / 1000;

            }
    	}

    	if($unit_set->WeightUnit != 'Ton'){
    		if($unit_set->WeightUnit == 'Pound') 
    		{
    			$calculated_values['OperatingWeight'] = $calculated_values['OperatingWeight'] * 2.20462262 * 1000;
    			$calculated_values['MaxShippingWeight'] = $calculated_values['MaxShippingWeight'] * 2.20462262 * 1000;
    			$calculated_values['FloodedWeight'] = $calculated_values['FloodedWeight'] * 2.20462262 * 1000;
    			$calculated_values['DryWeight'] = $calculated_values['DryWeight'] * 2.20462262 * 1000;
    		}
    		else if ($unit_set->WeightUnit == 'Kilogram') 
    		{
    			$calculated_values['OperatingWeight'] = $calculated_values['OperatingWeight'] * 1000;
    			$calculated_values['MaxShippingWeight'] = $calculated_values['MaxShippingWeight'] * 1000;
    			$calculated_values['FloodedWeight'] = $calculated_values['FloodedWeight'] * 1000;
    			$calculated_values['DryWeight'] = $calculated_values['DryWeight'] * 1000;
    		}
    	}


    	return $calculated_values;

    }

}
