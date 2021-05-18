<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\VamBaseController;
use App\Http\Controllers\UnitConversionController;
use App\Http\Controllers\ReportController;
use Illuminate\Http\Request;
use App\ChillerDefaultValue;
use App\ChillerMetallurgyOption;
use App\ChillerCalculationValue;
use App\UserReport;
use App\NotesAndError;
use App\UnitSet;
use App\Region;
use Exception;
use Log;
use PDF;
use DB;

class ChillerHeaterG2Controller extends Controller
{
    private $model_values;
    private $model_code = "CH_G2";
    private $calculation_values;
    private $notes;
    private $changed_value;


    public function getCHG2Series(){

        $chiller_form_values = $this->getFormValues(60);


        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')
                                        ->where('code',$this->model_code)
                                        ->where('min_model','<=',60)->where('max_model','>=',60)->first();

                              
        $chiller_options = $chiller_metallurgy_options->chillerOptions;
        
        $evaporator_options = $chiller_options->where('type', 'eva');
        $absorber_options = $chiller_options->where('type', 'abs');
        $condenser_options = $chiller_options->where('type', 'con');

        $unit_set_id = Auth::user()->unit_set_id;
        $unit_set = UnitSet::find($unit_set_id);
        
        $min_chilled_water_out = Auth::user()->min_chilled_water_out;
        if($min_chilled_water_out > $chiller_form_values['min_chilled_water_out'])
            $chiller_form_values['min_chilled_water_out'] = $min_chilled_water_out;

        $unit_conversions = new UnitConversionController;
        
        $converted_values = $unit_conversions->formUnitConversion($chiller_form_values,$this->model_code);

        
    
        $regions = Region::all();

        $vam_base = new VamBaseController();
        $language_datas = $vam_base->getLanguageDatas();
        $units_data = $vam_base->getUnitsData();

        return view('ch_g2_series')->with('default_values',$converted_values)
                                        ->with('unit_set',$unit_set)
                                        ->with('units_data',$units_data)
                                        ->with('evaporator_options',$evaporator_options)
                                        ->with('absorber_options',$absorber_options)
                                        ->with('condenser_options',$condenser_options)
                                        ->with('chiller_metallurgy_options',$chiller_metallurgy_options) 
                                        ->with('language_datas',$language_datas) 
                                        ->with('regions',$regions);
    }

