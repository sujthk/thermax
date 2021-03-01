<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\VamBaseController;
use App\Http\Controllers\UnitConversionController;
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


class L5SeriesController extends Controller
{

    private $model_code = "L5";
    private $model_values;
    private $calculation_values;
    private $notes;
    private $changed_value;


    public function getL5Series(){

        $chiller_form_values = $this->getFormValues(185);

        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')
                                        ->where('code',$this->model_code)
                                        ->where('min_model','<=',185)->where('max_model','>',185)->first();

        // Log::info($chiller_metallurgy_options);                                
        $chiller_options = $chiller_metallurgy_options->chillerOptions;
        
        $evaporator_options = $chiller_options->where('type', 'eva');
        $absorber_options = $chiller_options->where('type', 'abs');
        $condenser_options = $chiller_options->where('type', 'con');

        $regions = Region::all();
        $unit_set_id = Auth::user()->unit_set_id;
        $unit_set = UnitSet::find($unit_set_id);
        $vam_base = new VamBaseController();
        $language_datas = $vam_base->getLanguageDatas();
        $units_data = $vam_base->getUnitsData();

        $min_chilled_water_out = Auth::user()->min_chilled_water_out;
        if($min_chilled_water_out > $chiller_form_values['min_chilled_water_out'])
            $chiller_form_values['min_chilled_water_out'] = $min_chilled_water_out;

        $unit_conversions = new UnitConversionController;
        
        $converted_values = $unit_conversions->formUnitConversion($chiller_form_values,$this->model_code);


        return view('l5_series')->with('default_values',$converted_values)
                                        ->with('language_datas',$language_datas)
                                        ->with('evaporator_options',$evaporator_options)
                                        ->with('absorber_options',$absorber_options)
                                        ->with('condenser_options',$condenser_options) 
                                        ->with('chiller_metallurgy_options',$chiller_metallurgy_options)
                                        ->with('unit_set',$unit_set)
                                        ->with('units_data',$units_data)
                                        ->with('regions',$regions);
    }


    public function postAjaxL5(Request $request){

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
       

        return response()->json(['status'=>true,'msg'=>'Ajax Datas','model_values'=>$converted_values,'changed_value'=>$this->changed_value]);
    }


    public function validateChillerAttribute($attribute){

        switch (strtoupper($attribute))
        {
            case "MODEL_NUMBER":
                // $this->modulNumberDoubleEffectS2();
                
                $range_calculation = $this->CWFLOW();
                
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
                $range_calculation = $this->CWFLOW();
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

                $range_calculation = $this->CWFLOW();
                if(!$range_calculation['status']){
                    return array('status'=>false,'msg'=>$range_calculation['msg']);
                }

                break;  
            
            case "EVAPORATOR_TUBE_TYPE":
                             
                $this->model_values['evaporator_thickness_change'] = true;
                if (floatval($this->model_values['chilled_water_out']) < 3.5 && floatval($this->model_values['glycol_chilled_water']) == 0)
                {
                    if (floatval($this->model_values['evaporator_material_value']) != 3)
                    {

                        return array('status' => false,'msg' => $this->notes['NOTES_EVA_TUBETYPE']);
                    }
                }
                $range_calculation = $this->CWFLOW();
                if(!$range_calculation['status']){          
                    return array('status'=>false,'msg'=>$range_calculation['msg']);
                }
                break;
            case "ABSORBER_TUBE_TYPE":
                $range_calculation = $this->CWFLOW();
                if(!$range_calculation['status']){
                    return array('status'=>false,'msg'=>$range_calculation['msg']);
                }
                break; 
            case "CONDENSER_TUBE_TYPE":
                $range_calculation = $this->CWFLOW();
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
                $range_calculation = $this->CWFLOW();
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

                    if(($cooling_water_flow > $min_range) && ($cooling_water_flow < $max_range)){
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
                    $range_calculation = $this->CWFLOW();
                    if(!$range_calculation['status']){
                        return array('status'=>false,'msg'=>$range_calculation['msg']);
                    }
                    break;
                }
                elseif ($this->model_values['evaporator_thickness'] != 0.57 && (($this->model_values['evaporator_thickness'] * 20) - (int)($this->model_values['evaporator_thickness'] * 20)) > 0) {
                    return array('status' => false,'msg' => $this->notes['NOTES_TUBE_THICK']);
                }
                else{
                    return array('status' => false,'msg' =>$this->notes['NOTES_EVA_THICK']);
                }
            break;
            case "ABSORBER_THICKNESS":
                $this->model_values['absorber_thickness_change'] = false;
                if(($this->model_values['absorber_thickness'] >= $this->model_values['absorber_thickness_min_range']) && ($this->model_values['absorber_thickness'] <= $this->model_values['absorber_thickness_max_range'])){
                    $range_calculation = $this->CWFLOW();
                    if(!$range_calculation['status']){
                        return array('status'=>false,'msg'=>$range_calculation['msg']);
                    }
                    break;
                }
                elseif ($this->model_values['absorber_thickness'] != 0.57 && (($this->model_values['absorber_thickness'] * 20) - (int)($this->model_values['absorber_thickness'] * 20)) > 0) {
                    return array('status' => false,'msg' => $this->notes['NOTES_TUBE_THICK']);
                }
                else{
                    return array('status' => false,'msg' => $this->notes['NOTES_ABS_THICK']);
                }
            break;
            case "CONDENSER_THICKNESS":
                $this->model_values['condenser_thickness_change'] = false;
                if(($this->model_values['condenser_thickness'] >= $this->model_values['condenser_thickness_min_range']) && ($this->model_values['condenser_thickness'] <= $this->model_values['condenser_thickness_max_range'])){
                    $range_calculation = $this->CWFLOW();
                    if(!$range_calculation['status']){
                        return array('status'=>false,'msg'=>$range_calculation['msg']);
                    }
                    break;
                }
                elseif ($this->model_values['condenser_thickness'] != 0.57 && (($this->model_values['condenser_thickness'] * 20) - (int)($this->model_values['condenser_thickness'] * 20)) > 0) {
                    return array('status' => false,'msg' => $this->notes['NOTES_TUBE_THICK']);
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
            case "FOULING_HOT_WATER_VALUE":
                if($this->model_values['fouling_factor'] != 'standard' && !empty($this->model_values['fouling_hot_water_checked'])){
                    if($this->model_values['fouling_hot_water_value'] < $this->model_values['fouling_non_hot']){
                        return array('status' => false,'msg' => $this->notes['NOTES_HOW_FF_MIN']);
                    }
                }
            break;
            case "GLYCOL_HOT_WATER":
                if($this->model_values['glycol_hot_water'] > $this->model_values['glycol_max_hot_water']){
                    return array('status' => false,'msg' => $this->notes['NOTES_HW_GLY_OR']);
                }
            break;
            case "HOT_WATER_IN":
                if(!$this->model_values['hot_water_in'] >= $this->model_values['how_water_temp_min_range']){
                    return array('status' => false,'msg' => $this->notes['NOTES_HWIT_OR']);
                }
            break;

        }


        return array('status' => true,'msg' => "process run successfully");

    }

    public function chilledWaterValidating(){
        if($this->model_values['chilled_water_out'] < 1){
            $this->model_values['glycol_none'] = 'true';
            $this->model_values['glycol_selected'] = 2;
        }
        else{
            $this->model_values['glycol_none'] = 'false';
            // $this->model_values['glycol_selected'] = 2;
        }
        

        $glycol_validator = $this->validateChillerAttribute('GLYCOL_TYPE_CHANGED');
        if(!$glycol_validator['status'])
            return array('status'=>false,'msg'=>$glycol_validator['msg']);


        $metallurgy_validator = $this->metallurgyValidating();
        if(!$metallurgy_validator['status'])
            return array('status'=>false,'msg'=>$metallurgy_validator['msg']);
        

        return  array('status' => true,'msg' => "process run successfully");
    }

    public function onChangeMetallurgyOption(){
        if($this->model_values['metallurgy_standard']){
            $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$this->model_code)
                                        ->where('min_model','<=',(int)$this->model_values['model_number'])->where('max_model','>',(int)$this->model_values['model_number'])->first();

            $this->model_values['evaporator_material_value'] = $chiller_metallurgy_options->eva_default_value;
            // $this->model_values['evaporator_thickness'] = $this->default_model_values['evaporator_thickness'];
            $this->model_values['absorber_material_value'] = $chiller_metallurgy_options->abs_default_value;
            // $this->model_values['absorber_thickness'] = $this->default_model_values['absorber_thickness'];
            $this->model_values['condenser_material_value'] = $chiller_metallurgy_options->con_default_value;
            // $this->model_values['condenser_thickness'] = $this->default_model_values['condenser_thickness'];
        }

    }

