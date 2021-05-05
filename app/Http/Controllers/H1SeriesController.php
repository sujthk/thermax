<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\VamBaseController;
use App\Http\Controllers\UnitConversionController;
use App\Http\Controllers\ReportController;
use App\ChillerDefaultValue;
use App\ChillerMetallurgyOption;
use App\ChillerCalculationValue;
use App\UserReport;
use App\NotesAndError;
use App\Region;
use App\UnitSet;
use Exception;
use Log;
use PDF;
use DB;

class H1SeriesController extends Controller
{
    private $model_values;
    private $default_model_values;
    private $model_code = "H1";
    private $calculation_values;
    private $notes;
    private $changed_value;

    public function getH1Series(){

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

        return view('h1_series')->with('default_values',$converted_values)
                                            ->with('unit_set',$unit_set)
                                            ->with('units_data',$units_data)
                                            ->with('evaporator_options',$evaporator_options)
                                            ->with('absorber_options',$absorber_options)
                                            ->with('condenser_options',$condenser_options)
                                            ->with('chiller_metallurgy_options',$chiller_metallurgy_options)
                                            ->with('language_datas',$language_datas)
                                            ->with('regions',$regions);
    }

    public function postAjaxH1(Request $request){

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

        $attribute_validator = $this->validateChillerAttribute($changed_value);

        if(!$attribute_validator['status'])
            return response()->json(['status'=>false,'msg'=>$attribute_validator['msg'],'changed_value'=>$this->changed_value]);
        
        $this->updateInputs();
        $this->loadSpecSheetData();

        $converted_values = $unit_conversions->formUnitConversion($this->model_values,$this->model_code);

        return response()->json(['status'=>true,'msg'=>'Ajax Datas','model_values'=>$converted_values,'changed_value'=>$this->changed_value]);
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

        $IDC = floatval($this->calculation_values['IDC']);
        $IDA = floatval($this->calculation_values['IDA']);
        $TNC = floatval($this->calculation_values['TNC']);
        $TNAA = floatval($this->calculation_values['TNAA']);
        $PODA = floatval($this->calculation_values['PODA']);
        $THPA = floatval($this->calculation_values['THPA']);
        

        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$this->model_code)
        ->where('min_model','<=',$model_number)->where('max_model','>=',$model_number)->first();

        $chiller_options = $chiller_metallurgy_options->chillerOptions;


        $absorber_option = $chiller_options->where('type', 'abs')->where('value',$this->calculation_values['TU5'])->first();
        $condenser_option = $chiller_options->where('type','con')->where('value',$this->calculation_values['TV5'])->first();

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
        $GCWCMAX = 3.141593 / 4 * $IDC * $IDC * $VCMAX * $TNC * 3600 / $TCP;


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
        //if ($model_number < 360 && GCWCMAX < $FMAX1)
        //{
        //    $FMAX1 = GCWCMAX;
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



        // Log::info("init = ".$INIT);
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