    public function validateChillerAttribute($attribute){
        switch (strtoupper($attribute))
        {
            case "MODEL_NUMBER":
                // $this->modulNumberDoubleEffectG2();

                $range_calculation = $this->RANGECAL();

                if(!$range_calculation['status']){
                    return array('status'=>false,'msg'=>$range_calculation['msg']);
                }
            break;
            case "CAPACITY":
                $capacity = floatval($this->model_values['capacity']);
                if($capacity <= 0){
                    return array('status' => false,'msg' => $this->notes['NOTES_IV_CAPVAL']);
                }
                $this->model_values['capacity'] = $capacity;
                $range_calculation = $this->RANGECAL();
                if(!$range_calculation['status']){
                    return array('status'=>false,'msg'=>$range_calculation['msg']);
                }
            break;

            case "CHILLED_WATER_IN":
                if(floatval($this->model_values['chilled_water_out']) >= floatval($this->model_values['chilled_water_in'])){
                    return array('status' => false,'msg' => $this->notes['NOTES_CHW_OUT_TEMP']);
                }
            break;

            case "CHILLED_WATER_OUT":
                // STEAMPRESSURE
                // Validation
                if (floatval($this->model_values['chilled_water_out']) < floatval($this->model_values['min_chilled_water_out']))
                {
                    return array('status' => false,'msg' => $this->notes['NOTES_CHW_OT_MIN'].' (min = '.$this->model_values['min_chilled_water_out'].')');
                    
                }
                if (floatval($this->model_values['chilled_water_out']) >= floatval($this->model_values['chilled_water_in']))
                {
                    return array('status' => false,'msg' => $this->notes['NOTES_CHW_OT_IT']);
                }

                $chilled_water_out_validation = $this->chilledWaterValidating();
                if(!$chilled_water_out_validation['status']){
                    return array('status'=>false,'msg'=>$chilled_water_out_validation['msg']);
                }

                $range_calculation = $this->RANGECAL();
                if(!$range_calculation['status']){
                    return array('status'=>false,'msg'=>$range_calculation['msg']);
                }

                if (floatval($this->model_values['chilled_water_out']) < 3.5){
                	$this->model_values['max_hot_water_in'] = 75;
                	$this->model_values['max_hot_water_out'] = 80;
                }
                else{
                	$this->model_values['max_hot_water_in'] = 80;
                	$this->model_values['max_hot_water_out'] = 90;
                }

            break;  

            case "EVAPORATOR_TUBE_TYPE":

                $this->model_values['evaporator_thickness_change'] = true;
                if (floatval($this->model_values['chilled_water_out']) < 3.5 && floatval($this->model_values['glycol_chilled_water']) == 0)
                {
                    if (floatval($this->model_values['evaporator_material_value']) != 4)
                    {

                        return array('status' => false,'msg' => $this->notes['NOTES_EVA_TUBETYPE']);
                    }

                }
                $range_calculation = $this->RANGECAL();
                if(!$range_calculation['status']){          
                    return array('status'=>false,'msg'=>$range_calculation['msg']);
                }
            break;
            case "ABSORBER_TUBE_TYPE":
                $range_calculation = $this->RANGECAL();
                if(!$range_calculation['status']){
                    return array('status'=>false,'msg'=>$range_calculation['msg']);
                }
            break; 
            case "CONDENSER_TUBE_TYPE":
                $range_calculation = $this->RANGECAL();
                if(!$range_calculation['status']){
                    return array('status'=>false,'msg'=>$range_calculation['msg']);
                }
            break;        
            case "GLYCOL_TYPE_CHANGED":
                if(floatval($this->model_values['glycol_selected']) == 1){
                    $this->model_values['glycol_chilled_water'] = 0;
                    $this->model_values['glycol_cooling_water'] = 0;
                    $this->model_values['glycol_min_chilled_water'] = 0;

                    $metallurgy_validator = $this->metallurgyValidating();
                    if(!$metallurgy_validator['status'])
                        return array('status'=>false,'msg'=>$metallurgy_validator['msg']);
                }
                else{
                    if (floatval($this->model_values['chilled_water_out']) < 3.499) 
                    {
                        if (floatval($this->model_values['chilled_water_out']) < 1.99) 
                        {
                            $this->model_values['glycol_chilled_water'] = 10;
                            $this->model_values['glycol_min_chilled_water'] = 10;

                        }
                        else
                        {
                            $this->model_values['glycol_chilled_water'] = 7.5;
                            $this->model_values['glycol_min_chilled_water'] = 7.5;
                        }
                        $this->model_values['metallurgy_standard'] = true;                    
                        $this->onChangeMetallurgyOption();
                    }
                    else{
                        $this->model_values['glycol_min_chilled_water'] = 0;
                    }
                }
            break;  
            case "GLYCOL_CHILLED_WATER":
                if (($this->model_values['glycol_chilled_water'] > $this->model_values['glycol_max_chilled_water'] || $this->model_values['glycol_chilled_water'] < $this->model_values['glycol_min_chilled_water']))
                {
                    if ($this->model_values['glycol_min_chilled_water'] == 10)
                    {
                        return array('status' => false,'msg' => $this->notes['NOTES_CHW_GL_OR1']);
                    }
                    else if ($this->model_values['glycol_min_chilled_water'] == 7.5)
                    {
                        return array('status' => false,'msg' => $this->notes['NOTES_CHW_GL_OR2']);
                    }
                    else
                    {
                        return array('status' => false,'msg' => $this->notes['NOTES_CHW_GL_OR']);
                    }
                }
            break;
            case "GLYCOL_COOLING_WATER":
                if (($this->model_values['glycol_cooling_water'] > $this->model_values['glycol_max_cooling_water']))
                {
                    return array('status' => false,'msg' => $this->notes['NOTES_COW_GLY_OR']);
                }
            break;
            case "COOLING_WATER_IN":
                if (!(($this->model_values['cooling_water_in'] >= $this->model_values['cooling_water_in_min_range']) && ($this->model_values['cooling_water_in'] <= $this->model_values['cooling_water_in_max_range'])))
                {
                    return array('status' => false,'msg' => $this->notes['NOTES_COW_TEMP']);
                }
            break;
            case "COOLING_WATER_FLOW":
                $range_calculation = $this->RANGECAL();
                if(!$range_calculation['status']){
                    return array('status'=>false,'msg'=>$range_calculation['msg']);
                }
                if(!is_array($this->model_values['cooling_water_ranges'])){
                    $this->model_values['cooling_water_ranges'] = explode(",", $this->model_values['cooling_water_ranges']);
                }
                $cooling_water_ranges = $this->model_values['cooling_water_ranges'];
                $cooling_water_flow = $this->model_values['cooling_water_flow'];
                $range_validate = false;
                for ($i=0; $i < count($cooling_water_ranges); $i+=2) { 
                    $min_range = $cooling_water_ranges[$i];
                    $max_range = $cooling_water_ranges[$i+1];

                    if(($cooling_water_flow > ($min_range - 0.1)) && ($cooling_water_flow < ($max_range + 0.1))){
                        $range_validate = true;
                        break;
                    }

                }
                if(!$range_validate){
                    return array('status' => false,'msg' => $this->notes['NOTES_COW_RANGE']);
                }
            break;
            case "EVAPORATOR_THICKNESS":
                $this->model_values['evaporator_thickness_change'] = false;
                if(($this->model_values['evaporator_thickness'] >= $this->model_values['evaporator_thickness_min_range']) && ($this->model_values['evaporator_thickness'] <= $this->model_values['evaporator_thickness_max_range'])){
                    $range_calculation = $this->RANGECAL();
                    if(!$range_calculation['status']){
                        return array('status'=>false,'msg'=>$range_calculation['msg']);
                    }
                    break;
                }
                else{
                    return array('status' => false,'msg' =>$this->notes['NOTES_EVA_THICK']);
                }
            break;
            case "ABSORBER_THICKNESS":
                $this->model_values['absorber_thickness_change'] = false;
                if(($this->model_values['absorber_thickness'] >= $this->model_values['absorber_thickness_min_range']) && ($this->model_values['absorber_thickness'] <= $this->model_values['absorber_thickness_max_range'])){
                    $range_calculation = $this->RANGECAL();
                    if(!$range_calculation['status']){
                        return array('status'=>false,'msg'=>$range_calculation['msg']);
                    }
                    break;
                }
                else{
                    return array('status' => false,'msg' => $this->notes['NOTES_ABS_THICK']);
                }
            break;
            case "CONDENSER_THICKNESS":
                $this->model_values['condenser_thickness_change'] = false;
                if(($this->model_values['condenser_thickness'] >= $this->model_values['condenser_thickness_min_range']) && ($this->model_values['condenser_thickness'] <= $this->model_values['condenser_thickness_max_range'])){
                    $range_calculation = $this->RANGECAL();
                    if(!$range_calculation['status']){
                        return array('status'=>false,'msg'=>$range_calculation['msg']);
                    }
                    break;
                }
                else{
                    return array('status' => false,'msg' => $this->notes['NOTES_CON_THICK']);
                }
            break;
            case "FOULING_CHILLED_VALUE":
                if($this->model_values['fouling_factor'] == 'non_standard' && !empty($this->model_values['fouling_chilled_water_checked'])){
                    if($this->model_values['fouling_chilled_water_value'] < $this->model_values['fouling_non_chilled']){
                        return array('status' => false,'msg' => $this->notes['NOTES_CHW_FF_MIN']);
                    }
                }
                if($this->model_values['fouling_factor'] == 'ari'){
                    if($this->model_values['fouling_chilled_water_value'] < $this->model_values['fouling_ari_chilled']){
                        return array('status' => false,'msg' => $this->notes['NOTES_CHW_FF_MIN']);
                    }
                }

            break;
            case "FOULING_COOLING_VALUE":
                if($this->model_values['fouling_factor'] == 'non_standard' && !empty($this->model_values['fouling_cooling_water_checked'])){
                    if($this->model_values['fouling_cooling_water_value'] < $this->model_values['fouling_non_cooling']){
                        return array('status' => false,'msg' => $this->notes['NOTES_COW_FF_MIN']);
                    }
                }
                if($this->model_values['fouling_factor'] == 'ari'){
                    if($this->model_values['fouling_cooling_water_value'] < $this->model_values['fouling_ari_cooling']){
                        return array('status' => false,'msg' => $this->notes['NOTES_COW_FF_MIN']);
                    }
                }

            break;
            case "CALORIFIC_VALUE":
                if (!(($this->model_values['calorific_value'] >= 8000) && ($this->model_values['calorific_value'] <= 12000)))
                {
                    return array('status' => false,'msg' => $this->notes['NOTES_CV_OR']);
                }
            break;
            case "HEAT_DUTY":
            	$MinHeatDuty = floatval($this->model_values['capacity']) * 3024 * 0.1;
            	$MaxHeatDuty = floatval($this->model_values['capacity'])*3024*0.75;

            	if (!((floatval($this->model_values['heat_duty']) >= $MinHeatDuty) && (floatval($this->model_values['heat_duty']) <= $MaxHeatDuty)))
            	 {
            	     return array('status' => false,'msg' => $this->notes['NOTES_MAXHEAT_DUTY']);
            	 }
            break;
            case "HOT_WATER_IN":
	            if (!(($this->model_values['hot_water_in'] >= $this->model_values['min_hot_water_in']) && ($this->model_values['hot_water_in'] <=$this->model_values['max_hot_water_in'])))
	            {
	                return array('status' => false,'msg' => $this->notes['NOTES_HWIT_OR']);
	            }
            break;

            case "HOT_WATER_OUT":
	            if ($this->model_values['hot_water_out'] <= $this->model_values['hot_water_in'])
	            {
	                return array('status' => false,'msg' => $this->notes['NOTES_HWO_HWI']);
	            }
	            if ($this->model_values['hot_water_out'] > $this->model_values['max_hot_water_out'])
	            {
	                return array('status' => false,'msg' => $this->notes['NOTES_HWOT_OR']);

	            }
            break;

        }
        return array('status' => true,'msg' => "process run successfully");

    }