    public function metallurgyValidating(){
        
        if ($this->model_values['chilled_water_out'] < 3.499 && $this->model_values['chilled_water_out'] > 0.99 && $this->model_values['glycol_chilled_water'] == 0)
        {
            $this->model_values['tube_metallurgy_standard'] = 'false';
            $this->model_values['metallurgy_standard'] = false;
            $this->model_values['evaporator_material_value'] = 4;
            // $this->model_values['evaporator_thickness'] = 0.8;
            $this->model_values['evaporator_thickness_change'] = true;
            // $this->chillerAttributesChanged("EVAPORATORTUBETYPE");

        }
        else
        {   $this->model_values['tube_metallurgy_standard'] = 'true';
            $this->model_values['metallurgy_standard'] = true;
            $this->model_values['evaporator_thickness_change'] = true;
        }

        $evaporator_validator = $this->validateChillerAttribute('EVAPORATOR_TUBE_TYPE');
        if(!$evaporator_validator['status'])
            return array('status'=>false,'msg'=>$evaporator_validator['msg']);

        
        $this->onChangeMetallurgyOption();

        return  array('status' => true,'msg' => "process run successfully");
    }

    public function CWFLOW(){
        
        $MORE = 0;

        $this->RANGECAL();
        $range_values = array();

        if ($this->model_values['GCW1MIN'] > $this->model_values['FMAX'])
        {
            if ($this->model_values['GCW2MAX'] < $this->model_values['FMAX'] && $this->model_values['FMIN'] < $this->model_values['GCW2MAX'])
            {
                $range_values[] = $this->model_values['FMIN'];
                $range_values[] = $this->model_values['GCW2MAX'];
                $MORE = 1;
            }
            else if ($this->model_values['FMIN'] < $this->model_values['FMAX'])
            {
                $range_values[] = $this->model_values['FMIN'];
                $range_values[] = $this->model_values['FMAX'];
                $MORE = 1;
            }

        }
        else if ($this->model_values['GCW2MAX'] < $this->model_values['FMIN'])
        {
            if ($this->model_values['GCW1MIN'] > $this->model_values['FMIN'] && $this->model_values['GCW1MIN'] < $this->model_values['FMAX'])
            {
                $range_values[] = $this->model_values['GCW1MIN'];
                $range_values[] = $this->model_values['FMAX'];
                $MORE = 1;
            }
            else if ($this->model_values['FMIN'] < $this->model_values['FMAX'])
            {
                $range_values[] = $this->model_values['FMIN'];
                $range_values[] = $this->model_values['FMAX'];
                $MORE = 1;
            }
        }
        else
        {
            $range_values[] = $this->model_values['FMIN'];
            $range_values[] = $this->model_values['GCW2MAX'];
            $range_values[] = $this->model_values['GCW1MIN'];
            $range_values[] = $this->model_values['FMAX'];
            $MORE = 1;
        }
        if ($MORE == 0)
        {
            return array('status' => false,'msg' => $this->notes['NOTES_COW_MAX_LIM']);
        }
        $this->model_values['cooling_water_ranges'] = $range_values;

        return array('status' => true,'msg' => "process run successfully");

    }

