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

class ChillerHeaterS2Controller extends Controller
{
    private $model_values;
    private $model_code = "CH_S2";
    private $calculation_values;
    private $notes;
    private $changed_value;


    public function getCHS2Series(){

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

        return view('ch_s2_series')->with('default_values',$converted_values)
                                        ->with('unit_set',$unit_set)
                                        ->with('units_data',$units_data)
                                        ->with('evaporator_options',$evaporator_options)
                                        ->with('absorber_options',$absorber_options)
                                        ->with('condenser_options',$condenser_options)
                                        ->with('chiller_metallurgy_options',$chiller_metallurgy_options) 
                                        ->with('language_datas',$language_datas) 
                                        ->with('regions',$regions);
    }

    public function postAjaxCHS2(Request $request){

        $model_values = $request->input('values');
        $changed_value = $request->input('changed_value');
        // update user values with model values


        $unit_conversions = new UnitConversionController;
        if(!empty($changed_value)){

            $model_values = $unit_conversions->calculationUnitConversion($model_values,$this->model_code);
        }
        $this->changed_value =$changed_value;

        $this->model_values = $model_values;
        $this->castToBoolean();

        $vam_base = new VamBaseController();
        $this->notes = $vam_base->getNotesError();

        //$this->model_values = $this->calculation_values;
        $attribute_validator = $this->validateChillerAttribute($this->changed_value);

        
        if(!$attribute_validator['status'])
            return response()->json(['status'=>false,'msg'=>$attribute_validator['msg'],'changed_value'=>$this->changed_value]);

        $this->updateInputs();
        $this->loadSpecSheetData();
        
        $this->model_values['min_chilled_water_out'] = $this->calculation_values['min_chilled_water_out']; 

        $min_chilled_water_out = Auth::user()->min_chilled_water_out;
        if($min_chilled_water_out > $this->model_values['min_chilled_water_out'])
            $this->model_values['min_chilled_water_out'] = $min_chilled_water_out;

    
        $converted_values = $unit_conversions->formUnitConversion($this->model_values,$this->model_code);

       

        $model_number =(int)$this->model_values['model_number'];
        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$this->model_code)->where('min_model','<=',$model_number)->where('max_model','>',$model_number)->first();
        //$queries = DB::getQueryLog();


        $chiller_options = $chiller_metallurgy_options->chillerOptions;
        
        $evaporator_options = $chiller_options->where('type', 'eva');
        $absorber_options = $chiller_options->where('type', 'abs');
        $condenser_options = $chiller_options->where('type', 'con');