    public function WATERPROP()
    {
    	$vam_base = new VamBaseController();
        if ($this->calculation_values['GLL'] == 2)
        {
            $this->calculation_values['CHGLY_VIS12'] = $vam_base->EG_VISCOSITY($this->calculation_values['TCHW12'], $this->calculation_values['CHGLY']) / 1000;
            $this->calculation_values['CHGLY_TCON12'] = $vam_base->EG_THERMAL_CONDUCTIVITY($this->calculation_values['TCHW12'], $this->calculation_values['CHGLY']);
            $this->calculation_values['CHGLY_ROW12'] = $vam_base->EG_ROW($this->calculation_values['TCHW12'], $this->calculation_values['CHGLY']);
            $this->calculation_values['CHGLY_SPHT12'] = $vam_base->EG_SPHT($this->calculation_values['TCHW12'], $this->calculation_values['CHGLY']) * 1000;

            $this->calculation_values['COGLY_VISH1'] = $vam_base->EG_VISCOSITY($this->calculation_values['TCW11'], $this->calculation_values['COGLY']) / 1000;
            $this->calculation_values['COGLY_TCONH1'] = $vam_base->EG_THERMAL_CONDUCTIVITY($this->calculation_values['TCW11'], $this->calculation_values['COGLY']);
            $this->calculation_values['COGLY_ROWH1'] = $vam_base->EG_ROW($this->calculation_values['TCW11'], $this->calculation_values['COGLY']);
            $this->calculation_values['COGLY_SPHT1'] = $vam_base->EG_SPHT($this->calculation_values['TCW11'], $this->calculation_values['COGLY']) * 1000;
        }
        else
        {
            $this->calculation_values['CHGLY_VIS12'] = $vam_base->PG_VISCOSITY($this->calculation_values['TCHW12'], $this->calculation_values['CHGLY']) / 1000;
            $this->calculation_values['CHGLY_TCON12'] = $vam_base->PG_THERMAL_CONDUCTIVITY($this->calculation_values['TCHW12'], $this->calculation_values['CHGLY']);
            $this->calculation_values['CHGLY_ROW12'] = $vam_base->PG_ROW($this->calculation_values['TCHW12'], $this->calculation_values['CHGLY']);
            $this->calculation_values['CHGLY_SPHT12'] = $vam_base->PG_SPHT($this->calculation_values['TCHW12'], $this->calculation_values['CHGLY']) * 1000;

            $this->calculation_values['COGLY_VISH1'] = $vam_base->PG_VISCOSITY($this->calculation_values['TCW11'], $this->calculation_values['COGLY']) / 1000;
            $this->calculation_values['COGLY_TCONH1'] = $vam_base->PG_THERMAL_CONDUCTIVITY($this->calculation_values['TCW11'], $this->calculation_values['COGLY']);
            $this->calculation_values['COGLY_ROWH1'] = $vam_base->PG_ROW($this->calculation_values['TCW11'], $this->calculation_values['COGLY']);
            $this->calculation_values['COGLY_SPHT1'] = $vam_base->PG_SPHT($this->calculation_values['TCW11'], $this->calculation_values['COGLY']) * 1000;
        }
        $this->calculation_values['GCHW'] = $this->calculation_values['TON'] * 3024 / (($this->calculation_values['TCHW11'] - $this->calculation_values['TCHW12']) * $this->calculation_values['CHGLY_ROW12'] * $this->calculation_values['CHGLY_SPHT12'] / 4187);
    }