        //log::info($this->model_values['cooling_water_ranges']);
        return array('status' => true,'msg' => "process run successfully");
    }

    public function RANGECAL1($model_number,$chilled_water_out,$capacity)
    {
        $TCHW12 = $chilled_water_out;
        $TON = $capacity;

        if ($model_number < 600.0)
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

        // Log::info(print_r($this->calculation_values,true));
        $this->calculation_values['PSL1'] = $this->calculation_values['PSLI'] + $this->calculation_values['PSLO'];
        $this->calculation_values['KM2'] = 0;


        $this->calculation_values['MODEL'] = $this->model_values['model_number'];
        $this->calculation_values['TON'] = $this->model_values['capacity'];
        $this->calculation_values['TUU'] = $this->model_values['fouling_factor'];
        $this->calculation_values['FFCHW1'] = floatval($this->model_values['fouling_chilled_water_value']);
        $this->calculation_values['FFCOW1'] = floatval($this->model_values['fouling_cooling_water_value']);

        $chiller_metallurgy_option = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$this->model_code)
        ->where('min_model','<=',(int)$this->model_values['model_number'])->where('max_model','>=',(int)$this->model_values['model_number'])->first();


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
        $this->calculation_values['GLL'] = $this->model_values['glycol_selected']; 
        $this->calculation_values['CHGLY'] = $this->model_values['glycol_chilled_water']; 
        $this->calculation_values['COGLY'] = $this->model_values['glycol_cooling_water']; 
        $this->calculation_values['TCHW11'] = $this->model_values['chilled_water_in']; 
        $this->calculation_values['TCHW12'] = $this->model_values['chilled_water_out']; 
        $this->calculation_values['GCW'] = $this->model_values['cooling_water_flow']; 

        $this->calculation_values['isStandard'] = $this->model_values['metallurgy_standard']; 

        $this->calculation_values['THW1'] = $this->model_values['hot_water_in']; 
        $this->calculation_values['THW2'] = $this->model_values['hot_water_out'];  

        // Standard Calculation Values
        $this->calculation_values['CoolingWaterOutTemperature'] = 0;
        $this->calculation_values['ChilledWaterFlow'] = 0;
        $this->calculation_values['BypassFlow'] = 0;
        $this->calculation_values['ChilledFrictionLoss'] = 0;
        $this->calculation_values['CoolingFrictionLoss'] = 0;
        $this->calculation_values['HotWaterFlow'] = 0;
        $this->calculation_values['HotWaterFrictionLoss'] = 0;


        $this->DATA();

        $this->THICKNESS();
    }

    public function validateChillerAttribute($attribute){

        switch (strtoupper($attribute))
        {
            case "MODEL_NUMBER":
            // $this->modulNumberDoubleEffectH2();

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
    // Log::info(print_r($this->model_values,true));
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
            case "HOT_WATER_IN":
            if (!(($this->model_values['hot_water_in'] >= $this->model_values['min_hot_water_in']) && ($this->model_values['hot_water_in'] <=$this->model_values['max_hot_water_in'])))
            {
                return array('status' => false,'msg' => $this->notes['NOTES_HWIT_OR']);
            }
            break;

            case "HOT_WATER_OUT":
            if ($this->model_values['hot_water_out'] >= $this->model_values['hot_water_in'])
            {
                return array('status' => false,'msg' => $this->notes['NOTES_HWO_HWI']);
            }
            if ($this->model_values['hot_water_out'] < $this->model_values['min_hot_water_out'])
            {
                return array('status' => false,'msg' => $this->notes['NOTES_HWOT_MV']);

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
        // Log::info("metallurgy = ".print_r($this->model_values,true));
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
        {   
            $this->model_values['tube_metallurgy_standard'] = 'true';
            $this->model_values['metallurgy_standard'] = true;
            $this->model_values['evaporator_thickness_change'] = true;
        }

        $evaporator_validator = $this->validateChillerAttribute('EVAPORATOR_TUBE_TYPE');
        if(!$evaporator_validator['status'])
            return array('status'=>false,'msg'=>$evaporator_validator['msg']);

    // Log::info("metallurgy updated = ".print_r($this->model_values,true));
        $this->onChangeMetallurgyOption();

        return  array('status' => true,'msg' => "process run successfully");
    }

    public function onChangeMetallurgyOption(){
        if($this->model_values['metallurgy_standard']){
            $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$this->model_code)
            ->where('min_model','<=',(int)$this->model_values['model_number'])->where('max_model','>=',(int)$this->model_values['model_number'])->first();

            $this->model_values['evaporator_material_value'] = $chiller_metallurgy_options->eva_default_value;
            
            $this->model_values['absorber_material_value'] = $chiller_metallurgy_options->abs_default_value;

            $this->model_values['condenser_material_value'] = $chiller_metallurgy_options->con_default_value;

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
            'hot_water_in',
            'hot_water_out',
            'min_hot_water_in',
            'max_hot_water_in',
            'min_hot_water_out',
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
