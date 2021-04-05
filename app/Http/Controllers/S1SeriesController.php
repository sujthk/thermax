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

class S1SeriesController extends Controller
{
    private $model_values;
    private $model_code = "S1";
    private $calculation_values;
    private $notes;
    private $changed_value;


    public function getS1Series(){

        $chiller_form_values = $this->getFormValues(60);


        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')
                                        ->where('code','D_S2')
                                        ->where('min_model','<=',60)->where('max_model','>',60)->first();

                              
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

        return view('s1_series')->with('default_values',$converted_values)
                                        ->with('unit_set',$unit_set)
                                        ->with('units_data',$units_data)
                                        ->with('evaporator_options',$evaporator_options)
                                        ->with('absorber_options',$absorber_options)
                                        ->with('condenser_options',$condenser_options)
                                        ->with('chiller_metallurgy_options',$chiller_metallurgy_options) 
                                        ->with('language_datas',$language_datas) 
                                        ->with('regions',$regions);
    }


    public function postAjaxS1(Request $request){

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
                if (floatval($this->model_values['chilled_water_out']) < 3.5)
                {
                    $this->model_values['steam_pressure_min_range'] = 6;
                }
                else if (floatval($this->model_values['chilled_water_out']) <= 4.5 && floatval($this->model_values['chilled_water_out']) >= 3.5)
                {
                    $this->model_values['steam_pressure_min_range'] = 5;
                }
                else
                {
                    $this->model_values['steam_pressure_min_range'] = 3.5;
                }

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
      
      
        // $chiller_data = $this->getChillerData();

        $IDC = floatval($this->calculation_values['IDC']);
        $IDA = floatval($this->calculation_values['IDA']);
        $TNC = floatval($this->calculation_values['TNC']);
        $TNAA = floatval($this->calculation_values['TNAA']);
        $PODA = floatval($this->calculation_values['PODA']);
        $THPA = floatval($this->calculation_values['THPA']);


        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$this->model_code)
                                        ->where('min_model','<=',$model_number)->where('max_model','>',$model_number)->first();

        $chiller_options = $chiller_metallurgy_options->chillerOptions;
        
        // $evaporator_option = $chiller_options->where('type', 'eva')->where('value',$this->model_values['evaporator_material_value'])->first();
        $absorber_option = $chiller_options->where('type', 'abs')->where('value',$this->calculation_values['TU5'])->first();
        $condenser_option = $chiller_options->where('type', 'con')->where('value',$this->calculation_values['TV5'])->first();


        if($this->calculation_values['MODEL'] < 300){
            $TCP = 2;
        }
        else{
            $TCP = 1;
        }

        
        $VAMIN = $absorber_option->metallurgy->abs_min_velocity;          
        $VAMAX = $absorber_option->metallurgy->abs_max_velocity;
        $VCMIN = $condenser_option->metallurgy->con_min_velocity;
        $VCMAX = $condenser_option->metallurgy->con_max_velocity;


        $GCWMIN = 3.141593 / 4 * $IDC * $IDC * $VCMIN * $TNC * 3600 / $TCP;     //min required flow in condenser
        $GCWCMAX = 3.141593 / 4 * $IDC * $IDC * $VCMAX * $TNC * 3600 / 1;

        

        if ($GCWMIN > $GCWMIN1)
            $GCWMIN2 = $GCWMIN;
        else
            $GCWMIN2 = $GCWMIN1;

        $TAPMAX = 4;

        $FMIN[$TAPMAX] = 3.141593 / 4 * $IDA * $IDA * 3600 * $TNAA / $TAPMAX * $VAMIN;

        if ($FMIN[$TAPMAX] < $GCWMIN2)
            $FMIN[$TAPMAX] = $GCWMIN2;

        $FMAX[$TAPMAX] = 3.141593 / 4 * $IDA * $IDA * 3600 * $TNAA / $TAPMAX * $VAMAX;

        $GCWMAX = 3.141593 / 4 * $IDA * $IDA * 3600 * $TNAA * $VAMAX;
        
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
                $FMIN[$TAP] = 3.141593 / 4 * $IDA * $IDA * 3600 * $TNAA / $TAP * $VAMIN;
                $FMAX[$TAP] = 3.141593 / 4 * $IDA * $IDA * 3600 * $TNAA / $TAP * $VAMAX;
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
        $PIDA = ($PODA - (2 * $THPA)) / 1000;
        $APA = 3.141593 * $PIDA * $PIDA / 4;

        if ($model_number == 130 || $model_number == 810 || $model_number == 900)  //change
        {
            $GCWPMAX = $APA * 3.5 * 3600;
        }
        else if ($model_number == 310 || $model_number == 350 || $model_number == 410 || $model_number == 470 || $model_number == 530 || $model_number == 580 || $model_number == 630 || $model_number == 710)
        {
            $GCWPMAX = $APA * 3.8 * 3600;
        }
        else
        {
            $GCWPMAX = $APA * 4 * 3600;
        }


        if ($FMAX1 > $GCWPMAX)
        {
            $FMAX1 = $GCWPMAX;
        }


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

        // $range_values = array_sort($range_values);


        // for ($i=0; $i < $INIT; $i++) { 
        //  $range_values .= "(".$FMIN[$i]." - ".$FMAX[$i].")<br>";
        // }

        $this->model_values['cooling_water_ranges'] = $range_values;