    public function VELOCITY()
    {
        $this->calculation_values['VELEVA'] = 0;
        /********************* ABSORBER VELOCITY ********************/
        /*if ($this->calculation_values['MODEL'] > 800)
        {
            $this->calculation_values['TAP'] = 1;
            do
            {
                $this->calculation_values['VA'] = $this->calculation_values['GCW'] / (((3600 * 3.141593 * $this->calculation_values['IDA'] * $this->calculation_values['IDA']) / 4.0) * ($this->calculation_values['TNAA'] / $this->calculation_values['TAP']));

                if ($this->calculation_values['VA'] < VAMIN)
                {
                    $this->calculation_values['TAP'] = $this->calculation_values['TAP'] + 1;
                }
            } while ($this->calculation_values['VA'] < VAMIN);
        }
        else
        {*/
            $this->calculation_values['TAP'] = 0;
            do
            {
                $this->calculation_values['TAP'] = $this->calculation_values['TAP'] + 1;
                $this->calculation_values['VA'] = $this->calculation_values['GCW'] / (((3600 * 3.141593 * $this->calculation_values['IDA'] * $this->calculation_values['IDA']) / 4.0) * ($this->calculation_values['TNAA'] / $this->calculation_values['TAP']));
            } while ($this->calculation_values['VA'] < $this->calculation_values['VAMAX']);
        //}

        if ($this->calculation_values['VA'] > ($this->calculation_values['VAMAX'] + 0.01) && $this->calculation_values['TAP'] != 1)
        {
            $this->calculation_values['TAP'] = $this->calculation_values['TAP'] - 1;
            $this->calculation_values['VA'] = $this->calculation_values['GCW'] / (((3600 * 3.141593 * $this->calculation_values['IDA'] * $this->calculation_values['IDA']) / 4.0) * ($this->calculation_values['TNAA'] / $this->calculation_values['TAP']));
        }

        if ($this->calculation_values['TAP'] == 1)           //PARAFLOW
        {
            $this->calculation_values['GCWAH'] = 0.5 * $this->calculation_values['GCW'];
            $this->calculation_values['GCWAL'] = 0.5 * $this->calculation_values['GCW'];
        }
        else                //SERIES FLOW
        {
            $this->calculation_values['GCWAH'] = $this->calculation_values['GCW'];
            $this->calculation_values['GCWAL'] = $this->calculation_values['GCW'];
        }

        /**************** CONDENSER VELOCITY ******************/
        $this->calculation_values['TCP'] = 1;
        $this->calculation_values['GCWCMAX'] = 3.141593 / 4 * ($this->calculation_values['IDC'] * $this->calculation_values['IDC']) * $this->calculation_values['TNC'] * $this->calculation_values['VCMAX'] * 3600 / $this->calculation_values['TCP'];
        if ($this->calculation_values['GCW'] > $this->calculation_values['GCWCMAX'])
            $this->calculation_values['GCWC'] = $this->calculation_values['GCWCMAX'];
        else
            $this->calculation_values['GCWC'] = $this->calculation_values['GCW'];

        if ($this->calculation_values['MODEL'] < 300)
        {
            do
            {
                $this->calculation_values['VC'] = ($this->calculation_values['GCWC'] * 4) / (3.141593 * $this->calculation_values['IDC'] * $this->calculation_values['IDC'] * $this->calculation_values['TNC'] * 3600 / $this->calculation_values['TCP']);

                if ($this->calculation_values['VC'] < 1.4)
                {
                    $this->calculation_values['TCP'] = $this->calculation_values['TCP'] + 1;
                }

                if ($this->calculation_values['TCP'] > 2)
                {
                    $this->calculation_values['TCP'] = 2;
                    $this->calculation_values['VC'] = ($this->calculation_values['GCWC'] * 4) / (3.141593 * $this->calculation_values['IDC'] * $this->calculation_values['IDC'] * $this->calculation_values['TNC'] * 3600 / $this->calculation_values['TCP']);
                    break;
                }

            } while ($this->calculation_values['VC'] < 1.4);
        }
        else
        {
            $this->calculation_values['VC'] = ($this->calculation_values['GCWC'] * 4) / (3.141593 * $this->calculation_values['IDC'] * $this->calculation_values['IDC'] * $this->calculation_values['TNC'] * 3600 / $this->calculation_values['TCP']);
        }


        /********************* EVAPORATOR VELOCITY ********************/
        $this->calculation_values['GCHW'] = $this->calculation_values['TON'] * 3024 / (($this->calculation_values['TCHW11'] - $this->calculation_values['TCHW12']) * $this->calculation_values['CHGLY_ROW12'] * $this->calculation_values['CHGLY_SPHT12'] / 4187);
        if ($this->calculation_values['TU2'] == 3 && $this->calculation_values['TCHW12'] < 3.5 && $this->calculation_values['CHGLY'] == 0)
        {
            $this->calculation_values['TP'] = 1;
            do
            {
                $this->calculation_values['VEA'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * (($this->calculation_values['TNEV'] / 2) / $this->calculation_values['TP']));
                if ($this->calculation_values['VEA'] < 0.9)
                    $this->calculation_values['TP'] = $this->calculation_values['TP'] + 1;
            } while ($this->calculation_values['VEA'] < 0.9 && $this->calculation_values['TP'] <= 4);
            if ($this->calculation_values['TP'] > 4)
            {
                $this->calculation_values['TP'] = 4;
                $this->calculation_values['VEA'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * (($this->calculation_values['TNEV'] / 2) / $this->calculation_values['TP']));

                if ($this->calculation_values['VEA'] < 0.80)
                {
                    return  array('status' => false,'msg' => $this->notes['NOTES_CHW_VELO']);
                }
            }
            if ($this->calculation_values['VEA'] > 1.8)                        // 06/11/2017
            {
                if ($this->calculation_values['TP'] == 1)
                {
                    return  array('status' => false,'msg' => $this->notes['NOTES_CHW_VELO_HI']);
                }
                else
                {
                    $this->calculation_values['TP'] = $this->calculation_values['TP'] - 1;
                    $this->calculation_values['VEA'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * (($this->calculation_values['TNEV'] / 2) / $this->calculation_values['TP']));
                }
            }
        }
        else 
        {
            if (($this->calculation_values['TU2'] == 3 && $this->calculation_values['TCHW12'] >= 3.5) || ($this->calculation_values['TU2'] == 3 && $this->calculation_values['TCHW12'] < 3.5 && $this->calculation_values['CHGLY'] != 0))
            {
                $this->calculation_values['TP'] = 1;
                do
                {
                    $this->calculation_values['VEA'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * (($this->calculation_values['TNEV'] / 2) / $this->calculation_values['TP']));
                    if ($this->calculation_values['VEA'] < 0.7)
                        $this->calculation_values['TP'] = $this->calculation_values['TP'] + 1;
                } while ($this->calculation_values['VEA'] < 0.7 && $this->calculation_values['TP'] <= 4);

                if ($this->calculation_values['TP'] > 4)
                {
                    $this->calculation_values['TP'] = 4;
                    $this->calculation_values['VEA'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * (($this->calculation_values['TNEV'] / 2) / $this->calculation_values['TP']));

                    if ($this->calculation_values['VEA'] < 0.60)
                    {
                        return  array('status' => false,'msg' => $this->notes['NOTES_CHW_VELO']);
                    }
                }

                /*if ($this->calculation_values['VEA'] > 1.6)        //ENDCROSSED TUBES WILL BE USED
                {
                    $this->calculation_values['VELEVA'] = 1;
                    $this->calculation_values['IDE'] = ODE - ((2 * 0.75) / 1000);

                    $this->calculation_values['TP'] = 1;
                    do
                    {
                        $this->calculation_values['VEA'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * (($this->calculation_values['TNEV'] / 2) / $this->calculation_values['TP']));
                        if ($this->calculation_values['VEA'] < 1.5)
                            $this->calculation_values['TP'] = $this->calculation_values['TP'] + 1;
                    } while ($this->calculation_values['VEA'] < 1.5 && $this->calculation_values['TP'] <= 4);
                }

                if ($this->calculation_values['TP'] > 4)
                {
                    $this->calculation_values['TP'] = 4;
                    $this->calculation_values['VEA'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * (($this->calculation_values['TNEV'] / 2) / $this->calculation_values['TP']));
                }
                */
                //earlier maximum velocity for ec tubes 2.5 m/sec

                if ($this->calculation_values['VEA'] > 1.8)                                       // 14 FEB 2012
                {
                    if ($this->calculation_values['TP'] == 1)
                    {
                        return  array('status' => false,'msg' => $this->notes['NOTES_CHW_VELO_HI']);
                    }
                    else
                    {
                        $this->calculation_values['TP'] = $this->calculation_values['TP'] - 1;
                        $this->calculation_values['VEA'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * (($this->calculation_values['TNEV'] / 2) / $this->calculation_values['TP']));
                    }
                }
            }
            else
            {
                $this->calculation_values['TP'] = 1;
                do
                {
                    $this->calculation_values['VEA'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * (($this->calculation_values['TNEV'] / 2) / $this->calculation_values['TP']));
                    if ($this->calculation_values['VEA'] < 1.5)
                        $this->calculation_values['TP'] = $this->calculation_values['TP'] + 1;
                } while ($this->calculation_values['VEA'] < 1.5 && $this->calculation_values['TP'] <= 4);

                if ($this->calculation_values['TP'] > 4)
                {
                    $this->calculation_values['TP'] = 4;
                    $this->calculation_values['VEA'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * (($this->calculation_values['TNEV'] / 2) / $this->calculation_values['TP']));
                    if ($this->calculation_values['VEA'] < 1.4)
                    {
                        return  array('status' => false,'msg' => $this->notes['NOTES_CHW_VELO']);
                    }
                }
                if ($this->calculation_values['MODEL'] < 1200)
                {

                    if ($this->calculation_values['VEA'] > 2.64)
                    {
                        if ($this->calculation_values['TP'] == 1)
                        {
                            return  array('status' => false,'msg' => $this->notes['NOTES_CHW_VELO_HI']);
                        }
                        else
                        {
                            $this->calculation_values['TP'] = $this->calculation_values['TP'] - 1;
                            $this->calculation_values['VEA'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * (($this->calculation_values['TNEV'] / 2) / $this->calculation_values['TP']));
                        }
                    }
                }
                else
                {

                    if ($this->calculation_values['VEA'] > 2.78)                    // 14 FEB 2012
                    {
                        if ($this->calculation_values['TP'] == 1)
                        {
                            return  array('status' => false,'msg' => $this->notes['NOTES_CHW_VELO_HI']);
                        }
                        else
                        {
                            $this->calculation_values['TP'] = $this->calculation_values['TP'] - 1;
                            $this->calculation_values['VEA'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * (($this->calculation_values['TNEV'] / 2) / $this->calculation_values['TP']));
                        }
                    }
                }
            }
        }
        // PR_DROP_DATA();
        $this->PR_DROP_CHILL();

        if ($this->calculation_values['FLE'] > 12)
        {
            //if ($this->calculation_values['MODEL'] < 750 && $this->calculation_values['TU2'] < 2.1)
            //{
            //    $this->calculation_values['VEMIN'] = 0.45;
            //}
            //else
            {
                $this->calculation_values['VEMIN'] = 1;
            }
            $this->calculation_values['TP'] = 1;
            do
            {
                $this->calculation_values['VEA'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * (($this->calculation_values['TNEV'] / 2) / $this->calculation_values['TP']));
                if ($this->calculation_values['VEA'] < $this->calculation_values['VEMIN'])
                    $this->calculation_values['TP'] = $this->calculation_values['TP'] + 1;
            } while ($this->calculation_values['VEA'] < $this->calculation_values['VEMIN'] && $this->calculation_values['TP'] <= 4);

            if ($this->calculation_values['TP'] > 4)
            {
                $this->calculation_values['TP'] = 4;
                $this->calculation_values['VEA'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * (($this->calculation_values['TNEV'] / 2) / $this->calculation_values['TP']));
            }
        }
        return  array('status' => true,'msg' => "chilled water velocity");
    }