    public function RANGECAL()
    {

        $TCHW2L = $this->$model_values['chilled_water_out'];
        $TCW11 = $this->$model_values['cooling_water_in'];
        $TON = $this->$model_values['capacity'];
        $MODEL = $this->$model_values['model_number'];
        


        if ($TCHW2L > 5.99)
        {
            if ($TCW11 > 25.99)
            {
                $GCWMIN2 = $TON * 0.9;
            }
            else
            {
                $GCWMIN2 = $TON * (0.9 - ((26 - $TCW11) * .025));
            }
        }
        else
        {
            if ($TCHW2L > 4.49)
            {
                $GCWMIN2 = $TON * 1;
            }
            else if ($TCHW2L > 3.49)
            {
                $GCWMIN2 = $TON * (1 + ((4.5 - $TCHW2L) * .2));
            }
            else
            {
                $GCWMIN2 = $TON * 1.2;
            }
        }

        if ($MODEL < 700)
        {
            $VAMIN = 1.5; $VAMAX = 2.6;
            $VCMIN = 1.3; $VCMAX = 2.6;
        }
        else
        {
            $VAMIN = 1.58; $VAMAX = 2.78;
            $VCMIN = 1.35; $VCMAX = 2.78;
        }


        $this->updateInputs();

        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$this->model_code)
                                        ->where('min_model','<=',$model_number)->where('max_model','>',$model_number)->first();

        $chiller_options = $chiller_metallurgy_options->chillerOptions;
        
        // $evaporator_option = $chiller_options->where('type', 'eva')->where('value',$this->model_values['evaporator_material_value'])->first();
        $absorber_option = $chiller_options->where('type', 'abs')->where('value',$this->calculation_values['TU5'])->first();
        $condenser_option = $chiller_options->where('type', 'con')->where('value',$this->calculation_values['TV5'])->first();

        $VAMIN = $absorber_option->metallurgy->abs_min_velocity;          
        $VAMAX = $absorber_option->metallurgy->abs_max_velocity;
        $VCMIN = $condenser_option->metallurgy->con_min_velocity;
        $VCMAX = $condenser_option->metallurgy->con_max_velocity;

        $FMIN1 = 3.142 / 4 * floatval($this->calculation_values['IDC']) * floatval($this->calculation_values['IDC']) * floatval($this->calculation_values['TNC']) / 2 * $VCMIN * 3600 / 1;     //MIN FLOW CORRESPONDS TO 2 PASS SERIES FLOW IN CONDENSER

        $this->PIPE_SIZE();

        $APC = 3.141593 * $this->calculation_values['PIDC'] * $this->calculation_values['PIDC'] / 4;

        if ($GCWMIN2 > $FMIN1)
        {
            $this->calculation_values['FMIN'] = $GCWMIN2;
        }
        else
        {
            $this->calculation_values['FMIN'] = $FMIN1;
        }

        if ($MODEL < 340)
        {
            $FMAX1 = $APC * 4.2 * 3600;                       //MAX VELOCITY = 4.2M/SEC
        }
        else if ($MODEL > 340 && $MODEL < 1000)
        {
            $FMAX1 = $APC * 4 * 3600;                     //MAX VELOCITY = 4M/SEC
        }
        else
        {
            $FMAX1 = $APC * 4.5 * 3600;                       //MAX VELOCITY = 4.5M/SEC $MODELS 80C & 80D
        }

        $this->calculation_values['GCW2MAX'] = 3.142 / 4 * floatval($this->calculation_values['IDA']) * floatval($this->calculation_values['IDA']) * floatval($this->calculation_values['TNAA']) / 2 * $VAMAX * 3600;      //COW FLOW RANGES AT WHICH THERE IS 1,1 PASS IN ABSORBER AND VELOCITY IS LESS THAN 1.5 ARE BETWEEN GCW2MAX AND GCW1MIN
        $this->calculation_values['GCW1MIN'] = 3.142 / 4 * floatval($this->calculation_values['IDA']) * floatval($this->calculation_values['IDA']) * floatval($this->calculation_values['TNAA']) / 1 * $VAMIN * 3600;      // SUCH COW FLOW RANGES ARE NOT DISPLAYED

        $FMAX2 = 3.142 / 4 * floatval($this->calculation_values['IDA']) * floatval($this->calculation_values['IDA']) * floatval($this->calculation_values['TNAA']) / 1 * $VAMAX * 3600;       // 11.9.14 MAX FLOW CORRESPONDS TO 1,1 PASS FLOW IN ABS


        if ($FMAX2 < $FMAX1) // 11.9.14
        {
            $this->calculation_values['FMAX'] = $FMAX2;
        }
        else
        {
            $this->calculation_values['FMAX'] = $FMAX1;
        }