        return array('status' => true,'msg' => "process run successfully");
    }

    public function updateInputs(){

        $model_number = (int)$this->model_values['model_number'];
        $calculation_values = $this->getCalculationValues($model_number);
  
        $this->calculation_values = $calculation_values;

        $this->calculation_values['region_type'] = $this->model_values['region_type'];
        $this->calculation_values['model_name'] = $this->model_values['model_name'];


        $vam_base = new VamBaseController();

        $pid_ft3 = $vam_base->PIPE_ID($this->calculation_values['PNB']);
        $this->calculation_values['PODA'] = $pid_ft3['POD'];
        $this->calculation_values['THPA'] = $pid_ft3['THP'];

        
        $this->calculation_values['PSL1'] = $this->calculation_values['PSLI'] + $this->calculation_values['PSLO'];
        $this->calculation_values['KM2'] = 0;


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


    public function CALCULATIONS(){            
       

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
            $this->EVAPORATOR();
            $this->HTG();
        }
        else
        {
            $a = 1;
            $this->calculation_values['TCHW1H'] = $this->calculation_values['TCHW11'];
            $this->calculation_values['TCHW2L'] = $this->calculation_values['TCHW12'];
            $this->calculation_values['TCW1']; = $this->calculation_values['TCW11'];

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

            $this->EVAPORATOR();
            $this->HTG();

            $this->calculation_values['TCHW1H'] = $this->calculation_values['TCHW11'];
            $this->calculation_values['TCHW2L'] = $this->calculation_values['TCHW12'];
            $this->calculation_values['TCW1']; = $this->calculation_values['TCW11'];
            $TCW14 = $this->calculation_values['TCW4'];;
            $t11 = array();
            $t12 = array();
            $t3n1 = array();
            $t3n2 = array();

            do
            {
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
                $AILMTDC = (5 * $this->calculation_values['FFCOW1']) * ($this->calculation_values['GCW'] * $this->calculation_values['COGLY_ROWH1'] * ($this->calculation_values['COGLY_SPHT1'] / 4187) * ($this->calculation_values['TCW4']; - $this->calculation_values['TCW11']) * 3.968 / ($ARICOWA * 3.28084 * 3.28084));
                $ARIZC = $ARIRC / ($ALMTDC - $AILMTDC);
                $ARITDAC = $ARISSPC - ($ARIRC / (exp($ARIZC) - 1));
                $ARITCWI = $this->calculation_values['TCW11'] + ($ARITDAC / 1.8);

                $this->calculation_values['FFCHW'] = 0;
                $this->calculation_values['FFCOW'] = 0;
                $this->calculation_values['UEVA'] = 1.0 / ((1.0 / $this->calculation_values['KEVA']) + $this->calculation_values['FFCHW']);
                $this->calculation_values['UCON'] = 1.0 / ((1.0 / $this->calculation_values['KCON']) + $this->calculation_values['FFCOW']);
                $this->calculation_values['UEVAH'] = 1.0 / ((1.0 / $this->calculation_values['KEVA']) + $this->calculation_values['FFCHW']);
                $this->calculation_values['UEVAL'] = 1.0 / ((1.0 / $this->calculation_values['KEVA']) + $this->calculation_values['FFCHW']);
                $this->calculation_values['UABSH'] = 1.0 / ((1.0 / $this->calculation_values['KABSH']) + $this->calculation_values['FFCOW']);
                $this->calculation_values['UABSL'] = 1.0 / ((1.0 / $this->calculation_values['KABSL']) + $this->calculation_values['FFCOW']);

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
                $this->calculation_values['TCW1']; = $ARITCWI;
                $this->EVAPORATOR();
                $this->HTG();
                $t12[$a] = $this->calculation_values['T1'];
                $t3n2[$a] = $this->calculation_values['T3'];
            } while (abs($t11[$a] - $t12[$a]) > 0.005 || abs($t3n1[$a] - $t3n2[$a]) > 0.005);
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

        if ($this->calculation_values['TV5'] == 2.0 || $this->calculation_values['TV5'] == 0)
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
        $tchw2h = array();
        $err1 = array();
        $ferr1 = array();
        $vam_base = new VamBaseController();

        if ($this->calculation_values['TCW11'] <= 32.0)
            $this->calculation_values['TCWA'] = $this->calculation_values['TCW11'];
        else
            $this->calculation_values['TCWA'] = 32.0;
                    
        $this->calculation_values['GDIL'] = 70 * $this->calculation_values['MOD1'];
        $this->calculation_values['QEVA'] = $this->calculation_values['TON'] * 3024;
        $this->calculation_values['GCHW'] = $this->calculation_values['TON'] * 3024 / (($this->calculation_values['TCHW1H'] - $this->calculation_values['TCHW2L']) * $this->calculation_values['CHGLY_ROW12'] * $this->calculation_values['CHGLY_SPHT12'] / 4187);
        $this->calculation_values['LMTDEVA'] = $this->calculation_values['QEVA'] / ($this->calculation_values['UEVA'] * $this->calculation_values['AEVA']);
        $this->calculation_values['T1'] = $this->calculation_values['TCHW2'] - ($this->calculation_values['TCHW1H'] - $this->calculation_values['TCHW2L']) / (exp(($this->calculation_values['TCHW1H'] - $this->calculation_values['TCHW2L']) / $this->calculation_values['LMTDEVA']) - 1);

        $QAB = $this->calculation_values['QEVA'] * (1 + 1 / 0.7) * 0.65;
        $QCO = $this->calculation_values['QEVA'] * (1 + 1 / 0.7) * 0.35;

        $this->calculation_values['ATCW2'] = $this->calculation_values['TCW11'] + $QAB / ($this->calculation_values['GCW'] * 1000);
        $this->calculation_values['ATCW3'] = $this->calculation_values['ATCW2'] + $QCO / ($this->calculation_values['GCW'] * 1000);
        $this->calculation_values['LMTDCO'] = $QCO / ($this->calculation_values['KCON'] * $this->calculation_values['ACON']);
        $this->calculation_values['AT3'] = $this->calculation_values['ATCW3'] + ($this->calculation_values['ATCW3'] - $this->calculation_values['ATCW2']) / (exp(($this->calculation_values['ATCW3'] - $this->calculation_values['ATCW2']) / $this->calculation_values['LMTDCO']) - 1);

        $this->calculation_values['DT'] = $this->calculation_values['TCHW11'] - $this->calculation_values['TCHW12'];

        if ($this->calculation_values['TCW11'] < 34.01)
        {
            if (((($this->calculation_values['TON'] / $this->calculation_values['MODEL']) > 0.8 && ($this->calculation_values['TON'] / $this->calculation_values['MODEL']) < 1.01) || ($this->calculation_values['TON'] / $this->calculation_values['MODEL']) < 0.66) && $this->calculation_values['DT'] <= 13)
            {
                $this->calculation_values['ATCHW2H'] = ($this->calculation_values['TCHW1H'] + $this->calculation_values['TCHW2L']) / 2;
            }
            else
            {
                $this->calculation_values['ATCHW2H'] = (($this->calculation_values['TCHW1H'] + $this->calculation_values['TCHW2L']) / 2) + ((-0.0082 * $this->calculation_values['DT'] * $this->calculation_values['DT']) + (0.0973 * $this->calculation_values['DT']) - 0.2802);
            }
        }
        else
        {
            $this->calculation_values['ATCHW2H'] = (($this->calculation_values['TCHW1H'] + $this->calculation_values['TCHW2L']) / 2) + ((-0.0047 * $this->calculation_values['DT'] * $this->calculation_values['DT']) - (0.0849 * $this->calculation_values['DT']) + 0.0412);
        }

        /************** Int Chw temp assump **************/

        $ferr1[0] = 1;
        $p = 1;
        while (abs($ferr1[$p - 1]) > 0.1)
        {
            if ($p == 1)
            {
                if ($this->calculation_values['DT'] > 9)
                {
                    $tchw2h[$p] = $this->calculation_values['ATCHW2H'];    // -2.5;
                }
                else
                {
                    $tchw2h[$p] = $this->calculation_values['ATCHW2H'] + 0.1;
                }
            }
            if ($p == 2)
            {
                $tchw2h[$p] = $tchw2h[$p - 1] + 0.1;
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
        $t2 = array();
        $err = array()
        $ferr = array();
        $vam_base = new VamBaseController();

        $s = 0;
        $ferr[0] = 1;
        $m = 1;

        while (abs($ferr[$m - 1]) > 0.05)
        {

            if ($m == 1)
            {
                $t2[$m] = $this->calculation_values['ATCW2'] + 2.5;
            }
            if ($m == 2)
            {
                $t2[$m] = $t2[$m - 1] + 0.5;
            }
            if ($m > 2)
            {
                $t2[$m] = $t2[$m - 1] + $err[$m - 1] * ($t2[$m - 1] - $t2[$m - 2]) / ($err[$m - 2] - $err[$m - 1]) / 2;
            }
            $this->calculation_values['T2'] = $t2[$m];
            $this->calculation_values['XDIL'] = $vam_base->LIBR_CONC($this->calculation_values['T2'], $this->calculation_values['P1H']);
            $this->calculation_values['I2'] = $vam_base->LIBR_ENTHALPY($this->calculation_values['T2'], $this->calculation_values['XDIL']);
            $this->CONDENSER();
            $this->LTHE();             //*******FOR FINDING $this->calculation_values['T8'] **************//
            $this->calculation_values['I8'] = $vam_base->LIBR_ENTHALPY($this->calculation_values['T8'], $this->calculation_values['XCONC']);
            $this->calculation_values['I2'] = $vam_base->LIBR_ENTHALPY($this->calculation_values['T2'], $this->calculation_values['XDIL']);
            $this->calculation_values['QABSL'] = $this->calculation_values['GCONC'] * $this->calculation_values['I8'] + $this->calculation_values['J1L'] * $this->calculation_values['GREFL'] - $this->calculation_values['GDILL'] * $this->calculation_values['I2L'];
            $err[$m] = ($this->calculation_values['QABSL'] - $this->calculation_values['QLMTDABSL']);
            $ferr[$m] = ($this->calculation_values['QABSL'] - $this->calculation_values['QLMTDABSL']) / $this->calculation_values['QABSL'] * 100;
            $m++;
        }
    }


    public function CONDENSER()
    {
        $t3 = array();
        $error = array();
        $ferrr = array();
        $vam_base = new VamBaseController();

        if ($s == 0)
        {
            $this->calculation_values['AT3'] = $this->calculation_values['AT3'];
        }
        else
            $this->calculation_values['AT3'] = $this->calculation_values['T3'];
        $ferrr[0] = 2;
        $s = 1;
        while (abs($ferrr[$s - 1]) > 0.05)
        {
            if ($s == 1)
            {
                $t3[$s] = $this->calculation_values['AT3'];    //******REPRESENTATIVE FOR $this->calculation_values['T3']***********//
            }
            if ($s == 2)
            {
                $t3[$s] = $this->calculation_values['AT3'] + 0.2;
            }
            if ($s > 2)
            {
                $t3[$s] = $t3[$s - 1] + $error[$s - 1] * ($t3[$s - 1] - $t3[$s - 2]) / ($error[$s - 2] - $error[$s - 1]) / 2;
            }
            $this->calculation_values['T3'] = $t3[$s];
            $this->calculation_values['P3'] = $vam_base->LIBR_PRESSURE($this->calculation_values['T3'], 0);
            $this->calculation_values['I3'] = 100 + $this->calculation_values['T3'];

            $this->calculation_values['GREFL'] = $this->calculation_values['QEVAL'] / ($this->calculation_values['J1L'] - $this->calculation_values['I1H']);
            $this->calculation_values['GREFH'] = ($this->calculation_values['QEVAH'] + $this->calculation_values['GREFL'] * ($this->calculation_values['I3'] - $this->calculation_values['I1H'])) / ($this->calculation_values['J1H'] - $this->calculation_values['I3']);

            $this->calculation_values['GCONCH'] = $this->calculation_values['GDIL'] - $this->calculation_values['GREFH'];
            $this->calculation_values['XCONCH'] = $this->calculation_values['GDIL'] * $this->calculation_values['XDIL'] / $this->calculation_values['GCONCH'];
            $this->calculation_values['T6H'] = $vam_base->LIBR_TEMP($this->calculation_values['P1H'], $this->calculation_values['XCONCH']);
            $this->calculation_values['I6H'] = $vam_base->LIBR_ENTHALPY($this->calculation_values['T6H'], $this->calculation_values['XCONCH']);

            $this->calculation_values['GDILL'] = $this->calculation_values['GCONCH'];
            $this->calculation_values['XDILL'] = $this->calculation_values['XCONCH'];
            $this->calculation_values['T2L'] = $vam_base->LIBR_TEMP($this->calculation_values['P1L'], $this->calculation_values['XDILL']);
            $this->calculation_values['I2L'] = $vam_base->LIBR_ENTHALPY($this->calculation_values['T2L'], $this->calculation_values['XDILL']);

            $this->calculation_values['GCONC'] = $this->calculation_values['GDILL'] - $this->calculation_values['GREFL'];
            $this->calculation_values['XCONC'] = $this->calculation_values['GDILL'] * $this->calculation_values['XDILL'] / $this->calculation_values['GCONC'];
            $this->calculation_values['GREF'] = $this->calculation_values['GREFH'] + $this->calculation_values['GREFL'];

            $this->calculation_values['T6'] = $vam_base->LIBR_TEMP($this->calculation_values['P1L'], $this->calculation_values['XCONC']);

            $this->calculation_values['TCW1H'] = $this->calculation_values['TCW11'];
            $this->CWABSHOUT();    //***FOR COOLING WATER OUTLET TEMPERATURE FOR ABSORBER ***//
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
            $this->CWCONOUT();    //***FOR COOLING WATER OUTLET TEMPERATURE FOR CONDENSER ***//

            $this->calculation_values['LMTDCON'] = (($this->calculation_values['T3'] - $this->calculation_values['TCW3']) - ($this->calculation_values['T3'] - $this->calculation_values['TCW4'])) / log(($this->calculation_values['T3'] - $this->calculation_values['TCW3']) / ($this->calculation_values['T3'] - $this->calculation_values['TCW4']));
            $this->calculation_values['QLMTDCON'] = $this->calculation_values['ACON'] * $this->calculation_values['UCON'] * $this->calculation_values['LMTDCON'];
            $error[$s] = ($this->calculation_values['QCWCON'] - $this->calculation_values['QLMTDCON']);
            $ferrr[$s] = ($this->calculation_values['QCWCON'] - $this->calculation_values['QLMTDCON']) / $this->calculation_values['QCWCON'] * 100;
            $s++;
        }
    }

    public function CWABSHOUT()
    {
        $tcw2h = array();
        $error1 = array();
        $ferrr1 = array();
        $vam_base = new VamBaseController();

        $ferrr1[0] = 1;
        $i = 1;
        while (abs($ferrr1[$i - 1]) > 0.05)
        {
            if ($i == 1)
            {
                $tcw2h[$i] = $this->calculation_values['TCW1H'] + 1;
            }
            if ($i == 2)
            {
                $tcw2h[$i] = $tcw2h[$i - 1] + 0.5;
            }
            if ($i > 2)
            {
                $tcw2h[$i] = $tcw2h[$i - 1] + $error1[$i - 1] * ($tcw2h[$i - 1] - $tcw2h[$i - 2]) / ($error1[$i - 2] - $error1[$i - 1]);
            }
            if ($tcw2h[$i] > $this->calculation_values['T6H'] && $i > 2)
            {
                $tcw2h[$i] = $tcw2h[$i - 1] + $error1[$i - 1] * ($tcw2h[$i - 1] - $tcw2h[$i - 2]) / ($error1[$i - 2] - $error1[$i - 1]) / 5;
            }

            $this->calculation_values['TCW2H'] = $tcw2h[$i];
            if ($this->calculation_values['GL'] == 2)
            {
                $this->calculation_values['COGLY_ROWH1'] = $vam_base->EG_ROW($this->calculation_values['TCW1H'], $this->calculation_values['COGLY']);
                $this->calculation_values['COGLY_SPHT1'] = $vam_base->EG_SPHT($this->calculation_values['TCW1H'], $this->calculation_values['COGLY']) * 1000;
                $this->calculation_values['COGLY_SPHT2'] = $vam_base->EG_SPHT($this->calculation_values['TCW2H'], $this->calculation_values['COGLY']) * 1000;
            }
            else
            {                    
                $this->calculation_values['COGLY_ROWH1'] = $vam_base->PG_ROW($this->calculation_values['TCW1H'], $this->calculation_values['COGLY']);
                $this->calculation_values['COGLY_SPHT1'] = $vam_base->PG_SPHT($this->calculation_values['TCW1H'], $this->calculation_values['COGLY']) * 1000;
                $this->calculation_values['COGLY_SPHT2'] = $vam_base->PG_SPHT($this->calculation_values['TCW2H'], $this->calculation_values['COGLY']) * 1000;
            }
            $this->calculation_values['QCWABSH'] = $this->calculation_values['GCWAH'] * $this->calculation_values['COGLY_ROWH1'] * ($this->calculation_values['COGLY_SPHT2'] + $this->calculation_values['COGLY_SPHT1']) * 0.5 * ($this->calculation_values['TCW2H'] - $this->calculation_values['TCW1H']) / 4187;
            $this->calculation_values['LMTDABSH'] = (($this->calculation_values['T6H'] - $this->calculation_values['TCW2H']) - ($this->calculation_values['T2'] - $this->calculation_values['TCW1H'])) / log(($this->calculation_values['T6H'] - $this->calculation_values['TCW2H']) / ($this->calculation_values['T2'] - $this->calculation_values['TCW1H']));
            $this->calculation_values['QLMTDABSH'] = $this->calculation_values['UABSH'] * $this->calculation_values['AABSH'] * $this->calculation_values['LMTDABSH'];
            $error1[$i] = ($this->calculation_values['QCWABSH'] - $this->calculation_values['QLMTDABSH']);
            $ferrr1[$i] = ($this->calculation_values['QCWABSH'] - $this->calculation_values['QLMTDABSH']) / $this->calculation_values['QCWABSH'] * 100;
            $i++;
        }
    }


    public function CWABSLOUT()
    {
        $tcw2l = array();
        $error1 = array();
        $ferr5 = array();
        $vam_base = new VamBaseController();

        $ferr5[0] = 2;
        $c = 1;
        while (abs($ferr5[$c - 1]) > 0.1)
        {
            if ($c == 1)
            {
                $tcw2l[$c] = $this->calculation_values['TCW1L'] + 1.0;
            }
            if ($c == 2)
            {
                $tcw2l[$c] = $tcw2l[$c - 1] + 0.5;
            }
            if ($c >= 3)
            {
                $tcw2l[$c] = $tcw2l[$c - 1] + $ferr5[$c - 1] * ($tcw2l[$c - 1] - $tcw2l[$c - 2]) / ($ferr5[$c - 2] - $ferr5[$c - 1]) / 3;
            }
            if ($tcw2l[$c] > $this->calculation_values['T6'] && $c > 2)
            {
                $tcw2l[$c] = $tcw2l[$c - 1] + $ferr5[$c - 1] * ($tcw2l[$c - 1] - $tcw2l[$c - 2]) / ($ferr5[$c - 2] - $ferr5[$c - 1]) / 5;
            }
            $this->calculation_values['TCW2L'] = $tcw2l[$c];

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

            $this->calculation_values['QCWABSL'] = $this->calculation_values['GCWAL'] * $this->calculation_values['COGLY_ROWH1'] * (($this->calculation_values['TCW2L'] * $this->calculation_values['COGLY_SPHT2L']) - ($this->calculation_values['TCW1L'] * $this->calculation_values['COGLY_SPHT1L'])) / 4187;
            $this->calculation_values['LMTDABSL'] = (($this->calculation_values['T6'] - $this->calculation_values['TCW2L']) - ($this->calculation_values['T2L'] - $this->calculation_values['TCW1L'])) / log(($this->calculation_values['T6'] - $this->calculation_values['TCW2L']) / ($this->calculation_values['T2L'] - $this->calculation_values['TCW1L']));
            $this->calculation_values['QLMTDABSL'] = $this->calculation_values['UABSL'] * $this->calculation_values['AABSL'] * $this->calculation_values['LMTDABSL'];
            $ferr5[$c] = ($this->calculation_values['QCWABSL'] - $this->calculation_values['QLMTDABSL']) * 100 / $this->calculation_values['QCWABSL'];
            $c++;
        }

    }

    public function CWCONOUT()
    {
        $error3 = array();
        $ferrr3 = array();
        $tcw4 = array();
        $vam_base = new VamBaseController();

        $ferrr3[0] = 5;
        $k = 1;
        while (abs($ferrr3[$k - 1]) > .05)
        {
            if ($k == 1)
            {
                $tcw4[$k] = $this->calculation_values['TCW3'] + 2;
            }
            if ($k == 2)
            {
                $tcw4[$k] = $tcw4[$k-1] + 2.2;
            }
            if ($k > 2)
            {
                $tcw4[$k] = $tcw4[$k - 1] + $error3[$k - 1] * ($tcw4[$k - 1] - $tcw4[$k - 2]) / ($error3[$k - 2] - $error3[$k - 1]);
            }
            if ($tcw4[$k] > $this->calculation_values['T3'] && $k > 2)
            {
                $tcw4[$k] = $tcw4[$k - 1] + $error3[$k - 1] * ($tcw4[$k - 1] - $tcw4[$k - 2]) / ($error3[$k - 2] - $error3[$k - 1]) / 1;
            }
            $this->calculation_values['TCW4'] = $tcw4[$k];
            if ($this->calculation_values['GL'] == 2)
            {
                $this->calculation_values['COGLY_SPHT3'] = $vam_base->EG_SPHT($this->calculation_values['TCW3'], $this->calculation_values['COGLY']) * 1000;
                $this->calculation_values['COGLY_SPHT4'] = $vam_base->EG_SPHT($this->calculation_values['TCW4'], $this->calculation_values['COGLY']) * 1000;
            }
            else
            {
                $this->calculation_values['COGLY_SPHT3'] = $vam_base->PG_SPHT($this->calculation_values['TCW3'], $this->calculation_values['COGLY']) * 1000;
                $this->calculation_values['COGLY_SPHT4'] = $vam_base->PG_SPHT($this->calculation_values['TCW4'], $this->calculation_values['COGLY']) * 1000;
            }
            $this->calculation_values['QCWCON'] = $this->calculation_values['GCWC'] * $this->calculation_values['COGLY_ROWH1'] * ($this->calculation_values['COGLY_SPHT4'] + $this->calculation_values['COGLY_SPHT3']) * 0.5 * ($this->calculation_values['TCW4'] - $this->calculation_values['TCW3']) / 4187;
            $this->calculation_values['T4'] = $vam_base->LIBR_TEMP($this->calculation_values['P3'], $this->calculation_values['XCONC']);
            $this->calculation_values['I4'] = $vam_base->LIBR_ENTHALPY($this->calculation_values['T4'], $this->calculation_values['XCONC']);
            $this->calculation_values['J4'] = $vam_base->WATER_VAPOUR_ENTHALPY($this->calculation_values['T4'], $this->calculation_values['P3']);
            $this->calculation_values['QREFCON'] = $this->calculation_values['GREF'] * ($this->calculation_values['J4'] - $this->calculation_values['I3']);
            $error3[$k] = ($this->calculation_values['QREFCON'] - $this->calculation_values['QCWCON']);
            $ferrr3[$k] = ($this->calculation_values['QREFCON'] - $this->calculation_values['QCWCON']) / $this->calculation_values['QREFCON'] * 100;
            $k++;
        }
    }

    public function LTHE()
    {
        $t8 = array();
        $merr = array();
        $fmerr = array();
        $vam_base = new VamBaseController();

        $n = 1;
        $fmerr[0] = 2;
        while (abs($fmerr[$n - 1]) > 0.05)
        {
            if ($n == 1)
            {
                $t8[$n] = $this->calculation_values['T6'] + 5;
            }
            if ($n == 2)
            {
                $t8[$n] = $this->calculation_values['T6'] + 5.5;
            }
            if ($n > 2)
            {
                $t8[$n] = $t8[$n - 1] + $merr[$n - 1] * ($t8[$n - 1] - $t8[$n - 2]) / ($merr[$n - 2] - $merr[$n - 1]);
            }
            $this->calculation_values['T8'] = $t8[$n];
            $this->calculation_values['I8'] = $vam_base->LIBR_ENTHALPY($this->calculation_values['T8'], $this->calculation_values['XCONC']);
            $QHECON = $this->calculation_values['GCONC'] * ($this->calculation_values['I4'] - $this->calculation_values['I8']);
            $this->calculation_values['I7'] = $this->calculation_values['I2'] + $QHECON / $this->calculation_values['GDIL'];
            $this->calculation_values['T7'] = $vam_base->LIBR_TEMPERATURE($this->calculation_values['XDIL'], $this->calculation_values['I7']);
            $this->calculation_values['LMTDHE'] = (($this->calculation_values['T4'] - $this->calculation_values['T7']) - ($this->calculation_values['T8'] - $this->calculation_values['T2'])) / log(($this->calculation_values['T4'] - $this->calculation_values['T7']) / ($this->calculation_values['T8'] - $this->calculation_values['T2']));
            $QLMTDHE = $this->calculation_values['AHE'] * $this->calculation_values['UHE'] * $this->calculation_values['LMTDHE'];
            $merr[$n] = $QHECON - $QLMTDHE;
            $fmerr[$n] = ($QHECON - $QLMTDHE) / $QHECON * 100;
            $n++;
        }
    }

    public function HTG()
    {           
        $ERR3 = array();
        $ts = array();
        $b = 1;
        $vam_base = new VamBaseController();

        $ERR3[0] = 2;
        while (abs($ERR3[$b - 1]) > 0.05)
        {
            if ($b == 1)
            {
                $ts[$b] = $this->calculation_values['T4'] + 10;
            }
            if ($b == 2)
            {
                $ts[$b] = $ts[$b - 1] + 1;
            }
            if ($b > 2)
            {
                $ts[$b] = $ts[$b - 1] + $ERR3[$b - 1] * ($ts[$b - 1] - $ts[$b - 2]) / ($ERR3[$b - 2] - $ERR3[$b - 1]);
            }
            $this->calculation_values['TS'] = $ts[$b];
            $this->calculation_values['PS1'] = $vam_base->STEAM_PRESSURE($this->calculation_values['TS']);     //IN kg/cm2.g
            if ($this->calculation_values['PST1'] < 0.9)            //oct 07
            {
                $this->calculation_values['PS'] = $this->calculation_values['PS1'] + 0.3;
            }
            else
            {
                $this->calculation_values['PS'] = $this->calculation_values['PS1'] + 0.3;
            }
            $this->calculation_values['T5'] = $vam_base->LIBR_TEMP($this->calculation_values['P3'], $this->calculation_values['XDIL']);
            $this->calculation_values['LMTDGEN'] = (($this->calculation_values['TS'] - $this->calculation_values['T5']) - ($this->calculation_values['TS'] - $this->calculation_values['T4'])) / log(($this->calculation_values['TS'] - $this->calculation_values['T5']) / ($this->calculation_values['TS'] - $this->calculation_values['T4']));
            $this->calculation_values['QLMTDGEN'] = $this->calculation_values['UGEN'] * $this->calculation_values['AGEN'] * $this->calculation_values['LMTDGEN'];
            $this->calculation_values['HSTEAM'] = 639.427333 + (4.7783887 * ($this->calculation_values['PST1'] + 1.0)) - (0.3413875 * ($this->calculation_values['PST1'] + 1.0) * ($this->calculation_values['PST1'] + 1.0)) + (0.009782 * ($this->calculation_values['PST1'] + 1.0) * ($this->calculation_values['PST1'] + 1.0) * ($this->calculation_values['PST1'] + 1.0));
            $this->calculation_values['GSTEAM'] = $this->calculation_values['QLMTDGEN'] / ($this->calculation_values['HSTEAM'] - $this->calculation_values['TS']);
            $this->HR();
            $this->calculation_values['QGEN'] = ($this->calculation_values['I4'] * $this->calculation_values['GCONC']) + ($this->calculation_values['GREF'] * $this->calculation_values['J4']) - ($this->calculation_values['I20'] * $this->calculation_values['GDIL']);
            $ERR3[$b] = ($this->calculation_values['QLMTDGEN'] - $this->calculation_values['QGEN']) * 100 / $this->calculation_values['QLMTDGEN'];
            $b++;
        }

        if ($this->calculation_values['TCHW12'] < 5 || ($this->calculation_values['MODEL'] < 300 && $this->calculation_values['TCHW12'] < 6.7))
        {
            $this->calculation_values['SFACTOR2'] = 1.0738 - 0.0068 * $this->calculation_values['TCHW12'];
        }
        else
        {
            $this->calculation_values['SFACTOR2'] = 1.0;
        }
        $this->calculation_values['GSTEAM'] = $this->calculation_values['GSTEAM'] * $this->calculation_values['SFACTOR'] * $this->calculation_values['SFACTOR2'];                      
        $this->calculation_values['GSTEAM'] = $this->calculation_values['GSTEAM'] + 0.51;
        $this->PRESSURE_DROP();
    }

    public function STEAM_PRESSURE()
    {
        $PPS = (1.537837 * pow(10, -8) * pow($this->calculation_values['TS'], 4)) - (2.020148 * pow(10, -6) * pow($this->calculation_values['TS'], 3)) + (2.285933 * pow(10, -4) * pow($this->calculation_values['TS'], 2)) - (9.720448 * pow(10, -3) * $this->calculation_values['TS']) + 0.2025998;
        $this->calculation_values['PS'] = $PPS - 1.03323;       //IN kg/cm2 .g  (0.6 - 3.5 RANGE)   
    }

    public function HR()
    {
        $ERR2 = array();
        $tstout = array();
        $d = 1;
        $vam_base = new VamBaseController();

        $ERR2[0] = 2;
        while (abs($ERR2[$d - 1]) > 0.01)
        {
            if ($d == 1)
            {
                $tstout[$d] = $this->calculation_values['TS'] - 15;
            }
            if ($d == 2)
            {
                $tstout[$d] = $tstout[$d - 1] + 1;
            }
            if ($d > 2)
            {
                $tstout[$d] = $tstout[$d - 1] + $ERR2[$d - 1] * ($tstout[$d - 1] - $tstout[$d - 2]) / ($ERR2[$d - 2] - $ERR2[$d - 1]) / 5;
            }
            $this->calculation_values['TSTOUT'] = $tstout[$d];
            $this->calculation_values['QHR'] = $this->calculation_values['GSTEAM'] * ($this->calculation_values['TS'] - $this->calculation_values['TSTOUT']);
            $this->calculation_values['I20'] = $this->calculation_values['I7'] + $this->calculation_values['QHR'] / $this->calculation_values['GDIL'];
            $this->calculation_values['T20'] = $vam_base->LIBR_TEMPERATURE($this->calculation_values['XDIL'], $this->calculation_values['I20']);
            $this->calculation_values['LMTDHR'] = (($this->calculation_values['TS'] - 5 - $this->calculation_values['T20']) - ($this->calculation_values['TSTOUT'] - $this->calculation_values['T7'])) / log(($this->calculation_values['TS'] - 5 - $this->calculation_values['T20']) / ($this->calculation_values['TSTOUT'] - $this->calculation_values['T7']));
            $this->calculation_values['QLMTDHR'] = $this->calculation_values['UHR'] * $this->calculation_values['AHR'] * $this->calculation_values['LMTDHR'];
            $ERR2[$d] = ($this->calculation_values['QLMTDHR'] - $this->calculation_values['QHR']) * 100 / $this->calculation_values['QLMTDHR'];
            $d++;
        }
    }

    public function PRESSURE_DROP()
    {
        // PR_DROP_DATA();
        $this->PR_DROP_CHILL();
        $this->PR_DROP_COW();
    }

    public function PR_DROP_CHILL()
    {
        $CHGLY_ROW22 = 0;
        $CHGLY_VIS22 = 0;
        $FE1 = 0
        $F = 0;
        $VPE2 = 0;
        $VPBR = 0;
        $REPE2 = 0;
        $REBR = 0;
        $FF2 = 0;
        $FF3 = 0;
        $FL2 = 0;
        $FL3 = 0;
        $FL4 = 0;
        $FL5 = 0;
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

        if ($this->calculation_values['GL'] == 3)
        {
            $CHGLY_ROW22 = $vam_base->PG_ROW($TME, CHGLY);
            $CHGLY_VIS22 = $vam_base->PG_VISCOSITY($TME, CHGLY) / 1000;
        }
        else
        {
            $CHGLY_ROW22 = $vam_base->EG_ROW($TME, CHGLY);
            $CHGLY_VIS22 = $vam_base->EG_VISCOSITY($TME, CHGLY) / 1000;
        }

        $REPE1 = ($this->calculation_values['PIDE1'] * $VPE1 * $CHGLY_ROW22) / $CHGLY_VIS22;

        if ($this->calculation_values['MODEL'] > 300)
        {
            $REPE2 = ($this->calculation_values['PIDE2'] * $VPE2 * $CHGLY_ROW22) / $CHGLY_VIS22;
            $REBR = ($this->calculation_values['PIDE1'] * $VPBR * $CHGLY_ROW22) / $CHGLY_VIS22;         //REYNOLDS NO IN PIPE1
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

        $RE = ($this->calculation_values['VEA'] * $this->calculation_values['IDE'] * $CHGLY_ROW22) / $CHGLY_VIS22;          //REYNOLDS NO IN TUBES

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
        else if (($this->calculation_values['MODEL'] < 1200 && ($this->calculation_values['TU2'] == 1 || $this->calculation_values['TU2'] == 2 || $this->calculation_values['TU2'] == 0) || ($this->calculation_values['MODEL'] < 1200 && $this->calculation_values['TU2'] == 4)))                  // 12% AS PER EXPERIMENTATION      
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
        $FE4 = (($FE1 + $FE2 + $FE3) * $this->calculation_values['TP']) * 2;        //EVAPORATOR TUBE LOSS FOR DOUBLE ABS
        $this->calculation_values['FLE'] = $FLP + $FE4;             //TOTAL FRICTION LOSS IN CHILLED WATER CKT
        $this->calculation_values['PDE'] = $this->calculation_values['FLE'] + $this->calculation_values['SHE'];                 //PRESSURE DROP IN CHILLED WATER CKT
    }

    public function PR_DROP_COW()
    {
        $COGLY_ROWH33 = 0;
        $COGLY_VISH33 = 0;
        $FH = 0;
        $FL = 0;
        $F = 0;
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
        $this->calculation_values['REPA'] = ($this->calculation_values['PIDA'] * $this->calculation_values['VPA'] * $COGLY_ROWH33) / $COGLY_VISH33;         //REYNOLDS NO IN PIPE1  
        // RED1 = ((ED1NB) * VD1 * $COGLY_ROWH33) / $COGLY_VISH33;          //REYNOLDS NO IN DUCT1

        $this->calculation_values['FFA'] = 1.325 / pow(Math.Log((0.0457 / (3.7 * $this->calculation_values['PIDA'] * 1000)) + (5.74 / pow($this->calculation_values['REPA'], 0.9))), 2);        //FRICTION FACTOR CAL
        //  FFD1 = 1.325 / Math.Pow(Math.Log((0.0457 / (3.7 * (ED1NB) * 1000)) + (5.74 / Math.Pow(RED1, 0.9))), 2);

        $this->calculation_values['FLP1'] = ($this->calculation_values['FFA'] * ($this->calculation_values['PSL1'] + $this->calculation_values['PSL2']) / $this->calculation_values['PIDA']) * ($this->calculation_values['VPA'] * $this->calculation_values['VPA'] / (2 * 9.81)) + ((14 * $this->calculation_values['FT']) * ($this->calculation_values['VPA'] * $this->calculation_values['VPA']) / (2 * 9.81));      //FR LOSS IN PIPE                                   
        //   FLD1 = ((FFD1 * DSL) / ED1NB) * (VD1 * VD1 / (2 * 9.81));                                  //FR LOSS IN DUCT
        $this->calculation_values['FLOT'] = (1 + 0.5 + 1 + 0.5) * ($this->calculation_values['VPA'] * $this->calculation_values['VPA'] / (2 * 9.81));                                                                   //EXIT, ENTRY LOSS

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
        $FA4H = ($FA1H + $FA2H + $FA3H) * $this->calculation_values['TAPH'];                    //FRICTION LOSS IN ABSH TUBES

        $FA1L = 2 * $FL * $this->calculation_values['LE'] * $this->calculation_values['VAL'] * $this->calculation_values['VAL'] / (9.81 * $this->calculation_values['IDA']);
        $FA2L = $this->calculation_values['VAL'] * $this->calculation_values['VAL'] / (4 * 9.81);
        $FA3L = $this->calculation_values['VAL'] * $this->calculation_values['VAL'] / (2 * 9.81);
        $FA4L = ($FA1L + $FA2L + $FA3L) * $this->calculation_values['TAPL'];                    //FRICTION LOSS IN ABSL TUBES

        if ($this->calculation_values['TAP'] == 1)
        {
            $this->calculation_values['FLA'] = $FA4H + $this->calculation_values['AFLP'];       //PARAFLOW WILL HAVE ONE ENTRY, ONE EXIT, ONE TUBE FRICTION LOSS
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
            $F = (0.0014 + (0.137 / pow($RE1, 0.32))) * 1.12;
        }
        else if (($this->calculation_values['TV5'] < 2.1 || $this->calculation_values['TV5'] == 4) && $this->calculation_values['MODEL'] > 950)
        {
            $F = 0.0014 + (0.137 / pow($RE1, 0.32));
        }
        else
        {
            $F = 0.0014 + (0.125 / pow($RE1, 0.32));
        }

        $FC1 = 2 * $F * $this->calculation_values['LE'] * $this->calculation_values['VC'] * $this->calculation_values['VC'] / (9.81 * $this->calculation_values['IDC']);
        $FC2 = $this->calculation_values['VC'] * $this->calculation_values['VC'] / (4 * 9.81);
        $FC3 = $this->calculation_values['VC'] * $this->calculation_values['VC'] / (2 * 9.81);
        $this->calculation_values['FC4'] = ($FC1 + $FC2 + $FC3) * $this->calculation_values['TCP'];                     //FRICTION LOSS IN CONDENSER TUBES
        $this->calculation_values['FLC'] = $this->calculation_values['FC4'];

        $this->calculation_values['PDA'] = $this->calculation_values['FLA'] + $this->calculation_values['SHA'] + $this->calculation_values['FC4'];
    }

    public function castToBoolean(){
        $vam_base = new VamBaseController();


        $this->model_values['metallurgy_standard'] = $vam_base->getBoolean($this->model_values['metallurgy_standard']);
        $this->model_values['evaporator_thickness_change'] = $vam_base->getBoolean($this->model_values['evaporator_thickness_change']);
        $this->model_values['absorber_thickness_change'] = $vam_base->getBoolean($this->model_values['absorber_thickness_change']);
        $this->model_values['condenser_thickness_change'] = $vam_base->getBoolean($this->model_values['condenser_thickness_change']);
        $this->model_values['fouling_chilled_water_checked'] = $vam_base->getBoolean($this->model_values['fouling_chilled_water_checked']);
        $this->model_values['fouling_cooling_water_checked'] = $vam_base->getBoolean($this->model_values['fouling_cooling_water_checked']);
        $this->model_values['fouling_chilled_water_disabled'] = $vam_base->getBoolean($this->model_values['fouling_chilled_water_disabled']);
        $this->model_values['fouling_cooling_water_disabled'] = $vam_base->getBoolean($this->model_values['fouling_cooling_water_disabled']);
        $this->model_values['fouling_chilled_water_value_disabled'] = $vam_base->getBoolean($this->model_values['fouling_chilled_water_value_disabled']);
        $this->model_values['fouling_cooling_water_value_disabled'] = $vam_base->getBoolean($this->model_values['fouling_cooling_water_value_disabled']);
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

        return $GCWMIN1;
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