    public function PR_DROP_CHILL()
    {
        $vam_base = new VamBaseController();

        $this->calculation_values['PIDE1'] = ($this->calculation_values['PODE1'] - (2 * $this->calculation_values['THPE1'])) / 1000;
        $this->calculation_values['PIDE2'] = ($this->calculation_values['PODE2'] - (2 * $this->calculation_values['THPE2'])) / 1000;

        $VPE1 = ($this->calculation_values['GCHW'] * 4) / (3.141593 * $this->calculation_values['PIDE1'] * $this->calculation_values['PIDE1'] * 3600);

        if ($this->calculation_values['MODEL'] > 300)
        {
            $VPE2 = (0.5 * $this->calculation_values['GCHW'] * 4) / (3.141593 * $this->calculation_values['PIDE2'] * $this->calculation_values['PIDE2'] * 3600);
            $VPBR = (0.5 * $this->calculation_values['GCHW'] * 4) / (3.141593 * $this->calculation_values['PIDE1'] * $this->calculation_values['PIDE1'] * 3600);
        }
        else
        {
            $VPE2 = $VPBR = 0;
        }

        //PIPE1

        // double $VPE1 = ($this->calculation_values['GCHW'] * 4) / (3.141593 * $this->calculation_values['PIDE1'] * $this->calculation_values['PIDE1'] * 3600);            //VELOCITY IN PIPE1
        $TME = ($this->calculation_values['TCHW11'] + $this->calculation_values['TCHW12']) / 2.0;

        if ($this->calculation_values['GLL'] == 3)
        {
            $CHGLY_ROW22 = $vam_base->PG_ROW($TME, $this->calculation_values['CHGLY']);
            $CHGLY_VIS22 = $vam_base->PG_VISCOSITY($TME, $this->calculation_values['CHGLY']) / 1000;
        }
        else
        {
            $CHGLY_ROW22 = $vam_base->EG_ROW($TME, $this->calculation_values['CHGLY']);
            $CHGLY_VIS22 = $vam_base->EG_VISCOSITY($TME, $this->calculation_values['CHGLY']) / 1000;
        }

        $REPE1 = ($this->calculation_values['PIDE1'] * $VPE1 * $CHGLY_ROW22) / $CHGLY_VIS22;

        if ($this->calculation_values['MODEL'] > 300)
        {
            $REPE2 = ($this->calculation_values['PIDE2'] * $VPE2 * $CHGLY_ROW22) / $CHGLY_VIS22;
            $REBR = ($this->calculation_values['PIDE1'] * $VPBR * $CHGLY_ROW22) / $CHGLY_VIS22;          //REYNOLDS NO IN PIPE1
        }
        else
        {
            $REPE2 = $REBR = 0;
        }

        $FF1 = 1.325 / pow(log((0.0457 / (3.7 * $this->calculation_values['PIDE1'] * 1000)) + (5.74 / pow($REPE1, 0.9))), 2);       //FRICTION FACTOR CAL

        if ($this->calculation_values['MODEL'] > 300)
        {
            $FF2 = 1.325 / pow(log((0.0457 / (3.7 * $this->calculation_values['PIDE2'] * 1000)) + (5.74 / pow($REPE2, 0.9))), 2);
            $FF3 = 1.325 / pow(log((0.0457 / (3.7 * $this->calculation_values['PIDE1'] * 1000)) + (5.74 / pow($REBR, 0.9))), 2);
        }
        else
        {
            $FF2 = $FF3 = 0;
        }


        $FL1 = ($FF1 * ($this->calculation_values['SL1'] + $this->calculation_values['SL8']) / $this->calculation_values['PIDE1']) * ($VPE1 * $VPE1 / (2 * 9.81));

        if ($this->calculation_values['MODEL'] > 300)
        {
            $FL2 = ($FF2 * ($this->calculation_values['SL3'] + $this->calculation_values['SL4'] + $this->calculation_values['SL5'] + $this->calculation_values['SL6']) / $this->calculation_values['PIDE2']) * ($VPE2 * $VPE2 / (2 * 9.81));
            $FL3 = ($FF3 * ($this->calculation_values['SL2'] + $this->calculation_values['SL7']) / $this->calculation_values['PIDE1']) * ($VPBR * $VPBR / (2 * 9.81));
            $FL4 = (2 * $this->calculation_values['FT1'] * 20 * $VPBR * $VPBR / (2 * 9.81)) + (2 * $this->calculation_values['FT2'] * 60 * $VPE2 * $VPE2 / (2 * 9.81)) + (2 * $this->calculation_values['FT2'] * 14 * $VPE2 * $VPE2 / (2 * 9.81));
            $FL5 = ($VPE2 * $VPE2 / (2 * 9.81)) + (0.5 * $VPE2 * $VPE2 / (2 * 9.81));
        }
        else
        {
            $FL2 = $FL3 = $FL4 = 0;
            $FL5 = ($VPE1 * $VPE1 / (2 * 9.81)) + (0.5 * $VPE1 * $VPE1 / (2 * 9.81));
        }

        $FLP = $FL1 + $FL2 + $FL3 + $FL4 + $FL5;      //EVAPORATOR PIPE LOSS

        $RE = ($this->calculation_values['VEA'] * $this->calculation_values['IDE'] * $CHGLY_ROW22) / $CHGLY_VIS22;            //REYNOLDS NO IN TUBES

        //if ($this->calculation_values['MODEL'] < 750 && $this->calculation_values['TU2'] == 0)
        //{
        //    $F = 1.325 / pow(log((1.53 / (3.7 * $this->calculation_values['IDE'] * 1000)) + (5.74 / pow($RE, 0.9))), 2);
        //    $FE1 = $F * $this->calculation_values['LE'] * $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (2 * 9.81 * $this->calculation_values['IDE']);
        //}
        //else 
        if (($this->calculation_values['MODEL'] < 1200 && $this->calculation_values['TU2'] == 3))
        {
            $F = 1.325 / pow(log((1.53 / (3.7 * $this->calculation_values['IDE'] * 1000)) + (5.74 / pow($RE, 0.9))), 2);
            $FE1 = $F * $this->calculation_values['LE'] * $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (2 * 9.81 * $this->calculation_values['IDE']);
        }
        else if ($this->calculation_values['MODEL'] > 1200 && $this->calculation_values['TU2'] == 3)                                         //06/11/2017   Changed for SS FInned
        {
            $F = (1.325 / pow(log((1.53 / (3.7 * $this->calculation_values['IDE'] * 1000)) + (5.74 / pow($RE, 0.9))), 2)) * ((-0.0315 * $this->calculation_values['VEA']) + 0.85);
            $FE1 = $F * $this->calculation_values['LE'] * $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (2 * 9.81 * $this->calculation_values['IDE']);

        }
        else if (($this->calculation_values['MODEL'] < 1200 && ($this->calculation_values['TU2'] == 1 || $this->calculation_values['TU2'] == 2 || $this->calculation_values['TU2'] == 0) || ($this->calculation_values['MODEL'] < 1200 && $this->calculation_values['TU2'] == 4)))                    // 12% AS PER EXPERIMENTATION      
        {
            $F = (0.0014 + (0.137 / pow($RE, 0.32))) * 1.12;
            $FE1 = 2 * $F * $this->calculation_values['LE'] * $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (9.81 * $this->calculation_values['IDE']);
        }
        else if ($this->calculation_values['MODEL'] > 1200 && ($this->calculation_values['TU2'] == 1 || $this->calculation_values['TU2'] == 2 || $this->calculation_values['TU2'] == 4 || $this->calculation_values['TU2'] == 0))
        {
            $F = 0.0014 + (0.137 / pow($RE, 0.32));
            $FE1 = 2 * $F * $this->calculation_values['LE'] * $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (9.81 * $this->calculation_values['IDE']);
        }
        else
        {
            $F = 0.0014 + (0.125 / pow($RE, 0.32));
            $FE1 = 2 * $F * $this->calculation_values['LE'] * $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (9.81 * $this->calculation_values['IDE']);
        }

        $FE2 = $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (4 * 9.81);
        $FE3 = $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (2 * 9.81);
        $FE4 = (($FE1 + $FE2 + $FE3) * $this->calculation_values['TP']) * 2;      //EVAPORATOR TUBE LOSS FOR DOUBLE ABS
        $this->calculation_values['FLE'] = $FLP + $FE4;                //TOTAL FRICTION LOSS IN CHILLED WATER CKT
        $this->calculation_values['PDE'] = $this->calculation_values['FLE'] + $this->calculation_values['SHE'];                    //PRESSURE DROP IN CHILLED WATER CKT
    }