        //if ($MODEL == 600 || $MODEL == 650) // 11.9.14  ALLOW 1,1 PASS IN THESS $MODELS WITH 1.5MIN VEL
        //{
        //    FMIN2 = 3.142 / 4 * IDA * IDA * TNAA * $VAMIN * 3600;
        //}
    }

    public function PIPE_SIZE(){
        $vam_base = new VamBaseController();

        $NB = $this->calculation_values['EVANB'];
        $vam_base->PIPE_ID($NB);
        $this->calculation_values['PIDE'] = $this->calculation_values['PID'];
        $this->calculation_values['PODE'] = $this->calculation_values['POD'];
        $this->calculation_values['EVANB'] = $NB;

        $NB = $this->calculation_values['PNB'];
        $vam_base->PIPE_ID($NB);
        $this->calculation_values['PIDA'] = $this->calculation_values['PID'];
        $this->calculation_values['PODA'] = $this->calculation_values['POD'];
        $this->calculation_values['ABSNB'] = $NB;

        $NB = $this->calculation_values['CONNB'];
        $vam_base->PIPE_ID($NB);
        $this->calculation_values['PIDC'] = $this->calculation_values['PID'];
        $this->calculation_values['PODC'] = $this->calculation_values['POD'];
        $this->calculation_values['CONNB'] = $NB;

        if ($this->calculation_values['TGP'] == 6)
        {
            $this->PR_HW_DATA();
            $NB = $this->calculation_values['PNBH'];
        }
        else
        {
            $NB = $this->calculation_values['GENNB'];
        }
        $vam_base->PIPE_ID($NB);
        $this->calculation_values['PIDG'] = $this->calculation_values['PID'];
        $this->calculation_values['PODG'] = $this->calculation_values['POD'];
        $this->calculation_values['GENNB'] = $NB;
    }

    public function PR_HW_DATA()
    {
        if ($this->calculation_values['GHOT'] < 0.99)
        {
            $this->calculation_values['PNBH'] = 25;
        }
        else if ($this->calculation_values['GHOT'] > 0.99 && $this->calculation_values['GHOT'] < 1.99)
        {
            $this->calculation_values['PNBH'] = 32;
        }
        else if ($this->calculation_values['GHOT'] > 1.99 && $this->calculation_values['GHOT'] < 3.499)
        {
            $this->calculation_values['PNBH'] = 40;
        }
        else if ($this->calculation_values['GHOT'] > 3.499 && $this->calculation_values['GHOT'] < 6.99)
        {
            $this->calculation_values['PNBH'] = 50;
        }
        else if ($this->calculation_values['GHOT'] > 6.99 && $this->calculation_values['GHOT'] < 29.99)
        {
            $this->calculation_values['PNBH'] = 80;
        }
        else if ($this->calculation_values['GHOT'] > 29.99 && $this->calculation_values['GHOT'] < 64.99)
        {
            $this->calculation_values['PNBH'] = 100;
        }
        else if ($this->calculation_values['GHOT'] > 64.99 && $this->calculation_values['GHOT'] < 154.99)
        {
            $this->calculation_values['PNBH'] = 150;
        }
        else if ($this->calculation_values['GHOT'] > 154.99 && $this->calculation_values['GHOT'] < 294.99)
        {
            $this->calculation_values['PNBH'] = 200;
        }
    }



    public function updateInputs(){

        $model_number = (int)$this->model_values['model_number'];
        $calculation_values = $this->getCalculationValues($model_number);
        
        $this->calculation_values = $calculation_values;

        $this->calculation_values['region_type'] = $this->model_values['region_type'];
        $this->calculation_values['model_name'] = $this->model_values['model_name'];
        

        $vam_base = new VamBaseController();

        $pid_ft3 = $vam_base->PIPE_ID($this->calculation_values['PNB']);
        $this->calculation_values['PODA'] = $pid_ft3['PODA'];
        $this->calculation_values['THPA'] = $pid_ft3['THPA'];

        
        $this->calculation_values['PSL1'] = $this->calculation_values['PSLI'] + $this->calculation_values['PSLO'];


        $this->calculation_values['MODEL'] = $this->model_values['model_number'];
        $this->calculation_values['TON'] = $this->model_values['capacity'];
        $this->calculation_values['TUU'] = $this->model_values['fouling_factor'];
        $this->calculation_values['FFCHW1'] = floatval($this->model_values['fouling_chilled_water_value']);
        $this->calculation_values['FFCOW1'] = floatval($this->model_values['fouling_cooling_water_value']);
        $this->calculation_values['FFHOW1'] = floatval($this->model_values['fouling_hot_water_value']);

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
            $this->calculation_values['TG2'] = 1;
            $this->calculation_values['TG3'] = 0.65;
            // $this->calculation_values['FFCHW'] = 0.0; 
            // $this->calculation_values['FFCOW'] = 0.0;
        }
        else{
            $this->calculation_values['TU5'] = $this->model_values['absorber_material_value']; 
            $this->calculation_values['TU6'] = $this->model_values['absorber_thickness']; 

            $this->calculation_values['TU2'] = $this->model_values['evaporator_material_value']; 
            $this->calculation_values['TU3'] = $this->model_values['evaporator_thickness'];

            $this->calculation_values['TV5'] = $this->model_values['condenser_material_value']; 
            $this->calculation_values['TV6'] = $this->model_values['condenser_thickness'];

            $this->calculation_values['TG2'] = $this->model_values['generator_tube_value'];
            if($this->calculation_values['TG2'] == 1){
                $this->calculation_values['TG3'] = 0.65;
            }
            elseif ($this->calculation_values['TG2'] == 2) {
                $this->calculation_values['TG3'] = 0.8;
            }
        }


        $this->calculation_values['GCW'] = $this->model_values['cooling_water_flow'];
        $this->calculation_values['TCW11'] = $this->model_values['cooling_water_in'];
         $this->calculation_values['GL'] = $this->model_values['glycol_selected']; 
        $this->calculation_values['CHGLY'] = $this->model_values['glycol_chilled_water']; 
        $this->calculation_values['COGLY'] = $this->model_values['glycol_cooling_water'];

        $this->calculation_values['HWGLY'] = $this->model_values['glycol_hot_water']; 

        $this->calculation_values['TCHW1H'] = $this->model_values['chilled_water_in']; 
        $this->calculation_values['TCHW2L'] = $this->model_values['chilled_water_out'];
        $this->calculation_values['THW1'] = $this->model_values['hot_water_in'];
        $this->calculation_values['GHOT'] = $this->model_values['hot_water_flow'];
        $this->calculation_values['GHW'] = $this->calculation_values['GHOT'];

        if($this->calculation_values['TCHW2L'] < 3.5){
            $this->calculation_values['SFACTOR'] = $this->calculation_values['A_SFACTOR'] - (($this->calculation_values['B_SFACTOR'] - $this->calculation_values['TCHW2L']) *2/100);
            $this->calculation_values['GHW'] = $this->calculation_values['GHOT'] * $this->calculation_values['SFACTOR'];
        }

        $this->calculation_values['ODE'] = ($evaporator_option->metallurgy->ode)/1000;
        $this->calculation_values['ODA'] = ($absorber_option->metallurgy->ode)/1000;
        $this->calculation_values['ODC'] = ($condenser_option->metallurgy->ode)/1000;
        $this->calculation_values['ODG'] = 0.016;
 

        $this->calculation_values['isStandard'] = $this->model_values['metallurgy_standard']; 

        // // Standard Calculation Values
        $this->calculation_values['CoolingWaterOutTemperature'] = 0;
        $this->calculation_values['ChilledWaterFlow'] = 0;
        $this->calculation_values['BypassFlow'] = 0;
        $this->calculation_values['ChilledFrictionLoss'] = 0;
        $this->calculation_values['CoolingFrictionLoss'] = 0;
        $this->calculation_values['HotwaterFrictionLoss'] = 0;
        


        $this->DATA();

        $this->THICKNESS();
    }


    private function DATA()
    {


        $this->calculation_values['TNEV1'] = $this->calculation_values['TNEV'] / 2; 
        $this->calculation_values['TNG1'] = $this->calculation_values['TNG'] / 2;

        $this->calculation_values['KEVA'] = $this->calculation_values['KEVAH'];


        $this->calculation_values['AEVAH'] = $this->calculation_values['AEVA'] / 2;
        $this->calculation_values['AEVAL'] = $this->calculation_values['AEVA'] / 2;


        $this->calculation_values['ABSH'] = $this->calculation_values['AABS'] /2 ;
        $this->calculation_values['ABSL'] = $this->calculation_values['AABS'] /2 ;


        $this->calculation_values['ACONH'] = $this->calculation_values['ACON'] /2 ;
        $this->calculation_values['ACONL'] = $this->calculation_values['ACON'] /2 ;

        $this->calculation_values['AGENH'] = $this->calculation_values['AGEN'] /2 ;
        $this->calculation_values['AGENL'] = $this->calculation_values['AGEN'] /2 ;

        $vam_base = new VamBaseController();
        $THWO = $this->calculation_values['THW1'] - (($this->calculation_values['TON'] * 3024 * 4.187) / (0.77 * $this->calculation_values['GHW'] * $vam_base->PG_ROW($this->calculation_values['THW1'], 0) * $vam_base->PG_SPHT($this->calculation_values['THW1'], 0)));

        $this->calculation_values['AVGT'] = ($THWO + $this->calculation_values['THW1']) / 2;

        if ($this->calculation_values['AVGT'] > 80)
        {
            $this->calculation_values['UGEN'] = 1100;    // 1000; 
        }
        else if ($this->calculation_values['AVGT'] <= 80)
        {
            $this->calculation_values['UGEN'] = 1050;
        }

        $this->calculation_values['AGENL'] = $this->calculation_values['AGENL'] * 1.0;
        $this->calculation_values['AGENH'] = $this->calculation_values['AGENH'] * 1.0;

        $this->calculation_values['ALTHE'] = $this->calculation_values['ALTHE'] * $this->calculation_values['ALTHE_F'];


        $this->calculation_values['KEVA1'] = 1 / ((1 / $this->calculation_values['KEVAH']) - (0.65 / 340000.0));

        if ($this->calculation_values['TU2'] == 1){
            $this->calculation_values['KEVAH'] = (1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 37000))) * 0.95;
        }
        if ($this->calculation_values['TU2'] == 2 || $this->calculation_values['TU2'] == 6){
            $this->calculation_values['KEVAH'] = 1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 340000.0));            
        }
        if ($this->calculation_values['TU2'] == 4){
            $this->calculation_values['KEVAH'] = (1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 21000.0))) * 0.93;
        }
        if ($this->calculation_values['TU2'] == 3){
            $this->calculation_values['KEVAH'] = 1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 21000.0)) * 0.93;              //Changed to $this->calculation_values['KEVA1'] from 1600 on 06/11/2017 as tube metallurgy is changed
        }
        if ($this->calculation_values['TU2'] == 5){
            $this->calculation_values['KEVAH'] = 1 / ((1 / 1600.0) + ($this->calculation_values['TU3'] / 15000.0));
        }

        /******** DETERMINATION OF $this->calculation_values['KEVAL'] FOR NON STD.SELECTION*****/
        $this->calculation_values['KEVA2'] = 1 / ((1 / $this->calculation_values['KEVAL']) - (0.65 / 340000));

        if ($this->calculation_values['TU2'] == 1){
            $this->calculation_values['KEVAL'] = (1 / ((1 / $this->calculation_values['KEVA2']) + ($this->calculation_values['TU3'] / 37000)));
        }
        if ($this->calculation_values['TU2'] == 2 || $this->calculation_values['TU2'] == 6){
            $this->calculation_values['KEVAL'] = 1 / ((1 / $this->calculation_values['KEVA2']) + ($this->calculation_values['TU3'] / 340000.0));            
        }
        if ($this->calculation_values['TU2'] == 4){
            $this->calculation_values['KEVAL'] = (1 / ((1 / $this->calculation_values['KEVA2']) + ($this->calculation_values['TU3'] / 21000.0))) * 0.93;
        }
        if ($this->calculation_values['TU2'] == 3){
            $this->calculation_values['KEVAL'] = 1 / ((1 / $this->calculation_values['KEVA2']) + ($this->calculation_values['TU3'] / 21000.0)) * 0.93;              //Changed to $this->calculation_values['KEVA1'] from 1600 on 06/11/2017 as tube metallurgy is changed
        }
        if ($this->calculation_values['TU2'] == 5){
            $this->calculation_values['KEVAL'] = 1 / ((1 / 1600.0) + ($this->calculation_values['TU3'] / 15000.0));
        }

        /********* DETERMINATION OF KABS FOR NONSTD. SELECTION****/
        $this->calculation_values['KABS1'] = 1 / ((1 / $this->calculation_values['KABS']) - (0.65 / 340000));
        if ($this->calculation_values['TU5'] == 1)
        {
            $this->calculation_values['KABS'] = 1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 37000)) * 0.95;
        }
        else
        {
            if ($this->calculation_values['TU5'] == 2){
                $this->calculation_values['KABS'] = 1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 340000));
            }
            if ($this->calculation_values['TU5'] == 6){
                $this->calculation_values['KABS'] = (1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 21000))) * 0.93;
            }
            else
            {
                $this->calculation_values['KABS1'] = 1240;
                //if ($this->calculation_values['TU5'] == 3)
                //    $this->calculation_values['KABS'] = 1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 37000));
                //if ($this->calculation_values['TU5'] == 4)
                //    $this->calculation_values['KABS'] = 1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 340000));
                //if ($this->calculation_values['TU5'] == 5)
                //    $this->calculation_values['KABS'] = 1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 21000));
                if ($this->calculation_values['TU5'] == 7){
                    $this->calculation_values['KABS'] = 1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 15000));
                }
            }
        }

        /********** DETERMINATION OF KCON IN NONSTD. SELECTION*******/
        if ($this->calculation_values['MODEL'] < 600)
        {
            $this->calculation_values['KCON1'] = 1 / ((1 / $this->calculation_values['KCON']) - (0.6 / 340000));
        }
        else
        {
            $this->calculation_values['KCON1'] = 1 / ((1 / $this->calculation_values['KCON']) - (0.65 / 340000));
        }

        if ($this->calculation_values['TV5'] == 1)
        {
            $this->calculation_values['KCON'] = 3900;
            $this->calculation_values['KCON1'] = 1 / ((1 / $this->calculation_values['KCON']) - (0.65 / 340000));
            $this->calculation_values['KCON'] = 1 / ((1 / $this->calculation_values['KCON1']) + ($this->calculation_values['TV6'] / 37000));
        }
        else if ($this->calculation_values['TV5'] == 2)
            $this->calculation_values['KCON'] = 1 / ((1 / $this->calculation_values['KCON1']) + ($this->calculation_values['TV6'] / 340000));
        else if ($this->calculation_values['TV5'] == 3)
        {
            $this->calculation_values['KCON1'] = 4080;
            $this->calculation_values['KCON'] = 1 / ((1 / $this->calculation_values['KCON1']) + ($this->calculation_values['TV6'] / 21000)) * 0.93;
        }
        else
        {
            $this->calculation_values['KCON1'] = 3000;

            //if ($this->calculation_values['TV5'] == 4)
            //    $this->calculation_values['KCON'] = 1 / ((1 / $this->calculation_values['KCON1']) + ($this->calculation_values['TV6'] / 340000));
            if ($this->calculation_values['TV5'] == 5){
                $this->calculation_values['KCON'] = 1 / ((1 / $this->calculation_values['KCON1']) + ($this->calculation_values['TV6'] / 15000));
            }
        }
        if ($this->calculation_values['TV5'] == 0)
        {
            $this->calculation_values['KCON'] = 3000 * 2;
        }

       
    }


    private function THICKNESS()
    {


        if ($this->calculation_values['TU2'] == 4 || $this->calculation_values['TU2'] == 6)
        {
            $this->calculation_values['THE'] = $this->calculation_values['TU3'] + 0.1;
        }
        else
        {
            $this->calculation_values['THE'] = $this->calculation_values['TU3'];
        }


        if ($this->calculation_values['TU5'] < 2.1 || $this->calculation_values['TU5'] == 6)
        {
            $this->calculation_values['THA'] = $this->calculation_values['TU6'] + 0.1;
        }
        else
        {
            $this->calculation_values['THA'] = $this->calculation_values['TU6'];
        }

        if ($this->calculation_values['TV5'] == 1 || $this->calculation_values['TV5'] == 3)
        {
            $this->calculation_values['THC'] = $this->calculation_values['TV6'] + 0.1;
        }
        else
        {
            $this->calculation_values['THC'] = $this->calculation_values['TV6'];
        }

        $this->calculation_values['THG'] = $this->calculation_values['TG3'] + 0.1;

        $this->calculation_values['IDE']  = $this->calculation_values['ODE']  - (2.0 * $this->calculation_values['THE'] / 1000);
        $this->calculation_values['IDA']  = $this->calculation_values['ODA']  - (2.0 * $this->calculation_values['THA'] / 1000);
        $this->calculation_values['IDC']  = $this->calculation_values['ODC']  - (2.0 * $this->calculation_values['THC'] / 1000);
        $this->calculation_values['IDG']  = $this->calculation_values['ODG']  - (2.0 * $this->calculation_values['THG'] / 1000);


    }

    public function loadSpecSheetData(){
        $model_number = floatval($this->calculation_values['MODEL']);
         if($this->calculation_values['region_type'] == 2)
        {
            $this->calculation_values['TotalPowerConsumption'] = (1.732 * 460 * ($this->calculation_values['USA_HPAbsorbentPumpMotorAmp'] + $this->calculation_values['USA_RefrigerantPumpMotorAmp'] + $this->calculation_values['USA_PurgePumpMotorAmp'] + $this->calculation_values['USA_LPAbsorbentPumpMotorAmp']) / 1000) + 1;

            $this->calculation_values['PowerSupply'] = "460 V( ±10%), 60 Hz (±5%), 3 Phase+N";
        }
        else
        {
            $this->calculation_values['TotalPowerConsumption'] = (1.732 * 415 * ($this->calculation_values['HPAbsorbentPumpMotorAmp'] + $this->calculation_values['RefrigerantPumpMotorAmp'] + $this->calculation_values['LPAbsorbentPumpMotorAmp'] + $this->calculation_values['PurgePumpMotorAmp']) / 1000) + 1;

            $this->calculation_values['PowerSupply'] = "415 V( ±10%), 50 Hz (±5%), 3 Phase+N";

        }

        switch ($model_number) {
            case 185:
                if $this->calculation_values['TCHW2L'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC L5 D3";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC L5 D3";
                }

                break;
            case 210:
                if ($this->calculation_values['TCHW2L'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC L5 D4";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC L5 D4";
                }
                
                break;    

            case 245:
                if ($this->calculation_values['TCHW2L'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC L5 E1";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC L5 E1";
                }
                
                break; 
            case 270:
                if ($this->calculation_values['TCHW2L'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC L5 E2";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC L5 E2";
                }

                break;
            case 310:
                if ($this->calculation_values['TCHW2L'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC L5 E3";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC L5 E3";
                }    
                break;
            case 340:
                if ($this->calculation_values['TCHW2L'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC L5 E4";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC L5 E4";
                }    
                break;
            case 380: 
                if ($this->calculation_values['TCHW2L'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC L5 E5";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC L5 E5";
                }
                break;
            case 425:
                if ($this->calculation_values['TCHW2L'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC L5 E6";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC L5 E6";
                }
                break;     
            case 485:
                if ($this->calculation_values['TCHW2L'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC L5 F2";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC L5 F2";
                }
                break;    
            case 540:
                if ($this->calculation_values['TCHW2L'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC L5 F3";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC L5 F3";
                }
                break;  
            case 630:
                if ($this->calculation_values['TCHW2L'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC L5 G1";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC L5 G1";
                }
                break;  
            case 690:
                if ($this->calculation_values['TCHW2L'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC L5 G2";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC L5 G2";
                }
                break;
            case 730:
                if ($this->calculation_values['TCHW2L'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC L5 G3";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC L5 G3";
                }
                break; 
            case 780:
                if ($this->calculation_values['TCHW2L'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC L5 G4";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC L5 G4";
                }
                break;
            case 850:
                if ($this->calculation_values['TCHW2L'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC L5 G5";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC L5 G5";
                }
                break;
            case 950:
                if ($this->calculation_values['TCHW2L'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC L5 G6";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC L5 G6";
                }
                break;
            case 1050:
                if ($this->calculation_values['TCHW2L'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC L5 H1";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC L5 H1";
                }
                break; 
            case 1150:
                if ($this->calculation_values['TCHW2L'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC L5 H2";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC L5 H2";
                }
                break; 
            case 1260:
                if ($this->calculation_values['TCHW2L'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC L5 J1";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC L5 J1";
                }
                break;
            case 1380:
                if ($this->calculation_values['TCHW2L'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC L5 J2";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC L5 J2";
                }
                break;                                     
            default:
                # code...
                break;    
        }    
    }


    public function castToBoolean(){
        $vam_base = new VamBaseController();
        
        $this->model_values['metallurgy_standard'] = $vam_base->getBoolean($this->model_values['metallurgy_standard']);

        $this->model_values['evaporator_thickness_change'] = $vam_base->getBoolean($this->model_values['evaporator_thickness_change']);
        $this->model_values['absorber_thickness_change'] = $vam_base->getBoolean($this->model_values['absorber_thickness_change']);
        $this->model_values['condenser_thickness_change'] = $vam_base->getBoolean($this->model_values['condenser_thickness_change']);
        $this->model_values['fouling_chilled_water_checked'] = $vam_base->getBoolean($this->model_values['fouling_chilled_water_checked']);
        $this->model_values['fouling_cooling_water_checked'] = $vam_base->getBoolean($this->model_values['fouling_cooling_water_checked']);
        $this->model_values['fouling_hot_water_checked'] = $vam_base->getBoolean($this->model_values['fouling_hot_water_checked']);
        $this->model_values['fouling_chilled_water_disabled'] = $vam_base->getBoolean($this->model_values['fouling_chilled_water_disabled']);
        $this->model_values['fouling_cooling_water_disabled'] = $vam_base->getBoolean($this->model_values['fouling_cooling_water_disabled']);
        $this->model_values['fouling_hot_water_disabled'] = $vam_base->getBoolean($this->model_values['fouling_hot_water_disabled']);
        $this->model_values['fouling_chilled_water_value_disabled'] = $vam_base->getBoolean($this->model_values['fouling_chilled_water_value_disabled']);
        $this->model_values['fouling_cooling_water_value_disabled'] = $vam_base->getBoolean($this->model_values['fouling_cooling_water_value_disabled']);
        $this->model_values['fouling_hot_water_value_disabled'] = $vam_base->getBoolean($this->model_values['fouling_hot_water_value_disabled']);
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
            'fouling_non_hot',
            'metallurgy_standard',
            'cooling_water_ranges',
            'glycol_chilled_water',
            'glycol_cooling_water',
            'glycol_hot_water',
            'min_chilled_water_out',
            'glycol_max_chilled_water',
            'glycol_max_cooling_water',
            'glycol_min_chilled_water',
            'glycol_min_cooling_water',
            'glycol_min_hot_water',
            'glycol_max_hot_water',
            'steam_pressure_max_range',
            'steam_pressure_min_range',
            'cooling_water_in_max_range',
            'cooling_water_in_min_range',
            'hot_water_in',
            'how_water_temp_min_range',
            'how_water_temp_max_range',
            'generator_tube_list',
            'hot_water_flow']);

        $region_type = Auth::user()->region_type;

        if($region_type == 2)
            $region_name = Auth::user()->region->name;
        else
            $region_name = '';


        $standard_values = array('evaporator_thickness' => 0,'absorber_thickness' => 0,'condenser_thickness' => 0,'evaporator_thickness_min_range' => 0,'evaporator_thickness_max_range' => 0,'absorber_thickness_min_range' => 0,'absorber_thickness_max_range' => 0,'condenser_thickness_min_range' => 0,'condenser_thickness_max_range' => 0,'fouling_chilled_water_value' => 0,'fouling_cooling_water_value' => 0,'fouling_hot_water_value' => 0,'evaporator_thickness_change' => 1,'absorber_thickness_change' => 1,'condenser_thickness_change' => 1,'fouling_chilled_water_checked' => 0,'fouling_cooling_water_checked' => 0,'fouling_hot_water_checked' => 0,'fouling_chilled_water_disabled' => 1,'fouling_cooling_water_disabled' => 1,'fouling_hot_water_disabled' => 1,'fouling_chilled_water_value_disabled' => 1,'fouling_cooling_water_value_disabled' => 1,'fouling_hot_water_value_disabled' => 1,'region_name'=>$region_name,'region_type'=>$region_type);


        $form_values = collect($form_values)->union($standard_values);

        return $form_values;
    }


    public function getCalculationValues($model_number){

        $model_number = (int)$model_number;
        $chiller_calculation_values = ChillerCalculationValue::where('code',$this->model_code)->where('min_model',$model_number)->first();

        $calculation_values = $chiller_calculation_values->calculation_values;
        $calculation_values = json_decode($calculation_values,true);

        $calculation_values = array_only($calculation_values, ['LE',
            'ODG',
            'ODA',
            'ODE',
            'PNB',
            'SHA',
            'SHE',
            'SL1',
            'SL2',
            'SL3',
            'SL3',
            'SL4',
            'SL5',
            'GENNB',
            'GSL1',
            'GSL2',
            'SHG',
            'TNC',
            'AABS',
            'ACON',
            'AEVA',
            'AGEN',
            'KABS',
            'KCON',
            'KEVAH',
            'KEVAL',
            'EVANB',
            'PSL2',
            'PSLI',
            'PSLO',
            'TNAA',
            'TNEV',
            'TNG',
            'UGEN',
            'ALTHE',
            'ULTHE',
            'VEMIN1',
            'TEPMAX',
            'm_maxCHWWorkPressure',
            'm_maxCOWWorkPressure',
            'm_maxHWWorkPressure',
            'm_maxHWDesignPressure',
            'ChilledConnectionDiameter',
            'CoolingConnectionDiameter',
            'HotWaterConnectionDiameter',
            'Length',
            'Width',
            'Height',
            'ClearanceForTubeRemoval',
            'DryWeight',
            'MaxShippingWeight',
            'OperatingWeight',
            'FloodedWeight',
            'HPAbsorbentPumpMotorKW',
            'HPAbsorbentPumpMotorAmp',
            'LPAbsorbentPumpMotorKW',
            'LPAbsorbentPumpMotorAmp',
            'RefrigerantPumpMotorKW',
            'RefrigerantPumpMotorAmp',
            'PurgePumpMotorKW',
            'PurgePumpMotorAmp',
            'A_SFACTOR',
            'B_SFACTOR',
            'ALTHE_F',
            'USA_HPAbsorbentPumpMotorKW',
            'USA_HPAbsorbentPumpMotorAmp',
            'USA_LPAbsorbentPumpMotorKW',
            'USA_LPAbsorbentPumpMotorAmp',
            'USA_RefrigerantPumpMotorKW',
            'USA_RefrigerantPumpMotorAmp',
            'USA_PurgePumpMotorKW',
            'USA_PurgePumpMotorAmp',
            'USA_capacity',
            'USA_chilled_water_in',
            'USA_chilled_water_out',
            'USA_cooling_water_in',
            'USA_cooling_water_flow',
            'USA_hot_water_in',
            'USA_hot_water_flow',
            'MCA',
            'MOP',
            'ODC',
            'min_chilled_water_out'
            
        ]);

        return $calculation_values;
    }
}
