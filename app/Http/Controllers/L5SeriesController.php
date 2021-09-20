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
                                        ->where('min_model','<=',185)->where('max_model','>=',185)->first();

                               
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

        if($chiller_form_values['region_type'] == 2 || $chiller_form_values['region_type'] == 3)
        {
            $chiller_form_values['capacity'] =  $chiller_form_values['USA_capacity'];
            $chiller_form_values['chilled_water_in'] =  $chiller_form_values['USA_chilled_water_in'];
            $chiller_form_values['chilled_water_out'] =  $chiller_form_values['USA_chilled_water_out'];
            $chiller_form_values['cooling_water_in'] =  $chiller_form_values['USA_cooling_water_in'];
            $chiller_form_values['cooling_water_flow'] =  $chiller_form_values['USA_cooling_water_flow'];
            $chiller_form_values['hot_water_in'] =  $chiller_form_values['USA_hot_water_in'];
            $chiller_form_values['hot_water_flow'] =  $chiller_form_values['USA_hot_water_flow'];

            if($chiller_form_values['region_type'] == 2){
                $chiller_form_values['fouling_factor']="ari";
                $chiller_form_values['fouling_chilled_water_value'] =  $chiller_form_values['fouling_ari_chilled'];
                $chiller_form_values['fouling_cooling_water_value'] =  $chiller_form_values['fouling_ari_cooling'];
            }
            else{
                $chiller_form_values['fouling_factor']="standard";
            }
        }

        $min_chilled_water_out = Auth::user()->min_chilled_water_out;
        if($min_chilled_water_out > $chiller_form_values['min_chilled_water_out'])
            $chiller_form_values['min_chilled_water_out'] = $min_chilled_water_out;

        $unit_conversions = new UnitConversionController;
        
        $converted_values = $unit_conversions->formUnitConversion($chiller_form_values,$this->model_code);

        $calculator_name = DB::table('calculators')->where('code', $this->model_code)->first();
        $calculator_name = $calculator_name->display_name;

        return view('l5_series')->with('default_values',$converted_values)
                                        ->with('language_datas',$language_datas)
                                        ->with('evaporator_options',$evaporator_options)
                                        ->with('absorber_options',$absorber_options)
                                        ->with('condenser_options',$condenser_options) 
                                        ->with('chiller_metallurgy_options',$chiller_metallurgy_options)
                                        ->with('unit_set',$unit_set)
                                        ->with('units_data',$units_data)
                                        ->with('calculator_name',$calculator_name)
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
       

        $model_number =(int)$this->model_values['model_number'];
        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$this->model_code)->where('min_model','<=',$model_number)->where('max_model','>',$model_number)->first();
        //$queries = DB::getQueryLog();


        $chiller_options = $chiller_metallurgy_options->chillerOptions;
        
        $evaporator_options = $chiller_options->where('type', 'eva');
        $absorber_options = $chiller_options->where('type', 'abs');
        $condenser_options = $chiller_options->where('type', 'con');

        return response()->json(['status'=>true,'msg'=>'Ajax Datas','model_values'=>$converted_values,'changed_value'=>$this->changed_value,'evaporator_options'=>$evaporator_options,'absorber_options'=>$absorber_options,'condenser_options'=>$condenser_options,'chiller_metallurgy_options'=>$chiller_metallurgy_options]);
    }

    public function postL5(Request $request){

        $model_values = $request->input('values');
        $name = $request->input('name',"");
        $project = $request->input('project',"");
        $phone = $request->input('phone',"");


        $unit_conversions = new UnitConversionController;
        $converted_values = $unit_conversions->calculationUnitConversion($model_values,$this->model_code);

        $this->model_values = $converted_values;

        $this->castToBoolean();

        $vam_base = new VamBaseController();
        $this->notes = $vam_base->getNotesError();

        $validate_attribute =  $this->validateAllChillerAttributes();  
        if(!$validate_attribute['status'])
                return response()->json(['status'=>false,'msg'=>$validate_attribute['msg']]);
                                 

        $this->model_values = $converted_values;
        $this->castToBoolean();

        // $this->updateInputs();
        $this->CWFLOW();
        

        try {
            $this->WATERPROP();
            $velocity_status = $this->VELOCITY();
        } 
        catch (\Exception $e) {

            return response()->json(['status'=>false,'msg'=>$this->notes['NOTES_ERROR']]);
        }
        

        if(!$velocity_status['status'])
            return response()->json(['status'=>false,'msg'=>$velocity_status['msg']]);


        try {
            $this->CALCULATIONS();

            $this->CONVERGENCE();

            $this->RESULT_CALCULATE();
    
            $this->loadSpecSheetData();
        }
        catch (\Exception $e) {


            return response()->json(['status'=>false,'msg'=>$this->notes['NOTES_ERROR']]);
        }

        if($this->calculation_values['Result'] != "FAILED"){

            $user_detail = Auth::user();

            $user_data = array();
            $user_data['user_mail'] = $user_detail->username;
            $user_data['ip_address'] = $request->ip();;
            $user_data['customer_name'] = $name;
            $user_data['project_name'] = $project;
            $user_data['opportunity_number'] = $phone;
            $user_data['unit_set'] = $user_detail->unitSet->name;

            $report_controller = new ReportController();
            $save_report = $report_controller->saveCalculationReport($this->model_values,$this->calculation_values,$user_data,$this->model_code);

        }
        
        $calculated_values = $unit_conversions->reportUnitConversion($this->calculation_values,$this->model_code);

        // Log::info($calculated_values);
        if($calculated_values['Result'] =="FAILED")
        {
            return response()->json(['status'=>true,'msg'=>'Ajax Datas','calculation_values'=>$calculated_values]);
        }
        else
        {
            $showreport = $this->postShowReport($calculated_values,$name,$project,$phone);
            return response()->json(['status'=>true,'msg'=>'Ajax Datas','calculation_values'=>$calculated_values,'report'=>$showreport]);
        }

  
    }

    public function postShowReport($calculated_values,$name,$project,$phone){

        $calculation_values = $calculated_values;
        

        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$this->model_code)
                                        ->where('min_model','<=',(int)$calculation_values['MODEL'])->where('max_model','>',(int)$calculation_values['MODEL'])->first();

        $chiller_options = $chiller_metallurgy_options->chillerOptions;
        
        $evaporator_option = $chiller_options->where('type', 'eva')->where('value',$calculation_values['TU2'])->first();
        $absorber_option = $chiller_options->where('type', 'abs')->where('value',$calculation_values['TU5'])->first();
        $condenser_option = $chiller_options->where('type', 'con')->where('value',$calculation_values['TV5'])->first();

        $evaporator_name = $evaporator_option->metallurgy->display_name;
        $absorber_name = $absorber_option->metallurgy->display_name;
        $condenser_name = $condenser_option->metallurgy->display_name;

        $unit_set_id = Auth::user()->unit_set_id;
        $unit_set = UnitSet::find($unit_set_id);



        $vam_base = new VamBaseController();
        $language_datas = $vam_base->getLanguageDatas();
        $units_data = $vam_base->getUnitsData();
        
        $view = view("reports.l5_report", ['name' => $name,'phone' => $phone,'project' => $project,'calculation_values' => $calculation_values,'evaporator_name' => $evaporator_name,'absorber_name' => $absorber_name,'condenser_name' => $condenser_name,'unit_set' => $unit_set,'units_data' => $units_data,'language_datas' => $language_datas])->render();

        return $view;
    
    }

    public function postSaveReport(Request $request){
        $calculation_values = $request->input('calculation_values');
        $name = $request->input('name',"");
        $project = $request->input('project',"");
        $phone = $request->input('phone',"");
        $report_type = $request->input('report_type',"save_pdf");

        

        $user = Auth::user();

        $user_report = new UserReport;
        $user_report->user_id = $user->id;
        $user_report->name = $name;
        $user_report->project = $project;
        $user_report->phone = $phone;
        $user_report->calculator_code = $this->model_code;
        $user_report->unit_set_id = $user->unit_set_id;
        $user_report->report_type = $report_type;
        $user_report->region_type = $calculation_values['region_type'];
        $user_report->calculation_values = json_encode($calculation_values);
        $user_report->language = Auth::user()->language_id;
        $user_report->save();

        $redirect_url = route('download.l5report', ['user_report_id' => $user_report->id,'type' => $report_type]);
        
        return response()->json(['status'=>true,'msg'=>'Ajax Datas','redirect_url'=>$redirect_url]);
        
    }

    public function downloadReport($user_report_id,$type){

        $user_report = UserReport::find($user_report_id);
        if(!$user_report){
            return response()->json(['status'=>false,'msg'=>'Invalid Report']);
        }

        if($type == 'save_word'){
            $report_controller = new ReportController();
            $file_name = $report_controller->wordFormatL5($user_report_id,$this->model_code);

            // $file_name = "L5-Series-".Auth::user()->id.".docx";
            return response()->download(storage_path($file_name));
        }

        $calculation_values = json_decode($user_report->calculation_values,true);
        
        $name = $user_report->name;
        $project = $user_report->project;
        $phone = $user_report->phone;

        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$this->model_code)
                                        ->where('min_model','<=',(int)$calculation_values['MODEL'])->where('max_model','>=',(int)$calculation_values['MODEL'])->first();

        $chiller_options = $chiller_metallurgy_options->chillerOptions;
        
        $evaporator_option = $chiller_options->where('type', 'eva')->where('value',$calculation_values['TU2'])->first();
        $absorber_option = $chiller_options->where('type', 'abs')->where('value',$calculation_values['TU5'])->first();
        $condenser_option = $chiller_options->where('type', 'con')->where('value',$calculation_values['TV5'])->first();

        $evaporator_name = $evaporator_option->metallurgy->report_name;
        $absorber_name = $absorber_option->metallurgy->report_name;
        $condenser_name = $condenser_option->metallurgy->report_name;

        $unit_set_id = Auth::user()->unit_set_id;
        $unit_set = UnitSet::find($unit_set_id);

        $language = $user_report->language;


        $vam_base = new VamBaseController();
        $language_datas = $vam_base->getLanguageDatas();
        $units_data = $vam_base->getUnitsData();


        $pdf = PDF::loadView('reports.report_l5_pdf', ['name' => $name,'phone' => $phone,'project' => $project,'calculation_values' => $calculation_values,'evaporator_name' => $evaporator_name,'absorber_name' => $absorber_name,'condenser_name' => $condenser_name,'unit_set' => $unit_set,'units_data' => $units_data,'language_datas' => $language_datas,'language' => $language]);

        return $pdf->download('l5.pdf');

    }

    public function postResetL5(Request $request){
        $model_number =(int)$request->input('model_number');
        $model_values = $request->input('values');

        $chiller_form_values = $this->getFormValues($model_number);

        $chiller_form_values['region_type'] = $model_values['region_type'];
        if($model_values['region_type'] == 2 || $model_values['region_type'] == 3)
        {
            $chiller_form_values['capacity'] =  $chiller_form_values['USA_capacity'];
            $chiller_form_values['chilled_water_in'] =  $chiller_form_values['USA_chilled_water_in'];
            $chiller_form_values['chilled_water_out'] =  $chiller_form_values['USA_chilled_water_out'];
            $chiller_form_values['cooling_water_in'] =  $chiller_form_values['USA_cooling_water_in'];
            $chiller_form_values['cooling_water_flow'] =  $chiller_form_values['USA_cooling_water_flow'];
            $chiller_form_values['hot_water_in'] =  $chiller_form_values['USA_hot_water_in'];
            $chiller_form_values['hot_water_flow'] =  $chiller_form_values['USA_hot_water_flow'];

            if($chiller_form_values['region_type'] == 2)
                $chiller_form_values['fouling_factor']="ari";
            else
                $chiller_form_values['fouling_factor']="standard";

        }


        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$this->model_code)->where('min_model','<=',$model_number)->where('max_model','>=',$model_number)->first();


        $chiller_options = $chiller_metallurgy_options->chillerOptions;
        
        $evaporator_options = $chiller_options->where('type', 'eva');
        $absorber_options = $chiller_options->where('type', 'abs');
        $condenser_options = $chiller_options->where('type', 'con');


        $unit_set_id = Auth::user()->unit_set_id;
        $unit_set = UnitSet::find($unit_set_id);
    
            
        $this->model_values = $chiller_form_values;

        $this->castToBoolean();
        $this->CWFLOW();

        $min_chilled_water_out = Auth::user()->min_chilled_water_out;
        if($min_chilled_water_out > $this->model_values['min_chilled_water_out'])
            $this->model_values['min_chilled_water_out'] = $min_chilled_water_out;
        

        $unit_conversions = new UnitConversionController;
        $converted_values = $unit_conversions->formUnitConversion($this->model_values,$this->model_code);
 
        

        return response()->json(['status'=>true,'msg'=>'Ajax Datas','model_values'=>$converted_values,'evaporator_options'=>$evaporator_options,'absorber_options'=>$absorber_options,'condenser_options'=>$condenser_options,'chiller_metallurgy_options'=>$chiller_metallurgy_options]);

    }


    public function validateChillerAttribute($attribute){

        switch (strtoupper($attribute))
        {
            case "MODEL_NUMBER":
                // $this->modulNumberDoubleEffectS2();
                $this->model_values['metallurgy_standard'] = true;
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
                    if (floatval($this->model_values['evaporator_material_value']) != 4)
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
                    $this->model_values['glycol_hot_water'] = 0;
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
                if($this->model_values['hot_water_in'] < $this->model_values['how_water_temp_min_range'] || $this->model_values['hot_water_in'] > $this->model_values['how_water_temp_max_range']){
                    return array('status' => false,'msg' => $this->notes['NOTES_HWIT_OR']);
                }
            break;

        }


        return array('status' => true,'msg' => "process run successfully");

    }

    public function validateAllChillerAttributes(){

        // "CAPACITY"
        $capacity = floatval($this->model_values['capacity']);
        if($capacity <= 0){
            return array('status' => false,'msg' => $this->notes['NOTES_IV_CAPVAL']);
        }
        $this->model_values['capacity'] = $capacity;
        $range_calculation = $this->CWFLOW();
        if(!$range_calculation['status']){
            return array('status'=>false,'msg'=>$range_calculation['msg']);
        }
            

        // "CHILLED_WATER_IN":
        if(floatval($this->model_values['chilled_water_out']) >= floatval($this->model_values['chilled_water_in'])){
            return array('status' => false,'msg' => $this->notes['NOTES_CHW_OUT_TEMP']);
        }
        
         // "CHILLED_WATER_OUT":

        if (floatval($this->model_values['chilled_water_out']) < floatval($this->model_values['min_chilled_water_out']))
        {
            return array('status' => false,'msg' => $this->notes['NOTES_CHW_OT_MIN'].' (min = '.$this->model_values['min_chilled_water_out'].')');
        }
        if (floatval($this->model_values['chilled_water_out']) >= floatval($this->model_values['chilled_water_in']))
        {
            return array('status' => false,'msg' => $this->notes['NOTES_CHW_OT_IT']);
        }

                  
        // "EVAPORATOR_TUBE_TYPE":

        if (floatval($this->model_values['chilled_water_out']) < 3.5 && floatval($this->model_values['glycol_chilled_water']) == 0)
        {
            if (floatval($this->model_values['evaporator_material_value']) != 4)
            {

                return array('status' => false,'msg' => $this->notes['NOTES_EVA_TUBETYPE']);
            }

        }


        // "GLYCOL_CHILLED_WATER":
        
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

        // "GLYCOL_COOLING_WATER":
        if (($this->model_values['glycol_cooling_water'] > $this->model_values['glycol_max_cooling_water']))
        {
            return array('status' => false,'msg' => $this->notes['NOTES_COW_GLY_OR']);
        }
          

        // "COOLING_WATER_IN":
        if (!(($this->model_values['cooling_water_in'] >= $this->model_values['cooling_water_in_min_range']) && ($this->model_values['cooling_water_in'] <= $this->model_values['cooling_water_in_max_range'])))
        {
            return array('status' => false,'msg' => $this->notes['NOTES_COW_TEMP']);
        }
            


         // "COOLING_WATER_FLOW":


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
    


         // "EVAPORATOR_THICKNESS":
        if(($this->model_values['evaporator_thickness'] >= $this->model_values['evaporator_thickness_min_range']) && ($this->model_values['evaporator_thickness'] <= $this->model_values['evaporator_thickness_max_range'])){

        }
        else{
            return array('status' => false,'msg' =>$this->notes['NOTES_EVA_THICK']);
        }
    


    // "ABSORBER_THICKNESS":

        if(($this->model_values['absorber_thickness'] >= $this->model_values['absorber_thickness_min_range']) && ($this->model_values['absorber_thickness'] <= $this->model_values['absorber_thickness_max_range'])){

        }
        else{
            return array('status' => false,'msg' => $this->notes['NOTES_ABS_THICK']);
        }
          


         // "CONDENSER_THICKNESS":

        if(($this->model_values['condenser_thickness'] >= $this->model_values['condenser_thickness_min_range']) && ($this->model_values['condenser_thickness'] <= $this->model_values['condenser_thickness_max_range'])){

        }
        else{
            return array('status' => false,'msg' => $this->notes['NOTES_CON_THICK']);
        }
    
        // "FOULING_CHILLED_VALUE":
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
                
           

         // "FOULING_COOLING_VALUE":
       
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

        // "FOULING_HOT_WATER_VALUE":
        if($this->model_values['fouling_factor'] != 'standard' && !empty($this->model_values['fouling_hot_water_checked'])){
            if($this->model_values['fouling_hot_water_value'] < $this->model_values['fouling_non_hot']){
                return array('status' => false,'msg' => $this->notes['NOTES_HOW_FF_MIN']);
            }
        }
        
        // "GLYCOL_HOT_WATER":
        if($this->model_values['glycol_hot_water'] > $this->model_values['glycol_max_hot_water']){
            return array('status' => false,'msg' => $this->notes['NOTES_HW_GLY_OR']);
        }
        
        // "HOT_WATER_IN":
        if($this->model_values['hot_water_in'] < $this->model_values['how_water_temp_min_range'] || $this->model_values['hot_water_in'] > $this->model_values['how_water_temp_max_range']){
            return array('status' => false,'msg' => $this->notes['NOTES_HWIT_OR']);
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
                                        ->where('min_model','<=',(int)$this->model_values['model_number'])->where('max_model','>=',(int)$this->model_values['model_number'])->first();

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

        if ($this->calculation_values['GCW1MIN'] > $this->calculation_values['FMAX'])
        {
            if ($this->calculation_values['GCW2MAX'] < $this->calculation_values['FMAX'] && $this->calculation_values['FMIN'] < $this->calculation_values['GCW2MAX'])
            {
                $range_values[] = $this->calculation_values['FMIN'];
                $range_values[] = $this->calculation_values['GCW2MAX'];
                $MORE = 1;
            }
            else if ($this->calculation_values['FMIN'] < $this->calculation_values['FMAX'])
            {
                $range_values[] = $this->calculation_values['FMIN'];
                $range_values[] = $this->calculation_values['FMAX'];
                $MORE = 1;
            }

        }
        else if ($this->calculation_values['GCW2MAX'] < $this->calculation_values['FMIN'])
        {
            if ($this->calculation_values['GCW1MIN'] > $this->calculation_values['FMIN'] && $this->calculation_values['GCW1MIN'] < $this->calculation_values['FMAX'])
            {
                $range_values[] = $this->calculation_values['GCW1MIN'];
                $range_values[] = $this->calculation_values['FMAX'];
                $MORE = 1;
            }
            else if ($this->calculation_values['FMIN'] < $this->calculation_values['FMAX'])
            {
                $range_values[] = $this->calculation_values['FMIN'];
                $range_values[] = $this->calculation_values['FMAX'];
                $MORE = 1;
            }
        }
        else
        {
            $range_values[] = $this->calculation_values['FMIN'];
            $range_values[] = $this->calculation_values['GCW2MAX'];
            $range_values[] = $this->calculation_values['GCW1MIN'];
            $range_values[] = $this->calculation_values['FMAX'];
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

        $TCHW2L = $this->model_values['chilled_water_out'];
        $TCW11 = $this->model_values['cooling_water_in'];
        $TON = $this->model_values['capacity'];
        $MODEL = (int)$this->model_values['model_number'];
        


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
                                        ->where('min_model','<=',$MODEL)->where('max_model','>=',$MODEL)->first();

                                
        $chiller_options = $chiller_metallurgy_options->chillerOptions;
        
        // $evaporator_option = $chiller_options->where('type', 'eva')->where('value',$this->model_values['evaporator_material_value'])->first();
        $absorber_option = $chiller_options->where('type', 'abs')->where('value',$this->calculation_values['TU5'])->first();
        $condenser_option = $chiller_options->where('type', 'con')->where('value',$this->calculation_values['TV5'])->first();

        // $VAMIN = $absorber_option->metallurgy->abs_min_velocity;          
        // $VAMAX = $absorber_option->metallurgy->abs_max_velocity;
        // $VCMIN = $condenser_option->metallurgy->con_min_velocity;
        // $VCMAX = $condenser_option->metallurgy->con_max_velocity;

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
        $pid_ft3 = $vam_base->PIPE_ID($NB);
        $this->calculation_values['PIDE'] = $pid_ft3['PID'];
        $this->calculation_values['PODE'] = $pid_ft3['POD'];
        $this->calculation_values['FT'] = $pid_ft3['FT'];
        $this->calculation_values['EVANB'] = $NB;

        $NB = $this->calculation_values['PNB'];
        $pid_ft3 = $vam_base->PIPE_ID($NB);
        $this->calculation_values['PIDA'] = $pid_ft3['PID'];
        $this->calculation_values['PODA'] = $pid_ft3['POD'];
        $this->calculation_values['FT1'] = $pid_ft3['FT'];
        $this->calculation_values['ABSNB'] = $NB;

        $NB = $this->calculation_values['PNB'];
        $pid_ft3 = $vam_base->PIPE_ID($NB);
        $this->calculation_values['PIDC'] = $pid_ft3['PID'];
        $this->calculation_values['PODC'] = $pid_ft3['POD'];
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
        $pid_ft3 = $vam_base->PIPE_ID($NB);
        $this->calculation_values['PIDG'] = $pid_ft3['PID'];
        $this->calculation_values['PODG'] = $pid_ft3['POD'];
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
        $this->calculation_values['version'] = $this->model_values['version'];
        $this->calculation_values['version_date'] = $this->model_values['version_date'];
        

        $vam_base = new VamBaseController();

        $pid_ft3 = $vam_base->PIPE_ID($this->calculation_values['PNB']);
        $this->calculation_values['PODA'] = $pid_ft3['POD'];
        $this->calculation_values['THPA'] = $pid_ft3['THP'];

        
        $this->calculation_values['PSL1'] = $this->calculation_values['PSLI'] + $this->calculation_values['PSLO'];


        $this->calculation_values['MODEL'] = $this->model_values['model_number'];
        $this->calculation_values['TON'] = $this->model_values['capacity'];
        $this->calculation_values['TUU'] = $this->model_values['fouling_factor'];
        $this->calculation_values['FFCHW1'] = floatval($this->model_values['fouling_chilled_water_value']);
        $this->calculation_values['FFCOW1'] = floatval($this->model_values['fouling_cooling_water_value']);
        $this->calculation_values['FFHOW1'] = floatval($this->model_values['fouling_hot_water_value']);

        $chiller_metallurgy_option = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$this->model_code)
                                    ->where('min_model','<=',(int)$this->model_values['model_number'])->where('max_model','>=',(int)$this->model_values['model_number'])->first();


        $chiller_options = $chiller_metallurgy_option->chillerOptions; 
                    

        $evaporator_option = $chiller_options->where('type', 'eva')->where('value',$chiller_metallurgy_option->eva_default_value)->first();
        $absorber_option = $chiller_options->where('type', 'abs')->where('value',$chiller_metallurgy_option->abs_default_value)->first();
        $condenser_option = $chiller_options->where('type', 'con')->where('value',$chiller_metallurgy_option->con_default_value)->first();

        if(!isset($this->model_values['generator_tube_name'])){
            $this->model_values['generator_tube_name'] = "";
        }


        if($this->model_values['metallurgy_standard']){
                     
            $this->calculation_values['TU2'] = $chiller_metallurgy_option->eva_default_value; 
            $this->calculation_values['TU3'] = $evaporator_option->metallurgy->default_thickness;
            $this->calculation_values['TU5'] = $chiller_metallurgy_option->abs_default_value;
            $this->calculation_values['TU6'] = $absorber_option->metallurgy->default_thickness;
            $this->calculation_values['TV5'] = $chiller_metallurgy_option->con_default_value; 
            $this->calculation_values['TV6'] = $condenser_option->metallurgy->default_thickness;
            $this->calculation_values['generator_tube_name'] = $this->model_values['generator_tube_name'];
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
            $this->calculation_values['generator_tube_name'] = $this->model_values['generator_tube_name'];
            if($this->calculation_values['TG2'] == 1){
                $this->calculation_values['TG3'] = 0.65;
            }
            elseif ($this->calculation_values['TG2'] == 2) {
                $this->calculation_values['TG3'] = 0.8;
            }
        }

        if($this->model_values['hot_water_in'] > 105){
            $this->calculation_values['TG2'] = 2;
            $this->calculation_values['TG3'] = 0.8;
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
        $this->calculation_values['TGP'] = 0;
        $this->calculation_values['HHType'] = "Standard";
        // $this->calculation_values['EVAPDROP'] = 0;

        if($this->calculation_values['region_type'] == 1){
            $this->calculation_values['SS_FACTOR'] = 1;
        }
        else{
            $this->calculation_values['SS_FACTOR'] = 0.96;
        }
        


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


        $this->calculation_values['AABSH'] = $this->calculation_values['AABS'] /2 ;
        $this->calculation_values['AABSL'] = $this->calculation_values['AABS'] /2 ;


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

        if ($this->calculation_values['TU2'] == 1 || $this->calculation_values['TU2'] == 9){
            $this->calculation_values['KEVAH'] = (1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 37000))) * 0.95;
        }
        if ($this->calculation_values['TU2'] == 2 || $this->calculation_values['TU2'] == 6){
            $this->calculation_values['KEVAH'] = 1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 340000.0));            
        }
        if ($this->calculation_values['TU2'] == 4){
            $this->calculation_values['KEVAH'] = (1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 21000.0))) * $this->calculation_values['SS_FACTOR'];
        }
        if ($this->calculation_values['TU2'] == 3){
            $this->calculation_values['KEVAH'] = 1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 21000.0)) * $this->calculation_values['SS_FACTOR'];              //Changed to $this->calculation_values['KEVA1'] from 1600 on 06/11/2017 as tube metallurgy is changed
        }
        if ($this->calculation_values['TU2'] == 7 || $this->calculation_values['TU2'] == 8){
            $this->calculation_values['KEVAH'] = (1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 15000.0))) * 0.93;
        }
        if ($this->calculation_values['TU2'] == 5){
            $this->calculation_values['KEVAH'] = 1 / ((1 / 1600.0) + ($this->calculation_values['TU3'] / 15000.0));
        }

        /******** DETERMINATION OF $this->calculation_values['KEVAL'] FOR NON STD.SELECTION*****/
        $this->calculation_values['KEVA2'] = 1 / ((1 / $this->calculation_values['KEVAL']) - (0.65 / 340000));

        if ($this->calculation_values['TU2'] == 1 || $this->calculation_values['TU2'] == 9){
            $this->calculation_values['KEVAL'] = (1 / ((1 / $this->calculation_values['KEVA2']) + ($this->calculation_values['TU3'] / 37000)));
        }
        if ($this->calculation_values['TU2'] == 2 || $this->calculation_values['TU2'] == 6){
            $this->calculation_values['KEVAL'] = 1 / ((1 / $this->calculation_values['KEVA2']) + ($this->calculation_values['TU3'] / 340000.0));            
        }
        if ($this->calculation_values['TU2'] == 4){
            $this->calculation_values['KEVAL'] = (1 / ((1 / $this->calculation_values['KEVA2']) + ($this->calculation_values['TU3'] / 21000.0))) * $this->calculation_values['SS_FACTOR'];
        }
        if ($this->calculation_values['TU2'] == 3){
            $this->calculation_values['KEVAL'] = 1 / ((1 / $this->calculation_values['KEVA2']) + ($this->calculation_values['TU3'] / 21000.0)) * $this->calculation_values['SS_FACTOR'];              //Changed to $this->calculation_values['KEVA1'] from 1600 on 06/11/2017 as tube metallurgy is changed
        }
        if ($this->calculation_values['TU2'] == 7 || $this->calculation_values['TU2'] == 8){
            $this->calculation_values['KEVAL'] = (1 / ((1 / $this->calculation_values['KEVA2']) + ($this->calculation_values['TU3'] / 15000.0))) * 0.93;
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
            if ($this->calculation_values['TU5'] == 5){
                $this->calculation_values['KABS'] = (1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 15000))) * 0.93;
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
        else if ($this->calculation_values['TV5'] == 4){
            $this->calculation_values['KCON'] = 3900;
            $this->calculation_values['KCON1'] = 1 / ((1 / $this->calculation_values['KCON']) - (0.65 / 340000));
            $this->calculation_values['KCON'] = 1 / ((1 / $this->calculation_values['KCON1']) + ($this->calculation_values['TV6'] / 15000)) * 0.95;
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


        if ($this->calculation_values['TU2'] == 4 || $this->calculation_values['TU2'] == 2 || $this->calculation_values['TU2'] == 1 || $this->calculation_values['TU2'] == 8 )
        {
            $this->calculation_values['THE'] = $this->calculation_values['TU3'] + 0.1;
        }
        else
        {
            $this->calculation_values['THE'] = $this->calculation_values['TU3'];
        }


        if ($this->calculation_values['TU5'] < 2.1 || $this->calculation_values['TU5'] == 6 || $this->calculation_values['TU5'] == 5)
        {
            $this->calculation_values['THA'] = $this->calculation_values['TU6'] + 0.1;
        }
        else
        {
            $this->calculation_values['THA'] = $this->calculation_values['TU6'];
        }

        if ($this->calculation_values['TV5'] == 1 || $this->calculation_values['TV5'] == 3 || $this->calculation_values['TV5'] == 4)
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
                if ($this->calculation_values['TCHW2L'] < 3.5)
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
            case 450:
                if ($this->calculation_values['TCHW2L'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC L5 F1";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC L5 F1";
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

    public function WATERPROP(){

        $vam_base = new VamBaseController();
        
        if (intval($this->calculation_values['GL']) == 2)
        {
            $this->calculation_values['CHGLY_VIS12'] = $vam_base->EG_VISCOSITY($this->calculation_values['TCHW2L'], $this->calculation_values['CHGLY']) / 1000;
            $this->calculation_values['CHGLY_TCON12'] = $vam_base->EG_THERMAL_CONDUCTIVITY($this->calculation_values['TCHW2L'], $this->calculation_values['CHGLY']);
            $this->calculation_values['CHGLY_ROW12'] = $vam_base->EG_ROW($this->calculation_values['TCHW1H'], $this->calculation_values['CHGLY']);
            $this->calculation_values['CHGLY_SPHT11'] = $vam_base->EG_SPHT($this->calculation_values['TCHW1H'], $this->calculation_values['CHGLY']) * 1000;
            $this->calculation_values['CHGLY_SPHT12'] = $vam_base->EG_SPHT($this->calculation_values['TCHW2L'], $this->calculation_values['CHGLY']) * 1000;

            $this->calculation_values['COGLY_VIS1'] = $vam_base->EG_VISCOSITY($this->calculation_values['TCW11'], $this->calculation_values['COGLY']) / 1000;
            $this->calculation_values['COGLY_TCON1'] = $vam_base->EG_THERMAL_CONDUCTIVITY($this->calculation_values['TCW11'], $this->calculation_values['COGLY']);
            $this->calculation_values['COGLY_ROW1'] = $vam_base->EG_ROW($this->calculation_values['TCW11'], $this->calculation_values['COGLY']);
            $this->calculation_values['COGLY_SPHT11'] = $vam_base->EG_SPHT($this->calculation_values['TCW11'], $this->calculation_values['COGLY']) * 1000;
        }
        else
        {
            $this->calculation_values['CHGLY_VIS12'] = $vam_base->PG_VISCOSITY($this->calculation_values['TCHW2L'], $this->calculation_values['CHGLY']) / 1000;
            $this->calculation_values['CHGLY_TCON12'] = $vam_base->PG_THERMAL_CONDUCTIVITY($this->calculation_values['TCHW2L'], $this->calculation_values['CHGLY']);
            $this->calculation_values['CHGLY_SPHT11'] = $vam_base->PG_SPHT($this->calculation_values['TCHW1H'], $this->calculation_values['CHGLY']) * 1000;
            $this->calculation_values['CHGLY_ROW12'] = $vam_base->PG_ROW($this->calculation_values['TCHW1H'], $this->calculation_values['CHGLY']);
            $this->calculation_values['CHGLY_SPHT12'] = $vam_base->PG_SPHT($this->calculation_values['TCHW2L'], $this->calculation_values['CHGLY']) * 1000;

            $this->calculation_values['COGLY_VIS1'] = $vam_base->PG_VISCOSITY($this->calculation_values['TCW11'], $this->calculation_values['COGLY']) / 1000;
            $this->calculation_values['COGLY_TCON1'] = $vam_base->PG_THERMAL_CONDUCTIVITY($this->calculation_values['TCW11'], $this->calculation_values['COGLY']);
            $this->calculation_values['COGLY_ROW1'] = $vam_base->PG_ROW($this->calculation_values['TCW11'], $this->calculation_values['COGLY']);
            $this->calculation_values['COGLY_SPHT11'] = $vam_base->PG_SPHT($this->calculation_values['TCW11'], $this->calculation_values['COGLY']) * 1000;
        }
        $this->calculation_values['GCHW'] = $this->calculation_values['TON'] * 3024 / (($this->calculation_values['TCHW1H'] - $this->calculation_values['TCHW2L']) * $this->calculation_values['CHGLY_ROW12'] * 0.5 * ($this->calculation_values['CHGLY_SPHT11'] + $this->calculation_values['CHGLY_SPHT12']) / 4187);


    }

    public function VELOCITY(){
        $model_number =(int)$this->calculation_values['MODEL'];

        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$this->model_code)
                                        ->where('min_model','<=',$model_number)->where('max_model','>',$model_number)->first();

        $chiller_options = $chiller_metallurgy_options->chillerOptions;
        
        $evaporator_option = $chiller_options->where('type', 'eva')->where('value',$this->calculation_values['TU2'])->first();
        $absorber_option = $chiller_options->where('type', 'abs')->where('value',$this->calculation_values['TU5'])->first();
        $condenser_option = $chiller_options->where('type', 'con')->where('value',$this->calculation_values['TV5'])->first();

        // $this->calculation_values['VAMIN'] = $absorber_option->metallurgy->abs_min_velocity;          
        // $this->calculation_values['VAMAX'] = $absorber_option->metallurgy->abs_max_velocity;
        // $this->calculation_values['VCMIN'] = $condenser_option->metallurgy->con_min_velocity;
        // $this->calculation_values['VCMAX'] = $condenser_option->metallurgy->con_max_velocity;
        $this->calculation_values['VEMIN'] = $evaporator_option->metallurgy->eva_min_velocity;
        $this->calculation_values['VEMAX'] = $evaporator_option->metallurgy->eva_max_velocity;

        if ($this->calculation_values['MODEL'] < 700)
        {
            $this->calculation_values['VAMIN'] = 1.5; $this->calculation_values['VAMAX'] = 2.6;
            $this->calculation_values['VCMIN'] = 1.3; $this->calculation_values['VCMAX'] = 2.6;
        }
        else
        {
            $this->calculation_values['VAMIN'] = 1.58; $this->calculation_values['VAMAX'] = 2.78;
            $this->calculation_values['VCMIN'] = 1.35; $this->calculation_values['VCMAX'] = 2.78;
        }

        $this->calculation_values['TAP'] = 1;
        do
        {
            $this->calculation_values['VA'] = $this->calculation_values['GCW'] / (((3600 * 3.141593 * $this->calculation_values['IDA'] * $this->calculation_values['IDA']) / 4.0) * ($this->calculation_values['TNAA'] / $this->calculation_values['TAP']));

            if ($this->calculation_values['VA'] < $this->calculation_values['VAMIN'])
            {
                $this->calculation_values['TAP'] = $this->calculation_values['TAP'] + 1;
            }
        } while ($this->calculation_values['VA'] < $this->calculation_values['VAMIN'] && ($this->calculation_values['TAP'] < ($this->calculation_values['TAPMAX'] + 1)));

        if ($this->calculation_values['TAP'] > $this->calculation_values['TAPMAX'])
        {
            $this->calculation_values['TAP'] = $this->calculation_values['TAPMAX'];
            $this->calculation_values['VA'] = $this->calculation_values['GCW'] / (((3600 * 3.141593 * $this->calculation_values['IDA'] * $this->calculation_values['IDA']) / 4.0) * ($this->calculation_values['TNAA'] / $this->calculation_values['TAP']));
        }

        if ($this->calculation_values['VA'] > ($this->calculation_values['VAMAX'] + 0.005) && $this->calculation_values['TAP'] != 1)
        {
            $this->calculation_values['TAP'] = $this->calculation_values['TAP'] - 1;
            $this->calculation_values['VA'] = $this->calculation_values['GCW'] / (((3600 * 3.141593 * $this->calculation_values['IDA'] * $this->calculation_values['IDA']) / 4.0) * ($this->calculation_values['TNAA'] / $this->calculation_values['TAP']));
        }

        $this->calculation_values['CWC'] = 2;                //1 is for COW Series in Condenser, 2 is for COW parallel in condenser. 

        if ($this->calculation_values['CWC'] == 2)
        {
            $this->calculation_values['TNC1'] = $this->calculation_values['TNC'];
        }
        else
        {
            $this->calculation_values['TNC1'] = $this->calculation_values['TNC'] / 2;
        }

        $this->calculation_values['TCP'] = 1;
        $this->calculation_values['CWC1'] = $this->calculation_values['CWC'];
        do
        {
            $this->calculation_values['VC'] = $this->calculation_values['GCW'] / (((3600 * 3.141593 * $this->calculation_values['IDC'] * $this->calculation_values['IDC']) / 4.0) * ($this->calculation_values['TNC1'] / $this->calculation_values['TCP']));

            if ($this->calculation_values['VC'] < $this->calculation_values['VCMIN'])
            {
                $this->calculation_values['TCP'] = $this->calculation_values['TCP'] + 1;
            }
        } while ($this->calculation_values['VC'] < $this->calculation_values['VCMIN'] && $this->calculation_values['TCP'] < $this->calculation_values['TCPMAX']);

        //if ($this->calculation_values['TCP'] > 1)
        //{
        //    $this->calculation_values['TCP'] = 1;
        //    $this->calculation_values['VC'] = $this->calculation_values['GCW'] / (((3600 * 3.141593 * $this->calculation_values['IDC'] * $this->calculation_values['IDC']) / 4.0) * ($this->calculation_values['TNC1'] / $this->calculation_values['TCP']));
        //}
        //if ($this->calculation_values['VC'] > $this->calculation_values['VCMAX'])
        //{
        //    $this->calculation_values['CWC'] = 2;
        //    $this->calculation_values['CWC1'] = $this->calculation_values['CWC'];
        //    $this->calculation_values['TNC1'] = $this->calculation_values['TNC'];
        //    $this->calculation_values['TCP'] = 1;
        //    $this->calculation_values['VC'] = $this->calculation_values['GCW'] / (((3600 * 3.141593 * $this->calculation_values['IDC'] * $this->calculation_values['IDC']) / 4.0) * ($this->calculation_values['TNC1'] / $this->calculation_values['TCP']));
        //}

        $this->calculation_values['CWC'] = $this->calculation_values['CWC1'];

        if ($this->calculation_values['CWC'] == 2)
        {
            $this->calculation_values['GCWC'] = $this->calculation_values['GCW'] * 0.5;        //Condenser Parallel
        }
        else
        {
            $this->calculation_values['GCWC'] = $this->calculation_values['GCW'];     //Condenser Series
        }

        $this->calculation_values['VELEVA'] = 0; $this->calculation_values['TEP'] = 1;

        if ($this->calculation_values['TU2'] == 3 && $this->calculation_values['CHGLY'] == 0 && $this->calculation_values['TCHW2L'] < 3.5)
        {
            $this->calculation_values['VEMIN'] = $this->calculation_values['VEMIN1'];
            $this->calculation_values['VEMAX'] = 1.8;
        }

        do
        {
            $this->calculation_values['VE'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * ($this->calculation_values['TNEV1'] / $this->calculation_values['TEP']));
            if ($this->calculation_values['VE'] < $this->calculation_values['VEMIN'])
                $this->calculation_values['TEP'] = $this->calculation_values['TEP'] + 1;
        } while ($this->calculation_values['VE'] < $this->calculation_values['VEMIN'] && $this->calculation_values['TEP'] <= $this->calculation_values['TEPMAX']);

        if ($this->calculation_values['TEP'] > $this->calculation_values['TEPMAX'])
        {
            $this->calculation_values['TEP'] = $this->calculation_values['TEPMAX'];
            $this->calculation_values['VE'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * ($this->calculation_values['TNEV1'] / $this->calculation_values['TEP']));
            if ($this->calculation_values['VE'] < $this->calculation_values['VEMIN'])
            {
                return  array('status' => false,'msg' => $this->notes['NOTES_CHW_VELO']);  
            }
        }

        if ($this->calculation_values['VE'] > $this->calculation_values['VEMAX'])
        {
            if ($this->calculation_values['TEP'] > 1)
            {
                $this->calculation_values['TEP'] = $this->calculation_values['TEP'] - 1;
                $this->calculation_values['VE'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * ($this->calculation_values['TNEV1'] / $this->calculation_values['TEP']));
            }
            else
            {
                if ($this->calculation_values['TU2'] < 2.1)         //SELECTING ENDCROSSED TUBES
                {
                    //$this->calculation_values['THE'] = 0.65;
                    //$this->calculation_values['VELEVA'] = 1;
                    //$this->calculation_values['IDE'] = ODE - (2 * (THE + 0.1) / 1000);
                    //$this->calculation_values['VELEVA1'](chiller);
                    //if ($this->calculation_values['VE'] > $this->calculation_values['VEMAX'] && $this->calculation_values['TEP'] == 1)
                    //{
                    //    chiller.Notes = new string[] { LocalizedNote(NOTES_CHW_VELO_HI) };
                    //    return false;
                    //}
                    if ($this->calculation_values['TEP'] == 1)
                    {
                        return  array('status' => false,'msg' => $this->notes['NOTES_CHW_VELO_HI']);
                    }
                }
                else
                {
                    if ($this->calculation_values['TEP'] == 1)
                    {
                        return  array('status' => false,'msg' => $this->notes['NOTES_CHW_VELO_HI']); 
                    }
                }
            }
        }
        return  array('status' => true,'msg' => "chilled water velocity");     
    }

    public function DERATE_KEVAH()
    {
        $vam_base = new VamBaseController();
        
        $this->calculation_values['GLY_VIS'] = $vam_base->PG_VISCOSITY($this->calculation_values['TCHW2L'], 0) / 1000;
        $this->calculation_values['GLY_TCON'] = $vam_base->PG_THERMAL_CONDUCTIVITY($this->calculation_values['TCHW2L'], 0);
        $this->calculation_values['GLY_ROW'] = $vam_base->PG_ROW($this->calculation_values['TCHW2L'], 0);
        $this->calculation_values['GLY_SPHT'] = $vam_base->PG_SPHT($this->calculation_values['TCHW2L'], 0) * 1000;
        if ($this->calculation_values['MODEL'] < 700)
        {
            if ( $this->calculation_values['TU2'] == 3 || $this->calculation_values['TU2'] == 6 || $this->calculation_values['TU2'] == 7 || $this->calculation_values['TU2'] == 9)
            {
                $this->calculation_values['VEVA'] = 0.7;
            }
            else
            {
                $this->calculation_values['VEVA'] = 1.5;
            }
        }
        else
        {
            if ( $this->calculation_values['TU2'] == 3 || $this->calculation_values['TU2'] == 6 || $this->calculation_values['TU2'] == 7 || $this->calculation_values['TU2'] == 9)
            {
                $this->calculation_values['VEVA'] = 0.75;
            }
            else
            {
                $this->calculation_values['VEVA'] = 1.58;
            }
        }
        $this->calculation_values['RE'] = $this->calculation_values['GLY_ROW'] * $this->calculation_values['VEVA'] * $this->calculation_values['IDE'] / $this->calculation_values['GLY_VIS'];
        $this->calculation_values['PR'] = $this->calculation_values['GLY_VIS'] * $this->calculation_values['GLY_SPHT'] / $this->calculation_values['GLY_TCON'];
        $this->calculation_values['NU1'] = 0.023 * pow($this->calculation_values['RE'], 0.8) * pow($this->calculation_values['PR'], 0.3);
        $this->calculation_values['HI1'] = ($this->calculation_values['NU1'] * $this->calculation_values['GLY_TCON'] / $this->calculation_values['IDE']) * 3600 / 4187;

        if ($this->calculation_values['TU2'] == 0 || $this->calculation_values['TU2'] == 2 || $this->calculation_values['TU2'] == 6)
            $this->calculation_values['R1'] = log($this->calculation_values['ODE'] / $this->calculation_values['IDE']) * $this->calculation_values['ODE'] / (2 * 340);
        if ($this->calculation_values['TU2'] == 1 || $this->calculation_values['TU2'] == 9)
            $this->calculation_values['R1'] = log($this->calculation_values['ODE'] / $this->calculation_values['IDE']) * $this->calculation_values['ODE'] / (2 * 37);
        if ($this->calculation_values['TU2'] == 3 ||$this->calculation_values['TU2'] == 4)
            $this->calculation_values['R1'] = log($this->calculation_values['ODE'] / $this->calculation_values['IDE']) * $this->calculation_values['ODE'] / (2 * 21);
        if ($this->calculation_values['TU2'] == 5 || $this->calculation_values['TU2'] == 7 || $this->calculation_values['TU2'] == 8)
            $this->calculation_values['R1'] = log($this->calculation_values['ODE'] / $this->calculation_values['IDE']) * $this->calculation_values['ODE'] / (2 * 15);

        if ($this->calculation_values['TU2'] == 3 || $this->calculation_values['TU2'] == 6 || $this->calculation_values['TU2'] == 7 || $this->calculation_values['TU2'] == 9 )
        {
            $this->calculation_values['HI1'] = $this->calculation_values['HI1'] * 2;
        }

        $this->calculation_values['HO'] = 1 / (1 / $this->calculation_values['KEVAH'] - (1 * $this->calculation_values['ODE'] / ($this->calculation_values['HI1'] * $this->calculation_values['IDE'])) - $this->calculation_values['R1']);
        if ($this->calculation_values['VEH'] < $this->calculation_values['VEVA'])
        {
            $this->calculation_values['RE'] = $this->calculation_values['CHGLY_ROW12'] * $this->calculation_values['VEH'] * $this->calculation_values['IDE'] / $this->calculation_values['CHGLY_VIS12'];
        }
        else
        {
            $this->calculation_values['RE'] = $this->calculation_values['CHGLY_ROW12'] * $this->calculation_values['VEVA'] * $this->calculation_values['IDE'] / $this->calculation_values['CHGLY_VIS12'];
        }
        $this->calculation_values['PR'] = $this->calculation_values['CHGLY_VIS12'] * $this->calculation_values['CHGLY_SPHT12'] / $this->calculation_values['CHGLY_TCON12'];
        $this->calculation_values['NU1'] = 0.023 * pow($this->calculation_values['RE'], 0.8) * pow($this->calculation_values['PR'], 0.3);
        $this->calculation_values['HI'] = ($this->calculation_values['NU1'] * $this->calculation_values['CHGLY_TCON12'] / $this->calculation_values['IDE']) / 4187 * 3600;
        if ($this->calculation_values['TU2'] == 3 || $this->calculation_values['TU2'] == 6 || $this->calculation_values['TU2'] == 7 || $this->calculation_values['TU2'] == 9 )
        {
            $this->calculation_values['HI'] = $this->calculation_values['HI'] * 2;
        }
        $this->calculation_values['KEVAH'] = 1 / ((1 * $this->calculation_values['ODE'] / ($this->calculation_values['HI'] * $this->calculation_values['IDE'])) + $this->calculation_values['R1'] + 1 / $this->calculation_values['HO']);
        if ($this->calculation_values['CHGLY'] != 0)
        {
            $this->calculation_values['KEVAH'] = $this->calculation_values['KEVAH'] * 0.99;
        }
    }

    public function DERATE_KEVAL()
    {
        $vam_base = new VamBaseController();

        $this->calculation_values['GLY_VIS'] = $vam_base->PG_VISCOSITY($this->calculation_values['TCHW2L'], 0) / 1000;
        $this->calculation_values['GLY_TCON'] = $vam_base->PG_THERMAL_CONDUCTIVITY($this->calculation_values['TCHW2L'], 0);
        $this->calculation_values['GLY_ROW'] = $vam_base->PG_ROW($this->calculation_values['TCHW2L'], 0);
        $this->calculation_values['GLY_SPHT'] = $vam_base->PG_SPHT($this->calculation_values['TCHW2L'], 0) * 1000;
        if ($this->calculation_values['MODEL'] < 700)
       {
           if ( $this->calculation_values['TU2'] == 3 || $this->calculation_values['TU2'] == 6 || $this->calculation_values['TU2'] == 7 || $this->calculation_values['TU2'] == 9)
           {
               $this->calculation_values['VEVA'] = 0.7;
           }
           else
           {
               $this->calculation_values['VEVA'] = 1.5;
           }
       }
       else
       {
           if ( $this->calculation_values['TU2'] == 3 || $this->calculation_values['TU2'] == 6 || $this->calculation_values['TU2'] == 7 || $this->calculation_values['TU2'] == 9)
           {
               $this->calculation_values['VEVA'] = 0.75;
           }
           else
           {
               $this->calculation_values['VEVA'] = 1.58;
           }
       }
        $this->calculation_values['RE'] = $this->calculation_values['GLY_ROW'] * $this->calculation_values['VEVA'] * $this->calculation_values['IDE'] / $this->calculation_values['GLY_VIS'];
        $this->calculation_values['PR'] = $this->calculation_values['GLY_VIS'] * $this->calculation_values['GLY_SPHT'] / $this->calculation_values['GLY_TCON'];
        $this->calculation_values['NU1'] = 0.023 * pow($this->calculation_values['RE'], 0.8) * pow($this->calculation_values['PR'], 0.3);
        $this->calculation_values['HI1'] = ($this->calculation_values['NU1'] * $this->calculation_values['GLY_TCON'] / $this->calculation_values['IDE']) * 3600 / 4187;
        if ($this->calculation_values['TU2'] == 0 || $this->calculation_values['TU2'] == 2 || $this->calculation_values['TU2'] == 6)
            $this->calculation_values['R1'] = log($this->calculation_values['ODE'] / $this->calculation_values['IDE']) * $this->calculation_values['ODE'] / (2 * 340);
        if ($this->calculation_values['TU2'] == 1 || $this->calculation_values['TU2'] == 9)
            $this->calculation_values['R1'] = log($this->calculation_values['ODE'] / $this->calculation_values['IDE']) * $this->calculation_values['ODE'] / (2 * 37);
        if ($this->calculation_values['TU2'] == 3 ||$this->calculation_values['TU2'] ==4)
            $this->calculation_values['R1'] = log($this->calculation_values['ODE'] / $this->calculation_values['IDE']) * $this->calculation_values['ODE'] / (2 * 21);
        if ($this->calculation_values['TU2'] == 5 || $this->calculation_values['TU2'] == 7 || $this->calculation_values['TU2'] == 8)
            $this->calculation_values['R1'] = log($this->calculation_values['ODE'] / $this->calculation_values['IDE']) * $this->calculation_values['ODE'] / (2 * 15);

        if ($this->calculation_values['TU2'] == 3 || $this->calculation_values['TU2'] == 6 || $this->calculation_values['TU2'] == 7 || $this->calculation_values['TU2'] == 9 )
        {
            $this->calculation_values['HI1'] = $this->calculation_values['HI1'] * 2;
        }
        $this->calculation_values['HO'] = 1 / (1 / $this->calculation_values['KEVAL'] - (1 * $this->calculation_values['ODE'] / ($this->calculation_values['HI1'] * $this->calculation_values['IDE'])) - $this->calculation_values['R1']);
        if ($this->calculation_values['VEL'] < $this->calculation_values['VEVA'])
        {
            $this->calculation_values['RE'] = $this->calculation_values['CHGLY_ROW12'] * $this->calculation_values['VEL'] * $this->calculation_values['IDE'] / $this->calculation_values['CHGLY_VIS12'];
        }
        else
        {
            $this->calculation_values['RE'] = $this->calculation_values['CHGLY_ROW12'] * $this->calculation_values['VEVA'] * $this->calculation_values['IDE'] / $this->calculation_values['CHGLY_VIS12'];
        }
        $this->calculation_values['PR'] = $this->calculation_values['CHGLY_VIS12'] * $this->calculation_values['CHGLY_SPHT12'] / $this->calculation_values['CHGLY_TCON12'];
        $this->calculation_values['NU1'] = 0.023 * pow($this->calculation_values['RE'], 0.8) * pow($this->calculation_values['PR'], 0.3);
        $this->calculation_values['HI'] = ($this->calculation_values['NU1'] * $this->calculation_values['CHGLY_TCON12'] / $this->calculation_values['IDE']) / 4187 * 3600;
        if ($this->calculation_values['TU2'] == 3 || $this->calculation_values['TU2'] == 6 || $this->calculation_values['TU2'] == 7 || $this->calculation_values['TU2'] == 9 )
        {
            $this->calculation_values['HI'] = $this->calculation_values['HI'] * 2;
        }
        $this->calculation_values['KEVAL'] = 1 / ((1 * $this->calculation_values['ODE'] / ($this->calculation_values['HI'] * $this->calculation_values['IDE'])) + $this->calculation_values['R1'] + 1 / $this->calculation_values['HO']);
        if ($this->calculation_values['CHGLY'] != 0)
        {
            $this->calculation_values['KEVAL'] = $this->calculation_values['KEVAL'] * 0.99;
        }
    }

    public function DERATE_KABS()
    {
        $vam_base = new VamBaseController();
        
        $this->calculation_values['GLY_VIS'] = $vam_base->PG_VISCOSITY($this->calculation_values['TCW11'], 0) / 1000;
        $this->calculation_values['GLY_TCON'] = $vam_base->PG_THERMAL_CONDUCTIVITY($this->calculation_values['TCW11'], 0);
        $this->calculation_values['GLY_ROW'] = $vam_base->PG_ROW($this->calculation_values['TCW11'], 0);
        $this->calculation_values['GLY_SPHT'] = $vam_base->PG_SPHT($this->calculation_values['TCW11'], 0) * 1000;

        if ($this->calculation_values['MODEL'] < 700)
        {
            $this->calculation_values['VABS'] = 1.5;
        }
        else
        {
            $this->calculation_values['VABS'] = 1.58;
        }
        $this->calculation_values['RE'] = $this->calculation_values['GLY_ROW'] * $this->calculation_values['VABS'] * $this->calculation_values['IDA'] / $this->calculation_values['GLY_VIS'];
        $this->calculation_values['PR'] = $this->calculation_values['GLY_VIS'] * $this->calculation_values['GLY_SPHT'] / $this->calculation_values['GLY_TCON'];
        $this->calculation_values['NU1'] = 0.023 * pow($this->calculation_values['RE'], 0.8) * pow($this->calculation_values['PR'], 0.4);
        $this->calculation_values['HI1'] = ($this->calculation_values['NU1'] * $this->calculation_values['GLY_TCON'] / $this->calculation_values['IDA']) * 3600 / 4187;

        if ($this->calculation_values['TU5'] == 0 || $this->calculation_values['TU5'] == 2)
            $this->calculation_values['R1'] = log($this->calculation_values['ODA'] / $this->calculation_values['IDA']) * $this->calculation_values['ODA'] / (2 * 340);
        if ($this->calculation_values['TU5'] == 1)
            $this->calculation_values['R1'] = log($this->calculation_values['ODA'] / $this->calculation_values['IDA']) * $this->calculation_values['ODA'] / (2 * 37);
        if ($this->calculation_values['TU5'] == 6)
            $this->calculation_values['R1'] = log($this->calculation_values['ODA'] / $this->calculation_values['IDA']) * $this->calculation_values['ODA'] / (2 * 21);
        if ($this->calculation_values['TU5'] == 7 || $this->calculation_values['TU5'] == 5)
            $this->calculation_values['R1'] = log($this->calculation_values['ODA'] / $this->calculation_values['IDA']) * $this->calculation_values['ODA'] / (2 * 15);

        $this->calculation_values['HO'] = 1 / (1 / $this->calculation_values['KABS'] - (1 * $this->calculation_values['ODA'] / ($this->calculation_values['HI1'] * $this->calculation_values['IDA'])) - $this->calculation_values['R1']);
        if ($this->calculation_values['VA'] < $this->calculation_values['VABS'])
        {
            $this->calculation_values['RE'] = $this->calculation_values['COGLY_ROW1'] * $this->calculation_values['VA'] * $this->calculation_values['IDA'] / $this->calculation_values['COGLY_VIS1'];
        }
        else
        {
            $this->calculation_values['RE'] = $this->calculation_values['COGLY_ROW1'] * $this->calculation_values['VABS'] * $this->calculation_values['IDA'] / $this->calculation_values['COGLY_VIS1'];
        }
        $this->calculation_values['PR'] = $this->calculation_values['COGLY_VIS1'] * $this->calculation_values['COGLY_SPHT11'] / $this->calculation_values['COGLY_TCON1'];
        $this->calculation_values['NU1'] = 0.023 * pow($this->calculation_values['RE'], 0.8) * pow($this->calculation_values['PR'], 0.4);
        $this->calculation_values['HI'] = ($this->calculation_values['NU1'] * $this->calculation_values['COGLY_TCON1'] / $this->calculation_values['IDA']) / 4187 * 3600;
        $this->calculation_values['KABS'] = 1 / ((1 * $this->calculation_values['ODA'] / ($this->calculation_values['HI'] * $this->calculation_values['IDA'])) + $this->calculation_values['R1'] + 1 / $this->calculation_values['HO']);
        if ($this->calculation_values['COGLY'] != 0)
        {
            $this->calculation_values['KABS'] = $this->calculation_values['KABS'] * 0.99;
        }
    }

    public function DERATE_KCON()
    {
        $vam_base = new VamBaseController();

        $this->calculation_values['GLY_VIS'] = $vam_base->PG_VISCOSITY($this->calculation_values['TCW11'], 0) / 1000;
        $this->calculation_values['GLY_TCON'] = $vam_base->PG_THERMAL_CONDUCTIVITY($this->calculation_values['TCW11'], 0);
        $this->calculation_values['GLY_ROW'] = $vam_base->PG_ROW($this->calculation_values['TCW11'], 0);
        $this->calculation_values['GLY_SPHT'] = $vam_base->PG_SPHT($this->calculation_values['TCW11'], 0) * 1000;

        if ($this->calculation_values['MODEL'] < 700)
        {
            $this->calculation_values['VCON'] = 1.5;
        }
        else
        {
            $this->calculation_values['VCON'] = 1.5;
        }
        $this->calculation_values['RE'] = $this->calculation_values['GLY_ROW'] * $this->calculation_values['VCON'] * $this->calculation_values['IDC'] / $this->calculation_values['GLY_VIS'];
        $this->calculation_values['PR'] = $this->calculation_values['GLY_VIS'] * $this->calculation_values['GLY_SPHT'] / $this->calculation_values['GLY_TCON'];
        $this->calculation_values['NU1'] = 0.023 * pow($this->calculation_values['RE'], 0.8) * pow($this->calculation_values['PR'], 0.4);
        $this->calculation_values['HI1'] = ($this->calculation_values['NU1'] * $this->calculation_values['GLY_TCON'] / $this->calculation_values['IDC']) * 3600 / 4187;

        if ($this->calculation_values['TV5'] == 0 || $this->calculation_values['TV5'] == 2 || $this->calculation_values['TV5'] == 3)
            $this->calculation_values['R1'] = log($this->calculation_values['ODC'] / $this->calculation_values['IDC']) * $this->calculation_values['ODC'] / (2 * 340);
        if ($this->calculation_values['TV5'] == 1)
            $this->calculation_values['R1'] = log($this->calculation_values['ODC'] / $this->calculation_values['IDC']) * $this->calculation_values['ODC'] / (2 * 37);
        if ($this->calculation_values['TV5'] == 3)
            $this->calculation_values['R1'] = log($this->calculation_values['ODC'] / $this->calculation_values['IDC']) * $this->calculation_values['ODC'] / (2 * 21);
        if ($this->calculation_values['TV5'] == 5 || $this->calculation_values['TV5'] == 4)
            $this->calculation_values['R1'] = log($this->calculation_values['ODC'] / $this->calculation_values['IDC']) * $this->calculation_values['ODC'] / (2 * 15);

        $this->calculation_values['HO'] = 1 / (1 / $this->calculation_values['KCON'] - (1 * $this->calculation_values['ODC'] / ($this->calculation_values['HI1'] * $this->calculation_values['IDC'])) - $this->calculation_values['R1']);
        if ($this->calculation_values['VC'] < $this->calculation_values['VCON'])
        {
            $this->calculation_values['RE'] = $this->calculation_values['COGLY_ROW1'] * $this->calculation_values['VC'] * $this->calculation_values['IDC'] / $this->calculation_values['COGLY_VIS1'];
        }
        else
        {
            $this->calculation_values['RE'] = $this->calculation_values['COGLY_ROW1'] * $this->calculation_values['VCON'] * $this->calculation_values['IDC'] / $this->calculation_values['COGLY_VIS1'];
        }
        $this->calculation_values['PR'] = $this->calculation_values['COGLY_VIS1'] * $this->calculation_values['COGLY_SPHT11'] / $this->calculation_values['COGLY_TCON1'];
        $this->calculation_values['NU1'] = 0.023 * pow($this->calculation_values['RE'], 0.8) * pow($this->calculation_values['PR'], 0.4);
        $this->calculation_values['HI'] = ($this->calculation_values['NU1'] * $this->calculation_values['COGLY_TCON1'] / $this->calculation_values['IDC']) / 4187 * 3600;
        $this->calculation_values['KCON'] = 1 / ((1 * $this->calculation_values['ODC'] / ($this->calculation_values['HI'] * $this->calculation_values['IDC'])) + $this->calculation_values['R1'] + 1 / $this->calculation_values['HO']);
        if ($this->calculation_values['COGLY'] != 0)
        {
            $this->calculation_values['KCON'] = $this->calculation_values['KCON'] * 0.99;
        }
    }        

    public function HWVELOCITY()
    {
        $vam_base = new VamBaseController();

        $this->calculation_values['HWI'] = 0;
        // $this->calculation_values['THW4'] = $this->calculation_values['THW3'];
        // if ($this->calculation_values['GL'] == 2)
        // {
        //     $this->calculation_values['HWGLY_VIS4'] = $vam_base->EG_VISCOSITY($this->calculation_values['THW4'], $this->calculation_values['HWGLY']) / 1000;
        //     $this->calculation_values['HWGLY_TCON4'] = $vam_base->EG_THERMAL_CONDUCTIVITY($this->calculation_values['THW4'], $this->calculation_values['HWGLY']);
        //     $this->calculation_values['HWGLY_ROW4'] = $vam_base->EG_ROW($this->calculation_values['THW4'], $this->calculation_values['HWGLY']);
        //     $this->calculation_values['HWGLY_SPHT4'] = $vam_base->EG_SPHT($this->calculation_values['THW4'], $this->calculation_values['HWGLY']) * 1000;
        //     $this->calculation_values['HWGLY_SPHT1'] = $vam_base->EG_SPHT($this->calculation_values['THW1'], $this->calculation_values['HWGLY']) * 1000;
        //     $this->calculation_values['HWGLY_TCON1'] = $vam_base->EG_THERMAL_CONDUCTIVITY($this->calculation_values['THW1'], $this->calculation_values['HWGLY']);
        //     $this->calculation_values['HWGLY_VIS1'] = $vam_base->EG_VISCOSITY($this->calculation_values['THW1'], $this->calculation_values['HWGLY']) / 1000;
        // }
        // else
        // {
        //     $this->calculation_values['HWGLY_VIS4'] = $vam_base->PG_VISCOSITY($this->calculation_values['THW4'], $this->calculation_values['HWGLY']) / 1000;
        //     $this->calculation_values['HWGLY_TCON4'] = $vam_base->PG_THERMAL_CONDUCTIVITY($this->calculation_values['THW4'], $this->calculation_values['HWGLY']);
        //     $this->calculation_values['HWGLY_ROW4'] = $vam_base->PG_ROW($this->calculation_values['THW4'], $this->calculation_values['HWGLY']);
        //     $this->calculation_values['HWGLY_SPHT4'] = $vam_base->PG_SPHT($this->calculation_values['THW4'], $this->calculation_values['HWGLY']) * 1000;
        //     $this->calculation_values['HWGLY_SPHT1'] = $vam_base->PG_SPHT($this->calculation_values['THW1'], $this->calculation_values['HWGLY']) * 1000;
        //     $this->calculation_values['HWGLY_TCON1'] = $vam_base->PG_THERMAL_CONDUCTIVITY($this->calculation_values['THW1'], $this->calculation_values['HWGLY']);
        //     $this->calculation_values['HWGLY_VIS1'] = $vam_base->PG_VISCOSITY($this->calculation_values['THW1'], $this->calculation_values['HWGLY']) / 1000;
        // }


        $this->calculation_values['TGP'] = 1;

        do
        {
            $this->calculation_values['VG'] = $this->calculation_values['GHOT'] / 3600 / (3.142 / 4 * $this->calculation_values['IDG'] * $this->calculation_values['IDG'] * $this->calculation_values['TNG1'] / $this->calculation_values['TGP']);
            if ($this->calculation_values['VG'] < 1.25 && $this->calculation_values['TGP'] < 4)
            {
                $this->calculation_values['TGP']++;
            }
            else
            {
                break;
            }
        } while ($this->calculation_values['VG'] < 1.25);

        if ($this->calculation_values['VG'] < 1.25 && $this->calculation_values['MODEL'] > 340)
        {
            $this->calculation_values['TGP'] = 6;
            $this->calculation_values['VG'] = $this->calculation_values['GHOT'] / 3600 / (3.142 / 4 * $this->calculation_values['IDG'] * $this->calculation_values['IDG'] * $this->calculation_values['TNG1'] / $this->calculation_values['TGP']);
        }

        if ($this->calculation_values['VG'] > 2.78 && $this->calculation_values['TGP'] != 1)
        {
            $this->calculation_values['TGP'] = 1;
            $this->calculation_values['VG'] = $this->calculation_values['GHOT'] / 3600 / (3.142 / 4 * $this->calculation_values['IDG'] * $this->calculation_values['IDG'] * $this->calculation_values['TNG1'] / $this->calculation_values['TGP']);
        }


        // do
        // {
        //     // PR_DROP_DATA();
        //     $this->PIPE_SIZE();
        //     $this->PR_DROP_HW();

        //     if ($this->calculation_values['PDG'] > 10 && $this->calculation_values['TGP'] != 1)
        //     {
        //         if ($this->calculation_values['TGP'] == 6)
        //         {
        //             $this->calculation_values['TGP'] = $this->calculation_values['TGP'] - 2;
        //             $this->calculation_values['VG'] = $this->calculation_values['GHOT'] / 3600 / (3.142 / 4 * $this->calculation_values['IDG'] * $this->calculation_values['IDG'] * $this->calculation_values['TNG1'] / $this->calculation_values['TGP']);
        //         }
        //         else
        //         {
        //             $this->calculation_values['TGP'] = $this->calculation_values['TGP'] - 1;
        //             $this->calculation_values['VG'] = $this->calculation_values['GHOT'] / 3600 / (3.142 / 4 * $this->calculation_values['IDG'] * $this->calculation_values['IDG'] * $this->calculation_values['TNG1'] / $this->calculation_values['TGP']);
        //         }
        //     }
        //     else if ($this->calculation_values['PDG'] > 10 && $this->calculation_values['TGP'] == 1)
        //     {
        //         break;
        //     }
        // } while ($this->calculation_values['PDG'] > 10);
    }

    public function PR_DROP_HW()
    {

        $vam_base = new VamBaseController();

        $this->calculation_values['VPG'] = ($this->calculation_values['GHOT'] * 4) / (3.14153 * $this->calculation_values['PIDG'] * $this->calculation_values['PIDG'] * 3600);              //PIPE VELOCITY
        $this->calculation_values['TMG'] = ($this->calculation_values['THW1'] + $this->calculation_values['THW4']) / 2.0;

        if ($this->calculation_values['GL'] == 3)
        {
            $this->calculation_values['VISG'] = $vam_base->PG_VISCOSITY($this->calculation_values['TMG'], $this->calculation_values['HWGLY']) / 1000;
            $this->calculation_values['GLY_ROW'] = $vam_base->PG_ROW($this->calculation_values['TMG'], $this->calculation_values['HWGLY']);
        }
        else
        {
            $this->calculation_values['VISG'] = $vam_base->EG_VISCOSITY($this->calculation_values['TMG'], $this->calculation_values['HWGLY']) / 1000;
            $this->calculation_values['GLY_ROW'] = $vam_base->EG_ROW($this->calculation_values['TMG'], $this->calculation_values['HWGLY']);
        }

        $this->calculation_values['REPG'] = ($this->calculation_values['PIDG'] * $this->calculation_values['VPG'] * $this->calculation_values['GLY_ROW']) / $this->calculation_values['VISG'];                       //REYNOLDS NO IN PIPE

        $this->calculation_values['FFH'] = 1.325 / pow(log((0.0457 / (3.7 * $this->calculation_values['PIDG'] * 1000)) + (5.74 / pow($this->calculation_values['REPG'], 0.9))), 2);

        $this->calculation_values['FGP1'] = (($this->calculation_values['GSL1'] + $this->calculation_values['GSL2']) * $this->calculation_values['FFH'] / $this->calculation_values['PIDG']) * ($this->calculation_values['VPG'] * $this->calculation_values['VPG'] / (2 * 9.81));
        $this->calculation_values['FGP2'] = (($this->calculation_values['VPG'] * $this->calculation_values['VPG'] / (2 * 9.81)) + (0.5 * $this->calculation_values['VPG'] * $this->calculation_values['VPG'] / (2 * 9.81)));

        $this->calculation_values['FGP'] = $this->calculation_values['FGP1'] + $this->calculation_values['FGP2'];                                      //FR LOSS IN PIPES

        $this->calculation_values['RE'] = ($this->calculation_values['GLY_ROW'] * $this->calculation_values['VG'] * $this->calculation_values['IDG']) / $this->calculation_values['VISG'];

        $this->calculation_values['F'] = $this->calculation_values['F1'] = 0.0014 + (0.137 / pow($this->calculation_values['RE'], 0.32));  //Friction Factor


        $this->calculation_values['FG1'] = 2 * $this->calculation_values['F'] * $this->calculation_values['LE'] * $this->calculation_values['VG'] * $this->calculation_values['VG'] / (9.81 * $this->calculation_values['IDG']);
        $this->calculation_values['FG2'] = $this->calculation_values['VG'] * $this->calculation_values['VG'] / (4 * 9.81);
        $this->calculation_values['FG3'] = $this->calculation_values['VG'] * $this->calculation_values['VG'] / (2 * 9.81);
        $this->calculation_values['FG5'] = ($this->calculation_values['FG1'] + $this->calculation_values['FG2'] + $this->calculation_values['FG3']) * ($this->calculation_values['TGP'] + $this->calculation_values['TGP']);                      //FR LOSS IN TUBES

        $this->calculation_values['GFL'] = $this->calculation_values['FGP'] + $this->calculation_values['FG5'];                                        //TOTAL FRICTION LOSS IN HW
        $this->calculation_values['PDG'] = $this->calculation_values['GFL'] + $this->calculation_values['SHG'];

    } 

    public function DERATE_GEN()
    {
       
        $this->calculation_values['UGENH'] = $this->calculation_values['UGEN'];

        if ($this->calculation_values['VG'] < 1.25)
        {
            // if ($this->calculation_values['TG2'] == 1 || $this->calculation_values['TG2'] == 0)
            // {
                if ($this->calculation_values['AVGT'] <= 80)
                {
                    $this->calculation_values['FACT1'] = ((5000 / 11) * $this->calculation_values['VG'] + (5300 / 11)) / 1050;
                }
                else if ($this->calculation_values['AVGT'] > 80)
                {
                    $this->calculation_values['FACT1'] = ((5000 / 11) * $this->calculation_values['VG'] + (5850 / 11)) / 1100;
                }
            // }
            // else if ($this->calculation_values['TG2'] == 2)
            // {
            //     if ($this->calculation_values['AVGT'] <= 80)
            //     {
            //         $this->calculation_values['FACT1'] = ((5000 / 11) * $this->calculation_values['VG'] + (5300 / 11)) * 0.95 / 1050;
            //     }
            //     else if ($this->calculation_values['AVGT'] > 80)
            //     {
            //         $this->calculation_values['FACT1'] = ((5000 / 11) * $this->calculation_values['VG'] + (5850 / 11)) * 0.95 / 1100;
            //     }
            // }
        }
        else
        {
            $this->calculation_values['FACT1'] = 1;
        }

        if ($this->calculation_values['HWGLY'] > 0)
        {
            if ($this->calculation_values['GL'] == 2)
            {
                $this->calculation_values['FACT2'] = 1 - 0.0024 * $this->calculation_values['HWGLY'];
            }
            else if ($this->calculation_values['GL'] == 3)
            {
                $this->calculation_values['FACT2'] = 1 - 0.0028 * $this->calculation_values['HWGLY'];
            }
        }
        else
        {
            $this->calculation_values['FACT2'] = 1;
        }

        $this->calculation_values['UGENH'] = $this->calculation_values['UGENH'] * $this->calculation_values['FACT1'] * $this->calculation_values['FACT2'];

        if ($this->calculation_values['UGENH'] < 750)
        {
            $this->calculation_values['UGENH'] = 750;
        }

        if ($this->calculation_values['THW1'] > 105 || $this->calculation_values['TG2'] == 2)
        {
            $this->calculation_values['UGENH'] = $this->calculation_values['UGENH'] * 0.95;   //CUNI or SS Metallurgy above 105 DEG C
        }

        $this->calculation_values['UGENH'] = 1 / ((1 / $this->calculation_values['UGENH']) + $this->calculation_values['FFHOW1']);
                    
        $this->calculation_values['UGENL'] = $this->calculation_values['UGENH'];
    } 


    public function EVAPORATOR()
    {
        $ferr1 = array();
        $tchw2h = array();

        $this->calculation_values['b'] = 0;
        $this->calculation_values['c'] = 0;
        $this->calculation_values['d'] = 0;
        $this->calculation_values['f'] = 0;
        $this->calculation_values['g'] = 0;
        $this->calculation_values['h'] = 0;
        $this->calculation_values['i'] = 0;
        $this->calculation_values['k'] = 0;
        $this->calculation_values['l'] = 0;
        $this->calculation_values['m'] = 0;
        $this->calculation_values['n'] = 0;
        $this->calculation_values['o'] = 0;
        $this->calculation_values['p'] = 0;
        $this->calculation_values['q'] = 0;
        $this->calculation_values['u'] = 0;
        $this->calculation_values['s'] = 0;
        $this->calculation_values['v'] = 0;

        if ($this->calculation_values['TCHW2L'] < 3.5)
        {
            $this->calculation_values['SFACTOR'] = 1 - ((3.5 - $this->calculation_values['TCHW2L']) * 1.0 / 100);
            $this->calculation_values['GHW'] = $this->calculation_values['GHOT'] * $this->calculation_values['SFACTOR'];
        }
        else
        {
            $this->calculation_values['GHW'] = $this->calculation_values['GHOT'];
        }

        $this->calculation_values['QEVA'] = $this->calculation_values['TON'] * 3024;
        $this->calculation_values['GCHW'] = ($this->calculation_values['QEVA'] * 4187) / (($this->calculation_values['TCHW11'] - $this->calculation_values['TCHW12']) * $this->calculation_values['CHGLY_ROW12'] * $this->calculation_values['CHGLY_SPHT12']);
        $this->calculation_values['LMTDEVA'] = ($this->calculation_values['TON'] * 3024) / ($this->calculation_values['AEVA'] * $this->calculation_values['UEVA']);
        $this->calculation_values['T1'] = $this->calculation_values['TCHW2L'] - ($this->calculation_values['TCHW1H'] - $this->calculation_values['TCHW2L']) / (exp(($this->calculation_values['TCHW1H'] - $this->calculation_values['TCHW2L']) / $this->calculation_values['LMTDEVA']) - 1);

        $this->calculation_values['GDIL'] = 50 * $this->calculation_values['MODEL'];          //55
        $this->calculation_values['QCO'] = $this->calculation_values['QEVA'] * (1 + 1 / 0.7) * 0.3;
        $this->calculation_values['QAB'] = $this->calculation_values['QEVA'] * (1 + 1 / 0.7) * 0.7;

        $this->calculation_values['CW'] = 1;                         // 1 is for Absorber entry, 2 is for condenser entry          

        if ($this->calculation_values['CW'] == 2)
        {
            $this->calculation_values['TCW3'] = $this->calculation_values['TCW11'];
            $this->calculation_values['ATCW3'] = $this->calculation_values['TCW11'] + $this->calculation_values['QCO'] / ($this->calculation_values['GCW'] * 1000);
            $this->calculation_values['ATCW2'] = $this->calculation_values['ATCW3'] + $this->calculation_values['QAB'] / ($this->calculation_values['GCW'] * 1000);
            $this->calculation_values['ATCW1H'] = ($this->calculation_values['TCW11'] + $this->calculation_values['ATCW3']) / 2;
        }
        if ($this->calculation_values['CW'] == 1)
        {
            $this->calculation_values['ATCW2H'] = $this->calculation_values['TCW11'] + ($this->calculation_values['QAB'] * 0.5) / ($this->calculation_values['GCW'] * 1000);
            $this->calculation_values['ATCW2L'] = $this->calculation_values['TCW11'] + ($this->calculation_values['QAB'] * 0.5) / ($this->calculation_values['GCW'] * 1000);
            $this->calculation_values['TCWC1L'] = ($this->calculation_values['ATCW2H'] + $this->calculation_values['ATCW2L']) / 2;
            $this->calculation_values['TCW3'] = $this->calculation_values['TCWC1L'];
            $this->calculation_values['ATCW3'] = $this->calculation_values['TCW3'] + $this->calculation_values['QCO'] / ($this->calculation_values['GCW'] * 1000);
            $this->calculation_values['ATCW1H'] = ($this->calculation_values['TCW3'] + $this->calculation_values['ATCW3']) / 2;
            $this->calculation_values['LMTDCO'] = $this->calculation_values['QCO'] / (($this->calculation_values['ACONH'] + $this->calculation_values['ACONL']) * $this->calculation_values['UCON']);
            $this->calculation_values['T3'] = $this->calculation_values['ATCW3'] + ($this->calculation_values['ATCW3'] - $this->calculation_values['TCWC1L']) / (exp(($this->calculation_values['ATCW3'] - $this->calculation_values['TCWC1L']) / $this->calculation_values['LMTDCO']) - 1);
        }

        $this->calculation_values['GCWAH'] = $this->calculation_values['GCW'] / 2;        //COW parallel in Absorber
        $this->calculation_values['GCWAL'] = $this->calculation_values['GCWAH'];
        
        $vam_base = new VamBaseController();
        $this->calculation_values['COGLY_SPHT3'] = $vam_base->PG_SPHT($this->calculation_values['TCW3'], 0);
        $this->calculation_values['COGLY_SPHT1H'] = $vam_base->PG_SPHT($this->calculation_values['ATCW1H'], 0);
        $this->calculation_values['COGLY_SPHT4'] = $vam_base->PG_SPHT($this->calculation_values['ATCW3'], 0);            

        $this->calculation_values['QCONH'] = $this->calculation_values['GCW'] * $this->calculation_values['COGLY_ROW1'] * (($this->calculation_values['ATCW3'] * $this->calculation_values['COGLY_SPHT4']) - ($this->calculation_values['ATCW1H'] * $this->calculation_values['COGLY_SPHT1H'])) / 4.187;
        $this->calculation_values['LMTDCONH'] = $this->calculation_values['QCONH'] / ($this->calculation_values['UCONH'] * $this->calculation_values['ACONH']);
        $this->calculation_values['AT3H'] = $this->calculation_values['ATCW3'] + ($this->calculation_values['ATCW3'] - $this->calculation_values['ATCW1H']) / (exp(($this->calculation_values['ATCW3'] - $this->calculation_values['ATCW1H']) / $this->calculation_values['LMTDCONH']) - 1);

        $this->calculation_values['QCONL'] = $this->calculation_values['GCW'] * $this->calculation_values['COGLY_ROW1'] * (($this->calculation_values['ATCW1H'] * $this->calculation_values['COGLY_SPHT1H']) - ($this->calculation_values['TCW3'] * $this->calculation_values['COGLY_SPHT3'])) / 4.187;
        $this->calculation_values['LMTDCONL'] = $this->calculation_values['QCONL'] / ($this->calculation_values['UCONL'] * $this->calculation_values['ACONL']);
        $this->calculation_values['AT3L'] = $this->calculation_values['ATCW1H'] + ($this->calculation_values['ATCW1H'] - $this->calculation_values['TCW3']) / (exp(($this->calculation_values['ATCW1H'] - $this->calculation_values['TCW3']) / $this->calculation_values['LMTDCONL']) - 1);


        $DT = $this->calculation_values['TCHW11'] - $this->calculation_values['TCHW12'];

        if ($this->calculation_values['TCW11'] < 34.01)
        {
            if (((($this->calculation_values['TON'] / $this->calculation_values['MODEL']) > 0.8 && ($this->calculation_values['TON'] / $this->calculation_values['MODEL']) < 1.01) || ($this->calculation_values['TON'] / $this->calculation_values['MODEL']) < 0.66) && $DT <= 13)
            {
                $this->calculation_values['ATCHW2H'] = ($this->calculation_values['TCHW11'] + $this->calculation_values['TCHW12']) / 2;
            }
            else
            {
                $this->calculation_values['ATCHW2H'] = (($this->calculation_values['TCHW11'] + $this->calculation_values['TCHW12']) / 2) + ((-0.0082 * $DT * $DT) + (0.0973 * $DT) - 0.2802);
            }
        }
        else
        {
            $this->calculation_values['ATCHW2H'] = (($this->calculation_values['TCHW11'] + $this->calculation_values['TCHW12']) / 2) + ((-0.0047 * $DT * $DT) - (0.0849 * $DT) + 0.0412);
        }

        $ferr1[0] = 1;
        $this->calculation_values['p'] = 1;
        while (abs($ferr1[$this->calculation_values['p'] - 1]) > 0.1)
        {
            if ($this->calculation_values['p'] == 1)
            {
                if ($DT > 14)
                {
                    $tchw2h[$this->calculation_values['p']] = $this->calculation_values['ATCHW2H'];
                }
                else
                {
                    $tchw2h[$this->calculation_values['p']] = $this->calculation_values['ATCHW2H'] + 0.1;
                }
            }
            if ($this->calculation_values['p'] == 2)
            {
                $tchw2h[$this->calculation_values['p']] = $tchw2h[$this->calculation_values['p'] - 1] - 0.01;
            }
            if ($this->calculation_values['p'] >= 3)
            {
                if (($this->calculation_values['TON'] / $this->calculation_values['MODEL']) < 0.5)
                {
                    $tchw2h[$this->calculation_values['p']] = $tchw2h[$this->calculation_values['p'] - 1] + $ferr1[$this->calculation_values['p'] - 1] * ($tchw2h[$this->calculation_values['p'] - 1] - $tchw2h[$this->calculation_values['p'] - 2]) / ($ferr1[$this->calculation_values['p'] - 2] - $ferr1[$this->calculation_values['p'] - 1]) / 4;
                }
                else
                {
                    $tchw2h[$this->calculation_values['p']] = $tchw2h[$this->calculation_values['p'] - 1] + $ferr1[$this->calculation_values['p'] - 1] * ($tchw2h[$this->calculation_values['p'] - 1] - $tchw2h[$this->calculation_values['p'] - 2]) / ($ferr1[$this->calculation_values['p'] - 2] - $ferr1[$this->calculation_values['p'] - 1]);
                }
            }
            $this->calculation_values['TCHW2H'] = $tchw2h[$this->calculation_values['p']];
            $this->calculation_values['TCHW1L'] = $this->calculation_values['TCHW2H'];

            if ($this->calculation_values['GL'] == 2)
            {
                $this->calculation_values['CHGLY_SPHT2H'] = $vam_base->EG_SPHT($this->calculation_values['TCHW2H'], $this->calculation_values['CHGLY']) * 1000;
            }
            else
            {
                $this->calculation_values['CHGLY_SPHT2H'] = $vam_base->PG_SPHT($this->calculation_values['TCHW2H'], $this->calculation_values['CHGLY']) * 1000;
            }

            $this->calculation_values['QEVAL'] = $this->calculation_values['GCHW'] * $this->calculation_values['CHGLY_ROW12'] * 0.5 * ($this->calculation_values['CHGLY_SPHT12'] + $this->calculation_values['CHGLY_SPHT2H']) * ($this->calculation_values['TCHW1L'] - $this->calculation_values['TCHW12']) / 4187;
            $this->calculation_values['LMTDEVAL'] = $this->calculation_values['QEVAL'] / ($this->calculation_values['UEVAL'] * $this->calculation_values['AEVAL']);
            $this->calculation_values['T1L'] = $this->calculation_values['TCHW12'] + ($this->calculation_values['TCHW12'] - $this->calculation_values['TCHW1L']) / (exp(($this->calculation_values['TCHW1L'] - $this->calculation_values['TCHW12']) / $this->calculation_values['LMTDEVAL']) - 1);
            $this->calculation_values['P1L'] = $vam_base->LIBR_PRESSURE($this->calculation_values['T1L'], 0);
            $this->calculation_values['J1L'] = $vam_base->WATER_VAPOUR_ENTHALPY($this->calculation_values['T1L'], $this->calculation_values['P1L']);
            $this->calculation_values['I1L'] = $this->calculation_values['T1L'] + 100;

            $this->calculation_values['QEVAH'] = $this->calculation_values['GCHW'] * $this->calculation_values['CHGLY_ROW12'] * 0.5 * ($this->calculation_values['CHGLY_SPHT11'] + $this->calculation_values['CHGLY_SPHT2H']) * ($this->calculation_values['TCHW11'] - $this->calculation_values['TCHW2H']) / 4187;
            $this->calculation_values['LMTDEVAH'] = $this->calculation_values['QEVAH'] / ($this->calculation_values['UEVAH'] * $this->calculation_values['AEVAH']);
            $this->calculation_values['T1H'] = $this->calculation_values['TCHW2H'] + ($this->calculation_values['TCHW2H'] - $this->calculation_values['TCHW11']) / (exp(($this->calculation_values['TCHW11'] - $this->calculation_values['TCHW2H']) / $this->calculation_values['LMTDEVAH']) - 1);
            $this->calculation_values['P1H'] = $vam_base->LIBR_PRESSURE($this->calculation_values['T1H'], 0);
            $this->calculation_values['J1H'] = $vam_base->WATER_VAPOUR_ENTHALPY($this->calculation_values['T1H'], $this->calculation_values['P1H']);
            $this->calculation_values['I1H'] = $this->calculation_values['T1H'] + 100;

            $this->ABSORBER();
            $this->calculation_values['QABSH'] = $this->calculation_values['GCONCH'] * $this->calculation_values['I2L'] + $this->calculation_values['GREFH'] * $this->calculation_values['J1H'] - $this->calculation_values['GDIL'] * $this->calculation_values['I2'];                
            $ferr1[$this->calculation_values['p']] = ($this->calculation_values['QABSH'] - $this->calculation_values['QLMTDABSH']) * 100 / $this->calculation_values['QLMTDABSH'];
            $this->calculation_values['p']++;
        }

    }

    public function ABSORBER()
    {
        $ferr2 = array();
        $t2 = array();
        $vam_base = new VamBaseController();

        $ferr2[0] = 1;
        if ($this->calculation_values['CW'] == 2)
        {
            if ($this->calculation_values['q'] == 0)
            {
                $this->calculation_values['T2'] = $this->calculation_values['ATCW3'] + 7;
            }
            else
            {
                $this->calculation_values['T2'] = $this->calculation_values['T2'] + 3.5;
            }
        }
        if ($this->calculation_values['CW'] == 1)
        {
            if ($this->calculation_values['q'] == 0)
            {
                $this->calculation_values['T2'] = $this->calculation_values['TCW11'] + 5;
            }
            else
            {
                $this->calculation_values['T2'] = $this->calculation_values['TCW11'] + 5;
            }
        }

        $this->calculation_values['q'] = 1;
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
                $t2[$this->calculation_values['q']] = $t2[$this->calculation_values['q'] - 1] + $ferr2[$this->calculation_values['q'] - 1] * ($t2[$this->calculation_values['q'] - 1] - $t2[$this->calculation_values['q'] - 2]) / ($ferr2[$this->calculation_values['q'] - 2] - $ferr2[$this->calculation_values['q'] - 1]) / 2;
            }
            $this->calculation_values['T2'] = $t2[$this->calculation_values['q']];
            $this->calculation_values['XDIL'] = $vam_base->LIBR_CONC($this->calculation_values['T2'], $this->calculation_values['P1H']);
            $this->calculation_values['I2'] = $vam_base->LIBR_ENTHALPY($this->calculation_values['T2'], $this->calculation_values['XDIL']);

            //GENERATOR()
            $this->CONDENSERL();
            $this->calculation_values['QABSL'] = $this->calculation_values['GCONC'] * $this->calculation_values['I8'] + $this->calculation_values['GREFL'] * $this->calculation_values['J1L'] - $this->calculation_values['GDILL'] * $this->calculation_values['I2L'];
            $ferr2[$this->calculation_values['q']] = ($this->calculation_values['QABSL'] - $this->calculation_values['QLMTDABSL']) * 100 / $this->calculation_values['QLMTDABSL'];
            $this->calculation_values['q']++;
        }
    }

    public function CONDENSERL()
    {
        $t3l = array();
        $ferr13 = array();
        $vam_base = new VamBaseController();

        if ($this->calculation_values['l'] != 0)
        {
            $this->calculation_values['AT3L'] = $this->calculation_values['T3L'] - 2; // 0.8;
        }
        $ferr13[0] = 1;
        $this->calculation_values['l'] = 1;
        while (abs($ferr13[$this->calculation_values['l'] - 1]) > 0.1)
        {
            if ($this->calculation_values['l'] == 1)
            {
                $t3l[$this->calculation_values['l']] = $this->calculation_values['AT3L'] + 2;  // 0.5;
            }
            if ($this->calculation_values['l'] == 2)
            {
                $t3l[$this->calculation_values['l']] = $t3l[$this->calculation_values['l'] - 1] - 0.1;
            }
            if ($this->calculation_values['l'] >= 3)
            {
                $t3l[$this->calculation_values['l']] = $t3l[$this->calculation_values['l'] - 1] + $ferr13[$this->calculation_values['l'] - 1] * ($t3l[$this->calculation_values['l'] - 1] - $t3l[$this->calculation_values['l'] - 2]) / ($ferr13[$this->calculation_values['l'] - 2] - $ferr13[$this->calculation_values['l'] - 1]);
            }
            $this->calculation_values['T3L'] = $t3l[$this->calculation_values['l']];
            $this->calculation_values['P3L'] = $vam_base->LIBR_PRESSURE($this->calculation_values['T3L'], 0);
            $this->calculation_values['I3L'] = $this->calculation_values['T3L'] + 100;
            $this->calculation_values['T4'] = $vam_base->LIBR_TEMP($this->calculation_values['P3L'], $this->calculation_values['XDIL']);

            if ($this->calculation_values['CW'] == 2)
            {
                $this->calculation_values['TCW3A'] = $this->calculation_values['TCW11'];
                $this->CWCONLOUT();
            }


            $this->CONDENSERH();

            if ($this->calculation_values['CW'] == 2)
            {
                if ($this->calculation_values['CWC'] == 2)
                {
                    $this->calculation_values['TCW1H'] = $this->calculation_values['TCW1L'] = ($this->calculation_values['TCW4'] + $this->calculation_values['TCW4L']) / 2;
                    $this->CWABSHOUT();
                    $this->CWABSLOUT();
                }
                else
                {
                    $this->calculation_values['TCW1H'] = $this->calculation_values['TCW1L'] = $this->calculation_values['TCW4'];
                    $this->CWABSHOUT();
                    $this->CWABSLOUT();
                }
            }

            $this->LTHE();
            //GEN();
            $this->GENH();
            $this->GENL();

            //$this->calculation_values['QCONREFL'] = $this->calculation_values['GREF2'] * ($this->calculation_values['J9L'] - $this->calculation_values['I3L']);
            //ferr13[$this->calculation_values['l']] = ($this->calculation_values['QCONREFL'] - $this->calculation_values['QCONL']) * 100 / $this->calculation_values['QCONL'];

            $this->calculation_values['QGENH'] = ($this->calculation_values['GREF1'] * $this->calculation_values['J9']) + ($this->calculation_values['GCONC'] * $this->calculation_values['I9']) - ($this->calculation_values['GMED'] * $this->calculation_values['I9L']);
            $ferr13[$this->calculation_values['l']] = ($this->calculation_values['QGENH'] - $this->calculation_values['QLMTDGENH']) * 100 / $this->calculation_values['QLMTDGENH'];
            $this->calculation_values['l']++;
        }
    }

    public function CWCONLOUT()
    {
        $ferr14 = array();
        $vam_base = new VamBaseController();

        $ferr14[0] = 1;
        $this->calculation_values['v'] = 1;
        while (abs($ferr14[$this->calculation_values['v'] - 1]) > 0.01)
        {
            if ($this->calculation_values['v'] == 1)
            {
                $tcw4l[$this->calculation_values['v']] = $this->calculation_values['TCW3A'] + 0.1;
            }
            if ($this->calculation_values['v'] == 2)
            {
                $tcw4l[$this->calculation_values['v']] = $tcw4l[$this->calculation_values['v'] - 1] + 0.1;
            }
            if ($this->calculation_values['v'] >= 3)
            {
                $tcw4l[$this->calculation_values['v']] = $tcw4l[$this->calculation_values['v'] - 1] + $ferr14[$this->calculation_values['v'] - 1] * ($tcw4l[$this->calculation_values['v'] - 1] - $tcw4l[$this->calculation_values['v'] - 2]) / ($ferr14[$this->calculation_values['v'] - 2] - $ferr14[$this->calculation_values['v'] - 1]) / 5;
            }
            $this->calculation_values['TCW4L'] = $tcw4l[$this->calculation_values['v']];

            if ($this->calculation_values['GL'] == 2)
            {
                $this->calculation_values['COGLY_SPHT3'] = $vam_base->EG_SPHT($this->calculation_values['TCW3'], $this->calculation_values['COGLY']) * 1000;
                $this->calculation_values['COGLY_SPHT4'] = $vam_base->EG_SPHT($this->calculation_values['TCW4L'], $this->calculation_values['COGLY']) * 1000;
            }
            else
            {
                $this->calculation_values['COGLY_SPHT3'] = $vam_base->PG_SPHT($this->calculation_values['TCW3'], $this->calculation_values['COGLY']) * 1000;
                $this->calculation_values['COGLY_SPHT4'] = $vam_base->PG_SPHT($this->calculation_values['TCW4L'], $this->calculation_values['COGLY']) * 1000;
            }

            $this->calculation_values['LMTDCONL'] = (($this->calculation_values['T3L'] - $this->calculation_values['TCW3A']) - ($this->calculation_values['T3L'] - $this->calculation_values['TCW4L'])) / log(($this->calculation_values['T3L'] - $this->calculation_values['TCW3A']) / ($this->calculation_values['T3L'] - $this->calculation_values['TCW4L']));
            $this->calculation_values['QLMTDCONL'] = $this->calculation_values['UCONL'] * $this->calculation_values['ACONL'] * $this->calculation_values['LMTDCONL'];
            $this->calculation_values['QCONL'] = $this->calculation_values['GCWC'] * $this->calculation_values['COGLY_ROW1'] * (($this->calculation_values['TCW4L'] * $this->calculation_values['COGLY_SPHT4']) - ($this->calculation_values['TCW3A'] * $this->calculation_values['COGLY_SPHT3'])) / 4187;
            $ferr14[$this->calculation_values['v']] = ($this->calculation_values['QLMTDCONL'] - $this->calculation_values['QCONL']) * 100 / $this->calculation_values['QLMTDCONL'];
            $this->calculation_values['v']++;
        }
    }

    public function CONDENSERH()
    {
        $ferr10 = array();
        $t3h = array();
        $vam_base = new VamBaseController();

        if ($this->calculation_values['h'] != 0)
        {
            $this->calculation_values['AT3H'] = $this->calculation_values['T3H'] - 2;
        }

        $ferr10[0] = 1;
        $this->calculation_values['h'] = 1;
        while (abs($ferr10[$this->calculation_values['h'] - 1]) > 0.01)
        {
            if ($this->calculation_values['h'] == 1)
            {
                $t3h[$this->calculation_values['h']] = $this->calculation_values['AT3H'] + 2;
            }
            if ($this->calculation_values['h'] == 2)
            {
                $t3h[$this->calculation_values['h']] = $t3h[$this->calculation_values['h'] - 1] - 0.1;
            }
            if ($this->calculation_values['h'] >= 3)
            {
                $t3h[$this->calculation_values['h']] = $t3h[$this->calculation_values['h'] - 1] + $ferr10[$this->calculation_values['h'] - 1] * ($t3h[$this->calculation_values['h'] - 1] - $t3h[$this->calculation_values['h'] - 2]) / ($ferr10[$this->calculation_values['h'] - 2] - $ferr10[$this->calculation_values['h'] - 1]);
            }
            $this->calculation_values['T3H'] = $t3h[$this->calculation_values['h']];
            $this->calculation_values['P3H'] = $vam_base->LIBR_PRESSURE($this->calculation_values['T3H'], 0);
            $this->calculation_values['I3H'] = $this->calculation_values['T3H'] + 100;

            if ($this->calculation_values['CW'] == 2)
            {
                if ($this->calculation_values['CWC'] == 2)
                {
                    $this->calculation_values['TCW4LA'] = $this->calculation_values['TCW11'];
                    $this->CWCONHOUT();
                }
                else
                {
                    $this->calculation_values['TCW4LA'] = $this->calculation_values['TCW4L'];
                    $this->CWCONHOUT();
                }
            }

            $this->GENERATOR();

            $this->calculation_values['QCONREFL'] = $this->calculation_values['GREF2'] * ($this->calculation_values['J9L'] - $this->calculation_values['I3L']);
            $ferr10[$this->calculation_values['h']] = ($this->calculation_values['QCONREFL'] - $this->calculation_values['QCONL']) * 100 / $this->calculation_values['QCONL'];
            $this->calculation_values['h']++;
        }
    }

    public function CWCONHOUT()
    {
        $ferr12 = array();
        $tcw4 = array();
        $vam_base = new VamBaseController();

        $ferr12[0] = 1;
        $this->calculation_values['k'] = 1;
        while (abs($ferr12[$this->calculation_values['k'] - 1]) > 0.1)
        {
            if ($this->calculation_values['k'] == 1)
            {
                $tcw4[$this->calculation_values['k']] = $this->calculation_values['TCW4LA'] + 0.5;
            }
            if ($this->calculation_values['k'] == 2)
            {

                $tcw4[$this->calculation_values['k']] = $tcw4[$this->calculation_values['k'] - 1] + 0.2;
            }
            if ($this->calculation_values['k'] >= 3)
            {
                $tcw4[$this->calculation_values['k']] = $tcw4[$this->calculation_values['k'] - 1] + $ferr12[$this->calculation_values['k'] - 1] * ($tcw4[$this->calculation_values['k'] - 1] - $tcw4[$this->calculation_values['k'] - 2]) / ($ferr12[$this->calculation_values['k'] - 2] - $ferr12[$this->calculation_values['k'] - 1]) / 2;
            }
            if ($tcw4[$this->calculation_values['k']] > $this->calculation_values['T3H'] && $this->calculation_values['k'] > 2)
            {
                $tcw4[$this->calculation_values['k']] = $tcw4[$this->calculation_values['k'] - 1] + $ferr12[$this->calculation_values['k'] - 1] * ($tcw4[$this->calculation_values['k'] - 1] - $tcw4[$this->calculation_values['k'] - 2]) / ($ferr12[$this->calculation_values['k'] - 2] - $ferr12[$this->calculation_values['k'] - 1]) / 5;
            }

            $this->calculation_values['TCW4'] = $tcw4[$this->calculation_values['k']];

            if ($this->calculation_values['GL'] == 2)
            {
                $this->calculation_values['COGLY_SPHT3A'] = $vam_base->EG_SPHT($this->calculation_values['TCW4'], $this->calculation_values['COGLY']) * 1000;
                $this->calculation_values['COGLY_SPHT4A'] = $vam_base->EG_SPHT($this->calculation_values['TCW4L'], $this->calculation_values['COGLY']) * 1000;
            }
            else
            {
                $this->calculation_values['COGLY_SPHT3A'] = $vam_base->PG_SPHT($this->calculation_values['TCW4'], $this->calculation_values['COGLY']) * 1000;
                $this->calculation_values['COGLY_SPHT4A'] = $vam_base->PG_SPHT($this->calculation_values['TCW4L'], $this->calculation_values['COGLY']) * 1000;
            }

            $this->calculation_values['LMTDCONH'] = (($this->calculation_values['T3H'] - $this->calculation_values['TCW4LA']) - ($this->calculation_values['T3H'] - $this->calculation_values['TCW4'])) / log(($this->calculation_values['T3H'] - $this->calculation_values['TCW4LA']) / ($this->calculation_values['T3H'] - $this->calculation_values['TCW4']));
            $this->calculation_values['QLMTDCONH'] = $this->calculation_values['UCONH'] * $this->calculation_values['ACONH'] * $this->calculation_values['LMTDCONH'];
            $this->calculation_values['QCONH'] = $this->calculation_values['GCWC'] * $this->calculation_values['COGLY_ROW1'] * (($this->calculation_values['TCW4'] * $this->calculation_values['COGLY_SPHT3A']) - ($this->calculation_values['TCW4LA'] * $this->calculation_values['COGLY_SPHT4A'])) / 4187;
            $ferr12[$this->calculation_values['k']] = ($this->calculation_values['QLMTDCONH'] - $this->calculation_values['QCONH']) * 100 / $this->calculation_values['QLMTDCONH'];

            $this->calculation_values['k']++;
        }
    }

    public function GENERATOR()
    {

        $ferr11 = array();
        $t9 = array();
        $vam_base = new VamBaseController();


        if ($this->calculation_values['o'] == 0)
        {
            $this->calculation_values['GC'] = $this->calculation_values['GDIL'] - 5.40 * $this->calculation_values['TON'];
            $this->calculation_values['XC'] = $this->calculation_values['GDIL'] * $this->calculation_values['XDIL'] / $this->calculation_values['GC'];
            $this->calculation_values['ATHW11'] = $vam_base->LIBR_TEMP($this->calculation_values['P3H'], $this->calculation_values['XC']) - 0.1;
        }
        else
        {
            $this->calculation_values['ATHW11'] = $this->calculation_values['T9'];
        }
        $ferr11[0] = 1;
        $this->calculation_values['o'] = 1;
        while (abs($ferr11[$this->calculation_values['o'] - 1]) > 0.01)
        {
            if ($this->calculation_values['o'] == 1)
            {
                $t9[$this->calculation_values['o']] = $this->calculation_values['ATHW11'];
            }
            if ($this->calculation_values['o'] == 2)
            {
                $t9[$this->calculation_values['o']] = $t9[$this->calculation_values['o'] - 1] + 0.001;
            }
            if ($this->calculation_values['o'] >= 3)
            {
                $t9[$this->calculation_values['o']] = $t9[$this->calculation_values['o'] - 1] + $ferr11[$this->calculation_values['o'] - 1] * ($t9[$this->calculation_values['o'] - 1] - $t9[$this->calculation_values['o'] - 2]) / ($ferr11[$this->calculation_values['o'] - 2] - $ferr11[$this->calculation_values['o'] - 1]);
            }

            $this->calculation_values['T9'] = $t9[$this->calculation_values['o']];
            $this->calculation_values['XCONC'] = $vam_base->LIBR_CONC($this->calculation_values['T9'], $this->calculation_values['P3H']);
            $this->calculation_values['J9'] = $vam_base->WATER_VAPOUR_ENTHALPY($this->calculation_values['T9'], $this->calculation_values['P3H']);
            $this->calculation_values['I9'] = $vam_base->LIBR_ENTHALPY($this->calculation_values['T9'], $this->calculation_values['XCONC']);

            $this->calculation_values['GCONC'] = ($this->calculation_values['GDIL'] * $this->calculation_values['XDIL']) / $this->calculation_values['XCONC'];
            $this->calculation_values['GREF'] = $this->calculation_values['GDIL'] - $this->calculation_values['GCONC'];

            $this->calculation_values['GREFL'] = $this->calculation_values['QEVAL'] / ($this->calculation_values['J1L'] - $this->calculation_values['I1H']);
            $this->calculation_values['GREFH'] = $this->calculation_values['GREF'] - $this->calculation_values['GREFL'];

            $this->calculation_values['GREF1'] = (($this->calculation_values['GREFH'] * $this->calculation_values['J1H']) + ($this->calculation_values['GREF'] * $this->calculation_values['I1H']) - $this->calculation_values['QEVAH'] - ($this->calculation_values['GREF'] * $this->calculation_values['I3L']) - $this->calculation_values['GREFH'] * $this->calculation_values['I1H']) / ($this->calculation_values['I3H'] - $this->calculation_values['I3L']);
            $this->calculation_values['GREF2'] = $this->calculation_values['GREF'] - $this->calculation_values['GREF1'];

            $this->calculation_values['QCONREFH'] = $this->calculation_values['GREF1'] * ($this->calculation_values['J9'] - $this->calculation_values['I3H']);


            $this->calculation_values['GMED'] = $this->calculation_values['GCONC'] + $this->calculation_values['GREF1'];
            $this->calculation_values['XMED'] = ($this->calculation_values['GDIL'] * $this->calculation_values['XDIL']) / $this->calculation_values['GMED'];


            $this->calculation_values['T4H'] = $vam_base->LIBR_TEMP($this->calculation_values['P3H'], $this->calculation_values['XMED']);
            $this->calculation_values['I4H'] = $vam_base->LIBR_ENTHALPY($this->calculation_values['T4H'], $this->calculation_values['XMED']);

            $this->calculation_values['T9L'] = $vam_base->LIBR_TEMP($this->calculation_values['P3L'], $this->calculation_values['XMED']);
            $this->calculation_values['I9L'] = $vam_base->LIBR_ENTHALPY($this->calculation_values['T9L'], $this->calculation_values['XMED']);

            $this->calculation_values['J9L'] = $vam_base->WATER_VAPOUR_ENTHALPY($this->calculation_values['T9L'], $this->calculation_values['P3L']);

            $this->calculation_values['GCONCH'] = $this->calculation_values['GDIL'] - $this->calculation_values['GREFH'];
            $this->calculation_values['XCONCH'] = ($this->calculation_values['GDIL'] * $this->calculation_values['XDIL']) / $this->calculation_values['GCONCH'];

            $this->calculation_values['GCONCT'] = $this->calculation_values['GCONCH'] - $this->calculation_values['GREFL'];
            $this->calculation_values['XCONCT'] = ($this->calculation_values['GDIL'] * $this->calculation_values['XDIL']) / $this->calculation_values['GCONCT'];

            $this->calculation_values['T6H'] = $vam_base->LIBR_TEMP($this->calculation_values['P1H'], $this->calculation_values['XCONCH']);
            $this->calculation_values['I6H'] = $vam_base->LIBR_ENTHALPY($this->calculation_values['T6H'], $this->calculation_values['XCONCH']);

            $this->calculation_values['GDILL'] = $this->calculation_values['GCONCH'];
            $this->calculation_values['XDILL'] = $this->calculation_values['XCONCH'];

            $this->calculation_values['T6'] = $vam_base->LIBR_TEMP($this->calculation_values['P1L'], $this->calculation_values['XCONC']);

            $this->calculation_values['T2L'] = $vam_base->LIBR_TEMP($this->calculation_values['P1L'], $this->calculation_values['XDILL']);
            $this->calculation_values['I2L'] = $vam_base->LIBR_ENTHALPY($this->calculation_values['T2L'], $this->calculation_values['XDILL']);

            if ($this->calculation_values['CW'] == 1)
            {
                $this->calculation_values['TCW1H'] = $this->calculation_values['TCW1L'] = $this->calculation_values['TCW1A'];

                $this->CWABSHOUT();
                $this->CWABSLOUT();

                $this->calculation_values['TCW3'] = ($this->calculation_values['TCW2H'] + $this->calculation_values['TCW2L']) / 2;

                if ($this->calculation_values['CWC'] == 2)
                {
                    $this->calculation_values['TCW3A'] = $this->calculation_values['TCW3'];
                    $this->calculation_values['TCW4LA'] = $this->calculation_values['TCW3'];
                    $this->CWCONLOUT();
                    $this->CWCONHOUT();
                }
                else
                {
                    $this->calculation_values['TCW3A'] = $this->calculation_values['TCW3'];
                    $this->CWCONLOUT();
                    $this->calculation_values['TCW4LA'] = $this->calculation_values['TCW4L'];
                    $this->CWCONHOUT();
                }
            }
            $ferr11[$this->calculation_values['o']] = ($this->calculation_values['QCONH'] - $this->calculation_values['QCONREFH']) * 100 / $this->calculation_values['QCONH'];
            $this->calculation_values['o']++;
        }
    }

    public function CWABSHOUT()
    {
        $ferr6 = array();
        $tcw2h = array();
        $vam_base = new VamBaseController();

        $ferr6[0] = 2;
        $this->calculation_values['s'] = 1;
        while (abs($ferr6[$this->calculation_values['s'] - 1]) > 0.1)
        {
            if ($this->calculation_values['s'] == 1)
            {
                $tcw2h[$this->calculation_values['s']] = $this->calculation_values['TCW1H'] + 1.0;
            }
            if ($this->calculation_values['s'] == 2)
            {
                $tcw2h[$this->calculation_values['s']] = $tcw2h[$this->calculation_values['s'] - 1] + 0.5;
            }
            if ($this->calculation_values['s'] >= 3)
            {
                $tcw2h[$this->calculation_values['s']] = $tcw2h[$this->calculation_values['s'] - 1] + $ferr6[$this->calculation_values['s'] - 1] * ($tcw2h[$this->calculation_values['s'] - 1] - $tcw2h[$this->calculation_values['s'] - 2]) / ($ferr6[$this->calculation_values['s'] - 2] - $ferr6[$this->calculation_values['s'] - 1]);
            }
            if ($tcw2h[$this->calculation_values['s']] > $this->calculation_values['T6H'] && $this->calculation_values['s'] > 2)
            {
                $tcw2h[$this->calculation_values['s']] = $tcw2h[$this->calculation_values['s'] - 1] + $ferr6[$this->calculation_values['s'] - 1] * ($tcw2h[$this->calculation_values['s'] - 1] - $tcw2h[$this->calculation_values['s'] - 2]) / ($ferr6[$this->calculation_values['s'] - 2] - $ferr6[$this->calculation_values['s'] - 1]) / 5;
            }

            $this->calculation_values['TCW2H'] = $tcw2h[$this->calculation_values['s']];

            if ($this->calculation_values['GL'] == 2)
            {
                $this->calculation_values['COGLY_SPHT2H'] = $vam_base->EG_SPHT($this->calculation_values['TCW2H'], $this->calculation_values['COGLY']) * 1000;
            }
            else
            {
                $this->calculation_values['COGLY_SPHT2H'] = $vam_base->PG_SPHT($this->calculation_values['TCW2H'], $this->calculation_values['COGLY']) * 1000;
            }

            $this->calculation_values['QCWABSH'] = $this->calculation_values['GCWAH'] * $this->calculation_values['COGLY_ROW1'] * (($this->calculation_values['TCW2H'] * $this->calculation_values['COGLY_SPHT2H']) - ($this->calculation_values['TCW1H'] * $this->calculation_values['COGLY_SPHT11'])) / 4187;
            $this->calculation_values['LMTDABSH'] = (($this->calculation_values['T6H'] - $this->calculation_values['TCW2H']) - ($this->calculation_values['T2'] - $this->calculation_values['TCW1H'])) / log(($this->calculation_values['T6H'] - $this->calculation_values['TCW2H']) / ($this->calculation_values['T2'] - $this->calculation_values['TCW1H']));
            $this->calculation_values['QLMTDABSH'] = $this->calculation_values['UABSH'] * $this->calculation_values['AABSH'] * $this->calculation_values['LMTDABSH'];
            $ferr6[$this->calculation_values['s']] = ($this->calculation_values['QCWABSH'] - $this->calculation_values['QLMTDABSH']) * 100 / $this->calculation_values['QCWABSH'];
            $this->calculation_values['s']++;
        }

    }

    public function CWABSLOUT()
    {
        $ferr5 = array();
        $tcw2l = array();
        $vam_base = new VamBaseController();

        $ferr5[0] = 2;
        $this->calculation_values['m'] = 1;
        while (abs($ferr5[$this->calculation_values['m'] - 1]) > 0.01)
        {
            if ($this->calculation_values['m'] == 1)
            {
                $tcw2l[$this->calculation_values['m']] = $this->calculation_values['TCW1L'] + 1.0;
            }
            if ($this->calculation_values['m'] == 2)
            {
                $tcw2l[$this->calculation_values['m']] = $tcw2l[$this->calculation_values['m'] - 1] + 0.5;
            }
            if ($this->calculation_values['m'] >= 3)
            {
                $tcw2l[$this->calculation_values['m']] = $tcw2l[$this->calculation_values['m'] - 1] + $ferr5[$this->calculation_values['m'] - 1] * ($tcw2l[$this->calculation_values['m'] - 1] - $tcw2l[$this->calculation_values['m'] - 2]) / ($ferr5[$this->calculation_values['m'] - 2] - $ferr5[$this->calculation_values['m'] - 1]) / 3;
            }
            if ($tcw2l[$this->calculation_values['m']] > $this->calculation_values['T6'] && $this->calculation_values['m'] > 2)
            {
                $tcw2l[$this->calculation_values['m']] = $tcw2l[$this->calculation_values['m'] - 1] + $ferr5[$this->calculation_values['m'] - 1] * ($tcw2l[$this->calculation_values['m'] - 1] - $tcw2l[$this->calculation_values['m'] - 2]) / ($ferr5[$this->calculation_values['m'] - 2] - $ferr5[$this->calculation_values['m'] - 1]) / 5;
            }
            $this->calculation_values['TCW2L'] = $tcw2l[$this->calculation_values['m']];

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

            $this->calculation_values['QCWABSL'] = $this->calculation_values['GCWAL'] * $this->calculation_values['COGLY_ROW1'] * (($this->calculation_values['TCW2L'] * $this->calculation_values['COGLY_SPHT2L']) - ($this->calculation_values['TCW1L'] * $this->calculation_values['COGLY_SPHT1L'])) / 4187;
            $this->calculation_values['LMTDABSL'] = (($this->calculation_values['T6'] - $this->calculation_values['TCW2L']) - ($this->calculation_values['T2L'] - $this->calculation_values['TCW1L'])) / log(($this->calculation_values['T6'] - $this->calculation_values['TCW2L']) / ($this->calculation_values['T2L'] - $this->calculation_values['TCW1L']));
            $this->calculation_values['QLMTDABSL'] = $this->calculation_values['UABSL'] * $this->calculation_values['AABSL'] * $this->calculation_values['LMTDABSL'];
            $ferr5[$this->calculation_values['m']] = ($this->calculation_values['QCWABSL'] - $this->calculation_values['QLMTDABSL']) * 100 / $this->calculation_values['QCWABSL'];
            $this->calculation_values['m']++;
        }

    }

    public function LTHE()
    {
        $ferr7 = array();
        $t8 = array();
        $vam_base = new VamBaseController();

        $ferr7[0] = 1;
        $this->calculation_values['c'] = 1;
        while (abs($ferr7[$this->calculation_values['c'] - 1]) > 0.1)
        {
            if ($this->calculation_values['c'] == 1)
            {
                $t8[$this->calculation_values['c']] = $this->calculation_values['T2'] + 5;
            }
            if ($this->calculation_values['c'] == 2)
            {
                $t8[$this->calculation_values['c']] = $t8[$this->calculation_values['c'] - 1] + 0.1;
            }
            if ($this->calculation_values['c'] >= 3)
            {
                $t8[$this->calculation_values['c']] = $t8[$this->calculation_values['c'] - 1] + $ferr7[$this->calculation_values['c'] - 1] * ($t8[$this->calculation_values['c'] - 1] - $t8[$this->calculation_values['c'] - 2]) / ($ferr7[$this->calculation_values['c'] - 2] - $ferr7[$this->calculation_values['c'] - 1]);
            }
            $this->calculation_values['T8'] = $t8[$this->calculation_values['c']];

            $this->calculation_values['I8'] = $vam_base->LIBR_ENTHALPY($this->calculation_values['T8'], $this->calculation_values['XCONC']);
            $this->calculation_values['QLIBRLTHE'] = $this->calculation_values['GCONC'] * ($this->calculation_values['I9'] - $this->calculation_values['I8']);
            $this->calculation_values['I5'] = $this->calculation_values['I2'] + $this->calculation_values['QLIBRLTHE'] / $this->calculation_values['GDIL'];
            $this->calculation_values['T5'] = $vam_base->LIBR_TEMPERATURE($this->calculation_values['XDIL'], $this->calculation_values['I5']);
            $this->calculation_values['LMTDLTHE'] = (($this->calculation_values['T8'] - $this->calculation_values['T2']) - ($this->calculation_values['T9'] - $this->calculation_values['T5'])) / log(($this->calculation_values['T8'] - $this->calculation_values['T2']) / ($this->calculation_values['T9'] - $this->calculation_values['T5']));
            $this->calculation_values['QLMTDLTHE'] = $this->calculation_values['ULTHE'] * $this->calculation_values['ALTHE'] * $this->calculation_values['LMTDLTHE'];
            $ferr7[$this->calculation_values['c']] = ($this->calculation_values['QLMTDLTHE'] - $this->calculation_values['QLIBRLTHE']) * 100 / $this->calculation_values['QLMTDLTHE'];
            $this->calculation_values['c']++;

        }

    }

    public function GENH()
    {
        $ferr4 = array();
        $thw1h = array();
        $vam_base = new VamBaseController();

        $ferr4[0] = 1;
        $this->calculation_values['y'] = 1;
        while (abs($ferr4[$this->calculation_values['y'] - 1]) > 0.05)
        {
            if ($this->calculation_values['y'] == 1)
            {
                $thw1h[$this->calculation_values['y']] = $this->calculation_values['THW1'] - 1;
            }
            if ($this->calculation_values['y'] == 2)
            {
                $thw1h[$this->calculation_values['y']] = $thw1h[$this->calculation_values['y'] - 1] - 0.5;
            }
            if ($this->calculation_values['y'] >= 3)
            {
                $thw1h[$this->calculation_values['y']] = $thw1h[$this->calculation_values['y'] - 1] + $ferr4[$this->calculation_values['y'] - 1] * ($thw1h[$this->calculation_values['y'] - 1] - $thw1h[$this->calculation_values['y'] - 2]) / ($ferr4[$this->calculation_values['y'] - 2] - $ferr4[$this->calculation_values['y'] - 1]) / 4;
            }
            $this->calculation_values['THW1H'] = $thw1h[$this->calculation_values['y']];

            if ($this->calculation_values['GL'] == 2)
            {
                $this->calculation_values['CHWGLY_ROW11'] = $vam_base->EG_ROW($this->calculation_values['THW1'], $this->calculation_values['HWGLY']);
                $this->calculation_values['CHWGLY_SPHT11'] = $vam_base->EG_SPHT($this->calculation_values['THW1'], $this->calculation_values['HWGLY']);
                $this->calculation_values['CHWGLY_SPHT1H'] = $vam_base->EG_SPHT($this->calculation_values['THW1H'], $this->calculation_values['HWGLY']);
            }
            else
            {
                $this->calculation_values['CHWGLY_ROW11'] = $vam_base->PG_ROW($this->calculation_values['THW1'], $this->calculation_values['HWGLY']);
                $this->calculation_values['CHWGLY_SPHT11'] = $vam_base->PG_SPHT($this->calculation_values['THW1'], $this->calculation_values['HWGLY']);
                $this->calculation_values['CHWGLY_SPHT1H'] = $vam_base->PG_SPHT($this->calculation_values['THW1H'], $this->calculation_values['HWGLY']);
            }

            $this->calculation_values['QHWH'] = $this->calculation_values['GHW'] * $this->calculation_values['CHWGLY_ROW11'] * ($this->calculation_values['THW1'] - $this->calculation_values['THW1H']) * ($this->calculation_values['CHWGLY_SPHT11'] + $this->calculation_values['CHWGLY_SPHT1H']) * 0.5 / 4.187;

            $this->calculation_values['QGENH'] = ($this->calculation_values['GREF1'] * $this->calculation_values['J9']) + ($this->calculation_values['GCONC'] * $this->calculation_values['I9']) - ($this->calculation_values['GMED'] * $this->calculation_values['I9L']);
            $this->calculation_values['LMTDGENH'] = (($this->calculation_values['THW1'] - $this->calculation_values['T9']) - ($this->calculation_values['THW1H'] - $this->calculation_values['T4H'])) / log(($this->calculation_values['THW1'] - $this->calculation_values['T9']) / ($this->calculation_values['THW1H'] - $this->calculation_values['T4H']));
            $this->calculation_values['QLMTDGENH'] = $this->calculation_values['UGENH'] * $this->calculation_values['AGENH'] * $this->calculation_values['LMTDGENH'];
            $ferr4[$this->calculation_values['y']] = ($this->calculation_values['QHWH'] - $this->calculation_values['QLMTDGENH']) * 100 / $this->calculation_values['QHWH'];
            $this->calculation_values['y']++;
        }
    }

    public function GENL()
    {
        $ferr3 = array();
        $thw22 = array();
        $vam_base = new VamBaseController();

        $ferr3[0] = 1;
        $this->calculation_values['r'] = 1;
        while (abs($ferr3[$this->calculation_values['r'] - 1]) > 0.05)
        {
            if ($this->calculation_values['r'] == 1)
            {
                $thw22[$this->calculation_values['r']] = $this->calculation_values['THW1H'] - 10;
            }
            if ($this->calculation_values['r'] == 2)
            {
                $thw22[$this->calculation_values['r']] = $thw22[$this->calculation_values['r'] - 1] - 1;
            }
            if ($this->calculation_values['r'] >= 3)
            {
                $thw22[$this->calculation_values['r']] = $thw22[$this->calculation_values['r'] - 1] + $ferr3[$this->calculation_values['r'] - 1] * ($thw22[$this->calculation_values['r'] - 1] - $thw22[$this->calculation_values['r'] - 2]) / ($ferr3[$this->calculation_values['r'] - 2] - $ferr3[$this->calculation_values['r'] - 1]) / 4;
            }
            $this->calculation_values['THW22'] = $thw22[$this->calculation_values['r']];
            $this->calculation_values['THW3'] = $this->calculation_values['THW22'];
            $this->calculation_values['THW4'] = $this->calculation_values['THW22'];

            if ($this->calculation_values['GL'] == 2)
            {
                $this->calculation_values['CHWGLY_SPHT22'] = $vam_base->EG_SPHT($this->calculation_values['THW22'], $this->calculation_values['HWGLY']);
            }
            else
            {
                $this->calculation_values['CHWGLY_SPHT22'] = $vam_base->PG_SPHT($this->calculation_values['THW22'], $this->calculation_values['HWGLY']);
            }

            $this->calculation_values['QGENL'] = ($this->calculation_values['GMED'] * $this->calculation_values['I9L']) + ($this->calculation_values['GREF2'] * $this->calculation_values['J9L']) - ($this->calculation_values['GDIL'] * $this->calculation_values['I5']);
            $this->calculation_values['QHWL'] = $this->calculation_values['GHW'] * $this->calculation_values['CHWGLY_ROW11'] * ($this->calculation_values['THW1H'] - $this->calculation_values['THW22']) * ($this->calculation_values['CHWGLY_SPHT1H'] + $this->calculation_values['CHWGLY_SPHT22']) * 0.5 / 4.187;
            $ferr3[$this->calculation_values['r']] = ($this->calculation_values['QGENL'] - $this->calculation_values['QHWL']) * 100 / $this->calculation_values['QGENL'];
            $this->calculation_values['r']++;

        }

        $this->calculation_values['LMTDGENL'] = $this->calculation_values['QGENL'] / ($this->calculation_values['UGENL'] * $this->calculation_values['AGENL']);

        $this->calculation_values['LMTDGENLA'] = (($this->calculation_values['THW1H'] - $this->calculation_values['T9L']) - ($this->calculation_values['THW22'] - $this->calculation_values['T4'])) / log(($this->calculation_values['THW1H'] - $this->calculation_values['T9L']) / ($this->calculation_values['THW22'] - $this->calculation_values['T4']));

        $this->calculation_values['COP'] = ($this->calculation_values['TON'] * 3024) / ($this->calculation_values['GHW'] * $this->calculation_values['CHWGLY_ROW11'] * ($this->calculation_values['THW1'] - $this->calculation_values['THW22']) * ($this->calculation_values['CHWGLY_SPHT11'] + $this->calculation_values['CHWGLY_SPHT22']) * 0.5 / 4.187);
    }  


    public function CALCULATIONS()
    {
        $t11 = array();
        $t3n1 = array();
        $t12 = array();
        $t3n2 = array();

        $vam_base = new VamBaseController();

        // if ($this->calculation_values['EVAPDROP'] == 1)
        // {
        //     $this->calculation_values['TEPL'] = $this->calculation_values['TEP'] - 1;
        //     $this->calculation_values['TEPH'] = $this->calculation_values['TEP'] - 1;
        // }
        // else
        // {
        $this->calculation_values['TEPH'] = $this->calculation_values['TEP'];
        $this->calculation_values['TEPL'] = $this->calculation_values['TEP'];
        // }

        $this->calculation_values['VEH'] = $this->calculation_values['GCHW'] / 3600 / (3.141593 / 4 * $this->calculation_values['IDE'] * $this->calculation_values['IDE'] * $this->calculation_values['TNEV1'] / $this->calculation_values['TEPH']);
        $this->calculation_values['VEL'] = $this->calculation_values['GCHW'] / 3600 / (3.141593 / 4 * $this->calculation_values['IDE'] * $this->calculation_values['IDE'] * $this->calculation_values['TNEV1'] / $this->calculation_values['TEPL']);
        $this->calculation_values['VA'] = $this->calculation_values['GCW'] / 3600 / (3.141593 / 4 * $this->calculation_values['IDA'] * $this->calculation_values['IDA'] * $this->calculation_values['TNAA'] / $this->calculation_values['TAP']);
        $this->calculation_values['VC'] = $this->calculation_values['GCW'] / 3600 / (3.141593 / 4 * $this->calculation_values['IDC'] * $this->calculation_values['IDC'] * $this->calculation_values['TNC1'] / $this->calculation_values['TCP']);


        $this->DERATE_KEVAH();
        $this->DERATE_KEVAL();
        $this->DERATE_KABS();
        $this->DERATE_KCON();

        $this->HWVELOCITY();
        $this->DERATE_GEN();


        if ($this->calculation_values['TCHW2L'] < 3.499)
        {
            $this->calculation_values['KM3'] = (0.0343 * $this->calculation_values['TCHW2L']) + 0.82;
        }
        else
        {

            {
                $this->calculation_values['KM3'] = 1;
            }
        }            

        $this->calculation_values['KEVAH'] = $this->calculation_values['KEVAH'] * $this->calculation_values['KM3'];
        $this->calculation_values['KEVAL'] = $this->calculation_values['KEVAL'] * $this->calculation_values['KM3'];
        $this->calculation_values['KABS'] = $this->calculation_values['KABS'] * $this->calculation_values['KM3'];

        $this->calculation_values['UEVA'] = 1.0 / ((1.0 / $this->calculation_values['KEVAH']) + $this->calculation_values['FFCHW1']);
        $this->calculation_values['UEVAH'] = 1 / ((1 / $this->calculation_values['KEVAH']) + $this->calculation_values['FFCHW1']);
        $this->calculation_values['UEVAL'] = 1 / ((1 / $this->calculation_values['KEVAL']) + $this->calculation_values['FFCHW1']);
        $this->calculation_values['UCONH'] = 1 / ((1 / $this->calculation_values['KCON']) + $this->calculation_values['FFCOW1']);
        $this->calculation_values['UABSH'] = 1 / ((1 / $this->calculation_values['KABS']) + $this->calculation_values['FFCOW1']);
        $this->calculation_values['UABSL'] = $this->calculation_values['UABSH']; 
        $this->calculation_values['UCONL'] = $this->calculation_values['UCONH'];
        $this->calculation_values['UCON'] = $this->calculation_values['UCONL'];

        if ($this->calculation_values['TAP'] == 1) // 11.9.14
        {
            $this->calculation_values['UABSH'] = $this->calculation_values['UABSH'] * 0.9;
            $this->calculation_values['UABSL'] = $this->calculation_values['UABSL'] * 0.9;
        }


        //CW = 2;       //DEFAULT CONDENSER ENTRY, 1 FOR ABSORBER ENTRY

        /*************modification for property change*******************/
        if ($this->calculation_values['GL'] == 2)
        {
            $this->calculation_values['HWGLY_SPHT1'] = $vam_base->EG_SPHT($this->calculation_values['THW1'], $this->calculation_values['HWGLY']) / 4.187;
            $this->calculation_values['HWGLY_ROW1'] = $vam_base->EG_ROW($this->calculation_values['THW1'], $this->calculation_values['HWGLY']);
        }
        else
        {
            $this->calculation_values['HWGLY_SPHT1'] = $vam_base->PG_SPHT($this->calculation_values['THW1'], $this->calculation_values['HWGLY']) / 4.187;
            $this->calculation_values['HWGLY_ROW1'] = $vam_base->PG_ROW($this->calculation_values['THW1'], $this->calculation_values['HWGLY']);
        }
        if ($this->calculation_values['THW1'] >= 105)
            $this->calculation_values['ACOP'] = .77;
        else
            $this->calculation_values['ACOP'] = .73;

        $this->calculation_values['AHW2'] = $this->calculation_values['THW1'] - (($this->calculation_values['TON'] * 3024) / ($this->calculation_values['ACOP'] * $this->calculation_values['HWGLY_ROW1'] * $this->calculation_values['HWGLY_SPHT1'] * $this->calculation_values['GHW']));
        $this->calculation_values['AVGT'] = ($this->calculation_values['THW1'] + $this->calculation_values['AHW2']) / 2;

        /***********************************************************/
        $this->calculation_values['TCHW11'] = $this->calculation_values['TCHW1H'];
        $this->calculation_values['TCHW12'] = $this->calculation_values['TCHW2L'];
        $this->calculation_values['TCW1A'] = $this->calculation_values['TCW11'];



        if ($this->calculation_values['TUU'] != 'ari')
        {
            do
            {
                $this->EVAPORATOR();
                $this->PR_DROP_HW();

                if ($this->calculation_values['PDG'] > 10 && $this->calculation_values['TGP'] != 1)
                {
                    if ($this->calculation_values['TGP'] == 6)
                    {
                        $this->calculation_values['TGP'] = $this->calculation_values['TGP'] - 2;
                        $this->calculation_values['VG'] = $this->calculation_values['GHOT'] / 3600 / (3.142 / 4 * $this->calculation_values['IDG'] * $this->calculation_values['IDG'] * $this->calculation_values['TNG1'] / $this->calculation_values['TGP']);
                        $this->DERATE_GEN();
                    }
                    else
                    {
                        $this->calculation_values['TGP'] = $this->calculation_values['TGP'] - 1;
                        $this->calculation_values['VG'] = $this->calculation_values['GHOT'] / 3600 / (3.142 / 4 * $this->calculation_values['IDG'] * $this->calculation_values['IDG'] * $this->calculation_values['TNG1'] / $this->calculation_values['TGP']);
                        $this->DERATE_GEN();
                    }
                }
                else if ($this->calculation_values['PDG'] > 10 && $this->calculation_values['TGP'] == 1)
                {
                    break;
                }
            } while ($this->calculation_values['PDG'] > 10);
            $this->CONCHECK1();
        }
        else
        {
            $this->calculation_values['a'] = 1;

            $this->calculation_values['UEVA'] = 1.0 / ((1.0 / $this->calculation_values['KEVAH']) + ($this->calculation_values['FFCHW1'] * 0.5));
            $this->calculation_values['UEVAH'] = 1 / ((1 / $this->calculation_values['KEVAH']) + ($this->calculation_values['FFCHW1'] * 0.5));
            $this->calculation_values['UEVAL'] = 1 / ((1 / $this->calculation_values['KEVAL']) + ($this->calculation_values['FFCHW1'] * 0.5));
            $this->calculation_values['UCONH'] = 1 / ((1 / $this->calculation_values['KCON']) + ($this->calculation_values['FFCOW1'] * 0.5));
            $this->calculation_values['UABSH'] = 1 / ((1 / $this->calculation_values['KABS']) + ($this->calculation_values['FFCOW1'] * 0.5));
            $this->calculation_values['UABSL'] = $this->calculation_values['UABSH']; $this->calculation_values['UCONL'] = $this->calculation_values['UCONH'];

            if ($this->calculation_values['TAP'] == 1) // 11.9.14
            {
                $this->calculation_values['UABSH'] = $this->calculation_values['UABSH'] * 0.9;
                $this->calculation_values['UABSL'] = $this->calculation_values['UABSL'] * 0.9;
            }

            do
            {
                $this->EVAPORATOR();
                $this->PR_DROP_HW();

                if ($this->calculation_values['PDG'] > 10 && $this->calculation_values['TGP'] != 1)
                {
                    if ($this->calculation_values['TGP'] == 6)
                    {
                        $this->calculation_values['TGP'] = $this->calculation_values['TGP'] - 2;
                        $this->calculation_values['VG'] = $this->calculation_values['GHOT'] / 3600 / (3.142 / 4 * $this->calculation_values['IDG'] * $this->calculation_values['IDG'] * $this->calculation_values['TNG1'] / $this->calculation_values['TGP']);
                        $this->DERATE_GEN();
                    }
                    else
                    {
                        $this->calculation_values['TGP'] = $this->calculation_values['TGP'] - 1;
                        $this->calculation_values['VG'] = $this->calculation_values['GHOT'] / 3600 / (3.142 / 4 * $this->calculation_values['IDG'] * $this->calculation_values['IDG'] * $this->calculation_values['TNG1'] / $this->calculation_values['TGP']);
                        $this->DERATE_GEN();
                    }
                }
                else if ($this->calculation_values['PDG'] > 10 && $this->calculation_values['TGP'] == 1)
                {
                    break;
                }
            } while ($this->calculation_values['PDG'] > 10);

            $this->PRESSURE_DROP();
            $this->calculation_values['TCW14'] = ($this->calculation_values['TCW4'] + $this->calculation_values['TCW4L']) / 2;
            $this->calculation_values['LMTDCON'] = ($this->calculation_values['QCONH'] + $this->calculation_values['QCONL']) / ($this->calculation_values['UCON'] * ($this->calculation_values['ACONH'] + $this->calculation_values['ACONL']));
            $this->calculation_values['T3'] = $this->calculation_values['TCW14'] + ($this->calculation_values['TCW14'] - $this->calculation_values['TCW3']) / (exp(($this->calculation_values['TCW14'] - $this->calculation_values['TCW3']) / $this->calculation_values['LMTDCON']) - 1);

            do
            {
                $this->CONCHECK1();
                if ($this->calculation_values['XCONC'] > $this->calculation_values['KM'])
                {
                    break;
                }
                $t11[$this->calculation_values['a']] = $this->calculation_values['T1'];
                $t3n1[$this->calculation_values['a']] = $this->calculation_values['T3'];
                $this->calculation_values['ARISSP'] = ($this->calculation_values['TCHW12'] - $this->calculation_values['T1']) * 1.8;
                $this->calculation_values['ARIR'] = ($this->calculation_values['TCHW11'] - $this->calculation_values['TCHW12']) * 1.8;
                $this->calculation_values['ARILMTD'] = $this->calculation_values['ARIR'] / log(1 + ($this->calculation_values['ARIR'] / $this->calculation_values['ARISSP']));
                $this->calculation_values['ARICHWA'] = 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['LE'] * $this->calculation_values['TNEV'];
                $this->calculation_values['ARIILMTD'] = (5 * $this->calculation_values['FFCHW1']) * ($this->calculation_values['TON'] * 3024 * 3.968 / ($this->calculation_values['ARICHWA'] * 3.28084 * 3.28084));
                $this->calculation_values['ARIZ'] = $this->calculation_values['ARIR'] / ($this->calculation_values['ARILMTD'] - $this->calculation_values['ARIILMTD']);
                $this->calculation_values['ARITDA'] = $this->calculation_values['ARISSP'] - ($this->calculation_values['ARIR'] / (exp($this->calculation_values['ARIZ']) - 1));
                $this->calculation_values['ARITCHWI'] = $this->calculation_values['TCHW11'] - ($this->calculation_values['ARITDA'] / 1.8);
                $this->calculation_values['ARITCHWO'] = $this->calculation_values['TCHW12'] - ($this->calculation_values['ARITDA'] / 1.8);

                $this->calculation_values['ARISSPC'] = ($this->calculation_values['T3'] - $this->calculation_values['TCW14']) * 1.8;
                $this->calculation_values['ARIRC'] = ($this->calculation_values['TCW14'] - $this->calculation_values['TCW11']) * 1.8;
                $this->calculation_values['ALMTDC'] = $this->calculation_values['ARIRC'] / log(1 + ($this->calculation_values['ARIRC'] / $this->calculation_values['ARISSPC']));
                $this->calculation_values['ARICOWA'] = 3.141593 * $this->calculation_values['LE'] * ($this->calculation_values['IDA'] * $this->calculation_values['TNAA'] + $this->calculation_values['IDC'] * $this->calculation_values['TNC']);
                $this->calculation_values['AILMTDC'] = (5 * $this->calculation_values['FFCOW1']) * ($this->calculation_values['GCW'] * $this->calculation_values['COGLY_ROW1'] * ($this->calculation_values['COGLY_SPHT4'] / 4187) * ($this->calculation_values['TCW14'] - $this->calculation_values['TCW11']) * 3.968 / ($this->calculation_values['ARICOWA'] * 3.28084 * 3.28084));
                $this->calculation_values['ARIZC'] = $this->calculation_values['ARIRC'] / ($this->calculation_values['ALMTDC'] - $this->calculation_values['AILMTDC']);
                $this->calculation_values['ARITDAC'] = $this->calculation_values['ARISSPC'] - ($this->calculation_values['ARIRC'] / (exp($this->calculation_values['ARIZC']) - 1));
                $this->calculation_values['ARITCWI'] = $this->calculation_values['TCW11'] + ($this->calculation_values['ARITDAC'] / 1.8);

                $this->calculation_values['FFCHW'] = 0;
                $this->calculation_values['FFCOW'] = 0;
                $this->calculation_values['UEVA'] = 1.0 / ((1.0 / $this->calculation_values['KEVAH']) + $this->calculation_values['FFCHW']);
                $this->calculation_values['UEVAH'] = 1.0 / ((1.0 / $this->calculation_values['KEVAH']) + $this->calculation_values['FFCHW']);
                $this->calculation_values['UEVAL'] = 1.0 / ((1.0 / $this->calculation_values['KEVAL']) + $this->calculation_values['FFCHW']);
                $this->calculation_values['UABSH'] = 1.0 / ((1.0 / $this->calculation_values['KABS']) + $this->calculation_values['FFCOW']);
                $this->calculation_values['UABSL'] = 1.0 / ((1.0 / $this->calculation_values['KABS']) + $this->calculation_values['FFCOW']);
                $this->calculation_values['UCON'] = 1.0 / ((1.0 / $this->calculation_values['KCON']) + $this->calculation_values['FFCOW']);
                $this->calculation_values['UABSL'] = $this->calculation_values['UABSH'];
                $this->calculation_values['UCONL'] = $this->calculation_values['UCONH'] = $this->calculation_values['UCON'];

                if ($this->calculation_values['TAP'] == 1) // 11.9.14
                {
                    $this->calculation_values['UABSH'] = $this->calculation_values['UABSH'] * 0.9;
                    $this->calculation_values['UABSL'] = $this->calculation_values['UABSL'] * 0.9;
                }

                $this->calculation_values['TCHW11'] = $this->calculation_values['ARITCHWI'];
                $this->calculation_values['TCHW12'] = $this->calculation_values['ARITCHWO'];
                $this->calculation_values['TCW1A'] = $this->calculation_values['ARITCWI'];


                do
                {
                    $this->EVAPORATOR();
                    $this->PR_DROP_HW();

                    if ($this->calculation_values['PDG'] > 10 && $this->calculation_values['TGP'] != 1)
                    {
                        if ($this->calculation_values['TGP'] == 6)
                        {
                            $this->calculation_values['TGP'] = $this->calculation_values['TGP'] - 2;
                            $this->calculation_values['VG'] = $this->calculation_values['GHOT'] / 3600 / (3.142 / 4 * $this->calculation_values['IDG'] * $this->calculation_values['IDG'] * $this->calculation_values['TNG1'] / $this->calculation_values['TGP']);
                            $this->DERATE_GEN();
                        }
                        else
                        {
                            $this->calculation_values['TGP'] = $this->calculation_values['TGP'] - 1;
                            $this->calculation_values['VG'] = $this->calculation_values['GHOT'] / 3600 / (3.142 / 4 * $this->calculation_values['IDG'] * $this->calculation_values['IDG'] * $this->calculation_values['TNG1'] / $this->calculation_values['TGP']);
                            $this->DERATE_GEN();
                        }
                    }
                    else if ($this->calculation_values['PDG'] > 10 && $this->calculation_values['TGP'] == 1)
                    {
                        break;
                    }
                } while ($this->calculation_values['PDG'] > 10);
                $this->PRESSURE_DROP();

                $this->calculation_values['TCW14'] = ($this->calculation_values['TCW4'] + $this->calculation_values['TCW4L']) / 2;
                $this->calculation_values['LMTDCON'] = ($this->calculation_values['QCONH'] + $this->calculation_values['QCONL']) / ($this->calculation_values['UCON'] * ($this->calculation_values['ACONH'] + $this->calculation_values['ACONL']));
                $this->calculation_values['T3'] = $this->calculation_values['TCW14'] + ($this->calculation_values['TCW14'] - $this->calculation_values['TCW3']) / (exp(($this->calculation_values['TCW14'] - $this->calculation_values['TCW3']) / $this->calculation_values['LMTDCON']) - 1);

                $t12[$this->calculation_values['a']] = $this->calculation_values['T1'];
                $t3n2[$this->calculation_values['a']] = $this->calculation_values['T3'];
            } while ((abs($t11[$this->calculation_values['a']] - $t12[$this->calculation_values['a']]) > 0.005) || (abs($t3n1[$this->calculation_values['a']] - $t3n2[$this->calculation_values['a']]) > 0.005));
        }

        $this->PRESSURE_DROP();

    }


    public function CONCHECK1()
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

    public function PRESSURE_DROP()
    {
        // PR_DROP_DATA();
        $this->PIPE_SIZE(); /****** PIPE SIZE FOR EVA,ABS,CON*******/

        $this->calculation_values['VEH'] = $this->calculation_values['GCHW'] / 3600 / (3.142 / 4 * $this->calculation_values['IDE'] * $this->calculation_values['IDE'] * $this->calculation_values['TNEV1'] / $this->calculation_values['TEPH']);
        $this->calculation_values['VEL'] = $this->calculation_values['GCHW'] / 3600 / (3.142 / 4 * $this->calculation_values['IDE'] * $this->calculation_values['IDE'] * $this->calculation_values['TNEV1'] / $this->calculation_values['TEPL']);
        $this->calculation_values['VA'] = $this->calculation_values['GCW'] / 3600 / (3.142 / 4 * $this->calculation_values['IDA'] * $this->calculation_values['IDA'] * $this->calculation_values['TNAA'] / $this->calculation_values['TAP']);
        $this->calculation_values['VC'] = $this->calculation_values['GCW'] / 3600 / (3.142 / 4 * $this->calculation_values['IDC'] * $this->calculation_values['IDC'] * $this->calculation_values['TNC1'] / $this->calculation_values['TCP']);
        $this->calculation_values['VG'] = $this->calculation_values['GHOT'] / 3600 / (3.142 / 4 * $this->calculation_values['IDG'] * $this->calculation_values['IDG'] * $this->calculation_values['TNG1'] / $this->calculation_values['TGP']);

        $this->PR_DROP_CHILL();
        $this->PR_DROP_COW();
        $this->PR_DROP_HW();
    } 


    public function PR_DROP_CHILL()
    {
        $vam_base = new VamBaseController();
        /*** PRESSURE DROP ACC TO CRANE ***/

        $this->calculation_values['VPE'] = ($this->calculation_values['GCHW'] * 4) / (3.14153 * $this->calculation_values['PIDE'] * $this->calculation_values['PIDE'] * 3600);      //PIPE VELOCITY

        $this->calculation_values['TME'] = ($this->calculation_values['TCHW1H'] + $this->calculation_values['TCHW2L']) / 2.0;

        if ($this->calculation_values['GL'] == 3)
        {
            $this->calculation_values['VISE'] = $vam_base->PG_VISCOSITY($this->calculation_values['TME'], $this->calculation_values['CHGLY']) / 1000;
            $this->calculation_values['GLY_ROW'] = $vam_base->PG_ROW($this->calculation_values['TME'], $this->calculation_values['CHGLY']);
        }
        else
        {
            $this->calculation_values['VISE'] = $vam_base->EG_VISCOSITY($this->calculation_values['TME'], $this->calculation_values['CHGLY']) / 1000;
            $this->calculation_values['GLY_ROW'] = $vam_base->EG_ROW($this->calculation_values['TME'], $this->calculation_values['CHGLY']);
        }

        $this->calculation_values['REPE'] = ($this->calculation_values['PIDE'] * $this->calculation_values['VPE'] * $this->calculation_values['GLY_ROW']) / $this->calculation_values['VISE'];               //REYNOLDS NO IN PIPE

        $this->calculation_values['FF'] = 1.325 / pow(log((0.0457 / (3.7 * $this->calculation_values['PIDE'] * 1000)) + (5.74 / pow($this->calculation_values['REPE'], 0.9))), 2);      //FRIC FACTOR FOR ST LENGTH

        $this->calculation_values['FL1'] = (($this->calculation_values['SL1'] + $this->calculation_values['SL2'] + $this->calculation_values['SL3'] + $this->calculation_values['SL4'] + $this->calculation_values['SL5']) * $this->calculation_values['FF'] / $this->calculation_values['PIDE']) * ($this->calculation_values['VPE'] * $this->calculation_values['VPE'] / (2 * 9.81));
        $this->calculation_values['FL2'] = (30 * $this->calculation_values['FT']) * (($this->calculation_values['VPE'] * $this->calculation_values['VPE']) / (2 * 9.81)) * 2;                    //90 deg bends
        $this->calculation_values['FL3'] = ($this->calculation_values['VPE'] * $this->calculation_values['VPE'] / (2 * 9.81)) + (0.5 * $this->calculation_values['VPE'] * $this->calculation_values['VPE'] / (2 * 9.81));

        $this->calculation_values['FLP'] = $this->calculation_values['FL1'] + $this->calculation_values['FL2'] + $this->calculation_values['FL3'];          //FR LOSS IN PIPES

        /******* FRICTION LOSS IN EVA TUBES ******/

        $this->calculation_values['RE1'] = ($this->calculation_values['GLY_ROW'] * $this->calculation_values['VEH'] * $this->calculation_values['IDE']) / $this->calculation_values['VISE'];
        $this->calculation_values['RE2'] = ($this->calculation_values['GLY_ROW'] * $this->calculation_values['VEL'] * $this->calculation_values['IDE']) / $this->calculation_values['VISE'];


        if ( $this->calculation_values['TU2'] == 3 || $this->calculation_values['TU2'] == 6 || $this->calculation_values['TU2'] == 7 || $this->calculation_values['TU2'] == 9)
        {
            if ($this->calculation_values['MODEL'] < 700)
            {
                $this->calculation_values['F1'] = 1.325 / (pow((log(1.53 / (3.7 * ($this->calculation_values['IDE'] * 1000)) + 5.74 / (pow($this->calculation_values['RE1'], 0.9)))), 2));
                $this->calculation_values['F2'] = 1.325 / (pow((log(1.53 / (3.7 * ($this->calculation_values['IDE'] * 1000)) + 5.74 / (pow($this->calculation_values['RE2'], 0.9)))), 2));
                $this->calculation_values['FE1'] = ($this->calculation_values['F1'] * $this->calculation_values['LE'] * $this->calculation_values['VEH'] * $this->calculation_values['VEH']) / (2 * 9.81 * $this->calculation_values['IDE']);
                $this->calculation_values['FE11'] = ($this->calculation_values['F2'] * $this->calculation_values['LE'] * $this->calculation_values['VEL'] * $this->calculation_values['VEL']) / (2 * 9.81 * $this->calculation_values['IDE']);
            }
            else
            {
                $this->calculation_values['F1'] = (1.325 / (pow((log(1.53 / (3.7 * ($this->calculation_values['IDE'] * 1000)) + 5.74 / (pow($this->calculation_values['RE1'], 0.9)))), 2))) * ((-0.0315 * $this->calculation_values['VEH']) + 0.85);  // 11/29/2011 CHANGE IN 19 OD CORRUGATED
                $this->calculation_values['F2'] = (1.325 / (pow((log(1.53 / (3.7 * ($this->calculation_values['IDE'] * 1000)) + 5.74 / (pow($this->calculation_values['RE2'], 0.9)))), 2))) * ((-0.0315 * $this->calculation_values['VEL']) + 0.85);
                $this->calculation_values['FE1'] = ($this->calculation_values['F1'] * $this->calculation_values['LE'] * $this->calculation_values['VEH'] * $this->calculation_values['VEH']) / (2 * 9.81 * $this->calculation_values['IDE']);
                $this->calculation_values['FE11'] = ($this->calculation_values['F2'] * $this->calculation_values['LE'] * $this->calculation_values['VEL'] * $this->calculation_values['VEL']) / (2 * 9.81 * $this->calculation_values['IDE']);

            }
        }
        else if($this->calculation_values['TU2'] == 1 || $this->calculation_values['TU2'] == 2 || $this->calculation_values['TU2'] == 4 || $this->calculation_values['TU2'] == 8 || $this->calculation_values['TU2'] == 0)
        {
            $this->calculation_values['F1'] = 0.0014 + (0.137 / pow($this->calculation_values['RE1'], 0.32));
            $this->calculation_values['F2'] = 0.0014 + (0.137 / pow($this->calculation_values['RE2'], 0.32));
            $this->calculation_values['FE1'] = 2 * $this->calculation_values['F1'] * $this->calculation_values['LE'] * $this->calculation_values['VEH'] * $this->calculation_values['VEH'] / (9.81 * $this->calculation_values['IDE']);
            $this->calculation_values['FE11'] = 2 * $this->calculation_values['F2'] * $this->calculation_values['LE'] * $this->calculation_values['VEL'] * $this->calculation_values['VEL'] / (9.81 * $this->calculation_values['IDE']);
        }
        else
        {
            $this->calculation_values['F1'] = 0.0014 + (0.123 / pow($this->calculation_values['RE1'], 0.32));
            $this->calculation_values['F2'] = 0.0014 + (0.123 / pow($this->calculation_values['RE2'], 0.32));
            $this->calculation_values['FE1'] = 2 * $this->calculation_values['F1'] * $this->calculation_values['LE'] * $this->calculation_values['VEH'] * $this->calculation_values['VEH'] / (9.81 * $this->calculation_values['IDE']);
            $this->calculation_values['FE11'] = 2 * $this->calculation_values['F2'] * $this->calculation_values['LE'] * $this->calculation_values['VEL'] * $this->calculation_values['VEL'] / (9.81 * $this->calculation_values['IDE']);
        }

        $this->calculation_values['FE2'] = $this->calculation_values['VEH'] * $this->calculation_values['VEH'] / (4 * 9.81);
        $this->calculation_values['FE21'] = $this->calculation_values['VEL'] * $this->calculation_values['VEL'] / (4 * 9.81);
        $this->calculation_values['FE3'] = $this->calculation_values['VEH'] * $this->calculation_values['VEH'] / (2 * 9.81);
        $this->calculation_values['FE31'] = $this->calculation_values['VEL'] * $this->calculation_values['VEL'] / (2 * 9.81);
        $this->calculation_values['FE4'] = (($this->calculation_values['FE1'] + $this->calculation_values['FE2'] + $this->calculation_values['FE3']) * $this->calculation_values['TEPH']) + (($this->calculation_values['FE11'] + $this->calculation_values['FE21'] + $this->calculation_values['FE31']) * $this->calculation_values['TEPL']);
        $this->calculation_values['FLE'] = $this->calculation_values['FE4'] + $this->calculation_values['FLP'];
        $this->calculation_values['PDE'] = $this->calculation_values['FLE'] + $this->calculation_values['SHE'];            
    }

    public function PR_DROP_COW()
    {
        $vam_base = new VamBaseController();

        $this->calculation_values['APA'] = 3.141593 * $this->calculation_values['PIDA'] * $this->calculation_values['PIDA'] / 4;
        $this->calculation_values['VPA'] = ($this->calculation_values['GCW'] * 4) / (3.141593 * $this->calculation_values['PIDA'] * $this->calculation_values['PIDA'] * 3600);

        $this->calculation_values['TMA'] = ($this->calculation_values['TCW1L'] + $this->calculation_values['TCW2H']) / 2;
        $this->calculation_values['TMC'] = ($this->calculation_values['TCW2H'] + $this->calculation_values['TCW4']) / 2;

        if ($this->calculation_values['GL'] == 2)
        {
            $this->calculation_values['VISA'] = $vam_base->EG_VISCOSITY($this->calculation_values['TMA'], $this->calculation_values['COGLY']) / 1000;
            $this->calculation_values['VISC'] = $vam_base->EG_VISCOSITY($this->calculation_values['TMC'], $this->calculation_values['COGLY']) / 1000;
            $this->calculation_values['GLY_ROW'] = $vam_base->EG_ROW($this->calculation_values['TMA'], $this->calculation_values['COGLY']);
        }
        else
        {
            $this->calculation_values['VISA'] = $vam_base->PG_VISCOSITY($this->calculation_values['TMA'], $this->calculation_values['COGLY']) / 1000;
            $this->calculation_values['VISC'] = $vam_base->PG_VISCOSITY($this->calculation_values['TMC'], $this->calculation_values['COGLY']) / 1000;
            $this->calculation_values['GLY_ROW'] = $vam_base->PG_ROW($this->calculation_values['TMA'], $this->calculation_values['COGLY']);
        }

        $this->calculation_values['REPA'] = ($this->calculation_values['GLY_ROW'] * $this->calculation_values['VPA'] * $this->calculation_values['PIDA']) / $this->calculation_values['VISA'];

        $this->calculation_values['FFA'] = 1.325 / pow(log((0.0457 / (3.7 * $this->calculation_values['PIDA'] * 1000)) + (5.74 / pow($this->calculation_values['REPA'], 0.9))), 2);     //FRICTION FACTOR CAL

        $this->calculation_values['FLP1'] = ($this->calculation_values['FFA'] * ($this->calculation_values['PSL1'] + $this->calculation_values['PSL2']) / $this->calculation_values['PIDA']) * ($this->calculation_values['VPA'] * $this->calculation_values['VPA'] / (2 * 9.81)) + ((30 * $this->calculation_values['FT1']) * ($this->calculation_values['VPA'] * $this->calculation_values['VPA']) / (2 * 9.81));       //FR LOSS IN PIPE                                   

        $this->calculation_values['FLOT'] = (1 + 0.5 + 1 + 0.5) * ($this->calculation_values['VPA'] * $this->calculation_values['VPA'] / (2 * 9.81));                                                                  //EXIT, ENTRY LOSS

        $this->calculation_values['AFLP'] = ($this->calculation_values['FLP1'] + $this->calculation_values['FLOT']) * 1.075;               //7.5% SAFETY

        $this->calculation_values['RE'] = ($this->calculation_values['VA'] * $this->calculation_values['IDA'] * $this->calculation_values['GLY_ROW']) / $this->calculation_values['VISA'];

        if ($this->calculation_values['TU5'] < 2.1 || $this->calculation_values['TU5'] == 6 || $this->calculation_values['TU5'] == 5)
        {
            $this->calculation_values['F'] = 0.0014 + (0.137 / pow($this->calculation_values['RE'], 0.32));
        }
        else
        {
            $this->calculation_values['F'] = 0.0014 + (0.123 / pow($this->calculation_values['RE'], 0.32));
        }

        $this->calculation_values['FA1'] = 2 * $this->calculation_values['F'] * $this->calculation_values['LE'] * $this->calculation_values['VA'] * $this->calculation_values['VA'] / (9.81 * $this->calculation_values['IDA']);
        $this->calculation_values['FA2'] = $this->calculation_values['VA'] * $this->calculation_values['VA'] / (4 * 9.81);
        $this->calculation_values['FA3'] = $this->calculation_values['VA'] * $this->calculation_values['VA'] / (2 * 9.81);
        $this->calculation_values['FA4'] = ($this->calculation_values['FA1'] + $this->calculation_values['FA2'] + $this->calculation_values['FA3']) * $this->calculation_values['TAP'];
        $this->calculation_values['FLA'] = $this->calculation_values['FA4'] + $this->calculation_values['AFLP'];                            //FR LOSS IN ABS TUBES

        $this->calculation_values['RE'] = ($this->calculation_values['GLY_ROW'] * $this->calculation_values['VC'] * $this->calculation_values['IDC']) / $this->calculation_values['VISC'];

        if ($this->calculation_values['TV5'] < 3.1 || $this->calculation_values['TV5'] == 4)
        {
            $this->calculation_values['F'] = 0.0014 + (0.137 / pow($this->calculation_values['RE'], 0.32));
        }
        else
        {
            $this->calculation_values['F'] = 0.0014 + (0.123 / pow($this->calculation_values['RE'], 0.32));
        }
        $this->calculation_values['FC1'] = 2 * $this->calculation_values['F'] * $this->calculation_values['LE'] * $this->calculation_values['VC'] * $this->calculation_values['VC'] / (9.81 * $this->calculation_values['IDC']);
        $this->calculation_values['FC2'] = $this->calculation_values['VC'] * $this->calculation_values['VC'] / (4 * 9.81);
        $this->calculation_values['FC3'] = $this->calculation_values['VC'] * $this->calculation_values['VC'] / (2 * 9.81);
        if ($this->calculation_values['CWC'] == 2)
        {
            $this->calculation_values['FC4'] = ($this->calculation_values['FC1'] + $this->calculation_values['FC2'] + $this->calculation_values['FC3']) * $this->calculation_values['TCP'];              //FR LOSS IN CONDENSER TUBES
        }
        else
        {
            $this->calculation_values['FC4'] = ($this->calculation_values['FC1'] + $this->calculation_values['FC2'] + $this->calculation_values['FC3']) * ($this->calculation_values['TCP'] + $this->calculation_values['TCP']);      //FR LOSS IN CONDENSER TUBES
        }

        if ($this->calculation_values['CW'] == 2)
        {
            $this->calculation_values['PDA'] = $this->calculation_values['FLA'] + $this->calculation_values['FC4'] - $this->calculation_values['SHA'];
        }
        else
        {
            $this->calculation_values['PDA'] = $this->calculation_values['FLA'] + $this->calculation_values['FC4'] + $this->calculation_values['SHA'];
        }
    } 


    public function CONVERGENCE()
    {
        $j = 0;
        $CC = array();

        $CC[0][0] = $this->calculation_values['GCHW'] * $this->calculation_values['CHGLY_ROW12'] * 0.5 * ($this->calculation_values['CHGLY_SPHT2H'] + $this->calculation_values['CHGLY_SPHT11']) * ($this->calculation_values['TCHW11'] - $this->calculation_values['TCHW2H']) / 4187;
        $CC[1][0] = $this->calculation_values['UEVAH'] * $this->calculation_values['AEVAH'] * $this->calculation_values['LMTDEVAH'];
        $CC[2][0] = $this->calculation_values['GREFH'] * $this->calculation_values['J1H'] - $this->calculation_values['GREF1'] * $this->calculation_values['I3H'] + $this->calculation_values['GREF'] * $this->calculation_values['I1H'] - $this->calculation_values['GREFH'] * $this->calculation_values['I1H'] - $this->calculation_values['GREF2'] * $this->calculation_values['I3L'];                              //EVAPORATORH

        $CC[0][1] = $this->calculation_values['GCWAH'] * $this->calculation_values['COGLY_ROW1'] * (($this->calculation_values['TCW2H'] * $this->calculation_values['COGLY_SPHT2H']) - ($this->calculation_values['TCW1H'] * $this->calculation_values['COGLY_SPHT11'])) / 4187;
        $CC[1][1] = $this->calculation_values['UABSH'] * $this->calculation_values['AABSH'] * $this->calculation_values['LMTDABSH'];
        $CC[2][1] = $this->calculation_values['GCONCH'] * $this->calculation_values['I2L'] + $this->calculation_values['GREFH'] * $this->calculation_values['J1H'] - $this->calculation_values['GDIL'] * $this->calculation_values['I2'];          //ABSORBERH

        $CC[0][2] = $this->calculation_values['GCWC'] * $this->calculation_values['COGLY_ROW1'] * (($this->calculation_values['TCW4'] * $this->calculation_values['COGLY_SPHT3A']) - ($this->calculation_values['TCW4LA'] * $this->calculation_values['COGLY_SPHT4A'])) / 4187;
        $CC[1][2] = $this->calculation_values['UCONH'] * $this->calculation_values['ACONH'] * $this->calculation_values['LMTDCONH'];
        $CC[2][2] = $this->calculation_values['GREF1'] * ($this->calculation_values['J9'] - $this->calculation_values['I3H']);                               //CONDENSERH                           //CONDENSERH

        $CC[0][3] = $this->calculation_values['GHW'] * $this->calculation_values['CHWGLY_ROW11'] * ($this->calculation_values['THW1'] - $this->calculation_values['THW1H']) * ($this->calculation_values['CHWGLY_SPHT11'] + $this->calculation_values['CHWGLY_SPHT1H']) * 0.5 / 4.187;
        $CC[1][3] = $this->calculation_values['UGENH'] * $this->calculation_values['AGENH'] * $this->calculation_values['LMTDGENH'];
        $CC[2][3] = ($this->calculation_values['GREF1'] * $this->calculation_values['J9']) + ($this->calculation_values['GCONC'] * $this->calculation_values['I9']) - ($this->calculation_values['GMED'] * $this->calculation_values['I9L']);      //GENERATORH

        $CC[0][4] = $this->calculation_values['GCONC'] * ($this->calculation_values['I9'] - $this->calculation_values['I8']);
        $CC[1][4] = $this->calculation_values['ULTHE'] * $this->calculation_values['ALTHE'] * $this->calculation_values['LMTDLTHE'];
        $CC[2][4] = $this->calculation_values['GDIL'] * ($this->calculation_values['I5'] - $this->calculation_values['I2']);                                //LTHE

        $CC[0][5] = $this->calculation_values['GCHW'] * $this->calculation_values['CHGLY_ROW12'] * 0.5 * ($this->calculation_values['CHGLY_SPHT2H'] + $this->calculation_values['CHGLY_SPHT12']) * ($this->calculation_values['TCHW1L'] - $this->calculation_values['TCHW12']) / 4187;
        $CC[1][5] = $this->calculation_values['UEVAL'] * $this->calculation_values['AEVAL'] * $this->calculation_values['LMTDEVAL'];
        $CC[2][5] = $this->calculation_values['GREFL'] * ($this->calculation_values['J1L'] - $this->calculation_values['I1H']);                     //EVAPORATORL

        $CC[0][6] = $this->calculation_values['GCWAL'] * $this->calculation_values['COGLY_ROW1'] * (($this->calculation_values['TCW2L'] * $this->calculation_values['COGLY_SPHT2L']) - ($this->calculation_values['TCW1L'] * $this->calculation_values['COGLY_SPHT1L'])) / 4187;
        $CC[1][6] = $this->calculation_values['UABSL'] * $this->calculation_values['AABSL'] * $this->calculation_values['LMTDABSL'];
        $CC[2][6] = $this->calculation_values['GCONC'] * $this->calculation_values['I8'] + $this->calculation_values['GREFL'] * $this->calculation_values['J1L'] - $this->calculation_values['GDILL'] * $this->calculation_values['I2L'];          //ABSORBERL

        $CC[0][7] = $this->calculation_values['GCWC'] * $this->calculation_values['COGLY_ROW1'] * (($this->calculation_values['TCW4L'] * $this->calculation_values['COGLY_SPHT4']) - ($this->calculation_values['TCW3A'] * $this->calculation_values['COGLY_SPHT3'])) / 4187;
        $CC[1][7] = $this->calculation_values['UCONL'] * $this->calculation_values['ACONL'] * $this->calculation_values['LMTDCONL'];
        $CC[2][7] = $this->calculation_values['GREF2'] * ($this->calculation_values['J9L'] - $this->calculation_values['I3L']); ;                               //CONDENSERL

        $CC[0][8] = $this->calculation_values['GHW'] * $this->calculation_values['CHWGLY_ROW11'] * ($this->calculation_values['THW1H'] - $this->calculation_values['THW22']) * ($this->calculation_values['CHWGLY_SPHT1H'] + $this->calculation_values['CHWGLY_SPHT22']) * 0.5 / 4.187;
        $CC[1][8] = $this->calculation_values['UGENL'] * $this->calculation_values['AGENL'] * $this->calculation_values['LMTDGENL'];
        $CC[2][8] = ($this->calculation_values['GMED'] * $this->calculation_values['I9L']) + ($this->calculation_values['GREF2'] * $this->calculation_values['J9L']) - ($this->calculation_values['GDIL'] * $this->calculation_values['I5']); ;      //GENERATORL

        //$CC[0][9] = GCONCL * ($this->calculation_values['I9L'] - I8L);
        //$CC[1][9] = ULTHEL * ALTHEL * LMTDLTHEL;
        //$CC[2][9] = GDILL1 * (I11L - $this->calculation_values['I2']);                              //LTHEL



        for ($j = 0; $j < 9; $j++)
        {
            if ($CC[0][$j] <= $CC[1][$j] && $CC[0][$j] <= $CC[2][$j])
                $CC[3][$j] = $CC[0][$j];
            if ($CC[1][$j] <= $CC[0][$j] && $CC[1][$j] <= $CC[2][$j])
                $CC[3][$j] = $CC[1][$j];
            if ($CC[2][$j] <= $CC[0][$j] && $CC[2][$j] <= $CC[1][$j])
                $CC[3][$j] = $CC[2][$j];


            if ($CC[0][$j] >= $CC[1][$j] && $CC[0][$j] >= $CC[2][$j])
                $CC[4][$j] = $CC[0][$j];
            if ($CC[1][$j] >= $CC[0][$j] && $CC[1][$j] >= $CC[2][$j])
                $CC[4][$j] = $CC[1][$j];
            if ($CC[2][$j] >= $CC[0][$j] && $CC[2][$j] >= $CC[1][$j])
                $CC[4][$j] = $CC[2][$j];

            $CC[5][$j] = ($CC[4][$j] - $CC[3][$j]) / $CC[4][$j] * 100.0;
        }

        $HEATIN = ($this->calculation_values['TON'] * 3024) + $this->calculation_values['QGENH'] + $this->calculation_values['QGENL'];
        $HEATOUT = $this->calculation_values['GCWAH'] * $this->calculation_values['COGLY_ROW1'] * (($this->calculation_values['TCW2H'] * $this->calculation_values['COGLY_SPHT2H']) - ($this->calculation_values['TCW1H'] * $this->calculation_values['COGLY_SPHT11'])) / 4187 + $this->calculation_values['GCWC'] * $this->calculation_values['COGLY_ROW1'] * (($this->calculation_values['TCW4'] * $this->calculation_values['COGLY_SPHT3A']) - ($this->calculation_values['TCW4LA'] * $this->calculation_values['COGLY_SPHT4A'])) / 4187 + $this->calculation_values['GCWAL'] * $this->calculation_values['COGLY_ROW1'] * (($this->calculation_values['TCW2L'] * $this->calculation_values['COGLY_SPHT2L']) - ($this->calculation_values['TCW1L'] * $this->calculation_values['COGLY_SPHT1L'])) / 4187 + $this->calculation_values['GCWC'] * $this->calculation_values['COGLY_ROW1'] * (($this->calculation_values['TCW4L'] * $this->calculation_values['COGLY_SPHT4']) - ($this->calculation_values['TCW3A'] * $this->calculation_values['COGLY_SPHT3'])) / 4187;
        $this->calculation_values['HBERROR'] = ($HEATIN - $HEATOUT) * 100 / $HEATIN;
    }           


    public function RESULT_CALCULATE()
    {   
        $notes = array();
        $selection_notes = array();
        $this->calculation_values['Notes'] = "";
        $this->calculation_values['selection_notes'] = "";


        if (!$this->CONCHECK())
        {

            $this->calculation_values['Result'] = "FAILED";
            
            return false;
        }

        //Assign the output properties of chiller
        $this->HEATBALANCE();
        /***************************************************/
        $this->calculation_values['HeatInput'] = $this->calculation_values['GHOT'] * $this->calculation_values['HWGLY_ROWL1'] * (($this->calculation_values['HWGLY_SPHTL1'] + $this->calculation_values['HWGLY_SPHTL2']) / 2) * ($this->calculation_values['THW1'] - $this->calculation_values['THW4']);
        $this->calculation_values['HeatRejected'] = $this->calculation_values['TON'] * 3024 + ($this->calculation_values['GHOT'] * $this->calculation_values['HWGLY_ROWL1'] * (($this->calculation_values['HWGLY_SPHTL1'] + $this->calculation_values['HWGLY_SPHTL2']) / 2) * ($this->calculation_values['THW1'] - $this->calculation_values['THW4']));
        $this->calculation_values['COP'] = ($this->calculation_values['TON'] * 3024) / ($this->calculation_values['GHOT'] * $this->calculation_values['HWGLY_ROWL1'] * (($this->calculation_values['HWGLY_SPHTL1'] + $this->calculation_values['HWGLY_SPHTL2']) / 2) * ($this->calculation_values['THW1'] - $this->calculation_values['THW4']));

        $this->calculation_values['ChilledWaterFlow'] = $this->calculation_values['GCHW'];
        $this->calculation_values['CoolingWaterOutTemperature'] = $this->calculation_values['TCWA4'];
        $this->calculation_values['HotWaterOutletTemp'] = $this->calculation_values['THW4'];

        $this->calculation_values['EvaporatorPasses'] = $this->calculation_values['TEPH'] . "+" . $this->calculation_values['TEPL'];
        $this->calculation_values['AbsorberPasses'] = $this->calculation_values['TAP'] . "," . $this->calculation_values['TAP'];
        $this->calculation_values['GeneratorPasses'] = $this->calculation_values['TGP'] . "+" . $this->calculation_values['TGP'];
        if ($this->calculation_values['CWC'] == 2)
        {
            $this->calculation_values['CondenserPasses'] = $this->calculation_values['TCP'] . "," . $this->calculation_values['TCP'];
        }
        else
        {
            $this->calculation_values['CondenserPasses'] = $this->calculation_values['TCP']. "+".$this->calculation_values['TCP'];
        }

        $this->calculation_values['ChilledFrictionLoss'] = $this->calculation_values['FLE'];
        $this->calculation_values['ChilledPressureDrop'] = $this->calculation_values['PDE'];
        $this->calculation_values['CoolingFrictionLoss'] = $this->calculation_values['FLA'] + $this->calculation_values['FC4'];
        $this->calculation_values['CoolingPressureDrop'] = $this->calculation_values['PDA'];
        $this->calculation_values['HotWaterFrictionLoss'] = $this->calculation_values['GFL'];
        $this->calculation_values['HotWaterPressureDrop'] = $this->calculation_values['PDG'];

        $this->calculation_values['ModeBCoolingWaterInTemperature'] = "29.4";   //ntl: changed 19/7/11  

        $this->calculation_values['Result'] = "FAILED";
       
        if ($this->calculation_values['FLE'] > 10)
        {
            // array_push($notes,$this->notes['NOTES_PR_EVAP']);
            array_push($selection_notes,$this->notes['NOTES_PR_EVAP']);
        }
        if (($this->calculation_values['P3H'] - $this->calculation_values['P1H']) < 24 || ($this->calculation_values['P3L'] - $this->calculation_values['P1L']) < 24)
        {
            array_push($selection_notes,$this->notes['NOTES_LTHE_PRDROP']);
            $this->calculation_values['HHType'] = "NonStandard";
        }
        
        if ($this->calculation_values['THW1'] > 99 && ($this->calculation_values['region_type'] == 1 || $this->calculation_values['region_type'] == 2))
        {
            array_push($selection_notes,$this->notes['NOTES_PL_EC_CERTAPP']);
            array_push($selection_notes,$this->notes['NOTES_CONF_WT']);
        }
        else if ($this->calculation_values['THW1'] > 105 && $this->calculation_values['region_type'] == 2){
           array_push($selection_notes,$this->notes['NOTES_PL_EC_CERTAPP']);
           array_push($selection_notes,$this->notes['NOTES_CONF_WT']);
       }           

        if ($this->calculation_values['VELEVA'] == 1)
        {
            array_push($selection_notes,$this->notes['NOTES_EC_EVAP']);
            //notes.Add(LocalizedNote(NOTES_CON_DEL));
            $this->calculation_values['ECinEva'] = 1;
            //VELEVA=0;  SK 10TH APRIL
        }
        if (!$this->calculation_values['isStandard'])
        {
            array_push($selection_notes,$this->notes['NOTES_NSTD_TUBE_METAL']);
        }
        if ($this->calculation_values['TCHW2L'] < 4.49)
        {

            array_push($selection_notes,$this->notes['NOTES_COST_COW_SOV']);
        }
        if ($this->calculation_values['TCHW2L'] < 4.49)
        {
            array_push($selection_notes,$this->notes['NOTES_NONSTD_XSTK_MC']);
        }
        if ($this->calculation_values['VELEVA'] == 1)
        {
            //notes.Add(LocalizedNote(NOTES_EC_EVAP));

            array_push($selection_notes,$this->notes['NOTES_CON_DEL']);
            $this->calculation_values['ECinEva'] = 1;
            //VELEVA=0;  SK 10TH APRIL
        }              


        array_push($notes,$this->notes['NOTES_INSUL']);
        array_push($notes,$this->notes['NOTES_NON_INSUL']);
        array_push($notes,$this->notes['NOTES_ROOM_TEMP']);
        array_push($notes,$this->notes['NOTES_CUSTOM']);

        if ($this->calculation_values['THW1'] > 105)
        {
            array_push($selection_notes,$this->notes['NOTES_NONSTD_GEN_MET']);
        }


        if ($this->calculation_values['LMTDGENL'] < ($this->calculation_values['LMTDGENLA'] - 2))
        {
            if ($this->calculation_values['XCONC'] < ($this->calculation_values['KM'] - 0.8))
            {
                $this->calculation_values['Result'] = "OverDesigned";
            }
            if ($this->calculation_values['XCONC'] < ($this->calculation_values['KM'] - 0.4) && $this->calculation_values['XCONC'] > ($this->calculation_values['KM'] - 0.8))
            {
                array_push($selection_notes,$this->notes['NOTES_RED_COW']);
                $this->calculation_values['Result'] = "GoodSelection";
            }
            if ($this->calculation_values['XCONC'] < $this->calculation_values['KM'] && $this->calculation_values['XCONC'] > ($this->calculation_values['KM'] - 0.4))
            {
                $this->calculation_values['Result'] = "Optimal";
            }
        }
        else if ($this->calculation_values['LMTDGENL'] < ($this->calculation_values['LMTDGENLA'] - 1))
        {
            if ($this->calculation_values['XCONC'] < ($this->calculation_values['KM'] - 0.8))
            {
                array_push($selection_notes,$this->notes['NOTES_RED_COW']);
                $this->calculation_values['Result'] = "GoodSelection";
            }
            else
            {
                $this->calculation_values['Result'] = "Optimal";
            }
        }
        else
        {
            $this->calculation_values['Result'] = "Optimal";
        }

        $this->calculation_values['notes'] = $notes;
        $this->calculation_values['selection_notes'] = $selection_notes;
        
    }



    public function CONCHECK()
    {
        $this->CONCHECK1();


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
                if ($this->calculation_values['XCONC'] > $this->calculation_values['KM'])
                {
                    $this->calculation_values['Notes'] = $this->notes['NOTES_FAIL_CONC'];
                    return false;
                }
                else
                {

                    if ($this->calculation_values['LMTDGENL'] > $this->calculation_values['LMTDGENLA'])
                    {
                        $this->calculation_values['Notes'] = $this->notes['NOTES_HW_TEMP_LESS'];
                        return false;
                    }
                    else
                    {
                        if (($this->calculation_values['P3H'] - $this->calculation_values['P3L']) > 10)
                        {
                            $this->calculation_values['Notes'] = $this->notes['NOTES_PR_DIFF_HIGH'];
                            return false;
                        }
                        else
                        {
                            if (($this->calculation_values['P3L'] - $this->calculation_values['P3H']) > 1.5)
                            {
                                $this->calculation_values['Notes'] = $this->notes['NOTES_PR_DIFF_LOW'];
                                return false;
                            }
                            else
                            {
                                if ($this->calculation_values['TGP'] == 1 && $this->calculation_values['VG'] > 2.78)
                                {
                                    $this->calculation_values['Notes'] = $this->notes['NOTES_HW_FLO_LIM'];
                                    return false;
                                }
                                else
                                {
                                    if ($this->calculation_values['VG'] < 0.7)
                                    {
                                        $this->calculation_values['Notes'] = $this->notes['NOTES_HW_VELO'];
                                        return false;
                                    }
                                    else
                                    {
                                        if ($this->calculation_values['THW3'] < 55)
                                        {
                                            $this->calculation_values['Notes'] = $this->notes['NOTES_HWOUT_LESS'];
                                            return false;
                                        }

                                        else
                                        {
                                            if (($this->calculation_values['TCHW2L'] < 3.499 && $this->calculation_values['T1L'] < (-3.99)) || ($this->calculation_values['TCHW2L'] > 3.499 && $this->calculation_values['T1L'] < 0.499))
                                            {
                                                $this->calculation_values['Notes'] = $this->notes['NOTES_REF_TEMP'];
                                                return false;
                                            }
                                            else
                                            {
                                                if ($this->calculation_values['TON'] < ($this->calculation_values['MODEL'] * 0.35))
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


    public function LMTDCHECK()
    {
        if (!isset($this->calculation_values['LMTDEVAH']) || $this->calculation_values['LMTDEVAH'] < 0)
        {
            return false;
        }
        else if (!isset($this->calculation_values['LMTDEVAL']) || $this->calculation_values['LMTDEVAL'] < 0)
        {
            return false;
        }
        else if (!isset($this->calculation_values['LMTDABSH']) || $this->calculation_values['LMTDABSH'] < 0)
        {
            return false;
        }
        else if (!isset($this->calculation_values['LMTDABSL']) || $this->calculation_values['LMTDABSL'] < 0)
        {
            return false;
        }
        else if (!isset($this->calculation_values['LMTDCONH']) || $this->calculation_values['LMTDCONH'] < 0)
        {
            return false;
        }
        else if (!isset($this->calculation_values['LMTDCONL']) || $this->calculation_values['LMTDCONL'] < 0)
        {
            return false;
        }
        else if (!isset($this->calculation_values['LMTDLTHE']) || $this->calculation_values['LMTDLTHE'] < 0)
        {
            return false;
        }
        else if (!isset($this->calculation_values['LMTDGENH']) || $this->calculation_values['LMTDGENH'] < 0)
        {
            return false;
        }
        else if (!isset($this->calculation_values['LMTDGENL']) || $this->calculation_values['LMTDGENL'] < 0)
        {
            return false;
        }
        else if (!isset($this->calculation_values['LMTDGENLA']) || $this->calculation_values['LMTDGENLA'] < 0)
        {
            return false;
        }
        else
        {
            return true;
        }

    }


    public function HCAP()
    {

        $this->calculation_values['HIGHCAP'] = 0;
        
        if ($this->calculation_values['MODEL'] == 185)
        {
            $this->calculation_values['HIGHCAP'] = 320;
        }

        if ($this->calculation_values['MODEL'] == 210)
        {
            $this->calculation_values['HIGHCAP'] = 320;
        }
        else if ($this->calculation_values['MODEL'] == 245)
        {
            $this->calculation_values['HIGHCAP'] = 384;
        }
        else if ($this->calculation_values['MODEL'] == 270)
        {
            $this->calculation_values['HIGHCAP'] = 429;
        }
        else if ($this->calculation_values['MODEL'] == 310)
        {
            $this->calculation_values['HIGHCAP'] = 488;
        }
        else if ($this->calculation_values['MODEL'] == 340)
        {
            $this->calculation_values['HIGHCAP'] = 533;
        }
        else if ($this->calculation_values['MODEL'] == 380)
        {
            $this->calculation_values['HIGHCAP'] = 601;
        }
        else if ($this->calculation_values['MODEL'] == 425)
        {
            $this->calculation_values['HIGHCAP'] = 662;
        }
        else if ($this->calculation_values['MODEL'] == 485)
        {
            $this->calculation_values['HIGHCAP'] = 747;
        }
        else if ($this->calculation_values['MODEL'] == 540)
        {
            $this->calculation_values['HIGHCAP'] = 814;
        }
        else if ($this->calculation_values['MODEL'] == 630)
        {
            $this->calculation_values['HIGHCAP'] = 985;
        }
        else if ($this->calculation_values['MODEL'] == 690)
        {
            $this->calculation_values['HIGHCAP'] = 1073;
        }
        else if ($this->calculation_values['MODEL'] == 730)
        {
            $this->calculation_values['HIGHCAP'] = 1267;
        }
        else if ($this->calculation_values['MODEL'] == 780)
        {
            $this->calculation_values['HIGHCAP'] = 1267;
        }
        else if ($this->calculation_values['MODEL'] == 850)
        {
            $this->calculation_values['HIGHCAP'] = 1365;
        }
        else if ($this->calculation_values['MODEL'] == 950)
        {
            $this->calculation_values['HIGHCAP'] = 1495;
        }
        else if ($this->calculation_values['MODEL'] == 1050)
        {
            $this->calculation_values['HIGHCAP'] = 1674;
        }
        else if ($this->calculation_values['MODEL'] == 1150)
        {
            $this->calculation_values['HIGHCAP'] = 1811;
        }
        else if ($this->calculation_values['MODEL'] == 1260)
        {
            $this->calculation_values['HIGHCAP'] = 1999;
        }
        else if ($this->calculation_values['MODEL'] == 1380)
        {
            $this->calculation_values['HIGHCAP'] = 2165;
        }
        else
        {
            $this->calculation_values['HIGHCAP'] = 2165;
        }           

        if ($this->calculation_values['TON'] > $this->calculation_values['HIGHCAP'])
            return false;
        else
            return true;
    }

    public function HEATBALANCE()
    {
        $ii = 0;
        $herr = array();
        $tcwa4 = array();
        $vam_base = new VamBaseController();
        
        $ii = 1;
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
            $this->calculation_values['TCWM'] = ($this->calculation_values['TCW11'] + $this->calculation_values['TCWA4']) / 2;

            if ($this->calculation_values['GL'] == 2)
            {
                $this->calculation_values['COGLY_SPHTA1'] = $vam_base->EG_SPHT($this->calculation_values['TCW11'], $this->calculation_values['COGLY']) * 1000 / 4187;
                $this->calculation_values['COGLY_SPHTA4'] = $vam_base->EG_SPHT($this->calculation_values['TCWA4'], $this->calculation_values['COGLY']) * 1000 / 4187;
            }
            else
            {
                $this->calculation_values['COGLY_SPHTA1'] = $vam_base->PG_SPHT($this->calculation_values['TCW11'], $this->calculation_values['COGLY']) * 1000 / 4187;
                $this->calculation_values['COGLY_SPHTA4'] = $vam_base->PG_SPHT($this->calculation_values['TCWA4'], $this->calculation_values['COGLY']) * 1000 / 4187;
            }

            $this->calculation_values['QCWR'] = $this->calculation_values['GCW'] * $this->calculation_values['COGLY_ROW1'] * ($this->calculation_values['COGLY_SPHTA1'] + $this->calculation_values['COGLY_SPHTA4']) * 0.5 * ($this->calculation_values['TCWA4'] - $this->calculation_values['TCW11']);

            if ($this->calculation_values['GL'] == 2)
            {
                $this->calculation_values['HWGLY_SPHTL1'] = $vam_base->EG_SPHT($this->calculation_values['THW1'], $this->calculation_values['HWGLY']) / 4.187;
                $this->calculation_values['HWGLY_SPHTL2'] = $vam_base->EG_SPHT($this->calculation_values['THW4'], $this->calculation_values['HWGLY']) / 4.187;
                $this->calculation_values['HWGLY_ROWL1'] = $vam_base->EG_ROW($this->calculation_values['THW1'], $this->calculation_values['HWGLY']);
            }
            else
            {
                $this->calculation_values['HWGLY_SPHTL1'] = $vam_base->PG_SPHT($this->calculation_values['THW1'], $this->calculation_values['HWGLY']) / 4.187;
                $this->calculation_values['HWGLY_SPHTL2'] = $vam_base->PG_SPHT($this->calculation_values['THW4'], $this->calculation_values['HWGLY']) / 4.187;
                $this->calculation_values['HWGLY_ROWL1'] = $vam_base->PG_ROW($this->calculation_values['THW1'], $this->calculation_values['HWGLY']);
            }
            //$this->calculation_values['GHOT'] = GHW;
            $this->calculation_values['QINPUT'] = ($this->calculation_values['TON'] * 3024) + ($this->calculation_values['GHOT'] * $this->calculation_values['HWGLY_ROWL1'] * (($this->calculation_values['HWGLY_SPHTL1'] + $this->calculation_values['HWGLY_SPHTL2']) / 2) * ($this->calculation_values['THW1'] - $this->calculation_values['THW4']));
            $herr[$ii] = ($this->calculation_values['QINPUT'] - $this->calculation_values['QCWR']) * 100 / $this->calculation_values['QINPUT'];
            $ii++;
        }
        $this->calculation_values['COP'] = ($this->calculation_values['TON'] * 3024) / ($this->calculation_values['GHOT'] * $this->calculation_values['HWGLY_ROWL1'] * (($this->calculation_values['HWGLY_SPHTL1'] + $this->calculation_values['HWGLY_SPHTL2']) / 2) * ($this->calculation_values['THW1'] - $this->calculation_values['THW4']));

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
            'cooling_water_in_max_range',
            'cooling_water_in_min_range',
            'hot_water_in',
            'how_water_temp_min_range',
            'how_water_temp_max_range',
            'generator_tube_list',
            'hot_water_flow',
            'USA_capacity',
            'USA_chilled_water_in',
            'USA_chilled_water_out',
            'USA_cooling_water_in',
            'USA_cooling_water_flow',
            'USA_hot_water_in',
            'USA_hot_water_flow'
            ]);

        $region_type = Auth::user()->region_type;

        if($region_type == 2)
            $region_name = Auth::user()->region->name;
        else
            $region_name = '';


        $version = DB::table('versions')->orderBy('id', 'desc')->first();
        $version_date = date('d-M-Y', strtotime($version->created_at));
        
        $standard_values = array('evaporator_thickness' => 0,'absorber_thickness' => 0,'condenser_thickness' => 0,'evaporator_thickness_min_range' => 0,'evaporator_thickness_max_range' => 0,'absorber_thickness_min_range' => 0,'absorber_thickness_max_range' => 0,'condenser_thickness_min_range' => 0,'condenser_thickness_max_range' => 0,'fouling_chilled_water_value' => 0,'fouling_cooling_water_value' => 0,'fouling_hot_water_value' => 0,'evaporator_thickness_change' => 1,'absorber_thickness_change' => 1,'condenser_thickness_change' => 1,'fouling_chilled_water_checked' => 0,'fouling_cooling_water_checked' => 0,'fouling_hot_water_checked' => 0,'fouling_chilled_water_disabled' => 1,'fouling_cooling_water_disabled' => 1,'fouling_hot_water_disabled' => 1,'fouling_chilled_water_value_disabled' => 1,'fouling_cooling_water_value_disabled' => 1,'fouling_hot_water_value_disabled' => 1,'region_name'=>$region_name,'region_type'=>$region_type,'version' => $version->version,'version_date' => $version_date);


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
            'min_chilled_water_out',
            'TCPMAX',
            'TAPMAX'
            
        ]);

        return $calculation_values;
    }


    public function testingL5Calculation($datas){
        
        $this->model_values = $datas;

        $vam_base = new VamBaseController();
        $this->notes = $vam_base->getNotesError();

        $this->model_values['metallurgy_standard'] = $vam_base->getBoolean($this->model_values['metallurgy_standard']);
        $this->CWFLOW();


        $this->calculation_values['msg'] = '';
       try {
           $this->WATERPROP();

           $velocity_status = $this->VELOCITY();

       } 
       catch (\Exception $e) {
            $this->calculation_values['msg'] = $this->notes['NOTES_ERROR'];
          
       }
       

       if(isset($velocity_status['status']) && !$velocity_status['status']){
            $this->calculation_values['msg'] = $velocity_status['msg'];
       }



       try {
           $this->CALCULATIONS();

           $this->CONVERGENCE();

           $this->RESULT_CALCULATE();
       
           $this->loadSpecSheetData();
       }
       catch (\Exception $e) {

            $this->calculation_values['msg'] = $this->notes['NOTES_ERROR'];
          
       }

        return $this->calculation_values;
        // return response()->json(['status'=>true,'msg'=>'Ajax Datas','calculation_values'=>$this->calculation_values]);

    
    }

}