    public function CALCULATIONS()
    {
        $this->calculation_values['SIDEARM'] = 0;
        if ($this->calculation_values['TON'] < ($this->calculation_values['MODEL'] * 0.5))
        {
            $this->calculation_values['FR1'] = 0.10;
        }
        else if (($this->calculation_values['TON'] > ($this->calculation_values['MODEL'] * 0.5)) && ($this->calculation_values['TON'] < ($this->calculation_values['MODEL'] * 0.72)))
        {
            $this->calculation_values['FR1'] = 0.18;
        }
        else
        {
            $this->calculation_values['FR1'] = 0.20;
        }

        // DATA(chiller);

        $this->calculation_values['VEA'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * (($this->calculation_values['TNEV'] / 2) / $this->calculation_values['TP']));
        $this->calculation_values['VC'] = ($this->calculation_values['GCWC'] * 4) / (3.141593 * $this->calculation_values['IDC'] * $this->calculation_values['IDC'] * $this->calculation_values['TNC'] * 3600 / $this->calculation_values['TCP']);

        if ($this->calculation_values['TAP'] == 3)
        {
            $this->calculation_values['TAPH'] = 1;
            $this->calculation_values['TAPL'] = 1;
        }
        else
        {
            if ($this->calculation_values['TAP'] == 1)
            {
                $this->calculation_values['TAPH'] = 1;
                $this->calculation_values['TAPL'] = 1;
            }
            else
            {
                $this->calculation_values['TAPH'] = $this->calculation_values['TAP'] / 2;
                $this->calculation_values['TAPL'] = $this->calculation_values['TAP'] / 2;
            }
        }

        $this->calculation_values['VAH'] = $this->calculation_values['GCWAH'] / (((3600 * 3.141593 * $this->calculation_values['IDA'] * $this->calculation_values['IDA']) / 4.0) * (($this->calculation_values['TNAA'] / 2) / $this->calculation_values['TAPH']));
        $this->calculation_values['VAL'] = $this->calculation_values['GCWAL'] / (((3600 * 3.141593 * $this->calculation_values['IDA'] * $this->calculation_values['IDA']) / 4.0) * (($this->calculation_values['TNAA'] / 2) / $this->calculation_values['TAPL']));

        $this->DERATE_KEVA();
        $this->DERATE_KABSH();
        $this->DERATE_KABSL();
        $this->DERATE_KCON();

        if ($this->calculation_values['MODEL'] < 3500)
        {
            if ($this->calculation_values['TCHW12'] < 3.499 || ($this->calculation_values['MODEL'] < 300 && $this->calculation_values['TCHW12'] <= 5.0))
            {
                $this->calculation_values['KM3'] = (0.0343 * $this->calculation_values['TCHW12']) + 0.82;
            }
            else
            {
                {
                    $this->calculation_values['KM3'] = 1;
                }
            }
        }

        $this->calculation_values['TCHW1H'] = $this->calculation_values['TCHW11'];
        $this->calculation_values['TCHW2L'] = $this->calculation_values['TCHW12'];
        $this->calculation_values['TCW1H'] = $this->calculation_values['TCW11'];

        $this->calculation_values['DT'] = $this->calculation_values['TCHW1H'] - $this->calculation_values['TCHW2L'];

        if ($this->calculation_values['DT'] >= 11)
        {
            $this->calculation_values['KM4'] = 1.11 - 0.01 * $this->calculation_values['DT'];
        }
        else
        {
            $this->calculation_values['KM4'] = 1;
        }
        $this->calculation_values['KEVA'] = $this->calculation_values['KEVA'] * $this->calculation_values['KM3'] * $this->calculation_values['KM4'];
        $this->calculation_values['KABSH'] = $this->calculation_values['KABSH'] * $this->calculation_values['KM3'] * $this->calculation_values['KM4'];
        $this->calculation_values['KABSL'] = $this->calculation_values['KABSL'] * $this->calculation_values['KM3'] * $this->calculation_values['KM4'];

        $this->calculation_values['UEVA'] = 1.0 / ((1.0 / $this->calculation_values['KEVA']) + $this->calculation_values['FFCHW1']);
        $this->calculation_values['UEVAH'] = 1.0 / ((1.0 / $this->calculation_values['KEVA']) + $this->calculation_values['FFCHW1']);
        $this->calculation_values['UEVAL'] = 1.0 / ((1.0 / $this->calculation_values['KEVA']) + $this->calculation_values['FFCHW1']);
        $this->calculation_values['UABSH'] = 1.0 / ((1.0 / $this->calculation_values['KABSH']) + $this->calculation_values['FFCOW1']);
        $this->calculation_values['UABSL'] = 1.0 / ((1.0 / $this->calculation_values['KABSL']) + $this->calculation_values['FFCOW1']);
        $this->calculation_values['UCON'] = 1.0 / ((1.0 / $this->calculation_values['KCON']) + $this->calculation_values['FFCOW1']);

        if ($this->calculation_values['TAP'] == 1) // 11.9.14
        {
            if ($this->calculation_values['MODEL'] == 760)
            {
                $this->calculation_values['UABSH'] = $this->calculation_values['UABSH'] * 0.93;
                $this->calculation_values['UABSL'] = $this->calculation_values['UABSL'] * 0.93;
            }
            else
            {
                $this->calculation_values['UABSH'] = $this->calculation_values['UABSH'] * 0.9;
                $this->calculation_values['UABSL'] = $this->calculation_values['UABSL'] * 0.9;
            }
        }
        /**/

        //$this->calculation_values['UEVAH'] = $this->calculation_values['UEVAH'] * 0.96;
        //$this->calculation_values['UEVAL'] = $this->calculation_values['UEVAL'] * 0.96;
        //$this->calculation_values['UABSH'] = $this->calculation_values['UABSH'] * 0.96;
        //$this->calculation_values['UABSL'] = $this->calculation_values['UABSL'] * 0.96;
        /*************************/

        if ($this->calculation_values['TUU'] != 'ari')
        {
            $this->calculation_values['ALTGACT']=$this->calculation_values['ALTG'];
            do
            {
                $this->EVAPORATOR();
                //HTG();
                    
                if (($this->calculation_values['ALTG'] / $this->calculation_values['ALTGACT']) > 0.53)
                {
                    if (($this->calculation_values['T13'] - $this->calculation_values['THW2']) < 2)
                    {
                        $this->calculation_values['ALTG'] = $this->calculation_values['ALTG'] * 0.95;
                    }
                }
                else
                {
                    $this->calculation_values['SIDEARM'] = 1;
                    break;
                }

            } while (($this->calculation_values['T13'] - $this->calculation_values['THW2']) < 2);
        }
        else
        {
            $a = 1;
            $this->calculation_values['ALTGACT'] = $this->calculation_values['ALTG'];

            $this->calculation_values['UEVA'] = 1.0 / ((1.0 / $this->calculation_values['KEVA']) + ($this->calculation_values['FFCHW1'] * 0.5));
            $this->calculation_values['UEVAH'] = 1.0 / ((1.0 / $this->calculation_values['KEVA']) + ($this->calculation_values['FFCHW1'] * 0.5));
            $this->calculation_values['UEVAL'] = 1.0 / ((1.0 / $this->calculation_values['KEVA']) + ($this->calculation_values['FFCHW1'] * 0.5));
            $this->calculation_values['UABSH'] = 1.0 / ((1.0 / $this->calculation_values['KABSH']) + ($this->calculation_values['FFCOW1'] * 0.5));
            $this->calculation_values['UABSL'] = 1.0 / ((1.0 / $this->calculation_values['KABSL']) + ($this->calculation_values['FFCOW1'] * 0.5));
            $this->calculation_values['UCON'] = 1.0 / ((1.0 / $this->calculation_values['KCON']) + ($this->calculation_values['FFCOW1'] * 0.5));

            if ($this->calculation_values['TAP'] == 1)
            {
                if ($this->calculation_values['MODEL'] == 760)
                {
                    $this->calculation_values['UABSH'] = $this->calculation_values['UABSH'] * 0.93;
                    $this->calculation_values['UABSL'] = $this->calculation_values['UABSL'] * 0.93;
                }
                else
                {
                    $this->calculation_values['UABSH'] = $this->calculation_values['UABSH'] * 0.9;
                    $this->calculation_values['UABSL'] = $this->calculation_values['UABSL'] * 0.9;
                }
            }

            do
            {
                $this->calculation_values['TCHW1H'] = $this->calculation_values['TCHW11'];
                $this->calculation_values['TCHW2L'] = $this->calculation_values['TCHW12'];
                $this->calculation_values['TCW1H'] = $this->calculation_values['TCW11'];
                $this->EVAPORATOR(chiller);
                //HTG();

                if (($this->calculation_values['ALTG'] / $this->calculation_values['ALTGACT']) > 0.53)
                {
                    if (($this->calculation_values['T13'] - $this->calculation_values['THW2']) < 2)
                    {
                        $this->calculation_values['ALTG'] = $this->calculation_values['ALTG'] * 0.95;
                    }
                }
                else
                {
                    $this->calculation_values['SIDEARM'] = 1;
                    break;
                }
            } while (($this->calculation_values['T13'] - $this->calculation_values['THW2']) < 2);
            $this->calculation_values['TCHW1H'] = $this->calculation_values['TCHW11'];
            $this->calculation_values['TCHW2L'] = $this->calculation_values['TCHW12'];
            $this->calculation_values['TCW1H'] = $this->calculation_values['TCW11'];
            $TCW14 = $this->calculation_values['TCW4'];
            $t11 = array();
            $t3n1 = array();
            $t12 = array();
            $t3n2 = array();

            do
            {
                if ($this->calculation_values['T13'] > $this->calculation_values['AT13'])
                {
                    break;
                }
                $this->CONCHECK1();
                if ($this->calculation_values['XCONC'] > $this->calculation_values['KM'])
                {
                    break;
                }
                $t11[$a] = $this->calculation_values['T1'];
                $t3n1[$a] = $this->calculation_values['T3'];
                $ARISSP = ($this->calculation_values['TCHW12'] - $this->calculation_values['T1']) * 1.8;
                $ARIR = ($this->calculation_values['TCHW11'] - $this->calculation_values['TCHW12']) * 1.8;
                $ARILMTD = $ARIR / log(1 + ($ARIR / $ARISSP));
                $ARICHWA = 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['LE'] * $this->calculation_values['TNEV'];
                $ARIILMTD = (5 * $this->calculation_values['FFCHW1']) * ($this->calculation_values['TON'] * 3024 * 3.968 / ($ARICHWA * 3.28084 * 3.28084));
                $ARIZ = $ARIR / ($ARILMTD - $ARIILMTD);
                $ARITDA = $ARISSP - ($ARIR / (exp($ARIZ) - 1));
                $ARITCHWI = $this->calculation_values['TCHW11'] - ($ARITDA / 1.8);
                $ARITCHWO = $this->calculation_values['TCHW12'] - ($ARITDA / 1.8);

                $ARISSPC = ($this->calculation_values['T3'] - $TCW14) * 1.8;
                $ARIRC = ($TCW14 - $this->calculation_values['TCW11']) * 1.8;
                $ALMTDC = $ARIRC / log(1 + ($ARIRC / $ARISSPC));
                $ARICOWA = 3.141593 * $this->calculation_values['LE'] * ($this->calculation_values['IDA'] * $this->calculation_values['TNAA'] + $this->calculation_values['IDC'] * $this->calculation_values['TNC']);
                $AILMTDC = (5 * $this->calculation_values['FFCOW1']) * ($this->calculation_values['GCW'] * $this->calculation_values['COGLY_ROWH1'] * ($this->calculation_values['COGLY_SPHT1'] / 4187) * ($this->calculation_values['TCW4'] - $this->calculation_values['TCW11']) * 3.968 / ($ARICOWA * 3.28084 * 3.28084));
                $ARIZC = $ARIRC / ($ALMTDC - $AILMTDC);
                $ARITDAC = $ARISSPC - ($ARIRC / (exp($ARIZC) - 1));
                $ARITCWI = $this->calculation_values['TCW11'] + ($ARITDAC / 1.8);
                
                $this->calculation_values['FFCHW'] = 0;
                $this->calculation_values['FFCOW'] = 0;
                $this->calculation_values['UEVA'] = 1.0 / ((1.0 / $this->calculation_values['KEVA']) + $this->calculation_values['FFCHW']);
                $this->calculation_values['UEVAH'] = 1.0 / ((1.0 / $this->calculation_values['KEVA']) + $this->calculation_values['FFCHW']);
                $this->calculation_values['UEVAL'] = 1.0 / ((1.0 / $this->calculation_values['KEVA']) + $this->calculation_values['FFCHW']);
                $this->calculation_values['UABSH'] = 1.0 / ((1.0 / $this->calculation_values['KABSH']) + $this->calculation_values['FFCOW']);
                $this->calculation_values['UABSL'] = 1.0 / ((1.0 / $this->calculation_values['KABSL']) + $this->calculation_values['FFCOW']);
                $this->calculation_values['UCON'] = 1.0 / ((1.0 / $this->calculation_values['KCON']) + $this->calculation_values['FFCOW']);

                if ($this->calculation_values['TAP'] == 1)
                {
                    if ($this->calculation_values['MODEL'] == 760)
                    {
                        $this->calculation_values['UABSH'] = $this->calculation_values['UABSH'] * 0.93;
                        $this->calculation_values['UABSL'] = $this->calculation_values['UABSL'] * 0.93;
                    }
                    else
                    {
                        $this->calculation_values['UABSH'] = $this->calculation_values['UABSH'] * 0.9;
                        $this->calculation_values['UABSL'] = $this->calculation_values['UABSL'] * 0.9;
                    }
                }

                $this->calculation_values['TCHW1H'] = $ARITCHWI;
                $this->calculation_values['TCHW2L'] = $ARITCHWO;
                $this->calculation_values['TCW1H'] = $ARITCWI;
                $this->EVAPORATOR();
                //HTG();
               
                $t12[$a] = $this->calculation_values['T1'];
                $t3n2[$a] = $this->calculation_values['T3'];
            } while ((abs($t11[$a] - $t12[$a]) > 0.005) || (abs($t3n1[$a] - $t3n2[$a]) > 0.005));
        }
    }