        return response()->json(['status'=>true,'msg'=>'Ajax Datas','model_values'=>$converted_values,'changed_value'=>$this->changed_value,'evaporator_options'=>$evaporator_options,'absorber_options'=>$absorber_options,'condenser_options'=>$condenser_options,'chiller_metallurgy_options'=>$chiller_metallurgy_options]);
    }

    public function validateChillerAttribute($attribute){

        switch (strtoupper($attribute))
        {
            case "MODEL_NUMBER":
                // $this->modulNumberDoubleEffectS2();
                
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
    
                // Validation
                if (floatval($this->model_values['chilled_water_out']) < floatval($this->model_values['min_chilled_water_out']))
                {
                    return array('status' => false,'msg' => $this->notes['NOTES_CHW_OT_MIN'] .' (min = '.$this->model_values['min_chilled_water_out'].')');
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

                    // else{
                    //  $this->model_values['evaporator_thickness_change'] = false;
                    // }
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
                    if (floatval($this->model_values['chilled_water_out']) < 3.499)     //06/11/2017
                    {
                        if (floatval($this->model_values['chilled_water_out']) < 1.99)     // Verify
                        {
                            
                            $this->model_values['glycol_chilled_water'] = 10;
                            // if($this->model_values['glycol_chilled_water'] <= 10){
                            //     $this->changed_value = 'glycol_chilled_water';
                            //     return array('status' => false,'msg' => $this->notes['NOTES_CHW_GL_OR1']);
                            // }
                            $this->model_values['glycol_min_chilled_water'] = 10;

                        }
                        else
                        {
                            
                            $this->model_values['glycol_chilled_water'] = 7.5;
                            // if($this->model_values['glycol_chilled_water'] <= 7.5){
                            //     $this->changed_value = 'glycol_chilled_water';
                            //     return array('status' => false,'msg' => $this->notes['NOTES_CHW_GL_OR2']);
                            // }
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
            case "STEAM_PRESSURE":
                if (!(($this->model_values['steam_pressure'] >= $this->model_values['steam_pressure_min_range']) && ($this->model_values['steam_pressure'] <= $this->model_values['steam_pressure_max_range'])))
                {
                    return array('status' => false,'msg' => $this->notes['NOTES_STMPR_RANGE']);
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

    public function RANGECAL()
    {
        $FMIN1 = 0; 
        $FMAX1 = 0;
        $TAPMAX = 0;
        $FMAX = array();
        $FMIN = array();
     
        $model_number = (int)$this->model_values['model_number'];
        $chilled_water_out = $this->model_values['chilled_water_out'];
        $capacity = $this->model_values['capacity'];

        $GCWMIN1 = $this->RANGECAL1($model_number,$chilled_water_out,$capacity);
       
        $this->updateInputs();

        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$this->model_code)
                                        ->where('min_model','<=',$model_number)->where('max_model','>',$model_number)->first();

        $chiller_options = $chiller_metallurgy_options->chillerOptions;
        
        // $evaporator_option = $chiller_options->where('type', 'eva')->where('value',$this->model_values['evaporator_material_value'])->first();
        $absorber_option = $chiller_options->where('type', 'abs')->where('value',$this->calculation_values['TU5'])->first();
        $condenser_option = $chiller_options->where('type', 'con')->where('value',$this->calculation_values['TV5'])->first();


        if ($this->calculation_values['MODEL'] < 300)
        {
            $TCP = 2;
        }
        else
        {
            $TCP = 1;
        }

        $VAMIN = $absorber_option->metallurgy->abs_min_velocity;          
        $VAMAX = $absorber_option->metallurgy->abs_max_velocity;
        $VCMIN = $condenser_option->metallurgy->con_min_velocity;
        $VCMAX = $condenser_option->metallurgy->con_max_velocity;


        $GCWMIN = 3.141593 / 4 * $this->calculation_values['IDC'] * $this->calculation_values['IDC'] * $VCMIN * $this->calculation_values['TNC'] * 3600 / $TCP;		//min required flow in condenser
        $GCWCMAX = 3.141593 / 4 * $this->calculation_values['IDC'] * $this->calculation_values['IDC'] * $VCMAX * $this->calculation_values['TNC'] * 3600 / 1;

        if ($GCWMIN > $GCWMIN1)
            $GCWMIN2 = $GCWMIN;
        else
            $GCWMIN2 = $GCWMIN1;

        $TAPMAX = 4;

        $FMIN[$TAPMAX] = 3.141593 / 4 * $this->calculation_values['IDA'] * $this->calculation_values['IDA'] * 3600 * $this->calculation_values['TNAA'] / $TAPMAX * $VAMIN;

        if ($FMIN[$TAPMAX] < $GCWMIN2)
            $FMIN[$TAPMAX] = $GCWMIN2;

        $FMAX[$TAPMAX] = 3.141593 / 4 * $this->calculation_values['IDA'] * $this->calculation_values['IDA'] * 3600 * $this->calculation_values['TNAA'] / $TAPMAX * $VAMAX;

        $GCWMAX = 3.141593 / 4 * $this->calculation_values['IDA'] * $this->calculation_values['IDA'] * 3600 * $this->calculation_values['TNAA'] * $VAMAX;
        $INIT = 1;
        if ($FMIN[$TAPMAX] > $GCWMAX)
        {
            return array('status' => false,'msg' => $this->notes['NOTES_COW_MAX_LIM']  );
        }
        else
        {
            $FMIN1 = $FMIN[$TAPMAX];
            $FMAX1 = $FMAX[$TAPMAX];
            for ($TAP = $TAPMAX - 1; $TAP >= 1; $TAP--)
            {
                $FMIN[$TAP] = 3.141593 / 4 * $this->calculation_values['IDA'] * $this->calculation_values['IDA'] * 3600 * $this->calculation_values['TNAA'] / $TAP * $VAMIN;
                $FMAX[$TAP] = 3.141593 / 4 * $this->calculation_values['IDA'] * $this->calculation_values['IDA'] * 3600 * $this->calculation_values['TNAA'] / $TAP * $VAMAX;
                if ($FMIN[$TAP] > $FMAX1 && $FMIN1 < $FMAX1)
                {
                    $FLOWMN[$INIT] = $FMIN1;
                    $FLOWMX[$INIT] = $FMAX1;
                    $INIT++;
                    $FMIN1 = $FMIN[$TAP];
                    $FMAX1 = $FMAX[$TAP];
                }
                else
                {
                    $FMAX1 = $FMAX[$TAP];
                }
            }
        }

        // PR_DROP_DATA();
        $this->calculation_values['PIDA'] = ($this->calculation_values['PODA'] - (2 * $this->calculation_values['THPA'])) / 1000;
        $this->calculation_values['APA'] = 3.141593 * $this->calculation_values['PIDA'] * $this->calculation_values['PIDA'] / 4;

        if ($this->calculation_values['MODEL'] == 130 || $this->calculation_values['MODEL'] == 810 || $this->calculation_values['MODEL'] == 900)  //change
        {
            $GCWPMAX = $this->calculation_values['APA'] * 3.5 * 3600;
        }
        else if ($this->calculation_values['MODEL'] == 310 || $this->calculation_values['MODEL'] == 350 || $this->calculation_values['MODEL'] == 410 || $this->calculation_values['MODEL'] == 470 || $this->calculation_values['MODEL'] == 530 || $this->calculation_values['MODEL'] == 580 || $this->calculation_values['MODEL'] == 630 || $this->calculation_values['MODEL'] == 710)
        {
            $GCWPMAX = $this->calculation_values['APA'] * 3.8 * 3600;
        }
        else
        {
            $GCWPMAX = $this->calculation_values['APA'] * 4 * 3600;
        }
        if ($FMAX1 > $GCWPMAX)
        {
            $FMAX1 = $GCWPMAX;
        }
        //if ($this->calculation_values['MODEL'] < 350 && $GCWCMAX < $FMAX1)
        //{
        //    $FMAX1 = $GCWCMAX;
        //}

        if ($FMIN1 < $FMAX1)
        {
            $FLOWMN[$INIT] = $FMIN1;
            $FLOWMX[$INIT] = $FMAX1;
        }
        else
        {
            return array('status' => false,'msg' => $this->notes['NOTES_COW_MAX_LIM']);
        }

        $range_values = array();
        foreach ($FLOWMN as $key => $min) {
            if(!empty($FLOWMX[$key])){
                $min = round($FLOWMN[$key], 1);
                $max = round($FLOWMX[$key], 1);

                $range_values[] = $min;
                $range_values[] = $max;
                // $range_values .= "(".$min." - ".$max.")<br>";
            }

        }

        $this->model_values['cooling_water_ranges'] = $range_values;

        return array('status' => true,'msg' => "process run successfully");
    }

    public function RANGECAL1($model_number,$chilled_water_out,$capacity)
    {
        $TCHW12 = $chilled_water_out;
        $TON = $capacity;

        if ($model_number < 750.0)
        {
            if ($TCHW12 < 6.699 && $TCHW12 > 4.99)
                $KM1 = 1.8824 - 0.1765 * $TCHW12;
            else
            {
                if ($TCHW12 <= 4.99 && $TCHW12 > 4.5)
                    $KM1 = 1.0;
                else
                {
                    if ($TCHW12 <= 4.5 && $TCHW12 > 3.49)
                        $KM1 = 1.0 + (4.5 - $TCHW12) * 0.2;
                    else
                    {
                        if ($TCHW12 < 3.5)
                        {
                            $KM1 = 1.2;
                        }
                        else
                        {
                            $KM1 = 0.7;
                        }
                    }
                }
            }
            $GCWMIN1 = $TON * $KM1;
        }
        else
        {

            if ($TCHW12 < 6.699 && $TCHW12 > 4.99)
                $KM1 = 1.8824 - 0.1765 * $TCHW12;
            else
            {
                if ($TCHW12 <= 4.99 && $TCHW12 > 4.5)
                    $KM1 = 1.0;
                else
                {
                    if ($TCHW12 <= 4.5 && $TCHW12 > 3.49)
                        $KM1 = 1.0 + (4.5 - $TCHW12) * 0.2;
                    else
                    {
                        if ($TCHW12 < 3.5)
                        {
                            $KM1 = 1.2;
                        }
                        else
                        {
                            $KM1 = 0.7;
                        }
                    }
                }
            }
            $GCWMIN1 = $TON * $KM1;
        }
    }

    public function updateInputs()
    {

        $model_number = (int)$this->model_values['model_number'];
        $calculation_values = $this->getCalculationValues($model_number);
        //$this->calculation_values = $chiller_data;
        //$this->modulNumberDoubleEffectS2();
        $this->calculation_values = $calculation_values;

        $this->calculation_values['region_type'] = $this->model_values['region_type'];
        $this->calculation_values['model_name'] = $this->model_values['model_name'];

        $this->calculation_values['MODEL'] = $this->model_values['model_number'];
        $this->calculation_values['TON'] = $this->model_values['capacity'];
        $this->calculation_values['TUU'] = $this->model_values['fouling_factor'];
        $this->calculation_values['FFCHW1'] = floatval($this->model_values['fouling_chilled_water_value']);
        $this->calculation_values['FFCOW1'] = floatval($this->model_values['fouling_cooling_water_value']);


        $chiller_metallurgy_option = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$this->model_code)
                                    ->where('min_model','<=',(int)$this->model_values['model_number'])->where('max_model','>',(int)$this->model_values['model_number'])->first();


        $chiller_options = $chiller_metallurgy_option->chillerOptions; 
                    

        $evaporator_option = $chiller_options->where('type', 'eva')->where('value',$chiller_metallurgy_option->eva_default_value)->first();
        $absorber_option = $chiller_options->where('type', 'abs')->where('value',$chiller_metallurgy_option->abs_default_value)->first();
        $condenser_option = $chiller_options->where('type', 'con')->where('value',$chiller_metallurgy_option->con_default_value)->first();

        if($this->model_values['metallurgy_standard']){
                     
            $this->calculation_values['TU2'] = $chiller_metallurgy_option->eva_default_value; 
            $this->calculation_values['TU3'] = $evaporator_option->metallurgy->default_thickness;
            $this->calculation_values['TU5'] = $chiller_metallurgy_option->abs_default_value;
            $this->calculation_values['TU6'] = $absorber_option->metallurgy->default_thickness;
            $this->calculation_values['TV5'] = $chiller_metallurgy_option->con_default_value; 
            $this->calculation_values['TV6'] = $condenser_option->metallurgy->default_thickness;
            $this->calculation_values['FFCHW'] = 0.0; 
            $this->calculation_values['FFCOW'] = 0.0;
        }
        else{
            $this->calculation_values['TU5'] = $this->model_values['absorber_material_value']; 
            $this->calculation_values['TU6'] = $this->model_values['absorber_thickness']; 

            $this->calculation_values['TU2'] = $this->model_values['evaporator_material_value']; 
            $this->calculation_values['TU3'] = $this->model_values['evaporator_thickness'];

            $this->calculation_values['TV5'] = $this->model_values['condenser_material_value']; 
            $this->calculation_values['TV6'] = $this->model_values['condenser_thickness'];
        }

        $this->calculation_values['ODE'] = ($evaporator_option->metallurgy->ode)/1000;
        $this->calculation_values['ODA'] = ($absorber_option->metallurgy->ode)/1000;
        $this->calculation_values['ODC'] = ($condenser_option->metallurgy->ode)/1000;

        $this->calculation_values['TCW11'] = $this->model_values['cooling_water_in']; 
        // Glycol Selected = (1 = none, 2 = 'ethylene', 3 = 'Propylene' 

        $this->calculation_values['GL'] = $this->model_values['glycol_selected']; 
        $this->calculation_values['CHGLY'] = $this->model_values['glycol_chilled_water']; 
        $this->calculation_values['COGLY'] = $this->model_values['glycol_cooling_water']; 
        $this->calculation_values['TCHW11'] = $this->model_values['chilled_water_in']; 
        $this->calculation_values['TCHW12'] = $this->model_values['chilled_water_out']; 
        $this->calculation_values['GCW'] = $this->model_values['cooling_water_flow']; 
        $this->calculation_values['PST1'] = $this->model_values['steam_pressure']; 
        $this->calculation_values['isStandard'] = $this->model_values['metallurgy_standard']; 

        $this->calculation_values['HEATCAP'] = $this->model_values['heat_duty']; 
        $this->calculation_values['THW1'] = $this->model_values['hot_water_in']; 
        $this->calculation_values['THW2'] = $this->model_values['hot_water_out']; 


        // chiller.HHType = HHTypes.Standard;
        // chiller.SideArm = SideArm.Present;
        
        // chiller.ECinEva = 0;

        // Standard Calculation Values
        $this->calculation_values['CoolingWaterOutTemperature'] = 0;
        $this->calculation_values['ChilledWaterFlow'] = 0;
        $this->calculation_values['BypassFlow'] = 0;
        $this->calculation_values['ChilledFrictionLoss'] = 0;
        $this->calculation_values['CoolingFrictionLoss'] = 0;
        $this->calculation_values['SteamConsumption'] = 0;


        $this->DATA();

        $this->THICKNESS();
    }

    private function DATA()
    {

        if ($this->calculation_values['TCW11'] <= 32.0)
            $this->calculation_values['TCWA'] = $this->calculation_values['TCW11'];
        else
            $this->calculation_values['TCWA'] = 32.0;



        // $this->calculation_values['AT13'] = 101;
        if ($this->calculation_values['TCW11'] < 29.4)
            $this->calculation_values['AT13'] = 99.99;
        else
            $this->calculation_values['AT13'] = ($this->calculation_values['A_AT13'] * $this->calculation_values['TCWA']) + $this->calculation_values['B_AT13'];

        $this->calculation_values['ALTHE'] = $this->calculation_values['ALTHE'] * $this->calculation_values['ALTHE_F'];
        $this->calculation_values['AHTHE'] = $this->calculation_values['AHTHE'] * $this->calculation_values['AHTHE_F'];
        $this->calculation_values['AHR'] = $this->calculation_values['AHR'] * $this->calculation_values['AHR_F'];


        if($this->calculation_values['region_type'] == 2 || $this->calculation_values['region_type'] == 3)
        {
            $this->calculation_values['AT13'] =$this->calculation_values['AT13']-$this->calculation_values['EX_AT13'] ;
            $this->calculation_values['KEVA'] =$this->calculation_values['KEVA']*$this->calculation_values['EX_KEVA'] ;
            $this->calculation_values['KABS'] =$this->calculation_values['KABS']*$this->calculation_values['EX_KABS'] ;
        }    

        $this->calculation_values['KEVA1'] = 1 / ((1 / $this->calculation_values['KEVA']) - (0.65 / 340000.0));
        
        if ($this->calculation_values['TU2'] == 2)
            $this->calculation_values['KEVA'] = 1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 340000.0));
        if ($this->calculation_values['TU2'] == 1)
            $this->calculation_values['KEVA'] = 1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 37000.0));
        if ($this->calculation_values['TU2'] == 4)
            $this->calculation_values['KEVA'] = (1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 21000.0))) * 0.93;
        if ($this->calculation_values['TU2'] == 3)
            $this->calculation_values['KEVA'] = 1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 21000.0)) * 0.93;              //Changed to $this->calculation_values['KEVA1'] from 1600 on 06/11/2017 as tube metallurgy is changed
        if ($this->calculation_values['TU2'] == 5)
            $this->calculation_values['KEVA'] = 1 / ((1 / 1600.0) + ($this->calculation_values['TU3'] / 15000.0));
        /********* VARIATION OF $this->calculation_values['KABS'] WITH CON METALLURGY ****/
        if ($this->calculation_values['TU2'] == 6 || $this->calculation_values['TU2'] == 7 || $this->calculation_values['TU2'] == 8)
        {
            if ($this->calculation_values['TV5'] == 1)
                $this->calculation_values['KM5'] = 1;
            else if ($this->calculation_values['TV5'] == 2)
                $this->calculation_values['KM5'] = 1;
            else if ($this->calculation_values['TV5'] == 3)
                $this->calculation_values['KM5'] = 1;
            else if ($this->calculation_values['TV5'] == 4)
                $this->calculation_values['KM5'] = 1;
            else if ($this->calculation_values['TV5'] == 5)
                $this->calculation_values['KM5'] = 1;
            else
                $this->calculation_values['KM5'] = 1;
        }
        else
            $this->calculation_values['KM5'] = 1;
        /********* DETERMINATION OF $this->calculation_values['KABS'] FOR NONSTD. SELECTION****/
        $this->calculation_values['KABS1'] = 1 / ((1 / $this->calculation_values['KABS']) - (0.65 / 340000));
        if ($this->calculation_values['TU5'] == 1)
        {
            $this->calculation_values['KABS'] = 1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 37000));
        }
        else
        {
            if ($this->calculation_values['TU5'] == 2)
                $this->calculation_values['KABS'] = 1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 340000));
            if ($this->calculation_values['TU5'] == 6)
                $this->calculation_values['KABS'] = (1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 21000))) * 0.93;
            else
            {
                $this->calculation_values['KABS1'] = 1240;
                if ($this->calculation_values['TU5'] == 3)
                    $this->calculation_values['KABS'] = 1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 37000));
                if ($this->calculation_values['TU5'] == 4)
                    $this->calculation_values['KABS'] = 1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 340000));
                if ($this->calculation_values['TU5'] == 5)
                    $this->calculation_values['KABS'] = 1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 21000));
                if ($this->calculation_values['TU5'] == 7)
                    $this->calculation_values['KABS'] = 1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 15000));
            }
        }
        $this->calculation_values['KABS'] = $this->calculation_values['KABS'] * $this->calculation_values['KM5'];


        /********** DETERMINATION OF $this->calculation_values['KCON'] IN NONSTD. SELECTION*******/
        $this->calculation_values['KCON1'] = 1 / ((1 / $this->calculation_values['KCON']) - (0.65 / 340000));

        if ($this->calculation_values['TV5'] == 1)
        {
            //$this->calculation_values['KCON1'] = 4000;
            $this->calculation_values['KCON'] = 1 / ((1 / $this->calculation_values['KCON1']) + ($this->calculation_values['TV6'] / 37000));
        }
        else if ($this->calculation_values['TV5'] == 2)
            $this->calculation_values['KCON'] = 1 / ((1 / $this->calculation_values['KCON1']) + ($this->calculation_values['TV6'] / 340000));
        else if ($this->calculation_values['TV5'] == 4)
            $this->calculation_values['KCON'] = 1 / ((1 / $this->calculation_values['KCON1']) + ($this->calculation_values['TV6'] / 21000)) * 0.95;  
        else
        {
            $this->calculation_values['KCON1'] = 3000;
            if ($this->calculation_values['TV5'] == 3)
                $this->calculation_values['KCON'] = 1 / ((1 / $this->calculation_values['KCON1']) + ($this->calculation_values['TV6'] / 21000));
            if ($this->calculation_values['TV5'] == 5)
                $this->calculation_values['KCON'] = 1 / ((1 / $this->calculation_values['KCON1']) + ($this->calculation_values['TV6'] / 15000));
        }
        //if ($this->calculation_values['TV5'] == 0)
        //{
        //    $this->calculation_values['KCON'] = 3000 * 2;
        //}


        $this->calculation_values['AEVAH'] = $this->calculation_values['AEVA'] / 2;
        $this->calculation_values['AEVAL'] = $this->calculation_values['AEVA'] / 2;
        $this->calculation_values['AABSH'] = $this->calculation_values['AABS'] / 2;
        $this->calculation_values['AABSL'] = $this->calculation_values['AABS'] / 2;
    }

    private function THICKNESS()
    {
        $this->calculation_values['THE'] = $this->calculation_values['TU3'];
        $this->calculation_values['THA'] = $this->calculation_values['TU6'];
        $this->calculation_values['THC'] = $this->calculation_values['TV6'];

        
        if ($this->calculation_values['TU2'] < 2.1 || $this->calculation_values['TU2']==4)
            $this->calculation_values['IDE'] = $this->calculation_values['ODE'] - ((2.0 * ($this->calculation_values['THE'] + 0.1)) / 1000.0);
        else
            $this->calculation_values['IDE'] = $this->calculation_values['ODE'] - ((2.0 * $this->calculation_values['THE']) / 1000.0);

        if ($this->calculation_values['TU5'] < 2.1|| $this->calculation_values['TU5'] == 6)
            $this->calculation_values['IDA'] = $this->calculation_values['ODA'] - ((2.0 * ($this->calculation_values['THA'] + 0.1)) / 1000.0);
        else
            $this->calculation_values['IDA'] = $this->calculation_values['ODA'] - ((2.0 * $this->calculation_values['THA']) / 1000.0);

        if ($this->calculation_values['TV5'] == 1 || $this->calculation_values['TV5'] == 2 || $this->calculation_values['TV5'] == 0 || $this->calculation_values['TV5'] == 4)
            $this->calculation_values['IDC'] = $this->calculation_values['ODC'] - ((2.0 * ($this->calculation_values['THC'] + 0.1)) / 1000.0);
        else
            $this->calculation_values['IDC'] = $this->calculation_values['ODC'] - ((2.0 * $this->calculation_values['THC']) / 1000.0);    
    }

    public function WATERPROP()
    {
        $vam_base = new VamBaseController();
        
        if ($this->calculation_values['GL'] == 2)
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

        $model_number =(int)$this->calculation_values['MODEL'];

        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$this->model_code)
                                        ->where('min_model','<=',$model_number)->where('max_model','>',$model_number)->first();

        $chiller_options = $chiller_metallurgy_options->chillerOptions;
        
        $evaporator_option = $chiller_options->where('type', 'eva')->where('value',$this->calculation_values['TU2'])->first();
        $absorber_option = $chiller_options->where('type', 'abs')->where('value',$this->calculation_values['TU5'])->first();
        $condenser_option = $chiller_options->where('type', 'con')->where('value',$this->calculation_values['TV5'])->first();

        $this->calculation_values['VAMIN'] = $absorber_option->metallurgy->abs_min_velocity;          
        $this->calculation_values['VAMAX'] = $absorber_option->metallurgy->abs_max_velocity;
        $this->calculation_values['VCMIN'] = $condenser_option->metallurgy->con_min_velocity;
        $this->calculation_values['VCMAX'] = $condenser_option->metallurgy->con_max_velocity;
        $this->calculation_values['VEMIN'] = $evaporator_option->metallurgy->eva_min_velocity;
        $this->calculation_values['VEMAX'] = $evaporator_option->metallurgy->eva_max_velocity;

        $this->calculation_values['VELEVA'] = 0;

        $this->calculation_values['TAP'] = 0;
        do
        {
            $this->calculation_values['TAP'] = $this->calculation_values['TAP'] + 1;
            $this->calculation_values['VA'] = $this->calculation_values['GCW'] / (((3600 * 3.141593 * $this->calculation_values['IDA'] * $this->calculation_values['IDA']) / 4.0) * ($this->calculation_values['TNAA'] / $this->calculation_values['TAP']));

        } while ($this->calculation_values['VA'] < $this->calculation_values['VAMAX']);


        if ($this->calculation_values['VA'] > $this->calculation_values['VAMAX'] && $this->calculation_values['TAP'] != 1)
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

                if ($this->calculation_values['VEA'] > 1.8)                        // 14 FEB 2012
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
                    if ($this->calculation_values['VEA'] > 2.78)                   // 14 FEB 2012
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
            $this->calculation_values['VPE2'] = (0.5 * $this->calculation_values['GCHW'] * 4) / (3.141593 * $this->calculation_values['PIDE2'] * $this->calculation_values['PIDE2'] * 3600);
            $this->calculation_values['VPBR'] = (0.5 * $this->calculation_values['GCHW'] * 4) / (3.141593 * $this->calculation_values['PIDE1'] * $this->calculation_values['PIDE1'] * 3600);
        }
        else
        {
            $this->calculation_values['VPE2'] = $this->calculation_values['VPBR'] = 0;
        }

        //PIPE1

        // double $VPE1 = ($this->calculation_values['GCHW'] * 4) / (3.141593 * $this->calculation_values['PIDE1'] * $this->calculation_values['PIDE1'] * 3600);            //VELOCITY IN PIPE1
        $TME = ($this->calculation_values['TCHW11'] + $this->calculation_values['TCHW12']) / 2.0;

        if ($this->calculation_values['GL'] == 3)
        {
            $this->calculation_values['CHGLY_ROW22'] = $vam_base->PG_ROW($TME, $this->calculation_values['CHGLY']);
            $this->calculation_values['CHGLY_VIS22'] = $vam_base->PG_VISCOSITY($TME, $this->calculation_values['CHGLY']) / 1000;
        }
        else
        {
            $this->calculation_values['CHGLY_ROW22'] = $vam_base->EG_ROW($TME, $this->calculation_values['CHGLY']);
            $this->calculation_values['CHGLY_VIS22'] = $vam_base->EG_VISCOSITY($TME, $this->calculation_values['CHGLY']) / 1000;
        }

        $REPE1 = ($this->calculation_values['PIDE1'] * $VPE1 * $this->calculation_values['CHGLY_ROW22']) / $this->calculation_values['CHGLY_VIS22'];

        if ($this->calculation_values['MODEL'] > 300)
        {
            $this->calculation_values['REPE2'] = ($this->calculation_values['PIDE2'] * $this->calculation_values['VPE2'] * $this->calculation_values['CHGLY_ROW22']) / $this->calculation_values['CHGLY_VIS22'];
            $this->calculation_values['REBR'] = ($this->calculation_values['PIDE1'] * $this->calculation_values['VPBR'] * $this->calculation_values['CHGLY_ROW22']) / $this->calculation_values['CHGLY_VIS22'];          //REYNOLDS NO IN PIPE1
        }
        else
        {
            $this->calculation_values['REPE2'] = $this->calculation_values['REBR'] = 0;
        }

        $FF1 = 1.325 / pow(log((0.0457 / (3.7 * $this->calculation_values['PIDE1'] * 1000)) + (5.74 / pow($REPE1, 0.9))), 2);       //FRICTION FACTOR CAL

        if ($this->calculation_values['MODEL'] > 300)
        {
            $this->calculation_values['FF2'] = 1.325 / pow(log((0.0457 / (3.7 * $this->calculation_values['PIDE2'] * 1000)) + (5.74 / pow($this->calculation_values['REPE2'], 0.9))), 2);
            $this->calculation_values['FF3'] = 1.325 / pow(log((0.0457 / (3.7 * $this->calculation_values['PIDE1'] * 1000)) + (5.74 / pow($this->calculation_values['REBR'], 0.9))), 2);
        }
        else
        {
            $this->calculation_values['FF2'] = $this->calculation_values['FF3'] = 0;
        }


        $FL1 = ($FF1 * ($this->calculation_values['SL1'] + $this->calculation_values['SL8']) / $this->calculation_values['PIDE1']) * ($VPE1 * $VPE1 / (2 * 9.81));

        if ($this->calculation_values['MODEL'] > 300)
        {
            $this->calculation_values['FL2'] = ($this->calculation_values['FF2'] * ($this->calculation_values['SL3'] + $this->calculation_values['SL4'] + $this->calculation_values['SL5'] + $this->calculation_values['SL6']) / $this->calculation_values['PIDE2']) * ($this->calculation_values['VPE2'] * $this->calculation_values['VPE2'] / (2 * 9.81));
            $this->calculation_values['FL3'] = ($this->calculation_values['FF3'] * ($this->calculation_values['SL2'] + $this->calculation_values['SL7']) / $this->calculation_values['PIDE1']) * ($this->calculation_values['VPBR'] * $this->calculation_values['VPBR'] / (2 * 9.81));
            $this->calculation_values['FL4'] = (2 * $this->calculation_values['FT1'] * 20 * $this->calculation_values['VPBR'] * $this->calculation_values['VPBR'] / (2 * 9.81)) + (2 * $this->calculation_values['FT2'] * 60 * $this->calculation_values['VPE2'] * $this->calculation_values['VPE2'] / (2 * 9.81)) + (2 * $this->calculation_values['FT2'] * 14 * $this->calculation_values['VPE2'] * $this->calculation_values['VPE2'] / (2 * 9.81));
            $this->calculation_values['FL5'] = ($this->calculation_values['VPE2'] * $this->calculation_values['VPE2'] / (2 * 9.81)) + (0.5 * $this->calculation_values['VPE2'] * $this->calculation_values['VPE2'] / (2 * 9.81));
        }
        else
        {
            $this->calculation_values['FL2'] = $this->calculation_values['FL3'] = $this->calculation_values['FL4'] = 0;
            $this->calculation_values['FL5'] = ($VPE1 * $VPE1 / (2 * 9.81)) + (0.5 * $VPE1 * $VPE1 / (2 * 9.81));
        }

        $FLP = $FL1 + $this->calculation_values['FL2'] + $this->calculation_values['FL3'] + $this->calculation_values['FL4'] + $this->calculation_values['FL5'];      //EVAPORATOR PIPE LOSS

        $RE = ($this->calculation_values['VEA'] * $this->calculation_values['IDE'] * $this->calculation_values['CHGLY_ROW22']) / $this->calculation_values['CHGLY_VIS22'];            //REYNOLDS NO IN TUBES

        if (($this->calculation_values['MODEL'] < 1200 && $this->calculation_values['TU2'] == 3))
        {
            $this->calculation_values['F'] = 1.325 / pow(log((1.53 / (3.7 * $this->calculation_values['IDE'] * 1000)) + (5.74 / pow($RE, 0.9))), 2);
            $this->calculation_values['FE1'] = $this->calculation_values['F'] * $this->calculation_values['LE'] * $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (2 * 9.81 * $this->calculation_values['IDE']);
        }
        else if ($this->calculation_values['MODEL'] > 1200 && $this->calculation_values['TU2'] == 3)                                         //06/11/2017   Changed for SS FInned
        {
            $this->calculation_values['F'] = (1.325 / pow(log((1.53 / (3.7 * $this->calculation_values['IDE'] * 1000)) + (5.74 / pow($RE, 0.9))), 2)) * ((-0.0315 * $this->calculation_values['VEA']) + 0.85);
            $this->calculation_values['FE1'] = $this->calculation_values['F'] * $this->calculation_values['LE'] * $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (2 * 9.81 * $this->calculation_values['IDE']);

        }
        else if (($this->calculation_values['MODEL'] < 1200 && ($this->calculation_values['TU2'] == 1 || $this->calculation_values['TU2'] == 2 || $this->calculation_values['TU2'] == 0) || ($this->calculation_values['MODEL'] < 1200 && $this->calculation_values['TU2'] == 4)))                    // 12% AS PER EXPERIMENTATION      
        {
            $this->calculation_values['F'] = (0.0014 + (0.137 / pow($RE, 0.32))) * 1.12;
            $this->calculation_values['FE1'] = 2 * $this->calculation_values['F'] * $this->calculation_values['LE'] * $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (9.81 * $this->calculation_values['IDE']);
        }
        else if ($this->calculation_values['MODEL'] > 1200 && ($this->calculation_values['TU2'] == 1 || $this->calculation_values['TU2'] == 2 || $this->calculation_values['TU2'] == 4 || $this->calculation_values['TU2'] == 0))
        {
            $this->calculation_values['F'] = 0.0014 + (0.137 / pow($RE, 0.32));
            $this->calculation_values['FE1'] = 2 * $this->calculation_values['F'] * $this->calculation_values['LE'] * $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (9.81 * $this->calculation_values['IDE']);
        }
        else
        {
            $this->calculation_values['F'] = 0.0014 + (0.125 / pow($RE, 0.32));
            $this->calculation_values['FE1'] = 2 * $this->calculation_values['F'] * $this->calculation_values['LE'] * $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (9.81 * $this->calculation_values['IDE']);
        }

        $FE2 = $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (4 * 9.81);
        $FE3 = $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (2 * 9.81);
        $FE4 = (($this->calculation_values['FE1'] + $FE2 + $FE3) * $this->calculation_values['TP']) * 2;      //EVAPORATOR TUBE LOSS FOR DOUBLE ABS
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

        if ($this->calculation_values['PST1'] < 6.01)
        {

            if ($this->calculation_values['MODEL'] == 310)
                $this->calculation_values['AHTG'] = 29.1;
            if ($this->calculation_values['MODEL'] == 350)
                $this->calculation_values['AHTG'] = 31.3;
            if ($this->calculation_values['MODEL'] == 410)
                $this->calculation_values['AHTG'] = 37.4;
            if ($this->calculation_values['MODEL'] == 470)
                $this->calculation_values['AHTG'] = 46.1;
            if ($this->calculation_values['MODEL'] == 530)
                $this->calculation_values['AHTG'] = 50.3;
            if ($this->calculation_values['MODEL'] == 580)
                $this->calculation_values['AHTG'] = 54.2;
            if ($this->calculation_values['MODEL'] == 630)
                $this->calculation_values['AHTG'] = 64.0;
            if ($this->calculation_values['MODEL'] == 710)
                $this->calculation_values['AHTG'] = 67.4;
            if ($this->calculation_values['MODEL'] == 760)
                $this->calculation_values['AHTG'] = 77.3;
            if ($this->calculation_values['MODEL'] == 810)
                $this->calculation_values['AHTG'] = 84.3;
            if ($this->calculation_values['MODEL'] == 900)
                $this->calculation_values['AHTG'] = 89.8;
            if ($this->calculation_values['MODEL'] == 1010)
                $this->calculation_values['AHTG'] = 110.3;
            if ($this->calculation_values['MODEL'] == 1130)
                $this->calculation_values['AHTG'] = 117.6;
            if ($this->calculation_values['MODEL'] == 1260)
                $this->calculation_values['AHTG'] = 136.3;
            if ($this->calculation_values['MODEL'] == 1380)
                $this->calculation_values['AHTG'] = 146.1;
            if ($this->calculation_values['MODEL'] == 1560)
                $this->calculation_values['AHTG'] = 175.9;
            if ($this->calculation_values['MODEL'] == 1690)
                $this->calculation_values['AHTG'] = 186.3;
            if ($this->calculation_values['MODEL'] == 1890)
                $this->calculation_values['AHTG'] = 211.6;
            if ($this->calculation_values['MODEL'] == 2130)
                $this->calculation_values['AHTG'] = 224.2;
            if ($this->calculation_values['MODEL'] == 2270)
                $this->calculation_values['AHTG'] = 253.9;
            if ($this->calculation_values['MODEL'] == 2560)
                $this->calculation_values['AHTG'] = 269.0;
        }

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

        if ($this->calculation_values['TUU'] != 'ari')
        {
            $this->calculation_values['ALTGACT'] = $this->calculation_values['ALTG'];
            do
            {
                $this->calculation_values['TSTOUT'] = 85;
                do
                {
                    if ($this->calculation_values['TCHW2L'] < 2 && $this->calculation_values['TSTOUT'] < 60)
                    {
                        $this->calculation_values['FR1'] = $this->calculation_values['FR1'] - 0.01;
                    }
                    else
                    {
                        if ($this->calculation_values['TSTOUT'] < 70)
                        {
                            $this->calculation_values['FR1'] = $this->calculation_values['FR1'] - 0.01;
                        }
                    }
                    $this->EVAPORATOR();
                    $this->HTG();
                    if ($this->calculation_values['TCHW2L'] <= 2 && $this->calculation_values['TSTOUT'] >= 60)
                        break;
                    if ($this->calculation_values['TCHW2L'] > 2 && $this->calculation_values['TSTOUT'] > 70)
                        break;

                } while ($this->calculation_values['TSTOUT'] < 70);
                if (($this->calculation_values['ALTG'] / $this->calculation_values['ALTGACT']) > 0.45)
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
            $this->calculation_values['ALTGACT']=$this->calculation_values['ALTG'];

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
                $this->calculation_values['TSTOUT'] = 85;
                do
                {
                    if ($this->calculation_values['TSTOUT'] < 70)
                    {
                        $this->calculation_values['FR1'] = $this->calculation_values['FR1'] - 0.01;
                    }
                    $this->calculation_values['TCHW1H'] = $this->calculation_values['TCHW11'];
                    $this->calculation_values['TCHW2L'] = $this->calculation_values['TCHW12'];
                    $this->calculation_values['TCW1H'] = $this->calculation_values['TCW11'];
                    $this->EVAPORATOR();
                    $this->HTG();

                } while ($this->calculation_values['TSTOUT'] < 70);
                if(($this->calculation_values['ALTG']/$this->calculation_values['ALTGACT'])>0.45)
                {
                    if(($this->calculation_values['T13']-$this->calculation_values['THW2'])<2)
                    {
                        $this->calculation_values['ALTG']=$this->calculation_values['ALTG']*0.95;
                    }
                }
                else
                {
                    $this->calculation_values['SIDEARM']=1;
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
                do
                {
                    if ($this->calculation_values['TSTOUT'] < 70)
                    {
                        $this->calculation_values['FR1'] = $this->calculation_values['FR1'] - 0.01;
                    }
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
                    $this->HTG();

                } while ($this->calculation_values['TSTOUT'] < 70);
                $t12[$a] = $this->calculation_values['T1'];
                $t3n2[$a] = $this->calculation_values['T3'];
            } while ((abs($t11[$a] - $t12[$a]) > 0.005) || (abs($t3n1[$a] - $t3n2[$a]) > 0.005));
        }
    }

    public function DERATE_KEVA()
    {
        $GLY_VIS = 0;
        $GLY_TCON = 0;
        $GLY_ROW = 0;
        $GLY_SPHT = 0;
        $RE = 0;
        $PR = 0;
        $NU1 = 0;
        $HI = 0;
        $HI1 = 0;
        $VEVA = 0;
        $R = 0;
        $R1 = 0;
        $HO = 0;

        $vam_base = new VamBaseController();

        $GLY_VIS = $vam_base->EG_VISCOSITY($this->calculation_values['TCHW12'], 0) / 1000;
        $GLY_TCON = $vam_base->EG_THERMAL_CONDUCTIVITY($this->calculation_values['TCHW12'], 0);
        $GLY_ROW = $vam_base->EG_ROW($this->calculation_values['TCHW12'], 0);
        $GLY_SPHT = $vam_base->EG_SPHT($this->calculation_values['TCHW12'], 0) * 1000;

        if (($this->calculation_values['MODEL'] < 1200 && $this->calculation_values['TU2'] == 3))
        {
            $VEVA = 0.7;
        }
        else if ($this->calculation_values['MODEL'] > 1200 && $this->calculation_values['TU2'] == 3)
        {
            $VEVA = 0.75;
        }
        else
        {
            $VEVA = 1.5;
        }
        $RE = $GLY_ROW * $VEVA * $this->calculation_values['IDE'] / $GLY_VIS;
        $PR = $GLY_VIS * $GLY_SPHT / $GLY_TCON;
        $NU1 = 0.023 * pow($RE, 0.8) * pow($PR, 0.3);
        $HI1 = ($NU1 * $GLY_TCON / $this->calculation_values['IDE']) * 3600 / 4187;

        if ($this->calculation_values['TU2'] == 2.0 || $this->calculation_values['TU2'] == 0)
            $R1 = log($this->calculation_values['ODE'] / $this->calculation_values['IDE']) * $this->calculation_values['ODE'] / (2 * 340);
        if ($this->calculation_values['TU2'] == 1.0)
            $R1 = log($this->calculation_values['ODE'] / $this->calculation_values['IDE']) * $this->calculation_values['ODE'] / (2 * 37);
        if ($this->calculation_values['TU2'] == 3.0 || $this->calculation_values['TU2'] == 4.0)
            $R1 = log($this->calculation_values['ODE'] / $this->calculation_values['IDE']) * $this->calculation_values['ODE'] / (2 * 21);
        if ($this->calculation_values['TU2'] == 5.0)
            $R1 = log($this->calculation_values['ODE'] / $this->calculation_values['IDE']) * $this->calculation_values['ODE'] / (2 * 15);
        $HO = 1 / (1 / $this->calculation_values['KEVA'] - ($this->calculation_values['ODE'] / ($HI1 * $this->calculation_values['IDE'])) - $R1);
        if ($this->calculation_values['VEA'] < $VEVA)
        {
            $RE = $this->calculation_values['CHGLY_ROW12'] * $this->calculation_values['VEA'] * $this->calculation_values['IDE'] / $this->calculation_values['CHGLY_VIS12'];
        }
        else
        {
            $RE = $this->calculation_values['CHGLY_ROW12'] * $VEVA * $this->calculation_values['IDE'] / $this->calculation_values['CHGLY_VIS12'];
        }
        $PR = $this->calculation_values['CHGLY_VIS12'] * $this->calculation_values['CHGLY_SPHT12'] / $this->calculation_values['CHGLY_TCON12'];
        $NU1 = 0.023 * pow($RE, 0.8) * pow($PR, 0.3);
        $HI = ($NU1 * $this->calculation_values['CHGLY_TCON12'] / $this->calculation_values['IDE']) * 3600 / 4187;

        //if ($this->calculation_values['TU2'] < 2.1 && $this->calculation_values['MODEL'] < 750 && $this->calculation_values['VELEVA'] == 0)
        //{
        //    $HI = $HI * 2;
        //}
        $this->calculation_values['KEVA'] = 1 / (($this->calculation_values['ODE'] / ($HI * $this->calculation_values['IDE'])) + $R1 + 1 / $HO);
        if ($this->calculation_values['CHGLY'] != 0)
        {
            $this->calculation_values['KEVA'] = $this->calculation_values['KEVA'] * 0.99;
        }
    }

    public function DERATE_KABSH()
    {
        $GLY_VIS = 0;
        $GLY_TCON = 0;
        $GLY_ROW = 0;
        $GLY_SPHT = 0;
        $RE = 0;
        $PR = 0;
        $NU1 = 0;
        $HI = 0;
        $HI1 = 0;
        $VABS = 0;
        $R = 0;
        $R1 = 0;
        $HO = 0;

        $vam_base = new VamBaseController();
        
        $GLY_VIS = $vam_base->EG_VISCOSITY($this->calculation_values['TCW11'], 0) / 1000;
        $GLY_TCON = $vam_base->EG_THERMAL_CONDUCTIVITY($this->calculation_values['TCW11'], 0);
        $GLY_ROW = $vam_base->EG_ROW($this->calculation_values['TCW11'], 0);
        $GLY_SPHT = $vam_base->EG_SPHT($this->calculation_values['TCW11'], 0) * 1000;

        if ($this->calculation_values['MODEL'] < 1200)
        {
            $VABS = 1.5;
        }
        else
        {
            $VABS = 1.5;
        }
        $RE = $GLY_ROW * $VABS * $this->calculation_values['IDA'] / $GLY_VIS;
        $PR = $GLY_VIS * $GLY_SPHT / $GLY_TCON;
        $NU1 = 0.023 * pow($RE, 0.8) * pow($PR, 0.4);
        $HI1 = ($NU1 * $GLY_TCON / $this->calculation_values['IDA']) * 3600 / 4187;
        //$R = log($this->calculation_values['ODA'] / $this->calculation_values['IDA']) * $this->calculation_values['ODA'] / (2 * 340);
        //$HO = 1 / (1 / $this->calculation_values['KABS'] - ($this->calculation_values['ODA'] / ($HI1 * $this->calculation_values['IDA'])) - $R);

        if ($this->calculation_values['TU5'] == 2.0 || $this->calculation_values['TU5'] == 4.0 || $this->calculation_values['TU5'] == 0)
            $R1 = log($this->calculation_values['ODA'] / $this->calculation_values['IDA']) * $this->calculation_values['ODA'] / (2 * 340);
        else if ($this->calculation_values['TU5'] == 1.0 || $this->calculation_values['TU5'] == 3.0)
            $R1 = log($this->calculation_values['ODA'] / $this->calculation_values['IDA']) * $this->calculation_values['ODA'] / (2 * 37);
        else if ($this->calculation_values['TU5'] == 5 || $this->calculation_values['TU5'] == 6)
            $R1 = log($this->calculation_values['ODA'] / $this->calculation_values['IDA']) * $this->calculation_values['ODA'] / (2 * 21);
        else if ($this->calculation_values['TU5'] == 7.0)
            $R1 = log($this->calculation_values['ODA'] / $this->calculation_values['IDA']) * $this->calculation_values['ODA'] / (2 * 15);
        $HO = 1 / (1 / $this->calculation_values['KABS'] - ($this->calculation_values['ODA'] / ($HI1 * $this->calculation_values['IDA'])) - $R1);

        if ($this->calculation_values['VAH'] < $VABS)
        {
            $RE = $this->calculation_values['COGLY_ROWH1'] * $this->calculation_values['VAH'] * $this->calculation_values['IDA'] / $this->calculation_values['COGLY_VISH1'];
        }
        else
        {
            $RE = $this->calculation_values['COGLY_ROWH1'] * $VABS * $this->calculation_values['IDA'] / $this->calculation_values['COGLY_VISH1'];
        }
        $PR = $this->calculation_values['COGLY_VISH1'] * $this->calculation_values['COGLY_SPHT1'] / $this->calculation_values['COGLY_TCONH1'];
        $NU1 = 0.023 * pow($RE, 0.8) * pow($PR, 0.4);
        $HI = ($NU1 * $this->calculation_values['COGLY_TCONH1'] / $this->calculation_values['IDA']) * 3600 / 4187;

        $this->calculation_values['KABSH'] = 1 / (($this->calculation_values['ODA'] / ($HI * $this->calculation_values['IDA'])) + $R1 + 1 / $HO);
        if ($this->calculation_values['COGLY'] != 0)
        {
            $this->calculation_values['KABSH'] = $this->calculation_values['KABSH'] * 0.99;
        }
    }

    public function DERATE_KABSL()
    {
        
        $GLY_VIS = 0;
        $GLY_TCON = 0;
        $GLY_ROW = 0;
        $GLY_SPHT = 0;
        $RE = 0;
        $PR = 0;
        $NU1 = 0;
        $HI = 0;
        $HI1 = 0;
        $VABS = 0;
        $R = 0;
        $R1 = 0;
        $HO = 0;

        $vam_base = new VamBaseController();

        $GLY_VIS = $vam_base->EG_VISCOSITY($this->calculation_values['TCW11'], 0) / 1000;
        $GLY_TCON = $vam_base->EG_THERMAL_CONDUCTIVITY($this->calculation_values['TCW11'], 0);
        $GLY_ROW = $vam_base->EG_ROW($this->calculation_values['TCW11'], 0);
        $GLY_SPHT = $vam_base->EG_SPHT($this->calculation_values['TCW11'], 0) * 1000;

        if ($this->calculation_values['MODEL'] <1200)
        {
            $VABS = 1.5;
        }
        else
        {
            $VABS = 1.5;
        }

        $RE = $GLY_ROW * $VABS * $this->calculation_values['IDA'] / $GLY_VIS;
        $PR = $GLY_VIS * $GLY_SPHT / $GLY_TCON;
        $NU1 = 0.023 * pow($RE, 0.8) * pow($PR, 0.4);
        $HI1 = ($NU1 * $GLY_TCON / $this->calculation_values['IDA']) * 3600 / 4187;
        //$R = log($this->calculation_values['ODA'] / $this->calculation_values['IDA']) * $this->calculation_values['ODA'] / (2 * 340);
       // $HO = 1 / (1 / $this->calculation_values['KABS'] - ($this->calculation_values['ODA'] / ($HI1 * $this->calculation_values['IDA'])) - $R);

        if ($this->calculation_values['TU5'] == 2.0 || $this->calculation_values['TU5'] == 4.0 || $this->calculation_values['TU5'] == 0)
            $R1 = log($this->calculation_values['ODA'] / $this->calculation_values['IDA']) * $this->calculation_values['ODA'] / (2 * 340);
        else if ($this->calculation_values['TU5'] == 1.0 || $this->calculation_values['TU5'] == 3.0)
            $R1 = log($this->calculation_values['ODA'] / $this->calculation_values['IDA']) * $this->calculation_values['ODA'] / (2 * 37);
        else if ($this->calculation_values['TU5'] == 5 || $this->calculation_values['TU5'] == 6)
            $R1 = log($this->calculation_values['ODA'] / $this->calculation_values['IDA']) * $this->calculation_values['ODA'] / (2 * 21);
        else if ($this->calculation_values['TU5'] == 7.0)
            $R1 = log($this->calculation_values['ODA'] / $this->calculation_values['IDA']) * $this->calculation_values['ODA'] / (2 * 15);
        $HO = 1 / (1 / $this->calculation_values['KABS'] - ($this->calculation_values['ODA'] / ($HI1 * $this->calculation_values['IDA'])) - $R1);
        if ($this->calculation_values['VAL'] < $VABS)
        {
            $RE = $this->calculation_values['COGLY_ROWH1'] * $this->calculation_values['VAL'] * $this->calculation_values['IDA'] / $this->calculation_values['COGLY_VISH1'];
        }
        else
        {
            $RE = $this->calculation_values['COGLY_ROWH1'] * $VABS * $this->calculation_values['IDA'] / $this->calculation_values['COGLY_VISH1'];
        }
        $PR = $this->calculation_values['COGLY_VISH1'] * $this->calculation_values['COGLY_SPHT1'] / $this->calculation_values['COGLY_TCONH1'];
        $NU1 = 0.023 * pow($RE, 0.8) * pow($PR, 0.4);
        $HI = ($NU1 * $this->calculation_values['COGLY_TCONH1'] / $this->calculation_values['IDA']) * 3600 / 4187;

        $this->calculation_values['KABSL'] = 1 / (($this->calculation_values['ODA'] / ($HI * $this->calculation_values['IDA'])) + $R1 + 1 / $HO);
        if ($this->calculation_values['COGLY'] != 0)
        {
            $this->calculation_values['KABSL'] = $this->calculation_values['KABSL'] * 0.99;
        }
    }


    public function DERATE_KCON()
    {
        
        $GLY_VIS = 0;
        $GLY_TCON = 0;
        $GLY_ROW = 0;
        $GLY_SPHT = 0;
        $RE = 0;
        $PR = 0;
        $NU1 = 0;
        $HI = 0;
        $HI1 = 0;
        $VCON = 0;
        $R = 0;
        $R1 = 0;
        $HO = 0;

        $vam_base = new VamBaseController();

        $GLY_VIS = $vam_base->EG_VISCOSITY($this->calculation_values['TCW11'], 0) / 1000;
        $GLY_TCON = $vam_base->EG_THERMAL_CONDUCTIVITY($this->calculation_values['TCW11'], 0);
        $GLY_ROW = $vam_base->EG_ROW($this->calculation_values['TCW11'], 0);
        $GLY_SPHT = $vam_base->EG_SPHT($this->calculation_values['TCW11'], 0) * 1000;

        if ($this->calculation_values['MODEL'] < 950)
        {
            $VCON = 1.5;
        }
        else
        {
            $VCON = 1.5;
        }
        $RE = $GLY_ROW * $VCON * $this->calculation_values['IDC'] / $GLY_VIS;
        $PR = $GLY_VIS * $GLY_SPHT / $GLY_TCON;
        $NU1 = 0.023 * pow($RE, 0.8) * pow($PR, 0.4);
        $HI1 = ($NU1 * $GLY_TCON / $this->calculation_values['IDC']) * 3600 / 4187;
        //$R = log($this->calculation_values['ODC'] / $this->calculation_values['IDC']) * $this->calculation_values['ODC'] / (2 * 340);
        //$HO = 1 / (1 / $this->calculation_values['KCON'] - ($this->calculation_values['ODC'] / ($HI1 * $this->calculation_values['IDC'])) - $R);

        if ($this->calculation_values['TV5'] == 2.0 || $this->calculation_values['TV5'] == 0 || $this->calculation_values['TV5'] == 4 )
            $R1 = log($this->calculation_values['ODC'] / $this->calculation_values['IDC']) * $this->calculation_values['ODC'] / (2 * 340);
        if ($this->calculation_values['TV5'] == 1.0)
            $R1 = log($this->calculation_values['ODC'] / $this->calculation_values['IDC']) * $this->calculation_values['ODC'] / (2 * 37);
        if ($this->calculation_values['TV5'] == 3.0)
            $R1 = log($this->calculation_values['ODC'] / $this->calculation_values['IDC']) * $this->calculation_values['ODC'] / (2 * 21);
        if ($this->calculation_values['TV5'] == 5.0)
            $R1 = log($this->calculation_values['ODC'] / $this->calculation_values['IDC']) * $this->calculation_values['ODC'] / (2 * 15);
        $HO = 1 / (1 / $this->calculation_values['KCON'] - ($this->calculation_values['ODC'] / ($HI1 * $this->calculation_values['IDC'])) - $R1);

        if ($this->calculation_values['VC'] < $VCON)
        {
            $RE = $this->calculation_values['COGLY_ROWH1'] * $this->calculation_values['VC'] * $this->calculation_values['IDC'] / $this->calculation_values['COGLY_VISH1'];
        }
        else
        {
            $RE = $this->calculation_values['COGLY_ROWH1'] * $VCON * $this->calculation_values['IDC'] / $this->calculation_values['COGLY_VISH1'];
        }
        $PR = $this->calculation_values['COGLY_VISH1'] * $this->calculation_values['COGLY_SPHT1'] / $this->calculation_values['COGLY_TCONH1'];
        $NU1 = 0.023 * pow($RE, 0.8) * pow($PR, 0.4);
        $HI = ($NU1 * $this->calculation_values['COGLY_TCONH1'] / $this->calculation_values['IDC']) * 3600 / 4187;
        $this->calculation_values['KCON'] = 1 / (($this->calculation_values['ODC'] / ($HI * $this->calculation_values['IDC'])) + $R1 + 1 / $HO);
        if ($this->calculation_values['COGLY'] != 0)
        {
            $this->calculation_values['KCON'] = $this->calculation_values['KCON'] * 0.99;
        }
    }


    public function EVAPORATOR()
    {
        $this->calculation_values['i'] = 0; $this->calculation_values['q'] = 0; $this->calculation_values['r'] = 0;

        $this->calculation_values['LMTDEVA'] = ($this->calculation_values['TON'] * 3024) / ($this->calculation_values['AEVA'] * $this->calculation_values['UEVA']);
        $this->calculation_values['T1'] = $this->calculation_values['TCHW2L'] - ($this->calculation_values['TCHW1H'] - $this->calculation_values['TCHW2L']) / (exp(($this->calculation_values['TCHW1H'] - $this->calculation_values['TCHW2L']) / $this->calculation_values['LMTDEVA']) - 1); // OVERALL LMTD & $this->calculation_values['T1'] CALCULATED FOR CHW CIRCUIT FOR ARI 

        $this->calculation_values['GCHW'] = $this->calculation_values['TON'] * 3024 / (($this->calculation_values['TCHW1H'] - $this->calculation_values['TCHW2L']) * $this->calculation_values['CHGLY_ROW12'] * $this->calculation_values['CHGLY_SPHT12'] / 4187);
        $this->calculation_values['GDIL'] = 70 * $this->calculation_values['MODEL1'];
        $this->calculation_values['QEVA'] = $this->calculation_values['TON'] * 3024;
        
       // $this->calculation_values['AGREF4'] = $this->calculation_values['HEATCAP'] * 860 / 572;
        $this->calculation_values['AGREF4'] = $this->calculation_values['HEATCAP'] / 572;
        $this->calculation_values['COPE'] = ((((5.4 * $this->calculation_values['TON']) - $this->calculation_values['AGREF4']) * 1.4) + ($this->calculation_values['AGREF4'] * 0.7)) / (5.4 * $this->calculation_values['TON']);

        $QAB = ($this->calculation_values['QEVA'] * (1 + 1 / $this->calculation_values['COPE']) - ($this->calculation_values['HEATCAP'])) * 0.8;
        $QCO = ($this->calculation_values['QEVA'] * (1 + 1 / $this->calculation_values['COPE']) - ($this->calculation_values['HEATCAP'])) * 0.2;

        $this->calculation_values['ATCW2'] = $this->calculation_values['TCW1H'] + $QAB / ($this->calculation_values['GCW'] * 1000);
        $ATCW3 = $this->calculation_values['ATCW2'] + $QCO / ($this->calculation_values['GCW'] * 1000);
        $LMTDCO = $QCO / ($this->calculation_values['KCON'] * $this->calculation_values['ACON']);
        $this->calculation_values['AT3'] = $ATCW3 + ($ATCW3 - $this->calculation_values['ATCW2']) / (exp(($ATCW3 - $this->calculation_values['ATCW2']) / $LMTDCO) - 1);

        /************** Int Chw temp assump **************/

        $this->calculation_values['DT'] = $this->calculation_values['TCHW1H'] - $this->calculation_values['TCHW2L'];

        if ($this->calculation_values['TCW11'] < 34.01)
        {
            if (((($this->calculation_values['TON'] / $this->calculation_values['MODEL']) > 0.8 && ($this->calculation_values['TON'] / $this->calculation_values['MODEL']) < 1.01) || ($this->calculation_values['TON'] / $this->calculation_values['MODEL']) < 0.66) && $this->calculation_values['DT'] <= 13)
            {
                $ATCHW2H = ($this->calculation_values['TCHW1H'] + $this->calculation_values['TCHW2L']) / 2;
            }
            else
            {
                $ATCHW2H = (($this->calculation_values['TCHW1H'] + $this->calculation_values['TCHW2L']) / 2) + ((-0.0082 * $this->calculation_values['DT'] * $this->calculation_values['DT']) + (0.0973 * $this->calculation_values['DT']) - 0.2802);
            }
        }
        else
        {
            $ATCHW2H = (($this->calculation_values['TCHW1H'] + $this->calculation_values['TCHW2L']) / 2) + ((-0.0047 * $this->calculation_values['DT'] * $this->calculation_values['DT']) - (0.0849 * $this->calculation_values['DT']) + 0.0412);
        }


        /************** Int Chw temp assump **************/
        $vam_base = new VamBaseController();
        $err1 = array();
        $ferr1 = array();
        $tchw2h = array();

        $ferr1[0] = 1;
        $p = 1;
        while (abs($ferr1[$p - 1]) > 0.1)
        {
            if ($p == 1)
            {
                if ($this->calculation_values['DT'] > 14)
                {
                    $tchw2h[$p] = $ATCHW2H;    // -2.5;
                }
                else
                {
                    $tchw2h[$p] = $ATCHW2H + 0.1;
                }
            }
            if ($p == 2)
            {
                $tchw2h[$p] = $tchw2h[$p - 1] - 0.1;
                if ($this->calculation_values['MODEL'] == 2300 || $this->calculation_values['MODEL'] == 2450)
                {
                    $tchw2h[$p] = $tchw2h[$p - 1] + 0.01;//- 0.1;
                }
            }
            if ($p >= 3)
            {
                if (($this->calculation_values['TON'] / $this->calculation_values['MODEL']) < 0.5)
                {
                    $tchw2h[$p] = $tchw2h[$p - 1] + $err1[$p - 1] * ($tchw2h[$p - 1] - $tchw2h[$p - 2]) / ($err1[$p - 2] - $err1[$p - 1]) / 4;
                }
                else
                {
                    $tchw2h[$p] = $tchw2h[$p - 1] + $err1[$p - 1] * ($tchw2h[$p - 1] - $tchw2h[$p - 2]) / ($err1[$p - 2] - $err1[$p - 1]) / 2;
                }
            }
            $this->calculation_values['TCHW2H'] = $tchw2h[$p];
            $this->calculation_values['TCHW1L'] = $this->calculation_values['TCHW2H'];

            $this->calculation_values['QEVAH'] = $this->calculation_values['GCHW'] * $this->calculation_values['CHGLY_ROW12'] * $this->calculation_values['CHGLY_SPHT12'] * ($this->calculation_values['TCHW1H'] - $this->calculation_values['TCHW2H']) / 4187;
            $this->calculation_values['LMTDEVAH'] = $this->calculation_values['QEVAH'] / ($this->calculation_values['UEVAH'] * $this->calculation_values['AEVAH']);
            $this->calculation_values['T1H'] = $this->calculation_values['TCHW2H'] - ($this->calculation_values['TCHW1H'] - $this->calculation_values['TCHW2H']) / (exp(($this->calculation_values['TCHW1H'] - $this->calculation_values['TCHW2H']) / $this->calculation_values['LMTDEVAH']) - 1);
            $this->calculation_values['P1H'] = $vam_base->LIBR_PRESSURE($this->calculation_values['T1H'], 0);
            $this->calculation_values['J1H'] = $vam_base->WATER_VAPOUR_ENTHALPY($this->calculation_values['T1H'], $this->calculation_values['P1H']);
            $this->calculation_values['I1H'] = $this->calculation_values['T1H'] + 100;

            $this->calculation_values['QEVAL'] = $this->calculation_values['GCHW'] * $this->calculation_values['CHGLY_ROW12'] * $this->calculation_values['CHGLY_SPHT12'] * ($this->calculation_values['TCHW1L'] - $this->calculation_values['TCHW2L']) / 4187;
            $this->calculation_values['LMTDEVAL'] = $this->calculation_values['QEVAL'] / ($this->calculation_values['UEVAL'] * $this->calculation_values['AEVAL']);
            $this->calculation_values['T1L'] = $this->calculation_values['TCHW2L'] - ($this->calculation_values['TCHW1L'] - $this->calculation_values['TCHW2L']) / (exp(($this->calculation_values['TCHW1L'] - $this->calculation_values['TCHW2L']) / $this->calculation_values['LMTDEVAL']) - 1);
            $this->calculation_values['P1L'] = $vam_base->LIBR_PRESSURE($this->calculation_values['T1L'], 0);
            $this->calculation_values['J1L'] = $vam_base->WATER_VAPOUR_ENTHALPY($this->calculation_values['T1L'], $this->calculation_values['P1L']);
            $this->calculation_values['I1L'] = $this->calculation_values['T1L'] + 100;

            $this->ABSORBER();

            $this->calculation_values['QABSH'] = ($this->calculation_values['GREFH'] * $this->calculation_values['J1H']) + ($this->calculation_values['GCONCH'] * $this->calculation_values['I2L']) - ($this->calculation_values['GDIL'] * $this->calculation_values['I2']);
            $err1[$p] = ($this->calculation_values['QLMTDABSH'] - $this->calculation_values['QABSH']);
            $ferr1[$p] = ($this->calculation_values['QLMTDABSH'] - $this->calculation_values['QABSH']) / $this->calculation_values['QLMTDABSH'] * 100;
            $p++;
        }
    }


    public function ABSORBER()
    {
        $ferr2 = array();
        $t2 = array();

        $vam_base1 = new VamBaseController();

        if ($this->calculation_values['q'] == 0)
        {
            $this->calculation_values['T2'] = $this->calculation_values['ATCW2'] + 3;
        }
        else
        {
            $this->calculation_values['T2'] = $this->calculation_values['T2'];
        }

        $this->calculation_values['q'] = 1;
        $ferr2[0] = 1;
        while (abs($ferr2[$this->calculation_values['q'] - 1]) > 0.1)
        {
            if ($this->calculation_values['q'] == 1)
            {
                $t2[$this->calculation_values['q']] = $this->calculation_values['T2'];
            }
            if ($this->calculation_values['q'] == 2)
            {
                $t2[$this->calculation_values['q']] = $t2[$this->calculation_values['q'] - 1] + 0.1;
            }
            if ($this->calculation_values['q'] >= 3)
            {
                if (($this->calculation_values['TON'] / $this->calculation_values['MODEL']) < 0.5)
                {
                    $t2[$this->calculation_values['q']] = $t2[$this->calculation_values['q'] - 1] + $ferr2[$this->calculation_values['q'] - 1] * ($t2[$this->calculation_values['q'] - 1] - $t2[$this->calculation_values['q'] - 2]) / ($ferr2[$this->calculation_values['q'] - 2] - $ferr2[$this->calculation_values['q'] - 1]) / 3;
                }
                else
                {
                    $t2[$this->calculation_values['q']] = $t2[$this->calculation_values['q'] - 1] + $ferr2[$this->calculation_values['q'] - 1] * ($t2[$this->calculation_values['q'] - 1] - $t2[$this->calculation_values['q'] - 2]) / ($ferr2[$this->calculation_values['q'] - 2] - $ferr2[$this->calculation_values['q'] - 1]) / 2;
                    if ($this->calculation_values['MODEL'] == 1350 || $this->calculation_values['MODEL'] == 1650)
                    {
                        $t2[$this->calculation_values['q']] = $t2[$this->calculation_values['q'] - 1] + $ferr2[$this->calculation_values['q'] - 1] * ($t2[$this->calculation_values['q'] - 1] - $t2[$this->calculation_values['q'] - 2]) / ($ferr2[$this->calculation_values['q'] - 2] - $ferr2[$this->calculation_values['q'] - 1]) / 3;
                    }
                }
            }
            $this->calculation_values['T2'] = $t2[$this->calculation_values['q']];
            $this->calculation_values['XDIL'] = $vam_base1->LIBR_CONC($this->calculation_values['T2'], $this->calculation_values['P1H']);
            $this->calculation_values['I2'] = $vam_base1->LIBR_ENTHALPY($this->calculation_values['T2'], $this->calculation_values['XDIL']);

            $this->CONDENSER();

            $this->calculation_values['QABSL'] = ($this->calculation_values['GCONC'] * $this->calculation_values['I8']) + ($this->calculation_values['GREFL'] * $this->calculation_values['J1L']) - ($this->calculation_values['GDILL'] * $this->calculation_values['I2L']);
            $ferr2[$this->calculation_values['q']] = ($this->calculation_values['QLMTDABSL'] - $this->calculation_values['QABSL']) / $this->calculation_values['QLMTDABSL'] * 100;
            $this->calculation_values['q']++;
        }
    }

    public function CONDENSER()
    {
        $ferr3 = array();
        $t3 = array();

        $vam_base2 = new VamBaseController();

        if ($this->calculation_values['r'] == 0)
            $this->calculation_values['AT3'] = $this->calculation_values['AT3'];
        else
            $this->calculation_values['AT3'] = $this->calculation_values['T3'];

        $ferr3[0] = 1;
        $this->calculation_values['r'] = 1;

        while (abs($ferr3[$this->calculation_values['r'] - 1]) > 0.1)
        {
            if ($this->calculation_values['r'] == 1)
            {
                $t3[$this->calculation_values['r']] = $this->calculation_values['AT3'];
            }
            if ($this->calculation_values['r'] == 2)
            {
                $t3[$this->calculation_values['r']] = $t3[$this->calculation_values['r'] - 1] - 0.2;
            }
            if ($this->calculation_values['r'] >= 3)
            {
                $t3[$this->calculation_values['r']] = $t3[$this->calculation_values['r'] - 1] + $ferr3[$this->calculation_values['r'] - 1] * ($t3[$this->calculation_values['r'] - 1] - $t3[$this->calculation_values['r'] - 2]) / ($ferr3[$this->calculation_values['r'] - 2] - $ferr3[$this->calculation_values['r'] - 1]);
            }

            $this->calculation_values['T3'] = $t3[$this->calculation_values['r']];
            $this->calculation_values['P3'] = $vam_base2->LIBR_PRESSURE($this->calculation_values['T3'], 0);
            $this->calculation_values['I3'] = $this->calculation_values['T3'] + 100;

            $this->calculation_values['GREFL'] = $this->calculation_values['QEVAL'] / ($this->calculation_values['J1L'] - $this->calculation_values['I1H']);
            $this->calculation_values['GREFH'] = ($this->calculation_values['QEVAH'] + $this->calculation_values['GREFL'] * ($this->calculation_values['I3'] - $this->calculation_values['I1H'])) / ($this->calculation_values['J1H'] - $this->calculation_values['I3']);

            $this->calculation_values['GCONCH'] = $this->calculation_values['GDIL'] - $this->calculation_values['GREFH'];
            $this->calculation_values['XCONCH'] = $this->calculation_values['GDIL'] * $this->calculation_values['XDIL'] / $this->calculation_values['GCONCH'];
            $this->calculation_values['T6H'] = $vam_base2->LIBR_TEMP($this->calculation_values['P1H'], $this->calculation_values['XCONCH']);
            $this->calculation_values['I6H'] = $vam_base2->LIBR_ENTHALPY($this->calculation_values['T6H'], $this->calculation_values['XCONCH']);

            $this->calculation_values['GDILL'] = $this->calculation_values['GCONCH'];
            $this->calculation_values['XDILL'] = $this->calculation_values['XCONCH'];
            $this->calculation_values['T2L'] = $vam_base2->LIBR_TEMP($this->calculation_values['P1L'], $this->calculation_values['XDILL']);
            $this->calculation_values['I2L'] = $vam_base2->LIBR_ENTHALPY($this->calculation_values['T2L'], $this->calculation_values['XDILL']);

            $this->calculation_values['GCONC'] = $this->calculation_values['GDILL'] - $this->calculation_values['GREFL'];
            $this->calculation_values['XCONC'] = $this->calculation_values['GDILL'] * $this->calculation_values['XDILL'] / $this->calculation_values['GCONC'];
            $this->calculation_values['GREF'] = $this->calculation_values['GREFH'] + $this->calculation_values['GREFL'];
            $this->calculation_values['T6'] = $vam_base2->LIBR_TEMP($this->calculation_values['P1L'], $this->calculation_values['XCONC']);

            $this->CWABSHOUT();
            if ($this->calculation_values['TAP'] == 1)
            {
                $this->calculation_values['TCW1L'] = $this->calculation_values['TCW1H'];
            }
            else
            {
                $this->calculation_values['TCW1L'] = $this->calculation_values['TCW2H'];
            }
            $this->CWABSLOUT();
            if ($this->calculation_values['TAP'] == 1)
            {
                $this->calculation_values['TCW3'] = ($this->calculation_values['TCW2H'] + $this->calculation_values['TCW2L']) / 2;
            }
            else
            {
                $this->calculation_values['TCW3'] = $this->calculation_values['TCW2L'];
            }
            $this->CWCONOUT();

            $this->calculation_values['T9'] = $vam_base2->LIBR_TEMP($this->calculation_values['P3'], $this->calculation_values['XCONC']);
            $this->calculation_values['J9'] = $vam_base2->WATER_VAPOUR_ENTHALPY($this->calculation_values['T9'], $this->calculation_values['P3']);
            $this->calculation_values['I9'] = $vam_base2->LIBR_ENTHALPY($this->calculation_values['T9'], $this->calculation_values['XCONC']);

            $this->DHE();
            $this->LTHE();
            $this->HTHE();

            $this->calculation_values['QLTG'] = ($this->calculation_values['GCONC'] * $this->calculation_values['I9']) + ($this->calculation_values['GREF2'] * $this->calculation_values['J9']) - ($this->calculation_values['GMED'] * $this->calculation_values['I10']);
            $ferr3[$this->calculation_values['r']] = ($this->calculation_values['QLMTDLTG'] - $this->calculation_values['QLTG']) / $this->calculation_values['QLMTDLTG'] * 100;
            $this->calculation_values['r']++;
        }
    }

    public function CWABSHOUT()
    {
        $ferr4 = array();
        $tcw2h = array();
        $s = 0;

        $vam_base3 = new VamBaseController();

        $ferr4[0] = 2;
        $s = 1;
        while (abs($ferr4[$s - 1]) > 0.1)
        {
            if ($s == 1)
            {
                $tcw2h[$s] = $this->calculation_values['TCW1H'] + 1.0;
            }
            if ($s == 2)
            {
                $tcw2h[$s] = $tcw2h[$s - 1] + 0.5;
            }
            if ($s >= 3)
            {
                $tcw2h[$s] = $tcw2h[$s - 1] + $ferr4[$s - 1] * ($tcw2h[$s - 1] - $tcw2h[$s - 2]) / ($ferr4[$s - 2] - $ferr4[$s - 1]);
            }
            if ($tcw2h[$s] > $this->calculation_values['T6H'] && $s > 2)
            {
                $tcw2h[$s] = $tcw2h[$s - 1] + $ferr4[$s - 1] * ($tcw2h[$s - 1] - $tcw2h[$s - 2]) / ($ferr4[$s - 2] - $ferr4[$s - 1]) / 5;
            }

            $this->calculation_values['TCW2H'] = $tcw2h[$s];

            if ($this->calculation_values['GL'] == 2)
            {
                $this->calculation_values['COGLY_ROWH1'] = $vam_base3->EG_ROW($this->calculation_values['TCW1H'], $this->calculation_values['COGLY']);
                $this->calculation_values['COGLY_SPHT1H'] = $vam_base3->EG_SPHT($this->calculation_values['TCW1H'], $this->calculation_values['COGLY']) * 1000;
                $this->calculation_values['COGLY_SPHT2H'] = $vam_base3->EG_SPHT($this->calculation_values['TCW2H'], $this->calculation_values['COGLY']) * 1000;
            }
            else
            {
                $this->calculation_values['COGLY_ROWH1'] = $vam_base3->PG_ROW($this->calculation_values['TCW1H'], $this->calculation_values['COGLY']);
                $this->calculation_values['COGLY_SPHT1H'] = $vam_base3->PG_SPHT($this->calculation_values['TCW1H'], $this->calculation_values['COGLY']) * 1000;
                $this->calculation_values['COGLY_SPHT2H'] = $vam_base3->PG_SPHT($this->calculation_values['TCW2H'], $this->calculation_values['COGLY']) * 1000;
            }

            $QCWABSH = $this->calculation_values['GCWAH'] * $this->calculation_values['COGLY_ROWH1'] * ($this->calculation_values['COGLY_SPHT2H'] + $this->calculation_values['COGLY_SPHT1H']) * 0.5 * ($this->calculation_values['TCW2H'] - $this->calculation_values['TCW1H']) / 4187;
            $this->calculation_values['LMTDABSH'] = (($this->calculation_values['T6H'] - $this->calculation_values['TCW2H']) - ($this->calculation_values['T2'] - $this->calculation_values['TCW1H'])) / log(($this->calculation_values['T6H'] - $this->calculation_values['TCW2H']) / ($this->calculation_values['T2'] - $this->calculation_values['TCW1H']));
            $this->calculation_values['QLMTDABSH'] = $this->calculation_values['UABSH'] * $this->calculation_values['AABSH'] * $this->calculation_values['LMTDABSH'];
            $ferr4[$s] = ($QCWABSH - $this->calculation_values['QLMTDABSH']) * 100 / $QCWABSH;
            $s++;
        }
    }


    public function CWABSLOUT()
    {
        $ferr5 = array();
        $tcw2l = array();
        $m = 0;

        $vam_base = new VamBaseController();

        $ferr5[0] = 2;
        $m = 1;
        while (abs($ferr5[$m - 1]) > 0.1)
        {
            if ($m == 1)
            {
                $tcw2l[$m] = $this->calculation_values['TCW1L'] + 1.0;
            }
            if ($m == 2)
            {
                $tcw2l[$m] = $tcw2l[$m - 1] + 0.5;
            }
            if ($m >= 3)
            {
                $tcw2l[$m] = $tcw2l[$m - 1] + $ferr5[$m - 1] * ($tcw2l[$m - 1] - $tcw2l[$m - 2]) / ($ferr5[$m - 2] - $ferr5[$m - 1]) / 3;
            }
            if ($tcw2l[$m] > $this->calculation_values['T6'] && $m > 2)
            {
                $tcw2l[$m] = $tcw2l[$m - 1] + $ferr5[$m - 1] * ($tcw2l[$m - 1] - $tcw2l[$m - 2]) / ($ferr5[$m - 2] - $ferr5[$m - 1]) / 5;
            }
            $this->calculation_values['TCW2L'] = $tcw2l[$m];

            if ($this->calculation_values['GL'] == 2)
            {
                $this->calculation_values['COGLY_SPHT1L'] = $vam_base->EG_SPHT($this->calculation_values['TCW1L'], $this->calculation_values['COGLY']) * 1000;
                $this->calculation_values['COGLY_SPHT2L'] = $vam_base->EG_SPHT($this->calculation_values['TCW2L'], $this->calculation_values['COGLY']) * 1000;
            }
            else
            {
                $this->calculation_values['COGLY_SPHT1L'] = $vam_base->PG_SPHT($this->calculation_values['TCW1L'], $this->calculation_values['COGLY']) * 1000;
                $this->calculation_values['COGLY_SPHT2L'] = $vam_base->PG_SPHT($this->calculation_values['TCW2L'], $this->calculation_values['COGLY']) * 1000;
            }

            $QCWABSL = $this->calculation_values['GCWAL'] * $this->calculation_values['COGLY_ROWH1'] * ($this->calculation_values['COGLY_SPHT2L'] + $this->calculation_values['COGLY_SPHT1L']) * 0.5 * ($this->calculation_values['TCW2L'] - $this->calculation_values['TCW1L']) / 4187;
            $this->calculation_values['LMTDABSL'] = (($this->calculation_values['T6'] - $this->calculation_values['TCW2L']) - ($this->calculation_values['T2L'] - $this->calculation_values['TCW1L'])) / log(($this->calculation_values['T6'] - $this->calculation_values['TCW2L']) / ($this->calculation_values['T2L'] - $this->calculation_values['TCW1L']));
            $this->calculation_values['QLMTDABSL'] = $this->calculation_values['UABSL'] * $this->calculation_values['AABSL'] * $this->calculation_values['LMTDABSL'];
            $ferr5[$m] = ($QCWABSL - $this->calculation_values['QLMTDABSL']) * 100 / $QCWABSL;
            $m++;
        }
    }


    public function CWCONOUT()
    {
        $ferr6 = array();
        $tcw4 = array();
        $b = 0;

        $vam_base = new VamBaseController();

        $ferr6[0] = 2;
        $b = 1;
        while (abs($ferr6[$b - 1]) > 0.1)
        {
            if ($b == 1)
            {
                $tcw4[$b] = $this->calculation_values['TCW3'] + 0.5;
            }
            if ($b == 2)
            {
                $tcw4[$b] = $tcw4[$b - 1] + 0.2;
            }
            if ($b >= 3)
            {
                $tcw4[$b] = $tcw4[$b - 1] + $ferr6[$b - 1] * ($tcw4[$b - 1] - $tcw4[$b - 2]) / ($ferr6[$b - 2] - $ferr6[$b - 1])/2;
            }
            if ($tcw4[$b] > $this->calculation_values['T3'] && $b > 2)
            {
                $tcw4[$b] = $tcw4[$b - 1] + $ferr6[$b - 1] * ($tcw4[$b - 1] - $tcw4[$b - 2]) / ($ferr6[$b - 2] - $ferr6[$b - 1]) / 5;
            }

            $this->calculation_values['TCW4'] = $tcw4[$b];

            if ($this->calculation_values['GL'] == 2)
            {
                $this->calculation_values['COGLY_SPHT3'] = EG_SPHT($this->calculation_values['TCW3'], $this->calculation_values['COGLY']) * 1000;
                $this->calculation_values['COGLY_SPHT4'] = EG_SPHT($this->calculation_values['TCW4'], $this->calculation_values['COGLY']) * 1000;
            }
            else
            {
                $this->calculation_values['COGLY_SPHT3'] = PG_SPHT($this->calculation_values['TCW3'], $this->calculation_values['COGLY']) * 1000;
                $this->calculation_values['COGLY_SPHT4'] = PG_SPHT($this->calculation_values['TCW4'], $this->calculation_values['COGLY']) * 1000;
            }

            $this->calculation_values['LMTDCON'] = (($this->calculation_values['T3'] - $this->calculation_values['TCW3']) - ($this->calculation_values['T3'] - $this->calculation_values['TCW4'])) / log(($this->calculation_values['T3'] - $this->calculation_values['TCW3']) / ($this->calculation_values['T3'] - $this->calculation_values['TCW4']));
            $QLMTDCON = $this->calculation_values['UCON'] * $this->calculation_values['ACON'] * $this->calculation_values['LMTDCON'];
            $this->calculation_values['QCON'] = $this->calculation_values['GCWC'] * $this->calculation_values['COGLY_ROWH1'] * ($this->calculation_values['COGLY_SPHT4'] + $this->calculation_values['COGLY_SPHT3']) * 0.5 * ($this->calculation_values['TCW4'] - $this->calculation_values['TCW3']) / 4187;
            $ferr6[$b] = ($QLMTDCON - $this->calculation_values['QCON']) * 100 / $QLMTDCON;
            $b++;
        }
    }


    public function DHE()
    {
        $ferr7 = array();
        $t22 = array();
        $c = 0;
        $vam_base = new VamBaseController();

        $this->calculation_values['GDIL2'] = $this->calculation_values['GDIL'] * $this->calculation_values['FR1'];
        $this->calculation_values['GDIL1'] = $this->calculation_values['GDIL'] - $this->calculation_values['GDIL2'];

        $ferr7[0] = 1;
        $c = 1;
        while (abs($ferr7[$c - 1]) > 0.1)
        {
            if ($c == 1)
            {
                $t22[$c] = $this->calculation_values['T2']+7;
            }
            if ($c == 2)
            {
                $t22[$c] = $t22[$c - 1] + 2;
            }
            if ($c >= 3)
            {
                $t22[$c] = $t22[$c - 1] + $ferr7[$c - 1] * ($t22[$c - 1] - $t22[$c - 2]) / ($ferr7[$c - 2] - $ferr7[$c - 1]) / 2;
            }

            $this->calculation_values['T22'] = $t22[$c];
            $this->calculation_values['I22'] = $this->calculation_values['T22'] + 100;

            //$this->calculation_values['GREF1'] = ($this->calculation_values['QCON'] - $this->calculation_values['GREF'] * ($this->calculation_values['J9'] - $this->calculation_values['I3'])) / ($this->calculation_values['I22'] - $this->calculation_values['J9']);
            //$this->calculation_values['GREF2'] = $this->calculation_values['GREF'] - $this->calculation_values['GREF1'];
            //$this->calculation_values['GMED'] = $this->calculation_values['GDIL'] - $this->calculation_values['GREF1'];
           // XMED = $this->calculation_values['GDIL'] * $this->calculation_values['XDIL'] / $this->calculation_values['GMED'];

            $this->LTG();

            $this->calculation_values['GREF2'] = $this->calculation_values['GREF'] - $this->calculation_values['GREF1'];
            $QREFDHE = $this->calculation_values['GREF3'] * ($this->calculation_values['I13'] - $this->calculation_values['I22']);
            $this->calculation_values['I21'] = $this->calculation_values['I2'] + ($QREFDHE / $this->calculation_values['GDIL2']);
            $this->calculation_values['T21'] = $vam_base->LIBR_TEMPERATURE($this->calculation_values['XDIL'], $this->calculation_values['I21']);
            $this->calculation_values['LMTDDHE'] = (($this->calculation_values['T13'] - $this->calculation_values['T21']) - ($this->calculation_values['T22'] - $this->calculation_values['T2'])) / log(($this->calculation_values['T13'] - $this->calculation_values['T21']) / ($this->calculation_values['T22'] - $this->calculation_values['T2']));
            $QLIBRDHE = $this->calculation_values['UDHE'] * $this->calculation_values['ADHE'] * $this->calculation_values['LMTDDHE'];
            $ferr7[$c] = ($QREFDHE - $QLIBRDHE) * 100 / $QREFDHE;
            $c++;
        }
    }

    public function LTG()
    {
        $ferr8 = array();
        $t13 = array();
        $d = 0;

        $vam_base = new VamBaseController();

        $ferr8[0] = 2;
        $d = 1;
        while (abs($ferr8[$d - 1]) > 0.1)
        {
            if ($d == 1)
            {
                $t13[$d] = $this->calculation_values['T9'] + 1;
            }
            if ($d == 2)
            {
                $t13[$d] = $t13[$d - 1] + 1;
            }
            if ($d >= 3)
            {
                $t13[$d] = $t13[$d - 1] + $ferr8[$d - 1] * ($t13[$d - 1] - $t13[$d - 2]) / ($ferr8[$d - 2] - $ferr8[$d - 1]) / 5;
            }
            $this->calculation_values['T13'] = $t13[$d];
            $this->calculation_values['I13'] = $this->calculation_values['T13'] + 100;
            $this->calculation_values['P4'] = $vam_base->LIBR_PRESSURE($this->calculation_values['T13'], 0);

            $this->SAREF();

            //T4 = LIBR_TEMP($this->calculation_values['P4'], $this->calculation_values['XMED']);
            //J4 = WATER_VAPOUR_ENTHALPY(T4, $this->calculation_values['P4']);
            $this->calculation_values['T12'] = $vam_base->LIBR_TEMP($this->calculation_values['P3'], $this->calculation_values['XMED']);
            $this->calculation_values['LMTDLTG'] = (($this->calculation_values['T13'] - $this->calculation_values['T12']) - ($this->calculation_values['T13'] - $this->calculation_values['T9'])) / log(($this->calculation_values['T13'] - $this->calculation_values['T12']) / ($this->calculation_values['T13'] - $this->calculation_values['T9']));
            $this->calculation_values['QLMTDLTG'] = $this->calculation_values['ULTG'] * $this->calculation_values['ALTG'] * $this->calculation_values['LMTDLTG'];
            $this->calculation_values['QREFLTG'] = $this->calculation_values['GREF3'] * (J4 - $this->calculation_values['I13']);
            $ferr8[$d] = ($this->calculation_values['QREFLTG'] - $this->calculation_values['QLMTDLTG']) * 100 / $this->calculation_values['QLMTDLTG'];
            $d++;
        }
    }


    public function SAREF()
    {

        $ferr18 = array();
        $gref4 = array();
        $w = 0;
        $vam_base = new VamBaseController();

        $ferr18[0]=2;
        $w=1;
        while(abs($ferr18[$w-1])>0.01)
        {
            if($w==1)
            {
                $gref4[$w]=$this->calculation_values['AGREF4'];         
            }
            if($w==2)
            {
                $gref4[$w]=$gref4[$w-1]+1;
            }
            if($w>=3)
            {
                $gref4[$w]=$gref4[$w-1]+$ferr18[$w-1]*($gref4[$w-1]-$gref4[$w-2])/($ferr18[$w-2]-$ferr18[$w-1])/2;
            }
            $this->calculation_values['GREF4']=$gref4[$w];         // REF RELEASED IN SA
            
            $this->calculation_values['GREF3']=($this->calculation_values['QCON']+$this->calculation_values['GREF']*($this->calculation_values['I3']-$this->calculation_values['J9'])-$this->calculation_values['GREF4']*($this->calculation_values['I13']-$this->calculation_values['J9']))/($this->calculation_values['I22']-$this->calculation_values['J9']);  //REF USED IN LTG
            $this->calculation_values['GREF1']=$this->calculation_values['GREF3']+$this->calculation_values['GREF4'];      // REF RELEASED IN HTG
            $this->calculation_values['GMED']=$this->calculation_values['GDIL']-$this->calculation_values['GREF1'];
            $this->calculation_values['XMED']=$this->calculation_values['GDIL']*$this->calculation_values['XDIL']/$this->calculation_values['GMED'];
                    
            $this->calculation_values['T4']=$vam_base->LIBR_TEMP($this->calculation_values['P4'],$this->calculation_values['XMED']);
            $this->calculation_values['J4']=$vam_base->WATER_VAPOUR_ENTHALPY($this->calculation_values['T4'],$this->calculation_values['P4']);

            $this->calculation_values['QSA'] = $this->calculation_values['HEATCAP'];
           // $this->calculation_values['QSA']=$this->calculation_values['HEATCAP']*859.845;
            $this->calculation_values['QREFSA']=$this->calculation_values['GREF4']*($this->calculation_values['J4']-$this->calculation_values['I13']);
            $ferr18[$w]=($this->calculation_values['QREFSA']-$this->calculation_values['QSA'])*100/$this->calculation_values['QSA'];
            $w++;
        }
        $this->calculation_values['HW_ROW1']=$vam_base->HT_ROW($this->calculation_values['THW1']);
        $this->calculation_values['HW_SPHT1']=$vam_base->HOT_WATER($this->calculation_values['THW1']);
        $this->calculation_values['HW_SPHT2']=$vam_base->HOT_WATER($this->calculation_values['THW2']);
        $this->calculation_values['GHW']=$this->calculation_values['QSA']/($this->calculation_values['HW_ROW1']*($this->calculation_values['HW_SPHT1']+$this->calculation_values['HW_SPHT2'])*0.5*($this->calculation_values['THW2']-$this->calculation_values['THW1']));
        $this->calculation_values['LMTDSA']=(($this->calculation_values['T13']-$this->calculation_values['THW1'])-($this->calculation_values['T13']-$this->calculation_values['THW2']))/log(($this->calculation_values['T13']-$this->calculation_values['THW1'])/($this->calculation_values['T13']-$this->calculation_values['THW2']));
        $this->calculation_values['ASA']=$this->calculation_values['QSA']/($this->calculation_values['LMTDSA']*$this->calculation_values['USA']);
        $this->calculation_values['TNG'] = $this->calculation_values['ASA'] / (3.14 * $this->calculation_values['ODG'] * $this->calculation_values['LE'] * 0.985);
    }

    public function LTHE()
    {
        $ferr9 = array();
        $t8 = array();
        $h = 0;
        $vam_base = new VamBaseController();

        $ferr9[0] = 2;
        $h = 1;
        while (abs($ferr9[$h - 1]) > 0.1)
        {
            if ($h == 1)
            {
                $t8[$h] = $this->calculation_values['T6'] + 6;
            }
            if ($h == 2)
            {
                $t8[$h] = $t8[$h - 1] + 0.1;
            }
            if ($h >= 3)
            {
                $t8[$h] = $t8[$h - 1] + $ferr9[$h - 1] * ($t8[$h - 1] - $t8[$h - 2]) / ($ferr9[$h - 2] - $ferr9[$h - 1]);
            }

            $this->calculation_values['T8'] = $t8[$h];
            $this->calculation_values['I8'] = $vam_base->LIBR_ENTHALPY($this->calculation_values['T8'], $this->calculation_values['XCONC']);
            $QLIBRLTHE = $this->calculation_values['GCONC'] * ($this->calculation_values['I9'] - $this->calculation_values['I8']);
            $this->calculation_values['I11'] = ($QLIBRLTHE / $this->calculation_values['GDIL1']) + $this->calculation_values['I2'];
            $this->calculation_values['T11'] = $vam_base->LIBR_TEMPERATURE($this->calculation_values['XDIL'], $this->calculation_values['I11']);
            $this->calculation_values['LMTDLTHE'] = (($this->calculation_values['T9'] - $this->calculation_values['T11']) - ($this->calculation_values['T8'] - $this->calculation_values['T2'])) / log(($this->calculation_values['T9'] - $this->calculation_values['T11']) / ($this->calculation_values['T8'] - $this->calculation_values['T2']));
            $QLMTDLTHE = $this->calculation_values['ULTHE'] * $this->calculation_values['ALTHE'] * $this->calculation_values['LMTDLTHE'];
            $ferr9[$h] = ($QLMTDLTHE - $QLIBRLTHE) * 100 / $QLMTDLTHE;
            $h++;
        }
    }

    public function HTHE()
    {
        $ferr10 = array();
        $t7 = array();
        $ht = 0;
        $vam_base = new VamBaseController();

        $ferr10[0] = 2;
        $ht = 1;
        while (abs($ferr10[$ht - 1]) > 0.1)
        {
            if ($ht == 1)
            {
                $t7[$ht] = $this->calculation_values['T4'] - 20;
            }
            if ($ht == 2)
            {
                $t7[$ht] = $t7[$ht - 1] - 5;
            }
            if ($ht >= 3)
            {
                $t7[$ht] = $t7[$ht - 1] + $ferr10[$ht - 1] * ($t7[$ht - 1] - $t7[$ht - 2]) / ($ferr10[$ht - 2] - $ferr10[$ht - 1]);
            }

            $this->calculation_values['T7'] = $t7[$ht];
            $this->calculation_values['I7'] = $vam_base->LIBR_ENTHALPY($this->calculation_values['T7'], $this->calculation_values['XDIL']);
            $QLIBRHTHE = $this->calculation_values['GDIL1'] * ($this->calculation_values['I7'] - $this->calculation_values['I11']);
            $this->calculation_values['I4'] = $vam_base->LIBR_ENTHALPY($this->calculation_values['T4'], $this->calculation_values['XMED']);
            $this->calculation_values['I10'] = $this->calculation_values['I4'] - ($QLIBRHTHE / $this->calculation_values['GMED']);
            $this->calculation_values['T10'] = $vam_base->LIBR_TEMPERATURE($this->calculation_values['XMED'], $this->calculation_values['I10']);
            $this->calculation_values['LMTDHTHE'] = (($this->calculation_values['T4'] - $this->calculation_values['T7']) - ($this->calculation_values['T10'] - $this->calculation_values['T11'])) / log(($this->calculation_values['T4'] - $this->calculation_values['T7']) / ($this->calculation_values['T10'] - $this->calculation_values['T11']));
            $QLMTDHTHE = $this->calculation_values['UHTHE'] * $this->calculation_values['AHTHE'] * $this->calculation_values['LMTDHTHE'];
            $ferr10[$ht] = ($QLIBRHTHE - $QLMTDHTHE) * 100 / $QLIBRHTHE;
            $ht++;
        }
    }

    public function HTG()
    {
        $ferr11 = array();
        $ts = array();
        $tg = 0;

        $ferr11[0] = 1;
        $tg = 1;

        $vam_base = new VamBaseController();

        while (abs($ferr11[$tg - 1]) > 0.05)
        {
            if ($tg == 1)
            {
                $ts[$tg] = $this->calculation_values['T4'] + 10;
            }
            if ($tg == 2)
            {
                $ts[$tg] = $ts[$tg - 1] + 1;
            }
            if ($tg >= 3)
            {
                $ts[$tg] = $ts[$tg - 1] + $ferr11[$tg - 1] * ($ts[$tg - 1] - $ts[$tg - 2]) / ($ferr11[$tg - 2] - $ferr11[$tg - 1]) / 2;
            }

            $this->calculation_values['TS'] = $ts[$tg];
            $this->calculation_values['TSMIN'] = $this->calculation_values['TS'];

            if ($this->calculation_values['TCHW2L'] < 7.0)
            {
                $this->calculation_values['KM2'] = (-0.857413 * $this->calculation_values['TCHW2L'] + 6);     //INCREASED FROM 4 TO 5 FEB 2009

            }

            $PS1 = $vam_base->STEAM_PRESSURE($this->calculation_values['TSMIN'] + $this->calculation_values['KM2']);       //IN kg/cm2.g
            $this->calculation_values['PS'] = $PS1 + 0.7;

            $this->calculation_values['T5'] = $vam_base->LIBR_TEMP($this->calculation_values['P4'], $this->calculation_values['XDIL']);
            $this->calculation_values['LMTDHTG'] = (($this->calculation_values['TS'] - $this->calculation_values['T5']) - ($this->calculation_values['TS'] - $this->calculation_values['T4'])) / log(($this->calculation_values['TS'] - $this->calculation_values['T5']) / ($this->calculation_values['TS'] - $this->calculation_values['T4']));
            $QLMTDHTG = $this->calculation_values['UHTG'] * $this->calculation_values['AHTG'] * $this->calculation_values['LMTDHTG'];
            $this->calculation_values['HSTEAM'] = 639.427333 + (4.7783887 * ($this->calculation_values['PST1'] + 1.0)) - (0.3413875 * ($this->calculation_values['PST1'] + 1.0) * ($this->calculation_values['PST1'] + 1.0)) + (0.009782 * ($this->calculation_values['PST1'] + 1.0) * ($this->calculation_values['PST1'] + 1.0) * ($this->calculation_values['PST1'] + 1.0));
            $this->calculation_values['GSTEAM'] = $QLMTDHTG / ($this->calculation_values['HSTEAM'] - $this->calculation_values['TS']);
            $this->HR();
            $this->calculation_values['I14'] = ($this->calculation_values['GDIL2'] * $this->calculation_values['I20'] + $this->calculation_values['GDIL1'] * $this->calculation_values['I7']) / $this->calculation_values['GDIL'];
            $T14 = $vam_base->LIBR_TEMPERATURE($this->calculation_values['XDIL'], $this->calculation_values['I14']);
            $this->calculation_values['QHTG'] = ($this->calculation_values['GMED'] * $this->calculation_values['I4']) + ($this->calculation_values['GREF1'] * $this->calculation_values['J4']) - ($this->calculation_values['GDIL'] * $this->calculation_values['I14']);
            $ferr11[$tg] = ($QLMTDHTG - $this->calculation_values['QHTG']) * 100 / $QLMTDHTG;
            $tg++;
        }
        /********** $this->calculation_values['SFACTOR'] - STEAM *******/
        $SFACTOR1 = 0; $SFACTOR2 = 0; $SFACTOR3 = 0;

        if ($this->calculation_values['TCHW12'] < 5 || ($this->calculation_values['MODEL'] < 300 && $this->calculation_values['TCHW12'] < 6.7))
        {
            $SFACTOR1 = 1.0738 - 0.0068 * $this->calculation_values['TCHW12'];
        }
        else
        {
            $SFACTOR1 = 1.0;
        }

        if ($this->calculation_values['PST1'] > 5.99)
        {
            $SFACTOR2 = 1.0;
        }
        else
        {
            $SFACTOR2 = 1.0 + (0.0125 * (6.0 - $this->calculation_values['PST1']));
        }

        if ($this->calculation_values['DT'] < 14.999)
        {
            $SFACTOR3 = 1.0;
        }
        else
        {
            $SFACTOR3 = (0.0006667 * $this->calculation_values['DT']) + 1.01;
        }
        $this->calculation_values['PERC'] = $this->calculation_values['HEATCAP']/ ($this->calculation_values['TON'] * 3024);
        $this->calculation_values['SCH'] = 1.01 + ($this->calculation_values['PERC'] - 0.1) * 0.032308;
        $this->calculation_values['GSTEAM'] = $this->calculation_values['GSTEAM'] * $this->calculation_values['SFACTOR'] * $SFACTOR1 * $SFACTOR2 * $SFACTOR3 * $this->calculation_values['SCH'];

        $this->PRESSURE_DROP();
    }

    public function HR()
    {
        $ferr12 = array();
        $tstout = array();
        $vam_base = new VamBaseController();

        if ($this->calculation_values['i'] == 0)
            $this->calculation_values['TSTOUT1'] = 85;
        else
            $this->calculation_values['TSTOUT1'] = $this->calculation_values['T21'] + 3;

        $ferr12[0] = 2;
        $this->calculation_values['i'] = 1;

        while (abs($ferr12[$this->calculation_values['i'] - 1]) > 0.1)
        {
            if ($this->calculation_values['i'] == 1)
            {
                $tstout[$this->calculation_values['i']] = 100;//$this->calculation_values['TSTOUT1'];
            }
            if ($this->calculation_values['i'] == 2)
            {
                $tstout[$this->calculation_values['i']] = $tstout[$this->calculation_values['i'] - 1] + 0.5;
            }
            if ($this->calculation_values['i'] >= 3)
            {
                $tstout[$this->calculation_values['i']] = $tstout[$this->calculation_values['i'] - 1] + $ferr12[$this->calculation_values['i'] - 1] * ($tstout[$this->calculation_values['i'] - 1] - $tstout[$this->calculation_values['i'] - 2]) / ($ferr12[$this->calculation_values['i'] - 2] - $ferr12[$this->calculation_values['i'] - 1]) / 2;
            }
            $this->calculation_values['TSTOUT'] = $tstout[$this->calculation_values['i']];
            $this->calculation_values['QHR'] = $this->calculation_values['GSTEAM'] * ($this->calculation_values['TSMIN'] - $this->calculation_values['TSTOUT']);
            $this->calculation_values['I20'] = $this->calculation_values['I21'] + ($this->calculation_values['QHR'] / $this->calculation_values['GDIL2']);
            $this->calculation_values['T20'] = $vam_base->LIBR_TEMPERATURE($this->calculation_values['XDIL'], $this->calculation_values['I20']);
            $this->calculation_values['LMTDHR'] = (($this->calculation_values['TSMIN'] - 5 - $this->calculation_values['T20']) - ($this->calculation_values['TSTOUT'] - $this->calculation_values['T21'])) / log(($this->calculation_values['TSMIN'] - 5 - $this->calculation_values['T20']) / ($this->calculation_values['TSTOUT'] - $this->calculation_values['T21']));
            $QLMTDHR = $this->calculation_values['UHR'] * $this->calculation_values['AHR'] * $this->calculation_values['LMTDHR'];
            $ferr12[$this->calculation_values['i']] = ($this->calculation_values['QHR'] - $QLMTDHR) / $this->calculation_values['QHR'] * 100;
            $this->calculation_values['i']++;
        }
    }

    public function PRESSURE_DROP()
    {
        // PR_DROP_DATA();
        $this->PR_DROP_CHILL();
        $this->PR_DROP_COW();
        $this->PR_DROP_HW();
    }

    public function PR_DROP_COW()
    {

        $COGLY_ROWH33 = 0; $COGLY_VISH33 = 0; $FH = 0; $FL = 0;
        $vam_base = new VamBaseController();

        $this->calculation_values['PIDA'] = ($this->calculation_values['PODA'] - (2 * $this->calculation_values['THPA'])) / 1000;
        $this->calculation_values['APA'] = 3.141593 * $this->calculation_values['PIDA'] * $this->calculation_values['PIDA'] / 4;
        $this->calculation_values['VPA'] = ($this->calculation_values['GCW'] * 4) / (3.141593 * $this->calculation_values['PIDA'] * $this->calculation_values['PIDA'] * 3600);
        //VD1 = $this->calculation_values['GCW'] / (3600 * DW1 * DH1);   //Duct 2

        $TMA = ($this->calculation_values['TCW1H'] + $this->calculation_values['TCW2H'] + $this->calculation_values['TCW1L'] + $this->calculation_values['TCW2L']) / 4.0;
        if ($this->calculation_values['GL'] == 3)
        {
            $COGLY_ROWH33 = $vam_base->PG_ROW($TMA, $this->calculation_values['COGLY']);
            $COGLY_VISH33 = $vam_base->PG_VISCOSITY($TMA, $this->calculation_values['COGLY']) / 1000;
        }
        else
        {
            $COGLY_ROWH33 = $vam_base->EG_ROW($TMA, $this->calculation_values['COGLY']);
            $COGLY_VISH33 = $vam_base->EG_VISCOSITY($TMA, $this->calculation_values['COGLY']) / 1000;
        }
        $this->calculation_values['REPA'] = ($this->calculation_values['PIDA'] * $this->calculation_values['VPA'] * $COGLY_ROWH33) / $COGLY_VISH33;          //REYNOLDS NO IN PIPE1  
        // RED1 = ((ED1NB) * VD1 * $COGLY_ROWH33) / $COGLY_VISH33;            //REYNOLDS NO IN DUCT1

        $this->calculation_values['FFA'] = 1.325 / pow(log((0.0457 / (3.7 * $this->calculation_values['PIDA'] * 1000)) + (5.74 / pow($this->calculation_values['REPA'], 0.9))), 2);     //FRICTION FACTOR CAL
        //  FFD1 = 1.325 / pow(log((0.0457 / (3.7 * (ED1NB) * 1000)) + (5.74 / pow(RED1, 0.9))), 2);

        $this->calculation_values['FLP1'] = ($this->calculation_values['FFA'] * ($this->calculation_values['PSL1'] + $this->calculation_values['PSL2']) / $this->calculation_values['PIDA']) * ($this->calculation_values['VPA'] * $this->calculation_values['VPA'] / (2 * 9.81)) + ((14 * $this->calculation_values['FT']) * ($this->calculation_values['VPA'] * $this->calculation_values['VPA']) / (2 * 9.81));        //FR LOSS IN PIPE                                   
        //   FLD1 = ((FFD1 * DSL) / ED1NB) * (VD1 * VD1 / (2 * 9.81));                                  //FR LOSS IN DUCT
        $this->calculation_values['FLOT'] = (1 + 0.5 + 1 + 0.5) * ($this->calculation_values['VPA'] * $this->calculation_values['VPA'] / (2 * 9.81));                                                                  //EXIT, ENTRY LOSS

        $this->calculation_values['AFLP'] = ($this->calculation_values['FLP1'] + $this->calculation_values['FLOT']) * 1.075;               //7.5% SAFETY

        $REH = ($this->calculation_values['VAH'] * $this->calculation_values['IDA'] * $COGLY_ROWH33) / $COGLY_VISH33;
        $REL = ($this->calculation_values['VAL'] * $this->calculation_values['IDA'] * $COGLY_ROWH33) / $COGLY_VISH33;

        if (($this->calculation_values['TU5'] < 2.1 || $this->calculation_values['TU5'] == 6) && $this->calculation_values['MODEL'] < 1200)
        {
            $FH = (0.0014 + (0.137 / pow($REH, 0.32))) * 1.12;
            $FL = (0.0014 + (0.137 / pow($REL, 0.32))) * 1.12;
        }
        else if (($this->calculation_values['TU5'] < 2.1 || $this->calculation_values['TU5'] == 6) && $this->calculation_values['MODEL'] > 1200)
        {
            $FH = (0.0014 + (0.137 / pow($REH, 0.32)));
            $FL = (0.0014 + (0.137 / pow($REL, 0.32)));
        }
        else
        {
            $FH = 0.0014 + (0.125 / pow($REH, 0.32));
            $FL = 0.0014 + (0.125 / pow($REL, 0.32));
        }

        $FA1H = 2 * $FH * $this->calculation_values['LE'] * $this->calculation_values['VAH'] * $this->calculation_values['VAH'] / (9.81 * $this->calculation_values['IDA']);
        $FA2H = $this->calculation_values['VAH'] * $this->calculation_values['VAH'] / (4 * 9.81);
        $FA3H = $this->calculation_values['VAH'] * $this->calculation_values['VAH'] / (2 * 9.81);
        $FA4H = ($FA1H + $FA2H + $FA3H) * $this->calculation_values['TAPH'];                  //FRICTION LOSS IN ABSH TUBES

        $FA1L = 2 * $FL * $this->calculation_values['LE'] * $this->calculation_values['VAL'] * $this->calculation_values['VAL'] / (9.81 * $this->calculation_values['IDA']);
        $FA2L = $this->calculation_values['VAL'] * $this->calculation_values['VAL'] / (4 * 9.81);
        $FA3L = $this->calculation_values['VAL'] * $this->calculation_values['VAL'] / (2 * 9.81);
        $FA4L = ($FA1L + $FA2L + $FA3L) * $this->calculation_values['TAPL'];                  //FRICTION LOSS IN ABSL TUBES

        if ($this->calculation_values['TAP'] == 1)
        {
            $this->calculation_values['FLA'] = $FA4H + $this->calculation_values['AFLP'];      //PARAFLOW WILL HAVE ONE ENTRY, ONE EXIT, ONE TUBE FRICTION LOSS
        }
        else
        {
            $this->calculation_values['FLA'] = $FA4H + $FA4L + $this->calculation_values['AFLP'];
        }
        $TMC = ($this->calculation_values['TCW3'] + $this->calculation_values['TCW4']) / 2.0;

        if ($this->calculation_values['GL'] == 3)
        {
            $COGLY_ROWH33 = $vam_base->PG_ROW($TMC, $this->calculation_values['COGLY']);
            $COGLY_VISH33 = $vam_base->PG_VISCOSITY($TMC, $this->calculation_values['COGLY']) / 1000;
        }
        else
        {
            $COGLY_ROWH33 = $vam_base->EG_ROW($TMC, $this->calculation_values['COGLY']);
            $COGLY_VISH33 = $vam_base->EG_VISCOSITY($TMC, $this->calculation_values['COGLY']) / 1000;
        }
        $RE1 = ($this->calculation_values['VC'] * $this->calculation_values['IDC'] * $COGLY_ROWH33) / $COGLY_VISH33;

        if (($this->calculation_values['TV5'] < 2.1 || $this->calculation_values['TV5'] == 4) && $this->calculation_values['MODEL'] < 950)
        {
            $this->calculation_values['F'] = (0.0014 + (0.137 / pow($RE1, 0.32))) * 1.12;
        }
        else if (($this->calculation_values['TV5'] < 2.1 || $this->calculation_values['TV5'] == 4) && $this->calculation_values['MODEL'] > 950)
        {
            $this->calculation_values['F'] = 0.0014 + (0.137 / pow($RE1, 0.32));
        }
        else
        {
            $this->calculation_values['F'] = 0.0014 + (0.125 / pow($RE1, 0.32));
        }

        $FC1 = 2 * $this->calculation_values['F'] * $this->calculation_values['LE'] * $this->calculation_values['VC'] * $this->calculation_values['VC'] / (9.81 * $this->calculation_values['IDC']);
        $FC2 = $this->calculation_values['VC'] * $this->calculation_values['VC'] / (4 * 9.81);
        $FC3 = $this->calculation_values['VC'] * $this->calculation_values['VC'] / (2 * 9.81);
        $this->calculation_values['FC4'] = ($FC1 + $FC2 + $FC3) * $this->calculation_values['TCP'];                      //FRICTION LOSS IN CONDENSER TUBES
        $this->calculation_values['FLC'] = $this->calculation_values['FC4'];

        $this->calculation_values['PDA'] = $this->calculation_values['FLA'] + $this->calculation_values['SHA'] + $this->calculation_values['FC4'];
    }

    public function PR_DROP_HW()
    {
        $vam_base = new VamBaseController();

        if ($this->calculation_values['GHW'] <= 20)
            $this->calculation_values['NB'] = 50;
        else if ($this->calculation_values['GHW'] <= 45)
            $this->calculation_values['NB'] = 80;
        else if ($this->calculation_values['GHW'] <= 80)
            $this->calculation_values['NB'] = 100;
        else if ($this->calculation_values['GHW'] <= 120)
            $this->calculation_values['NB'] = 125;
        else if ($this->calculation_values['GHW'] <= 180)
            $this->calculation_values['NB'] = 150;
        else if ($this->calculation_values['GHW'] <= 300)
            $this->calculation_values['NB'] = 200;
        else if ($this->calculation_values['GHW'] <= 500)
            $this->calculation_values['NB'] = 250;
        else if ($this->calculation_values['GHW'] <= 650)
            $this->calculation_values['NB'] = 300;
        else if ($this->calculation_values['GHW'] <= 800)
            $this->calculation_values['NB'] = 350;
        else
            $this->calculation_values['NB'] = 400;


        $this->PIPE_ID();

        $this->calculation_values['GSL1'] = $this->calculation_values['PIDG'] * 3;
        $this->calculation_values['GSL2'] = $this->calculation_values['PIDG'] * 3;

        $this->calculation_values['VPG'] = ($this->calculation_values['GHW'] * 4) / (3.14153 * $this->calculation_values['PIDG'] * $this->calculation_values['PIDG'] * 3600);               //PIPE VELOCITY
        $this->calculation_values['TMG'] = ($this->calculation_values['THW1'] + $this->calculation_values['THW2']) / 2.0;

        $this->calculation_values['VISG'] = $vam_base->PG_VISCOSITY($this->calculation_values['TMG'], 0) / 1000;
        $GLY_ROW = $vam_base->HT_ROW($this->calculation_values['TMG']);

        $this->calculation_values['REPG'] = ($this->calculation_values['PIDG'] * $this->calculation_values['VPG'] * $GLY_ROW) / $this->calculation_values['VISG'];                       //REYNOLDS NO IN PIPE

        $this->calculation_values['FFH'] = 1.325 / pow(log((0.0457 / (3.7 * $this->calculation_values['PIDG'] * 1000)) + (5.74 / pow($this->calculation_values['REPG'], 0.9))), 2);

        $this->calculation_values['FGP1'] = (($this->calculation_values['GSL1'] + $this->calculation_values['GSL2']) * $this->calculation_values['FFH'] / $this->calculation_values['PIDG']) * ($this->calculation_values['VPG'] * $this->calculation_values['VPG'] / (2 * 9.81));
        $this->calculation_values['FGP2'] = (($this->calculation_values['VPG'] * $this->calculation_values['VPG'] / (2 * 9.81)) + (0.5 * $this->calculation_values['VPG'] * $this->calculation_values['VPG'] / (2 * 9.81))) + (2 * 8 * $this->calculation_values['FFH'] * ($this->calculation_values['VPG'] * $this->calculation_values['VPG'] / (2 * 9.81))); //ENTRY/EXIT/2 45 DEG BENDS

        $this->calculation_values['FGP'] = ($this->calculation_values['FGP1'] + $this->calculation_values['FGP2'])*1.15;                               //FR LOSS IN PIPES


        for ($this->calculation_values['TGP'] = 1; $this->calculation_values['TGP'] <= 6; $this->calculation_values['TGP']++)
        {

            $this->calculation_values['VG'] = $this->calculation_values['GHW'] / (((3600 * 3.141593 * $this->calculation_values['IDG'] * $this->calculation_values['IDG']) / 4.0) * ($this->calculation_values['TNG'] / $this->calculation_values['TGP']));
            if ($this->calculation_values['VG'] > 1.0)
            {
                break;
            }
        }

        if ($this->calculation_values['TGP'] > 1 && $this->calculation_values['VG'] > 1.61)
        {
            $this->calculation_values['TGP'] = $this->calculation_values['TGP'] - 1;
            $this->calculation_values['VG'] = $this->calculation_values['GHW'] / (((3600 * 3.141593 * $this->calculation_values['IDG'] * $this->calculation_values['IDG']) / 4.0) * ($this->calculation_values['TNG'] / $this->calculation_values['TGP']));

        }

        $RE = ($GLY_ROW * $this->calculation_values['VG'] * $this->calculation_values['IDG']) / $this->calculation_values['VISG'];

        if ($this->calculation_values['MODEL'] < 1200)
        {
            $this->calculation_values['F'] = (0.0014 + (0.137 / pow($RE, 0.32))) * 1.12;
        }
        else
        {
            $this->calculation_values['F'] = (0.0014 + (0.137 / pow($RE, 0.32)));
        }

        //double $this->calculation_values['F'] = (1.325 / pow(log((1.53 / (3.7 * $this->calculation_values['IDG'] * 1000)) + (5.74 / pow($RE, 0.9))), 2)) * ((-0.0315 * $this->calculation_values['VG']) + 0.85); //19 mm corrugated tubes 

        $this->calculation_values['FG1'] = $this->calculation_values['F'] * $this->calculation_values['LE'] * $this->calculation_values['VG'] * $this->calculation_values['VG'] / (9.81 * $this->calculation_values['IDG'] * 2);
        $this->calculation_values['FG2'] = $this->calculation_values['VG'] * $this->calculation_values['VG'] / (4 * 9.81);
        $this->calculation_values['FG3'] = $this->calculation_values['VG'] * $this->calculation_values['VG'] / (2 * 9.81);
        $this->calculation_values['FG5'] = ($this->calculation_values['FG1'] + $this->calculation_values['FG2'] + $this->calculation_values['FG3']) * ($this->calculation_values['TGP']);                        //FR LOSS IN TUBES

        $this->calculation_values['GFL'] = $this->calculation_values['FGP'] + $this->calculation_values['FG5'];                                    //TOTAL FRICTION LOSS IN HW

    }

    public function PIPE_ID()
    {
        switch($this->calculation_values['NB'])
        {
            case 50:        
                $this->calculation_values['POD']=60.3;$this->calculation_values['PTK']=3.91;
                break;
            case 65:
                $this->calculation_values['POD']=73.0;$this->calculation_values['PTK']=5.16;
                break;
            case 80:        
                $this->calculation_values['POD']=88.9;$this->calculation_values['PTK']=5.49;
                break;
            case 100:
                $this->calculation_values['POD']=114.3;$this->calculation_values['PTK']=6.02;
                break;
            case 125:
                $this->calculation_values['POD']=141.3;$this->calculation_values['PTK']=6.55;
                break;
            case 150:
                $this->calculation_values['POD']=168.3;$this->calculation_values['PTK']=7.11;
                break;
            case 200:
                $this->calculation_values['POD']=219.1;$this->calculation_values['PTK']=8.18;
                break;
            case 250:
                $this->calculation_values['POD']=273.0;$this->calculation_values['PTK']=9.27;
                break;
            case 300:
                $this->calculation_values['POD']=323.6;$this->calculation_values['PTK']=10.31;
                break;
            case 350:
                $this->calculation_values['POD']=355.6;$this->calculation_values['PTK']=11.13;
                break;
            case 400:
                $this->calculation_values['POD']=406.4;$this->calculation_values['PTK']=12.70;
                break;
            }
        $this->calculation_values['PIDG']=$this->calculation_values['POD']-2*($this->calculation_values['PTK']);
        $this->calculation_values['PIDG']=$this->calculation_values['PIDG']/1000;
    }

    public function CONVERGENCE()
    {
        $CC = array();
        $j = 0;

        $CC[0][0] = $this->calculation_values['GCHW'] * $this->calculation_values['CHGLY_ROW12'] * $this->calculation_values['CHGLY_SPHT12'] * ($this->calculation_values['TCHW1H'] - $this->calculation_values['TCHW2H']) / 4187;                        //EVAPORATORH
        $CC[1][0] = $this->calculation_values['UEVAH'] * $this->calculation_values['AEVAH'] * $this->calculation_values['LMTDEVAH'];
        $CC[2][0] = ($this->calculation_values['GREFH'] * ($this->calculation_values['J1H'] - $this->calculation_values['I3'])) - ($this->calculation_values['GREFL'] * ($this->calculation_values['I3'] - $this->calculation_values['I1H']));

        $CC[0][1] = $this->calculation_values['GCWAH'] * $this->calculation_values['COGLY_ROWH1'] * ($this->calculation_values['COGLY_SPHT2H'] + $this->calculation_values['COGLY_SPHT1H']) * 0.5 * ($this->calculation_values['TCW2H'] - $this->calculation_values['TCW1H']) / 4187;  //ABSORBERH
        $CC[1][1] = $this->calculation_values['UABSH'] * $this->calculation_values['AABSH'] * $this->calculation_values['LMTDABSH'];
        $CC[2][1] = $this->calculation_values['GREFH'] * $this->calculation_values['J1H'] + $this->calculation_values['GCONCH'] * $this->calculation_values['I2L'] - $this->calculation_values['GDIL'] * $this->calculation_values['I2'];

        $CC[0][2] = $this->calculation_values['GCWC'] * $this->calculation_values['COGLY_ROWH1'] * ($this->calculation_values['COGLY_SPHT4'] + $this->calculation_values['COGLY_SPHT3']) * 0.5 * ($this->calculation_values['TCW4'] - $this->calculation_values['TCW3']) / 4187;       //CONDENSER
        $CC[1][2] = $this->calculation_values['UCON'] * $this->calculation_values['ACON'] * $this->calculation_values['LMTDCON'];
        $CC[2][2] = ($this->calculation_values['GREF2'] * $this->calculation_values['J9']) + ($this->calculation_values['GREF3'] * $this->calculation_values['I22']) + ($this->calculation_values['GREF4'] * $this->calculation_values['I13']) - ($this->calculation_values['GREF'] * $this->calculation_values['I3']);

        $CC[0][3] = $this->calculation_values['GREF3'] * ($this->calculation_values['J4'] - $this->calculation_values['I13']);                                                                  //LTG
        $CC[1][3] = $this->calculation_values['ULTG'] * $this->calculation_values['ALTG'] * $this->calculation_values['LMTDLTG'];
        $CC[2][3] = $this->calculation_values['GCONC'] * $this->calculation_values['I9'] + $this->calculation_values['GREF2'] * $this->calculation_values['J9'] - $this->calculation_values['GMED'] * $this->calculation_values['I10'];

        $CC[0][4] = $this->calculation_values['QHTG'];                                                                                //HTG
        $CC[1][4] = $this->calculation_values['UHTG'] * $this->calculation_values['AHTG'] * $this->calculation_values['LMTDHTG'];
        $CC[2][4] = $this->calculation_values['GMED'] * $this->calculation_values['I4'] + $this->calculation_values['GREF1'] * $this->calculation_values['J4'] - $this->calculation_values['GDIL'] * $this->calculation_values['I14'];

        $CC[0][5] = $this->calculation_values['GDIL1'] * ($this->calculation_values['I11'] - $this->calculation_values['I2']);                                                                  //LTHE
        $CC[1][5] = $this->calculation_values['ULTHE'] * $this->calculation_values['ALTHE'] * $this->calculation_values['LMTDLTHE'];
        $CC[2][5] = $this->calculation_values['GCONC'] * ($this->calculation_values['I9'] - $this->calculation_values['I8']);

        $CC[0][6] = $this->calculation_values['GMED'] * ($this->calculation_values['I4'] - $this->calculation_values['I10']);                                                                   //HTHE
        $CC[1][6] = $this->calculation_values['UHTHE'] * $this->calculation_values['AHTHE'] * $this->calculation_values['LMTDHTHE'];
        $CC[2][6] = $this->calculation_values['GDIL1'] * ($this->calculation_values['I7'] - $this->calculation_values['I11']);

        $CC[0][7] = $this->calculation_values['GCHW'] * $this->calculation_values['CHGLY_ROW12'] * $this->calculation_values['CHGLY_SPHT12'] * ($this->calculation_values['TCHW1L'] - $this->calculation_values['TCHW2L']) / 4187;                        //EVAPORATORL
        $CC[1][7] = $this->calculation_values['UEVAL'] * $this->calculation_values['AEVAL'] * $this->calculation_values['LMTDEVAL'];
        $CC[2][7] = $this->calculation_values['GREFL'] * ($this->calculation_values['J1L'] - $this->calculation_values['I1H']);

        $CC[0][8] = $this->calculation_values['GCWAL'] * $this->calculation_values['COGLY_ROWH1'] * ($this->calculation_values['COGLY_SPHT2L'] + $this->calculation_values['COGLY_SPHT1L']) * 0.5 * ($this->calculation_values['TCW2L'] - $this->calculation_values['TCW1L']) / 4187;  //ABSORBERL
        $CC[1][8] = $this->calculation_values['UABSL'] * $this->calculation_values['AABSL'] * $this->calculation_values['LMTDABSL'];
        $CC[2][8] = $this->calculation_values['GCONC'] * $this->calculation_values['I8'] + $this->calculation_values['GREFL'] * $this->calculation_values['J1L'] - $this->calculation_values['GDILL'] * $this->calculation_values['I2L'];

        $CC[0][9] = $this->calculation_values['GDIL2'] * ($this->calculation_values['I21'] - $this->calculation_values['I2']);                                                                  //DHE
        $CC[1][9] = $this->calculation_values['UDHE'] * $this->calculation_values['ADHE'] * $this->calculation_values['LMTDDHE'];
        $CC[2][9] = $this->calculation_values['GREF3'] * ($this->calculation_values['I13'] - $this->calculation_values['I22']);

        $CC[0][10] = $this->calculation_values['QHR'];                                                                                //HR
        $CC[1][10] = $this->calculation_values['GDIL2'] * ($this->calculation_values['I20'] - $this->calculation_values['I21']);
        $CC[2][10] = $this->calculation_values['UHR'] * $this->calculation_values['AHR'] * $this->calculation_values['LMTDHR'];

        $CC[0][11] = $this->calculation_values['GREF4'] * ($this->calculation_values['J4'] - $this->calculation_values['I13']);                                                                             //HR
        $CC[1][11] = $this->calculation_values['GHW'] * $this->calculation_values['HW_ROW1'] * (($this->calculation_values['HW_SPHT1'] + $this->calculation_values['HW_SPHT2']) / 2) * ($this->calculation_values['THW2'] - $this->calculation_values['THW1']);
        $CC[2][11] = $this->calculation_values['USA'] * $this->calculation_values['ASA'] * $this->calculation_values['LMTDSA'];

        for ($j = 0; $j < 12; $j++)
        {
            if ($CC[0][$j] <= $CC[1][$j] && $CC[0][$j] <= $CC[2][$j])   //MIN
                $CC[3][$j] = $CC[0][$j];
            if ($CC[1][$j] <= $CC[0][$j] && $CC[1][$j] <= $CC[2][$j])
                $CC[3][$j] = $CC[1][$j];
            if ($CC[2][$j] <= $CC[0][$j] && $CC[2][$j] <= $CC[1][$j])
                $CC[3][$j] = $CC[2][$j];

            if ($CC[0][$j] >= $CC[1][$j] && $CC[0][$j] >= $CC[2][$j])   //MAX
                $CC[4][$j] = $CC[0][$j];
            if ($CC[1][$j] >= $CC[0][$j] && $CC[1][$j] >= $CC[2][$j])
                $CC[4][$j] = $CC[1][$j];
            if ($CC[2][$j] >= $CC[0][$j] && $CC[2][$j] >= $CC[1][$j])
                $CC[4][$j] = $CC[2][$j];

            $CC[5][$j] = ($CC[4][$j] - $CC[3][$j]) / $CC[4][$j] * 100.0;    //$R
        }

        $HEATIN = ($this->calculation_values['TON'] * 3024) + $this->calculation_values['QHTG'] + $this->calculation_values['QHR'];
        $HEATOUT = ($this->calculation_values['GCWAH'] * $this->calculation_values['COGLY_ROWH1'] * ($this->calculation_values['COGLY_SPHT2H'] + $this->calculation_values['COGLY_SPHT1H']) * 0.5 * ($this->calculation_values['TCW2H'] - $this->calculation_values['TCW1H']) / 4187) + ($this->calculation_values['GCWAL'] * $this->calculation_values['COGLY_ROWH1'] * ($this->calculation_values['COGLY_SPHT2L'] + $this->calculation_values['COGLY_SPHT1L']) * 0.5 * ($this->calculation_values['TCW2L'] - $this->calculation_values['TCW1L']) / 4187) + ($this->calculation_values['GCWC'] * $this->calculation_values['COGLY_ROWH1'] * ($this->calculation_values['COGLY_SPHT4'] + $this->calculation_values['COGLY_SPHT3']) * 0.5 * ($this->calculation_values['TCW4'] - $this->calculation_values['TCW3']) / 4187) + $this->calculation_values['QSA'];
        $this->calculation_values['HBERROR'] = ($HEATIN - $HEATOUT) / $HEATIN * 100;
    }

    public function LMTDCHECK()
    {
        if (!isset($this->calculation_values['LMTDEVAH']) || is_nan($this->calculation_values['LMTDEVAH']) || $this->calculation_values['LMTDEVAH'] < 0)
        {
            return false;
        }
        else if (!isset($this->calculation_values['LMTDEVAL']) || is_nan($this->calculation_values['LMTDEVAL']) || $this->calculation_values['LMTDEVAL'] < 0)
        {
            return false;
        }
        else if (!isset($this->calculation_values['LMTDABSH']) || is_nan($this->calculation_values['LMTDABSH']) || $this->calculation_values['LMTDABSH'] < 0)
        {
            return false;
        }
        else if (!isset($this->calculation_values['LMTDABSL']) || is_nan($this->calculation_values['LMTDABSL']) || $this->calculation_values['LMTDABSL'] < 0)
        {
            return false;
        }
        else if (!isset($this->calculation_values['LMTDCON']) || is_nan($this->calculation_values['LMTDCON']) || $this->calculation_values['LMTDCON'] < 0)
        {
            return false;
        }
        else if (!isset($this->calculation_values['LMTDHTG']) || is_nan($this->calculation_values['LMTDHTG']) || $this->calculation_values['LMTDHTG'] < 0)
        {
            return false;
        }
        else if (!isset($this->calculation_values['LMTDLTG']) || is_nan($this->calculation_values['LMTDLTG']) || $this->calculation_values['LMTDLTG'] < 0)
        {
            return false;
        }
        else if (!isset($this->calculation_values['LMTDLTHE']) || is_nan($this->calculation_values['LMTDLTHE']) || $this->calculation_values['LMTDLTHE'] < 0)
        {
            return false;
        }
        else if (!isset($this->calculation_values['LMTDHTHE']) || is_nan($this->calculation_values['LMTDHTHE']) || $this->calculation_values['LMTDHTHE'] < 0)
        {
            return false;
        }
        else if (!isset($this->calculation_values['LMTDDHE']) || is_nan($this->calculation_values['LMTDDHE']) || $this->calculation_values['LMTDDHE'] < 0)
        {
            return false;
        }
        else if (!isset($this->calculation_values['LMTDHR']) || is_nan($this->calculation_values['LMTDHR']) || $this->calculation_values['LMTDHR'] < 0)
        {
            return false;
        }
        else if (!isset($this->calculation_values['LMTDSA']) || is_nan($this->calculation_values['LMTDSA']) || $this->calculation_values['LMTDSA'] < 0)
        {
            return false;
        }
        else
        {
            return true;
        }

    }


    public function SEPARATION_HEIGHT_HTG()
    {

        $HTG_HSEP_DS = 0;

        if ($this->calculation_values['MODEL'] == 60 || $this->calculation_values['MODEL'] == 90)
        {
            $HTG_HSEP_DS = 229.95 / 288.56;
        }
        if ($this->calculation_values['MODEL'] == 75 || $this->calculation_values['MODEL'] == 110)
        {
            $HTG_HSEP_DS = 208.95 / 288.56;
        }
        if ($this->calculation_values['MODEL'] == 150)
        {
            $HTG_HSEP_DS = 237.8 / 319.8;
        }
        if ($this->calculation_values['MODEL'] == 175 || $this->calculation_values['MODEL'] == 210)
        {
            $HTG_HSEP_DS = 216.8 / 324.4;
        }
        if ($this->calculation_values['MODEL'] == 250)
        {
            $HTG_HSEP_DS = 195.8 / 324.4;
        }
        if ($this->calculation_values['MODEL'] == 310 || $this->calculation_values['MODEL'] == 350 || $this->calculation_values['MODEL'] == 410)
        {
            $HTG_HSEP_DS = 237.0 / 377;
        }
        if ($this->calculation_values['MODEL'] == 470 || $this->calculation_values['MODEL'] == 530 || $this->calculation_values['MODEL'] == 580)
        {
            $HTG_HSEP_DS = 220 / 426.5;
        }
        if ($this->calculation_values['MODEL'] == 630 || $this->calculation_values['MODEL'] == 710)
        {
            $HTG_HSEP_DS = 238.3 / 476.5;
        }
        if ($this->calculation_values['MODEL'] == 760 || $this->calculation_values['MODEL'] == 810 || $this->calculation_values['MODEL'] == 900)
        {
            $HTG_HSEP_DS = 275 / 526.4;
        }
        if ($this->calculation_values['MODEL'] == 1010 || $this->calculation_values['MODEL'] == 1130)
        {
            $HTG_HSEP_DS = 275 / 526.4;
        }
        if ($this->calculation_values['MODEL'] == 1260 || $this->calculation_values['MODEL'] == 1380)
        {
            $HTG_HSEP_DS = 270 / 587.3;
        }
        if ($this->calculation_values['MODEL'] == 1560 || $this->calculation_values['MODEL'] == 1690 || $this->calculation_values['MODEL'] == 1890 || $this->calculation_values['MODEL'] == 2130 || $this->calculation_values['MODEL'] == 2270 || $this->calculation_values['MODEL'] == 2560)
        {
            $HTG_HSEP_DS = 257 / 635.6;
        }


        $HTG_HSEP_DS_REQ = (($this->calculation_values['QHTG'] / 860) / $this->calculation_values['AHTG']) * 0.015;

        if ($HTG_HSEP_DS < $HTG_HSEP_DS_REQ)
            return false;
        else
            return true;
    }

    public function CONCHECK()
    {
      
        if ($this->calculation_values['MODEL'] < 130)
        {
            if ($this->calculation_values['TCW11'] < 29.4 && $this->calculation_values['GCW'] <= $this->calculation_values['TON'])
                $this->calculation_values['KM'] = 62.6 - ((29.4 - $this->calculation_values['TCW11']) * 0.038462) + (2.166667 * ($this->calculation_values['GCW'] / $this->calculation_values['TON'])) - 2.166667;
            else
            {
                if ($this->calculation_values['TCW11'] < 29.4)
                    $this->calculation_values['KM'] = 62.6 - ((29.4 - $this->calculation_values['TCW11']) * 0.038462);
                else
                {
                    if ($this->calculation_values['GCW'] <= $this->calculation_values['TON'])
                        $this->calculation_values['KM'] = 62.6 + (2.166667 * ($this->calculation_values['GCW'] / $this->calculation_values['TON'])) - 2.166667;
                    else
                        $this->calculation_values['KM'] = 62.6;
                }
            }
        }
        else if ($this->calculation_values['MODEL'] > 130)
        {
            if ($this->calculation_values['TCW11'] < 29.4 && $this->calculation_values['GCW'] <= $this->calculation_values['TON'])
                $this->calculation_values['KM'] = 63.00 - ((29.4 - $this->calculation_values['TCW11']) * 0.038462) + (2.166667 * ($this->calculation_values['GCW'] / $this->calculation_values['TON'])) - 2.166667;
            else
            {
                if ($this->calculation_values['TCW11'] < 29.4)
                    $this->calculation_values['KM'] = 63.00 - ((29.4 - $this->calculation_values['TCW11']) * 0.038462);
                else
                {
                    if ($this->calculation_values['GCW'] <= $this->calculation_values['TON'])
                        $this->calculation_values['KM'] = 63.00 + (2.166667 * ($this->calculation_values['GCW'] / $this->calculation_values['TON'])) - 2.166667;
                    else
                        $this->calculation_values['KM'] = 63.00;
                }
            }
        }

        if (!$this->LMTDCHECK() || abs($this->calculation_values['HBERROR']) > 1)
        {
            $this->calculation_values['Notes'] = $this->notes['NOTES_ERROR'];
            return false;
        }
        else
        {
            if (!$this->HCAP())
            {
                $this->calculation_values['Notes'] = $this->notes['NOTES_ELIM_VELO'];
                return false;
            }
            else
            {
                if (!$this->SEPARATION_HEIGHT_HTG())
                {
                    $this->calculation_values['Notes'] = $this->notes['NOTES_SEP_HT_HTG'];
                    return false;
                }
                else
                {
                    if (!$this->SEPARATION_HEIGHT_HTG())
                    {
                        $this->calculation_values['Notes'] = $this->notes['NOTES_SEP_HT_HTG'];
                        return false;
                    }
                    else
                    {
                        if ($this->calculation_values['XCONC'] > $this->calculation_values['KM'])
                        {
                            $this->calculation_values['Notes'] = $this->notes['NOTES_FAIL_CONC'];
                            return false;
                        }
                        else
                        {
                            if ($this->calculation_values['PS'] > $this->calculation_values['PST1'])
                            {
                                $this->calculation_values['Notes'] = $this->notes['NOTES_FAIL_SPRESS'] . round(($this->calculation_values['PS'] + 0.05), 2) . " kg/sq.cm";
                                return false;
                            }
                            else
                            {
                                if ($this->calculation_values['TGP'] == 1 && $this->calculation_values['VG'] > 1.61)
                                {
                                    $this->calculation_values['Notes'] = $this->notes['NOTES_HW_VEL_HI'];
                                    return false;
                                }
                                else
                                {
                                    if ($this->calculation_values['SIDEARM'] == 1)
                                    {
                                        $this->calculation_values['Notes'] = $this->notes['NOTES_FAIL_SIDEARM'];
                                        return false;
                                    }
                                    else
                                    {
                                        if ($this->calculation_values['ASA'] > $this->calculation_values['SAA'])
                                        {
                                            $this->calculation_values['Notes'] = $this->notes['NOTES_FAIL_HEATDUTY'];
                                            return false;
                                        }
                                        else
                                        {
                                            if (($this->calculation_values['TCHW12'] >= 3.5 && $this->calculation_values['T1L'] < 0.5) || ($this->calculation_values['TCHW12'] < 3.5 && $this->calculation_values['T1L'] < (-3.999)))
                                            {
                                                $this->calculation_values['Notes'] = $this->notes['NOTES_REF_TEMP'];
                                                return false;
                                            }
                                            else
                                            {
                                                if ($this->calculation_values['TON'] < ($this->calculation_values['MODEL1'] * 0.35))
                                                {
                                                    $this->calculation_values['Notes'] = $this->notes['NOTES_CAPACITYLOW'];
                                                    return false;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return true;
    }

    public function SEPARATION_HEIGHT_LTG()
    {

        $LTG_HSEP_DS = 0;

        if ($this->calculation_values['MODEL'] == 60 || $this->calculation_values['MODEL'] == 90)
        {
            $LTG_HSEP_DS = 192.8 / 228.35;
        }
        if ($this->calculation_values['MODEL'] == 75 || $this->calculation_values['MODEL'] == 110)
        {
            $LTG_HSEP_DS = 173.8 / 228.35;
        }
        if ($this->calculation_values['MODEL'] == 150)
        {
            $LTG_HSEP_DS = 211.8 / 250.4;
        }
        if ($this->calculation_values['MODEL'] == 175 || $this->calculation_values['MODEL'] == 210)
        {
            $LTG_HSEP_DS = 192.8 / 250.4;
        }
        if ($this->calculation_values['MODEL'] == 250)
        {
            $LTG_HSEP_DS = 173.8 / 250.4;
        }
        if ($this->calculation_values['MODEL'] == 310 || $this->calculation_values['MODEL'] == 350 || $this->calculation_values['MODEL'] == 410)
        {
            $LTG_HSEP_DS = 205 / 393.2;
        }
        if ($this->calculation_values['MODEL'] == 470 || $this->calculation_values['MODEL'] == 530 || $this->calculation_values['MODEL'] == 580)
        {
            $LTG_HSEP_DS = 219.9 / 481.2;
        }
        if ($this->calculation_values['MODEL'] == 630 || $this->calculation_values['MODEL'] == 710)
        {
            $LTG_HSEP_DS = 239.9 / 525.2;
        }
        if ($this->calculation_values['MODEL'] == 760 || $this->calculation_values['MODEL'] == 810 || $this->calculation_values['MODEL'] == 900 || $this->calculation_values['MODEL'] == 1010 || $this->calculation_values['MODEL'] == 1130)
        {
            $LTG_HSEP_DS = 244.4 / 548.2;
        }
        if ($this->calculation_values['MODEL'] == 1260 || $this->calculation_values['MODEL'] == 1380)
        {
            $LTG_HSEP_DS = 258.9 / 617.2;
        }
        if ($this->calculation_values['MODEL'] == 1560 || $this->calculation_values['MODEL'] == 1690)
        {
            $LTG_HSEP_DS = 309.4 / 732.2;
        }
        if ($this->calculation_values['MODEL'] == 1890 || $this->calculation_values['MODEL'] == 2130 || $this->calculation_values['MODEL'] == 2270 || $this->calculation_values['MODEL'] == 2560)
        {
            $LTG_HSEP_DS = 319.4 / 806.9;
        }


        $LTG_HSEP_DS_REQ = (($this->calculation_values['QREFLTG'] / 860) / $this->calculation_values['ALTG']) * 0.015;

        if ($LTG_HSEP_DS < $LTG_HSEP_DS_REQ)
            return false;
        else
            return true;
    }


    public function HCAP()
    {

        $HIGHCAP = 0;

        if ($this->calculation_values['MODEL'] == 60)
        {
            $HIGHCAP = 70;
        }
        else if ($this->calculation_values['MODEL'] == 75)
        {
            $HIGHCAP = 88;
        }
        else if ($this->calculation_values['MODEL'] == 90)
        {
            $HIGHCAP = 105;
        }
        else if ($this->calculation_values['MODEL'] == 110)
        {
            $HIGHCAP = 128;
        }
        else if ($this->calculation_values['MODEL'] == 150)
        {
            $HIGHCAP = 174;
        }
        else if ($this->calculation_values['MODEL'] == 175)
        {
            $HIGHCAP = 203;
        }
        else if ($this->calculation_values['MODEL'] == 210)
        {
            $HIGHCAP = 244;
        }
        else if ($this->calculation_values['MODEL'] == 250)
        {
            $HIGHCAP = 290;
        }
        else if ($this->calculation_values['MODEL'] == 310)
        {
            $HIGHCAP = 360;
        }
        else if ($this->calculation_values['MODEL'] == 350)
        {
            $HIGHCAP = 410;
        }
        else if ($this->calculation_values['MODEL'] == 410)
        {
            $HIGHCAP = 490;
        }
        else if ($this->calculation_values['MODEL'] == 470)
        {
            $HIGHCAP = 550;
        }
        else if ($this->calculation_values['MODEL'] == 530)
        {
            $HIGHCAP = 630;
        }
        else if ($this->calculation_values['MODEL'] == 580)
        {
            $HIGHCAP = 680;
        }
        else if ($this->calculation_values['MODEL'] == 630)
        {
            $HIGHCAP = 750;
        }
        else if ($this->calculation_values['MODEL'] == 710)
        {
            $HIGHCAP = 830;
        }
        else if ($this->calculation_values['MODEL'] == 760)
        {
            $HIGHCAP = 900;
        }
        else if ($this->calculation_values['MODEL'] == 810)
        {
            $HIGHCAP = 960;
        }
        else if ($this->calculation_values['MODEL'] == 900)
        {
            $HIGHCAP = 1080;
        }
        else if ($this->calculation_values['MODEL'] == 1010)
        {
            $HIGHCAP = 1210;
        }
        else if ($this->calculation_values['MODEL'] == 1130)
        {
            $HIGHCAP = 1360;
        }
        else if ($this->calculation_values['MODEL'] == 1260)
        {
            $HIGHCAP = 1500;
        }
        else if ($this->calculation_values['MODEL'] == 1380)
        {
            $HIGHCAP = 1630;
        }
        else if ($this->calculation_values['MODEL'] == 1560)
        {
            $HIGHCAP = 1850;
        }
        else if ($this->calculation_values['MODEL'] == 1690)
        {
            $HIGHCAP = 2000;
        }
        else if ($this->calculation_values['MODEL'] == 1890)
        {
            $HIGHCAP = 2240;
        }
        else if ($this->calculation_values['MODEL'] == 2130)
        {
            $HIGHCAP = 2530;
        }
        else if ($this->calculation_values['MODEL'] == 2270)
        {
            $HIGHCAP = 2670;
        }
        else if ($this->calculation_values['MODEL'] == 2560)
        {
            $HIGHCAP = 2840;
        }
        //else if ($this->calculation_values['MODEL'] == 2600)
        //{
        //    $HIGHCAP = 3173;
        //}
        //else if ($this->calculation_values['MODEL'] == 2800)
        //{
        //    $HIGHCAP = 3378;
        //}
        else
        {
            $HIGHCAP = 0;
        }


        if ($this->calculation_values['TON'] > $HIGHCAP)
            return false;
        else
            return true;
    }


    public function RESULT_CALCULATE(){

        $notes = array();
        $selection_notes = array();
        $this->calculation_values['Notes'] = "";
        $this->calculation_values['selection_notes'] = "";

        if ($this->calculation_values['T13'] > $this->calculation_values['AT13'])
        {
            $this->calculation_values['Result'] = "FAILED";
            $this->calculation_values['Notes'] = $this->notes['NOTES_FAIL_TEMP'];
            return;
        }
        if (!$this->CONCHECK())
        {
            $this->calculation_values['Result'] = "FAILED";
            return;
        }

        $this->HEATBALANCE();

        //Assign the output properties of chiller
        $this->calculation_values['HeatInput'] = $this->calculation_values['GSTEAM'] * ($this->calculation_values['HSTEAM'] - 90.0);
        $this->calculation_values['HeatRejected'] = $this->calculation_values['TON'] * 3024 + $this->calculation_values['GSTEAM'] * ($this->calculation_values['HSTEAM'] - 90.0);

        $this->calculation_values['CoolingWaterOutTemperature'] = round($this->calculation_values['TCWA4'],1);
        $this->calculation_values['SteamConsumption'] = round($this->calculation_values['GSTEAM'],1);
        
        $this->calculation_values['EvaporatorPasses'] = $this->calculation_values['TP'] . "+" . $this->calculation_values['TP'];
        
        if ($this->calculation_values['TAP'] == 1)
        {
            $this->calculation_values['AbsorberPasses'] = $this->calculation_values['TAPH'] . "," . $this->calculation_values['TAPL'];
        }
        else
        {
            $this->calculation_values['AbsorberPasses'] = $this->calculation_values['TAPH'] . "+" . $this->calculation_values['TAPL'];
        }
        $this->calculation_values['CondenserPasses'] = $this->calculation_values['TCP'];
        $this->calculation_values['ChilledFrictionLoss'] = round($this->calculation_values['FLE'],1);
        $this->calculation_values['CoolingFrictionLoss'] = round((($this->calculation_values['FLA'] + $this->calculation_values['FC4'])),1);
        $this->calculation_values['ChilledPressureDrop'] = $this->calculation_values['PDE'];
        $this->calculation_values['CoolingPressureDrop'] = $this->calculation_values['PDA'];
        $this->calculation_values['ChilledWaterFlow'] = round($this->calculation_values['GCHW'],1);
        $this->calculation_values['BypassFlow'] = $this->calculation_values['GCW'] - $this->calculation_values['GCWC'];

        $this->calculation_values['COP'] = ($this->calculation_values['TON'] * 3024) / ($this->calculation_values['GSTEAM'] * ($this->calculation_values['HSTEAM'] - 90));

       // chiller.HotWaterInletTemp = $this->calculation_values['THW1'];
       // chiller.HotWaterOutletTemp = $this->calculation_values['THW2'];
        $this->calculation_values['HotWaterFlow']=$this->calculation_values['GHW'];
        $this->calculation_values['GeneratorPasses'] = $this->calculation_values['TGP'];
        $this->calculation_values['HotWaterFrictionLoss'] = $this->calculation_values['GFL'];
        $this->calculation_values['HotWaterConnectionDiameter'] = $this->calculation_values['NB'];

        // chiller.ModeBCapacity = $this->calculation_values['TON'] * 0.5;
        // chiller.ModeBChilledWaterOutTemperature = $this->calculation_values['TCHW11'] - ($this->calculation_values['DT'] / 2);
        // chiller.ModeBCoolingWaterInTemperature = "40";
        // chiller.ModeBCoolingWaterOutTemperature = TCWS;
        // chiller.ModeBSteamConsumption = (($this->calculation_values['GSTEAM'] - ($this->calculation_values['HEATCAP'] * 0.65 / ($this->calculation_values['HSTEAM'] - 90)))* 0.75);
        // chiller.ModeBHeatInput = (($this->calculation_values['GSTEAM'] - ($this->calculation_values['HEATCAP'] * 0.65 / ($this->calculation_values['HSTEAM'] - 90))) * ($this->calculation_values['HSTEAM'] - 90) * 0.75);
        // chiller.ModeBHeatRejected = ($this->calculation_values['TON'] * 1512) + (($this->calculation_values['GSTEAM'] - ($this->calculation_values['HEATCAP'] * 0.65 / ($this->calculation_values['HSTEAM'] - 90))) * ($this->calculation_values['HSTEAM'] - 90) * 0.75);

        $this->calculation_values['Result'] = "FAILED";
        if (($this->calculation_values['P3'] - $this->calculation_values['P1L']) < 35)
        {
            array_push($notes,$this->notes['NOTES_LTHE_PRDROP']);
            $this->calculation_values['HHType'] = "NonStandard";
        }
        if (($this->calculation_values['P4'] - $this->calculation_values['P3']) < 350)
        {
            array_push($notes,$this->notes['NOTES_HTHE_PRDROP']);
            $this->calculation_values['HHType'] = "NonStandard";
        }
        if ($this->calculation_values['VELEVA'] == 1)
        {
            array_push($notes,$this->notes['NOTES_EC_EVAP']);
            $this->calculation_values['ECinEva'] = 1;
        }
        if (!$this->calculation_values['isStandard'])
        {
            array_push($notes,$this->notes['NOTES_NSTD_TUBE_METAL']);

        }
        if ($this->calculation_values['TCHW12'] < 4.49)
        {
            array_push($notes,$this->notes['NOTES_COST_COW_SOV']);
        }
        if ($this->calculation_values['TCHW12'] < 4.49)
        {
            array_push($notes,$this->notes['NOTES_NONSTD_XSTK_MC']);
        }
        if ($this->calculation_values['GCWC'] < $this->calculation_values['GCW'])
        {
           array_push($notes,$this->notes['NOTES_OUTPUT_GA']);
           $bypass = $this->notes['NOTES_OUTPUT_BYPASS'].round($this->calculation_values['GCW'] - $this->calculation_values['GCWC'], 2)."m3/hr";
           array_push($notes,$bypass);
        }
        
        if ($this->calculation_values['TUU'] == 'ari')
        {
            array_push($notes,$this->notes['NOTES_ARI']);
        }
        
        array_push($notes,$this->notes['NOTES_INSUL']);
        array_push($notes,$this->notes['NOTES_NON_INSUL']);
        array_push($notes,$this->notes['NOTES_ROOM_TEMP']);
        array_push($notes,$this->notes['NOTES_CUSTOM']);

        if ($this->calculation_values['XCONC'] < ($this->calculation_values['KM'] - 0.8))
        {
            if ($this->calculation_values['T13'] < ($this->calculation_values['AT13'] - 2))
            {
                $this->calculation_values['Result'] = "OverDesigned";
            }
            if ($this->calculation_values['T13'] >= ($this->calculation_values['AT13'] - 2) && $this->calculation_values['T13'] <= ($this->calculation_values['AT13'] - 1))
            {
                array_push($notes,$this->notes['NOTES_RED_COW']);
                $this->calculation_values['Result'] = "GoodSelection";  
            }
            if ($this->calculation_values['T13'] > ($this->calculation_values['AT13'] - 1) && $this->calculation_values['T13'] < $this->calculation_values['AT13'])
            {
                $this->calculation_values['Result'] = "Optimal";
            }
        }
        if ($this->calculation_values['XCONC'] < ($this->calculation_values['KM'] - 0.4) && $this->calculation_values['XCONC'] > ($this->calculation_values['KM'] - 0.8))
        {
            if ($this->calculation_values['T13'] <= ($this->calculation_values['AT13'] - 1))
            {
                array_push($notes,$this->notes['NOTES_RED_COW']);
                $this->calculation_values['Result'] = "GoodSelection";
            }
            if ($this->calculation_values['T13'] > ($this->calculation_values['AT13'] - 1) && $this->calculation_values['T13'] < $this->calculation_values['AT13'])
            {
                $this->calculation_values['Result'] = "Optimal";
            }
        }
        if ($this->calculation_values['XCONC'] < $this->calculation_values['KM'] && $this->calculation_values['XCONC'] > ($this->calculation_values['KM'] - 0.4))
        {
            $this->calculation_values['Result'] = "Optimal";
        }
        
        $this->calculation_values['notes'] = $notes;
    }

    
    public function HEATBALANCE()
    {
        $ii = 1;
        $COGLY_SPHT11;
        $herr = array();
        $tcwa4 = array();

        $vam_base = new VamBaseController();

        $herr[0] = 2;
        while (abs($herr[$ii - 1]) > 0.001)
        {
            if ($ii == 1)
            {
                $tcwa4[$ii] = $this->calculation_values['TCW11'] + 5;
            }
            if ($ii == 2)
            {
                $tcwa4[$ii] = $tcwa4[$ii - 1] + 0.5;
            }
            if ($ii > 2)
            {
                $tcwa4[$ii] = $tcwa4[$ii - 1] + $herr[$ii - 1] * ($tcwa4[$ii - 1] - $tcwa4[$ii - 2]) / ($herr[$ii - 2] - $herr[$ii - 1]);
            }

            $this->calculation_values['TCWA4'] = $tcwa4[$ii];

            if ($this->calculation_values['GL'] == 2)
            {
                $this->calculation_values['COGLY_ROWH'] = $vam_base->EG_ROW($this->calculation_values['TCW11'], $this->calculation_values['COGLY']);
                $COGLY_SPHT11 = $vam_base->EG_SPHT($this->calculation_values['TCW11'], $this->calculation_values['COGLY']) * 1000;
                $this->calculation_values['COGLY_SPHTA4'] = $vam_base->EG_SPHT($this->calculation_values['TCWA4'], $this->calculation_values['COGLY']) * 1000;
            }
            else
            {
                $this->calculation_values['COGLY_ROWH'] = $vam_base->PG_ROW($this->calculation_values['TCW11'], $this->calculation_values['COGLY']);
                $COGLY_SPHT11 = $vam_base->PG_SPHT($this->calculation_values['TCW11'], $this->calculation_values['COGLY']) * 1000;
                $this->calculation_values['COGLY_SPHTA4'] = $vam_base->PG_SPHT($this->calculation_values['TCWA4'], $this->calculation_values['COGLY']) * 1000;
            }

            $this->calculation_values['QCW'] = ($this->calculation_values['GCW'] * $this->calculation_values['COGLY_ROWH'] * ($this->calculation_values['COGLY_SPHTA4'] + $COGLY_SPHT11) * 0.5 * ($this->calculation_values['TCWA4'] - $this->calculation_values['TCW11']) / 4187)+$this->calculation_values['QSA'];
            $this->calculation_values['QINPUT'] = ($this->calculation_values['TON'] * 3024) + ($this->calculation_values['GSTEAM'] * ($this->calculation_values['HSTEAM'] - 90));
            $herr[$ii] = ($this->calculation_values['QINPUT'] - $this->calculation_values['QCW']) * 100 / $this->calculation_values['QINPUT'];
            $ii++;
        }

        $jj = 1;
        $COGLY_SPHT; $COGLY_SPHTS;
        $herr1 = array();
        $tcws = array();


        $herr1[0] = 2;
        while (abs($herr[$jj - 1]) > 0.001)
        {
            if ($jj == 1)
            {
                $tcws[$jj] = 40 + 5;
            }
            if ($jj == 2)
            {
                $tcws[$jj] = $tcws[$jj - 1] + 0.5;
            }
            if ($jj > 2)
            {
                $tcws[$jj] = $tcws[$jj - 1] + $herr1[$jj - 1] * ($tcws[$jj - 1] - $tcws[$jj - 2]) / ($herr1[$jj - 2] - $herr1[$jj - 1]);
            }

            $this->calculation_values['TCWS'] = $tcws[$jj];

            if ($this->calculation_values['GL'] == 2)
            {
                $this->calculation_values['COGLY_ROWH'] = $vam_base->EG_ROW(40, $this->calculation_values['COGLY']);
                $COGLY_SPHT = $vam_base->EG_SPHT(40, $this->calculation_values['COGLY']) * 1000;
                $COGLY_SPHTS = $vam_base->EG_SPHT($this->calculation_values['TCWS'], $this->calculation_values['COGLY']) * 1000;
            }
            else
            {
                $this->calculation_values['COGLY_ROWH'] = $vam_base->PG_ROW(40, $this->calculation_values['COGLY']);
                $COGLY_SPHT = $vam_base->PG_SPHT(40, $this->calculation_values['COGLY']) * 1000;
                $COGLY_SPHTS = $vam_base->PG_SPHT($this->calculation_values['TCWS'], $this->calculation_values['COGLY']) * 1000;
            }

            $QCWS = ($this->calculation_values['GCW'] * $this->calculation_values['COGLY_ROWH'] * ($COGLY_SPHTS + $COGLY_SPHT) * 0.5 * ($this->calculation_values['TCWS'] - 40) / 4187);
            $this->calculation_values['QINPUTS'] = ($this->calculation_values['TON'] * 1512) + (($this->calculation_values['GSTEAM'] - ($this->calculation_values['HEATCAP'] * 0.65 / ($this->calculation_values['HSTEAM'] - 90))) * ($this->calculation_values['HSTEAM'] - 90) * 0.75);
            $herr1[$jj] = ($this->calculation_values['QINPUTS'] - $QCWS) * 100 / $this->calculation_values['QINPUTS'];
            $jj++;
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
            'steam_pressure',
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
            'steam_pressure_max_range',
            'steam_pressure_min_range',
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
            'USA_cooling_water_flow']);



        $region_type = Auth::user()->region_type;

        if($region_type == 2)
            $region_name = Auth::user()->region->name;
        else
            $region_name = '';


        $standard_values = array('evaporator_thickness' => 0,'absorber_thickness' => 0,'condenser_thickness' => 0,'evaporator_thickness_min_range' => 0,'evaporator_thickness_max_range' => 0,'absorber_thickness_min_range' => 0,'absorber_thickness_max_range' => 0,'condenser_thickness_min_range' => 0,'condenser_thickness_max_range' => 0,'fouling_chilled_water_value' => 0,'fouling_cooling_water_value' => 0,'evaporator_thickness_change' => 1,'absorber_thickness_change' => 1,'condenser_thickness_change' => 1,'fouling_chilled_water_checked' => 0,'fouling_cooling_water_checked' => 0,'fouling_chilled_water_disabled' => 1,'fouling_cooling_water_disabled' => 1,'fouling_chilled_water_value_disabled' => 1,'fouling_cooling_water_value_disabled' => 1,'region_name'=>$region_name,'region_type'=>$region_type);


        $form_values = collect($form_values)->union($standard_values);

        return $form_values;
    }
}