    public function getFormValues($model_number){

        $model_number = (int)$model_number;
        $chiller_calculation_values = ChillerCalculationValue::where('code',$this->model_code)->where('min_model',$model_number)->first();

        $calculation_values = $chiller_calculation_values->calculation_values;
        $calculation_values = json_decode($calculation_values,true);

        $form_values = array_only($calculation_values, ['capacity',
            'model_name',
            'model_number',
            'glycol_none',
            'fouling_factor',
            'glycol_selected',
            'chilled_water_in',
            'cooling_water_in',
            'fouling_ari_chilled',
            'fouling_ari_cooling',
            'chilled_water_out',
            'cooling_water_flow',
            'fouling_non_chilled',
            'fouling_non_cooling',
            'metallurgy_standard',
            'cooling_water_ranges',
            'glycol_chilled_water',
            'glycol_cooling_water',
            'min_chilled_water_out',
            'glycol_max_chilled_water',
            'glycol_max_cooling_water',
            'glycol_min_chilled_water',
            'glycol_min_cooling_water',
            'cooling_water_in_max_range',
            'cooling_water_in_min_range',
            'heat_duty',
            'heat_duty_min',
            'hot_water_out',
            'hot_water_in',
            'min_hot_water_in',
            'max_hot_water_in',
            'max_hot_water_out',
            'USA_capacity',
            'USA_chilled_water_in',
            'USA_chilled_water_out',
            'USA_cooling_water_in',
            'USA_cooling_water_flow',
            'normal_ng_calorific_value',
            'gross_ng_calorific_value',
            'normal_hsd_calorific_value',
            'gross_hsd_calorific_value',
            'normal_sko_calorific_value',
            'gross_sko_calorific_value',
            'fuel_type',
            'fuel_value_type'

        ]);



        $region_type = Auth::user()->region_type;

        if($region_type == 2)
            $region_name = Auth::user()->region->name;
        else
            $region_name = '';


        $standard_values = array('evaporator_thickness' => 0,'absorber_thickness' => 0,'condenser_thickness' => 0,'evaporator_thickness_min_range' => 0,'evaporator_thickness_max_range' => 0,'absorber_thickness_min_range' => 0,'absorber_thickness_max_range' => 0,'condenser_thickness_min_range' => 0,'condenser_thickness_max_range' => 0,'fouling_chilled_water_value' => 0,'fouling_cooling_water_value' => 0,'evaporator_thickness_change' => 1,'absorber_thickness_change' => 1,'condenser_thickness_change' => 1,'fouling_chilled_water_checked' => 0,'fouling_cooling_water_checked' => 0,'fouling_chilled_water_disabled' => 1,'fouling_cooling_water_disabled' => 1,'fouling_chilled_water_value_disabled' => 1,'fouling_cooling_water_value_disabled' => 1,'region_name'=>$region_name,'region_type'=>$region_type,'calorific_value' => 0,'std_calorific_value' => 0);


        $form_values = collect($form_values)->union($standard_values);

        return $form_values;
    }
}
