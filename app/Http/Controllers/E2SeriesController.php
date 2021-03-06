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

class E2SeriesController extends Controller
{
    
    private $model_values;
    private $model_code = "D_E2";
    private $calculation_values;
    private $notes;
    private $changed_value;

    public function getE2Series(){

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

        if($chiller_form_values['region_type'] == 2 || $chiller_form_values['region_type'] == 3)
        {
            $chiller_form_values['capacity'] =  $chiller_form_values['USA_capacity'];
            $chiller_form_values['chilled_water_in'] =  $chiller_form_values['USA_chilled_water_in'];
            $chiller_form_values['chilled_water_out'] =  $chiller_form_values['USA_chilled_water_out'];
            $chiller_form_values['cooling_water_in'] =  $chiller_form_values['USA_cooling_water_in'];
            $chiller_form_values['cooling_water_flow'] =  $chiller_form_values['USA_cooling_water_flow'];

            if($chiller_form_values['region_type'] == 2){
                $chiller_form_values['fouling_factor']="standard";
                // $chiller_form_values['fouling_chilled_water_value'] =  $chiller_form_values['fouling_ari_chilled'];
                // $chiller_form_values['fouling_cooling_water_value'] =  $chiller_form_values['fouling_ari_cooling'];
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
  
        $regions = Region::all();
        $vam_base = new VamBaseController();
        $language_datas = $vam_base->getLanguageDatas();
        $units_data = $vam_base->getUnitsData();

        $calculator_name = DB::table('calculators')->where('code', $this->model_code)->first();
        $calculator_name = $calculator_name->display_name;
        
        return view('e2_series')->with(['default_values'=>$converted_values,'unit_set'=>$unit_set,'units_data'=>$units_data,'evaporator_options'=>$evaporator_options,'absorber_options'=>$absorber_options,'condenser_options'=>$condenser_options,'chiller_metallurgy_options'=>$chiller_metallurgy_options,'regions'=>$regions,'language_datas'=>$language_datas,'calculator_name'=>$calculator_name]);
                           
    }

    public function postAjaxE2(Request $request){

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

    public function postResetE2(Request $request){
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

            if($chiller_form_values['region_type'] == 2)
                $chiller_form_values['fouling_factor']="standard";
            else
                $chiller_form_values['fouling_factor']="standard";

        }


        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$this->model_code)->where('min_model','<=',$model_number)->where('max_model','>',$model_number)->first();
        //$queries = DB::getQueryLog();


        $chiller_options = $chiller_metallurgy_options->chillerOptions;
        
        $evaporator_options = $chiller_options->where('type', 'eva');
        $absorber_options = $chiller_options->where('type', 'abs');
        $condenser_options = $chiller_options->where('type', 'con');


        $unit_set_id = Auth::user()->unit_set_id;
        $unit_set = UnitSet::find($unit_set_id);

            
        $this->model_values = $chiller_form_values;

        $this->castToBoolean();
        $range_calculation = $this->RANGECAL();

        $min_chilled_water_out = Auth::user()->min_chilled_water_out;
        if($min_chilled_water_out > $this->model_values['min_chilled_water_out'])
            $this->model_values['min_chilled_water_out'] = $min_chilled_water_out;
        

        $unit_conversions = new UnitConversionController;
        $converted_values = $unit_conversions->formUnitConversion($this->model_values,$this->model_code);
    
        

        return response()->json(['status'=>true,'msg'=>'Ajax Datas','model_values'=>$converted_values,'evaporator_options'=>$evaporator_options,'absorber_options'=>$absorber_options,'condenser_options'=>$condenser_options,'chiller_metallurgy_options'=>$chiller_metallurgy_options]);

    }

    public function postE2(Request $request){

        $model_values = $request->input('values');
        $name = $request->input('name',"");
        $project = $request->input('project',"");
        $phone = $request->input('phone',"");

        // ini_set('memory_limit' ,'-1');
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

        $this->updateInputs();


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
        
        $view = view("reports.e2_report", ['name' => $name,'phone' => $phone,'project' => $project,'calculation_values' => $calculation_values,'evaporator_name' => $evaporator_name,'absorber_name' => $absorber_name,'condenser_name' => $condenser_name,'unit_set' => $unit_set,'units_data' => $units_data,'language_datas' => $language_datas])->render();

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

        $redirect_url = route('download.e2report', ['user_report_id' => $user_report->id,'type' => $report_type]);
        
        return response()->json(['status'=>true,'msg'=>'Ajax Datas','redirect_url'=>$redirect_url]);
        
    }

    public function downloadReport($user_report_id,$type){

        $user_report = UserReport::find($user_report_id);
        if(!$user_report){
            return response()->json(['status'=>false,'msg'=>'Invalid Report']);
        }

        if($type == 'save_word'){
            $report_controller = new ReportController();
            $file_name = $report_controller->wordFormatE2($user_report_id,$this->model_code);

            // $file_name = "S2-Steam-Fired-Series-".Auth::user()->id.".docx";
            return response()->download(storage_path($file_name));
        }

        $calculation_values = json_decode($user_report->calculation_values,true);
        
        $name = $user_report->name;
        $project = $user_report->project;
        $phone = $user_report->phone;

        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$this->model_code)
                                        ->where('min_model','<=',(int)$calculation_values['MODEL'])->where('max_model','>',(int)$calculation_values['MODEL'])->first();

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




        $pdf = PDF::loadView('reports.report_e2_pdf', ['name' => $name,'phone' => $phone,'project' => $project,'calculation_values' => $calculation_values,'evaporator_name' => $evaporator_name,'absorber_name' => $absorber_name,'condenser_name' => $condenser_name,'unit_set' => $unit_set,'units_data' => $units_data,'language_datas' => $language_datas,'language' => $language]);

        return $pdf->download('e2.pdf');

    }

    public function validateChillerAttribute($attribute){

        switch (strtoupper($attribute))
        {
            case "MODEL_NUMBER":
                // $this->modulNumberDoubleEffectS2();
                $this->model_values['metallurgy_standard'] = true;
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
            case "ECONOMIZER":
                if($this->model_values['engine_type'] == 'gas'){
                    if($this->model_values['economizer'] == 'yes'){
                        $this->model_values['gas_out_min'] = 140;
                    }
                    else{
                        $this->model_values['gas_out_min'] = 170;
                    }
                }
                else{
                    $this->model_values['gas_out_min'] = 190;
                }
            break;
            case "ENGINE_TYPE":
                if($this->model_values['engine_type'] == 'gas'){
                    $this->model_values['gas_out_min'] = 170;
                }
                else{
                    $this->model_values['gas_out_min'] = 190;
                }
            break;
            case "EXHAUST_GAS_IN":
                if ($this->model_values['economizer'] == 'yes')
                {
                    $this->model_values['gas_out_min'] = 140;
                }
                else if ($this->model_values['exhaust_gas_in'] > 399.99)
                {
                    $this->model_values['gas_out_min'] = 170;
                }
                else if ($this->model_values['exhaust_gas_in'] > 349.99)
                {
                    $this->model_values['gas_out_min'] = 175;
                }
                else
                {
                    $this->model_values['gas_out_min'] = 180;
                }//Minimum exhaust temp out reduced to 170 but spec sheet to remain at 180

                if ($this->model_values['exhaust_gas_in'] > $this->model_values['gas_in_max'])
                {
                    return array('status' => false,'msg' => $this->notes['NOTES_EG_INT_MAX']);
                }
                if ($this->model_values['exhaust_gas_in'] < $this->model_values['gas_in_min'])
                {
                    return array('status' => false,'msg' => $this->notes['NOTES_EG_INT_MIN']);
                }
            break;
            case "EXHAUST_GAS_OUT":
                if ($this->model_values['exhaust_gas_out'] < $this->model_values['gas_out_min'])
                {
                    return array('status' => false,'msg' => $this->notes['NOTES_EGT_MIN']);
                }
                if ($this->model_values['exhaust_gas_out'] >= $this->model_values['exhaust_gas_in'])
                {
                    return array('status' => false,'msg' => $this->notes['NOTES_EGT_IT_OT']);
                }
            break;
            case "EXHAUST_GAS_FLOW":
                if ($this->model_values['gas_flow'] == 0)
                {
                    return array('status' => false,'msg' => $this->notes['NOTES_EG_F']);
                }
                if ($this->model_values['gas_flow_load'] == 0)
                {
                    return array('status' => false,'msg' => $this->notes['NOTES_EG_F_FL']);
                }
                if ($this->model_values['gas_flow_load'] < $this->model_values['gas_flow']) // else stmt removed.
                {
                    //chiller.ExhaustGasFlowFullLoad = chiller.ExhaustGasFlowRate;
                    return array('status' => false,'msg' => $this->notes['NOTES_EG_F1_FL']);
                }
                if ($this->model_values['design_load'] == 0)
                {
                    return array('status' => false,'msg' => $this->notes['NOTES_DSL']);
                }
                if ($this->model_values['pressure_drop'] == 0)
                {
                    return array('status' => false,'msg' => $this->notes['NOTES_PR_DRP']);
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
        $range_calculation = $this->RANGECAL();
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

        // $chilled_water_out_validation = $this->chilledWaterValidating();
        // if(!$chilled_water_out_validation['status']){
        //     return array('status'=>false,'msg'=>$chilled_water_out_validation['msg']);
        // }

                  
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
                
        

        // "ECONOMIZER":
        if($this->model_values['engine_type'] == 'gas'){
            if($this->model_values['economizer'] == 'yes'){
                $this->model_values['gas_out_min'] = 140;
            }
            else{
                $this->model_values['gas_out_min'] = 170;
            }
        }
        else{
            $this->model_values['gas_out_min'] = 190;
        }

        // "EXHAUST_GAS_IN":

        if ($this->model_values['exhaust_gas_in'] > $this->model_values['gas_in_max'])
        {
            return array('status' => false,'msg' => $this->notes['NOTES_EG_INT_MAX']);
        }
        if ($this->model_values['exhaust_gas_in'] < $this->model_values['gas_in_min'])
        {
            return array('status' => false,'msg' => $this->notes['NOTES_EG_INT_MIN']);
        }

        // "EXHAUST_GAS_OUT":
        if ($this->model_values['exhaust_gas_out'] < $this->model_values['gas_out_min'])
        {
            return array('status' => false,'msg' => $this->notes['NOTES_EGT_MIN']);
        }
        if ($this->model_values['exhaust_gas_out'] >= $this->model_values['exhaust_gas_in'])
        {
            return array('status' => false,'msg' => $this->notes['NOTES_EGT_IT_OT']);
        }

        // "EXHAUST_GAS_FLOW":
        if ($this->model_values['gas_flow'] == 0)
        {
            return array('status' => false,'msg' => $this->notes['NOTES_EG_F']);
        }
        if ($this->model_values['gas_flow_load'] == 0)
        {
            return array('status' => false,'msg' => $this->notes['NOTES_EG_F_FL']);
        }
        if ($this->model_values['gas_flow_load'] < $this->model_values['gas_flow']) // else stmt removed.
        {
            //chiller.ExhaustGasFlowFullLoad = chiller.ExhaustGasFlowRate;
            return array('status' => false,'msg' => $this->notes['NOTES_EG_F1_FL']);
        }
        if ($this->model_values['design_load'] == 0)
        {
            return array('status' => false,'msg' => $this->notes['NOTES_DSL']);
        }
        if ($this->model_values['pressure_drop'] == 0)
        {
            return array('status' => false,'msg' => $this->notes['NOTES_PR_DRP']);
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
            $GCWPMAX = $APA * 4.2 * 3600;
        }

        if ($FMAX1 > $GCWPMAX)
        {
            $FMAX1 = $GCWPMAX;
        }
        //if ($MODEL < 350 && $GCWCMAX < $$FMAX1)
        //{
        //    $$FMAX1 = $GCWCMAX;
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
        $this->calculation_values['version'] = $this->model_values['version'];
        $this->calculation_values['version_date'] = $this->model_values['version_date'];


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

        $this->calculation_values['GLL'] = $this->model_values['glycol_selected']; 
        $this->calculation_values['CHGLY'] = $this->model_values['glycol_chilled_water']; 
        $this->calculation_values['COGLY'] = $this->model_values['glycol_cooling_water']; 
        $this->calculation_values['TCHW11'] = $this->model_values['chilled_water_in']; 
        $this->calculation_values['TCHW12'] = $this->model_values['chilled_water_out']; 
        $this->calculation_values['GCW'] = $this->model_values['cooling_water_flow']; 
        $this->calculation_values['TEXH1'] = $this->model_values['exhaust_gas_in']; 
        $this->calculation_values['TEXH2'] = $this->model_values['exhaust_gas_out']; 
        $this->calculation_values['TEXHOUT'] = $this->model_values['exhaust_gas_out']; 
        $this->calculation_values['engine_type'] = $this->model_values['engine_type']; 


        if ($this->model_values['engine_type'] == 'gas')
        {
            $this->calculation_values['CPEX1'] = (0.00005625 * $this->calculation_values['TEXH1']) + 0.251875;
            $this->calculation_values['CPEX2'] = (0.00005625 * $this->calculation_values['TEXH2']) + 0.251875;
        }
        else
        {
            $this->calculation_values['CPEX1'] = 0.256;
            $this->calculation_values['CPEX2'] = 0.256;
        }

        $this->calculation_values['GEXHAUST'] = $this->model_values['gas_flow']; 
        $this->calculation_values['LOAD'] = $this->model_values['gas_flow_load']; 
        $this->calculation_values['PERCENT'] = $this->model_values['design_load']; 
        $this->calculation_values['FURNPRDROP'] = $this->model_values['pressure_drop']; 
        $this->calculation_values['ECO'] = $this->model_values['economizer']; 

        $this->calculation_values['isStandard'] = $this->model_values['metallurgy_standard']; 

        $pid_ft1 = $vam_base->PIPE_ID($this->calculation_values['PNB1']);
        $this->calculation_values['PIDE1'] = $pid_ft1['PID'];
        $this->calculation_values['FT1'] = $pid_ft1['FT'];

        $pid_ft2 = $vam_base->PIPE_ID($this->calculation_values['PNB2']);
        $this->calculation_values['PIDE2'] = $pid_ft2['PID'];
        $this->calculation_values['FT2'] = $pid_ft2['FT'];

        $pid_ft = $vam_base->PIPE_ID($this->calculation_values['PNB']);
        $this->calculation_values['PIDA'] = $pid_ft['PID'];
        $this->calculation_values['FT'] = $pid_ft['FT'];

        // Standard Calculation Values
        $this->calculation_values['CoolingWaterOutTemperature'] = 0;
        $this->calculation_values['ChilledWaterFlow'] = 0;
        $this->calculation_values['BypassFlow'] = 0;
        $this->calculation_values['ChilledFrictionLoss'] = 0;
        $this->calculation_values['CoolingFrictionLoss'] = 0;
        $this->calculation_values['SteamConsumption'] = 0;
        $this->calculation_values['ActExhaustGasTempOut'] = 0;
        $this->calculation_values['ExhaustConnectionDiameter'] = 0;
        $this->calculation_values['AvgExhGasCp'] = 0;
        $this->calculation_values['TCWS'] = 0;

        if($this->calculation_values['region_type'] == 1){
            $this->calculation_values['SS_FACTOR'] = 1;
        }
        else{
            $this->calculation_values['SS_FACTOR'] = 0.96;
        }

        $this->DATA();

        $this->THICKNESS();
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

    public function loadSpecSheetData(){
        $model_number = floatval($this->calculation_values['MODEL']);

         if($this->calculation_values['region_type'] == 2)
        {
            $this->calculation_values['TotalPowerConsumption'] = (1.732 * 460 * ($this->calculation_values['USA_AbsorbentPumpMotorAmp'] + $this->calculation_values['USA_RefrigerantPumpMotorAmp'] + $this->calculation_values['USA_PurgePumpMotorAmp']) / 1000) + 1;

            $this->calculation_values['PowerSupply'] = "460 V( ??10%), 60 Hz (??5%), 3 Phase+N";
        }
        else
        {
            $this->calculation_values['TotalPowerConsumption'] = (1.732 * 415 * ($this->calculation_values['AbsorbentPumpMotorAmp'] + $this->calculation_values['RefrigerantPumpMotorAmp'] + $this->calculation_values['PurgePumpMotorAmp']) / 1000) + 1;

            $this->calculation_values['PowerSupply'] = "415 V( ??10%), 50 Hz (??5%), 3 Phase+N";

        }
        
        switch ($model_number) {
            case 60:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC E2 M1";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC E2 M1";
                }

                break;

            case 75:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC E2 M2";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC E2 M2";
                }

                break;    

            case 90:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC E2 N1";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC E2 N1";
                }

                break;     

            case 110:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC E2 N2";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC E2 N2";
                }

                break;     

            case 150:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC E2 N3";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC E2 N3";
                }

                break;      

            case 175:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC E2 N4";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC E2 N4";
                }

                break;     


            case 210:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC E2 P1";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC E2 P1";
                }

                break;     

            case 250:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC E2 P2";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC E2 P2";
                }

                break; 

            case 310:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC E2 D3";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC E2 D3";
                }

                break;

            case 350:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC E2 D4";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC E2 D4";
                }

                break;     

            case 410:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC E2 E1";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC E2 E1";
                }

                break;               

            case 470:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC E2 E2";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC E2 E2";
                }

                break;       

            case 530:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC E2 E3";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC E2 E3";
                }

                break;   

            case 580:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC E2 E4";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC E2 E4";
                }

                break;     

            case 630:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC E2 E5";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC E2 E5";
                }

                break;  

            case 710:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC E2 E6";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC E2 E6";
                }

                break;  

            case 760:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC E2 F1";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC E2 F1";
                }

                break;  

            case 810:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC E2 F2";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC E2 F2";
                }

                break;  

            case 900:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC E2 F3";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC E2 F3";
                }

                break; 

            case 1010:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC E2 G1";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC E2 G1";
                }

                break;

            case 1130:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC E2 G2";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC E2 G2";
                }

                break;   

            case 1260:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC E2 G3";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC E2 G3";
                }

                break;      

            case 1380:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC E2 G4";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC E2 G4";
                }

                break; 

            case 1560:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC E2 G5";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC E2 G5";
                }

                break;   

            case 1690:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC E2 G6";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC E2 G6";
                }

                break;     

            case 1890:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC E2 H1";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC E2 H1";
                }

                break; 

            case 2130:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC E2 H2";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC E2 H2";
                }

                break;   

            case 2270:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC E2 J1";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC E2 J1";
                }

                break; 

            case 2560:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC E2 J2";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC E2 J2";
                }

                break;            


            default:
                # code...
                break;
        }
    }


    private function DATA()
    {

        if ($this->calculation_values['TCW11'] <= 32.0)
            $this->calculation_values['TCWA'] = $this->calculation_values['TCW11'];
        else
            $this->calculation_values['TCWA'] = 32.0;



        $this->calculation_values['SFACTOR'] = $this->calculation_values['A_SFACTOR'] - ($this->calculation_values['B_SFACTOR'] * $this->calculation_values['TCWA']);

        if($this->calculation_values['MODEL'] == 60 || $this->calculation_values['MODEL'] == 75){
            $this->calculation_values['SFACTOR'] = $this->calculation_values['SFACTOR'] * 1.025;
        }
        elseif ($this->calculation_values['MODEL'] == 110 || $this->calculation_values['MODEL'] == 150 || $this->calculation_values['MODEL'] == 175 || $this->calculation_values['MODEL'] == 210 || $this->calculation_values['MODEL'] == 250 || $this->calculation_values['MODEL'] == 90) {
            $this->calculation_values['SFACTOR'] = $this->calculation_values['SFACTOR'] * 1.015;
        }
        else{
            $this->calculation_values['SFACTOR'] = $this->calculation_values['SFACTOR'] * 1;
        }

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

        if ($this->calculation_values['TU2'] == 2 || $this->calculation_values['TU2'] == 7)
            $this->calculation_values['KEVA'] = 1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 340000.0));
        if ($this->calculation_values['TU2'] == 1 || $this->calculation_values['TU2'] == 6)
            $this->calculation_values['KEVA'] = 1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 37000.0));
        if ($this->calculation_values['TU2'] == 4)
            $this->calculation_values['KEVA'] = (1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 21000.0))) * $this->calculation_values['SS_FACTOR'];
        if ($this->calculation_values['TU2'] == 3)
            $this->calculation_values['KEVA'] = 1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 21000.0)) * $this->calculation_values['SS_FACTOR'];          //Changed to $this->calculation_values['KEVA1'] from 1600 on 06/11/2017 as tube metallurgy is changed
        if ($this->calculation_values['TU2'] == 8 || $this->calculation_values['TU2'] == 9)
            $this->calculation_values['KEVA'] = (1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 15000.0))) * 0.93;
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

            if ($this->calculation_values['TU5'] == 4)
                $this->calculation_values['KABS'] = (1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 15000))) * 0.93;
            else
            {
                $this->calculation_values['KABS1'] = 1240;
                // if ($this->calculation_values['TU5'] == 3)
                //     $this->calculation_values['KABS'] = 1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 37000));
                // if ($this->calculation_values['TU5'] == 4)
                //     $this->calculation_values['KABS'] = 1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 340000));
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
        else if ($this->calculation_values['TV5'] == 6)
            $this->calculation_values['KCON'] = 1 / ((1 / $this->calculation_values['KCON1']) + ($this->calculation_values['TV6'] / 15000)) * 0.95; 
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


        if ($this->calculation_values['TU2'] < 2.1 || $this->calculation_values['TU2'] == 4 || $this->calculation_values['TU2'] == 9)
            $this->calculation_values['IDE'] = $this->calculation_values['ODE'] - ((2.0 * ($this->calculation_values['THE'] + 0.1)) / 1000.0);
        else
            $this->calculation_values['IDE'] = $this->calculation_values['ODE'] - ((2.0 * $this->calculation_values['THE']) / 1000.0);

        if ($this->calculation_values['TU5'] < 2.1 || $this->calculation_values['TU5'] == 6 || $this->calculation_values['TU5'] == 4)
            $this->calculation_values['IDA'] = $this->calculation_values['ODA'] - ((2.0 * ($this->calculation_values['THA'] + 0.1)) / 1000.0);
        else
            $this->calculation_values['IDA'] = $this->calculation_values['ODA'] - ((2.0 * $this->calculation_values['THA']) / 1000.0);

        if ($this->calculation_values['TV5'] == 1 || $this->calculation_values['TV5'] == 2 || $this->calculation_values['TV5'] == 0 || $this->calculation_values['TV5'] == 4 || $this->calculation_values['TV5'] == 6)
            $this->calculation_values['IDC'] = $this->calculation_values['ODC'] - ((2.0 * ($this->calculation_values['THC'] + 0.1)) / 1000.0);
        else
            $this->calculation_values['IDC'] = $this->calculation_values['ODC'] - ((2.0 * $this->calculation_values['THC']) / 1000.0);

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
                if ($this->calculation_values['VEA'] < $this->calculation_values['VEMIN1'])
                    $this->calculation_values['TP'] = $this->calculation_values['TP'] + 1;
            } while ($this->calculation_values['VEA'] < $this->calculation_values['VEMIN1'] && $this->calculation_values['TP'] <= $this->calculation_values['TEPMAX']);
            if ($this->calculation_values['TP'] > $this->calculation_values['TEPMAX'])
            {
                $this->calculation_values['TP'] = $this->calculation_values['TEPMAX'];
                $this->calculation_values['VEA'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * (($this->calculation_values['TNEV'] / 2) / $this->calculation_values['TP']));

                $VEMIN2 = $this->calculation_values['VEMIN1'] - 0.01;
                if ($this->calculation_values['VEA'] < $VEMIN2)
                {
                    return  array('status' => false,'msg' => $this->notes['NOTES_CHW_VELO']);
                }
            }
            if ($this->calculation_values['VEA'] > $this->calculation_values['VEMAX'])                        // 06/11/2017
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
                if ($this->calculation_values['VEA'] < $this->calculation_values['VEMIN'])
                    $this->calculation_values['TP'] = $this->calculation_values['TP'] + 1;
            } while ($this->calculation_values['VEA'] < $this->calculation_values['VEMIN'] && $this->calculation_values['TP'] <= $this->calculation_values['TEPMAX']);
            if ($this->calculation_values['TP'] > $this->calculation_values['TEPMAX'])
            {
                $this->calculation_values['TP'] = $this->calculation_values['TEPMAX'];
                $this->calculation_values['VEA'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * (($this->calculation_values['TNEV'] / 2) / $this->calculation_values['TP']));
                if ($this->calculation_values['VEA'] < ($this->calculation_values['VEMIN'] - 0.01))
                {
                    return  array('status' => false,'msg' => $this->notes['NOTES_CHW_VELO']);
                }
            }

            if ($this->calculation_values['VEA'] > $this->calculation_values['VEMAX'])                   // 14 FEB 2012
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

        // PR_DROP_DATA();
        $this->PR_DROP_CHILL();

        if ($this->calculation_values['FLE'] > 12)
        {
            if ($this->calculation_values['TU2'] == 3 || $this->calculation_values['TU2'] == 6 || $this->calculation_values['TU2'] == 7 || $this->calculation_values['TU2'] == 8 && $this->calculation_values['VELEVA'] == 0)
            {
               $this->calculation_values['VEMIN'] = 0.45;
            }
            else
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
    

    
    public function CALCULATIONS()
    {
        $this->calculation_values['HHType'] = "Standard";

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
                $this->calculation_values['TAPH'] = floor($this->calculation_values['TAP'] / 2);
                $this->calculation_values['TAPL'] = floor($this->calculation_values['TAP'] / 2);
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
                $this->calculation_values['UABSL'] = $this->calculation_values['UABSL'] * 0.93;
                $this->calculation_values['UABSH'] = $this->calculation_values['UABSH'] * 0.93;
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
            $this->EVAPORATOR();
            if ($this->calculation_values['ECO'] == 'yes')
            {
                $this->EHTG();
            }
            else
            {
                $this->HTG();
            }
        }
        else
        {
            $a = 1;
            $this->calculation_values['TCHW1H'] = $this->calculation_values['TCHW11'];
            $this->calculation_values['TCHW2L'] = $this->calculation_values['TCHW12'];
            $this->calculation_values['TCW1H'] = $this->calculation_values['TCW11'];

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
                    $this->calculation_values['UABSL'] = $this->calculation_values['UABSL'] * 0.93;
                    $this->calculation_values['UABSH'] = $this->calculation_values['UABSH'] * 0.93;
                }
                else
                {
                    $this->calculation_values['UABSH'] = $this->calculation_values['UABSH'] * 0.9;
                    $this->calculation_values['UABSL'] = $this->calculation_values['UABSL'] * 0.9;
                }
            }

            $this->EVAPORATOR();
            if ($this->calculation_values['ECO'] == 'yes')
            {
                $this->EHTG();
            }
            else
            {
                $this->HTG();
            }

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
                        $this->calculation_values['UABSL'] = $this->calculation_values['UABSL'] * 0.93;
                        $this->calculation_values['UABSH'] = $this->calculation_values['UABSH'] * 0.93;
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
                if ($this->calculation_values['ECO'] == 'yes')
                {
                    $this->EHTG();
                }
                else
                {
                    $this->HTG();
                }

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

        if (($this->calculation_values['MODEL'] < 1200 && $this->calculation_values['TU2'] == 3 || $this->calculation_values['TU2'] == 6 || $this->calculation_values['TU2'] == 7 || $this->calculation_values['TU2'] == 8))
        {
            $VEVA = 0.7;
        }
        else if ($this->calculation_values['MODEL'] > 1200 && $this->calculation_values['TU2'] == 3 || $this->calculation_values['TU2'] == 6 || $this->calculation_values['TU2'] == 7 || $this->calculation_values['TU2'] == 8)
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
        if ($this->calculation_values['TU2'] == 3 || $this->calculation_values['TU2'] == 6 || $this->calculation_values['TU2'] == 7 || $this->calculation_values['TU2'] == 8 && $this->calculation_values['VELEVA'] == 0)
        {
            $HI1 = $HI1 * 2;
        }

        if ($this->calculation_values['TU2'] == 2.0 || $this->calculation_values['TU2'] == 0 || $this->calculation_values['TU2'] == 7)
            $R1 = log($this->calculation_values['ODE'] / $this->calculation_values['IDE']) * $this->calculation_values['ODE'] / (2 * 340);
        if ($this->calculation_values['TU2'] == 1.0 || $this->calculation_values['TU2'] == 6)
            $R1 = log($this->calculation_values['ODE'] / $this->calculation_values['IDE']) * $this->calculation_values['ODE'] / (2 * 37);
        if ($this->calculation_values['TU2'] == 3.0 || $this->calculation_values['TU2'] == 4.0)
            $R1 = log($this->calculation_values['ODE'] / $this->calculation_values['IDE']) * $this->calculation_values['ODE'] / (2 * 21);
        if ($this->calculation_values['TU2'] == 5.0 || $this->calculation_values['TU2'] == 8 || $this->calculation_values['TU2'] == 9)
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

        if ($this->calculation_values['TU2'] == 3 || $this->calculation_values['TU2'] == 6 || $this->calculation_values['TU2'] == 7 || $this->calculation_values['TU2'] == 8 && $this->calculation_values['VELEVA'] == 0)
        {
            $HI = $HI * 2;
        }

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

        if ($this->calculation_values['TU5'] == 2.0 || $this->calculation_values['TU5'] == 0)
            $R1 = log($this->calculation_values['ODA'] / $this->calculation_values['IDA']) * $this->calculation_values['ODA'] / (2 * 340);
        else if ($this->calculation_values['TU5'] == 1.0)
            $R1 = log($this->calculation_values['ODA'] / $this->calculation_values['IDA']) * $this->calculation_values['ODA'] / (2 * 37);
        else if ($this->calculation_values['TU5'] == 5 || $this->calculation_values['TU5'] == 6)
            $R1 = log($this->calculation_values['ODA'] / $this->calculation_values['IDA']) * $this->calculation_values['ODA'] / (2 * 21);
        else if ($this->calculation_values['TU5'] == 7.0 || $this->calculation_values['TU5'] == 4)
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
        if ($this->calculation_values['TV5'] == 3.0 || $this->calculation_values['TV5'] == 4)
            $R1 = log($this->calculation_values['ODC'] / $this->calculation_values['IDC']) * $this->calculation_values['ODC'] / (2 * 21);
        if ($this->calculation_values['TV5'] == 5.0 || $this->calculation_values['TV5'] == 6)
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
        $this->calculation_values['q'] = 0;
        $this->calculation_values['r'] = 0;
        $ATCHW2H;

        $this->calculation_values['LMTDEVA'] = ($this->calculation_values['TON'] * 3024) / ($this->calculation_values['AEVA'] * $this->calculation_values['UEVA']);
        $this->calculation_values['T1'] = $this->calculation_values['TCHW2L'] - ($this->calculation_values['TCHW1H'] - $this->calculation_values['TCHW2L']) / (exp(($this->calculation_values['TCHW1H'] - $this->calculation_values['TCHW2L']) / $this->calculation_values['LMTDEVA']) - 1); // OVERALL LMTD & $this->calculation_values['T1'] CALCULATED FOR CHW CIRCUIT FOR ARI 

        $this->calculation_values['GCHW'] = $this->calculation_values['TON'] * 3024 / (($this->calculation_values['TCHW1H'] - $this->calculation_values['TCHW2L']) * $this->calculation_values['CHGLY_ROW12'] * $this->calculation_values['CHGLY_SPHT12'] / 4187);
        $this->calculation_values['GDIL'] = 70 * $this->calculation_values['MODEL1'];
        $this->calculation_values['QEVA'] = $this->calculation_values['TON'] * 3024;

        $QAB = $this->calculation_values['QEVA'] * (1 + 1 / 1.3) * 0.70;
        $QCO = $this->calculation_values['QEVA'] * (1 + 1 / 1.3) * 0.30;
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


        $vam_base = new VamBaseController();
        /************** Int Chw temp assump **************/

        $err1 = array();
        $ferr1 = array();
        $tchw2h = array();

        $ferr1[0] = 1;
        $p = 1;
        while (abs($ferr1[$p - 1]) > 0.1)
        {
            if ($p == 1)
            {
                if ($this->calculation_values['DT'] > 11)
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
        $ferr2 = array();
        $t2 = array();

        $vam_base1 = new VamBaseController();

        if ($this->calculation_values['q'] == 0)
        {
            $this->calculation_values['T2'] = $this->calculation_values['ATCW2'] + 2.5;
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
                $t2[$this->calculation_values['q']] = $t2[$this->calculation_values['q'] - 1] - 0.1;
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
                $t3[$this->calculation_values['r']] = $t3[$this->calculation_values['r'] - 1] + 0.2;
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

            if ($this->calculation_values['GLL'] == 2)
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

            $QCWABSH = $this->calculation_values['GCWAH'] * $this->calculation_values['COGLY_ROWH1'] * (($this->calculation_values['TCW2H'] * $this->calculation_values['COGLY_SPHT2H']) - ($this->calculation_values['TCW1H'] * $this->calculation_values['COGLY_SPHT1'])) / 4187;
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

            if ($this->calculation_values['GLL'] == 2)
            {
                $this->calculation_values['COGLY_SPHT1L'] = $vam_base->EG_SPHT($this->calculation_values['TCW1L'], $this->calculation_values['COGLY']) * 1000;
                $this->calculation_values['COGLY_SPHT2L'] = $vam_base->EG_SPHT($this->calculation_values['TCW2L'], $this->calculation_values['COGLY']) * 1000;
            }
            else
            {
                $this->calculation_values['COGLY_SPHT1L'] = $vam_base->PG_SPHT($this->calculation_values['TCW1L'], $this->calculation_values['COGLY']) * 1000;
                $this->calculation_values['COGLY_SPHT2L'] = $vam_base->PG_SPHT($this->calculation_values['TCW2L'], $this->calculation_values['COGLY']) * 1000;
            }

            $QCWABSL = $this->calculation_values['GCWAL'] * $this->calculation_values['COGLY_ROWH1'] * (($this->calculation_values['TCW2L'] * $this->calculation_values['COGLY_SPHT2L']) - ($this->calculation_values['TCW1L'] * $this->calculation_values['COGLY_SPHT1L'])) / 4187;
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
                $tcw4[$b] = $tcw4[$b - 1] + $ferr6[$b - 1] * ($tcw4[$b - 1] - $tcw4[$b - 2]) / ($ferr6[$b - 2] - $ferr6[$b - 1]);
            }
            if ($tcw4[$b] > $this->calculation_values['T3'] && $b > 2)
            {
                $tcw4[$b] = $tcw4[$b - 1] + $ferr6[$b - 1] * ($tcw4[$b - 1] - $tcw4[$b - 2]) / ($ferr6[$b - 2] - $ferr6[$b - 1]) / 5;
            }

            $this->calculation_values['TCW4'] = $tcw4[$b];

            if ($this->calculation_values['GLL'] == 2)
            {
                $this->calculation_values['COGLY_SPHT3'] = $vam_base->EG_SPHT($this->calculation_values['TCW3'], $this->calculation_values['COGLY']) * 1000;
                $this->calculation_values['COGLY_SPHT4'] = $vam_base->EG_SPHT($this->calculation_values['TCW4'], $this->calculation_values['COGLY']) * 1000;
            }
            else
            {
                $this->calculation_values['COGLY_SPHT3'] = $vam_base->PG_SPHT($this->calculation_values['TCW3'], $this->calculation_values['COGLY']) * 1000;
                $this->calculation_values['COGLY_SPHT4'] = $vam_base->PG_SPHT($this->calculation_values['TCW4'], $this->calculation_values['COGLY']) * 1000;
            }

            $this->calculation_values['LMTDCON'] = (($this->calculation_values['T3'] - $this->calculation_values['TCW3']) - ($this->calculation_values['T3'] - $this->calculation_values['TCW4'])) / log(($this->calculation_values['T3'] - $this->calculation_values['TCW3']) / ($this->calculation_values['T3'] - $this->calculation_values['TCW4']));
            $QLMTDCON = $this->calculation_values['UCON'] * $this->calculation_values['ACON'] * $this->calculation_values['LMTDCON'];
            $this->calculation_values['QCON'] = $this->calculation_values['GCWC'] * $this->calculation_values['COGLY_ROWH1'] * (($this->calculation_values['TCW4'] * $this->calculation_values['COGLY_SPHT4']) - ($this->calculation_values['TCW3'] * $this->calculation_values['COGLY_SPHT3'])) / 4187;
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
                $t22[$c] = $this->calculation_values['T2'] + 2;
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

            $this->calculation_values['GREF1'] = ($this->calculation_values['QCON'] - $this->calculation_values['GREF'] * ($this->calculation_values['J9'] - $this->calculation_values['I3'])) / ($this->calculation_values['I22'] - $this->calculation_values['J9']);
            $this->calculation_values['GREF2'] = $this->calculation_values['GREF'] - $this->calculation_values['GREF1'];
            $this->calculation_values['GMED'] = $this->calculation_values['GDIL'] - $this->calculation_values['GREF1'];
            $this->calculation_values['XMED'] = $this->calculation_values['GDIL'] * $this->calculation_values['XDIL'] / $this->calculation_values['GMED'];

            $this->LTG();

            $QREFDHE = $this->calculation_values['GREF1'] * ($this->calculation_values['I13'] - $this->calculation_values['I22']);
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
                $t13[$d] = $this->calculation_values['T9'] + 3;
            }
            if ($d == 2)
            {
                $t13[$d] = $t13[$d - 1] + 2;
            }
            if ($d >= 3)
            {
                $t13[$d] = $t13[$d - 1] + $ferr8[$d - 1] * ($t13[$d - 1] - $t13[$d - 2]) / ($ferr8[$d - 2] - $ferr8[$d - 1]) / 2;
            }
            $this->calculation_values['T13'] = $t13[$d];
            $this->calculation_values['I13'] = $this->calculation_values['T13'] + 100;
            $this->calculation_values['P4'] = $vam_base->LIBR_PRESSURE($this->calculation_values['T13'], 0);
            $this->calculation_values['T4'] = $vam_base->LIBR_TEMP($this->calculation_values['P4'], $this->calculation_values['XMED']);
            $this->calculation_values['J4'] = $vam_base->WATER_VAPOUR_ENTHALPY($this->calculation_values['T4'], $this->calculation_values['P4']);
            $this->calculation_values['T12'] = $vam_base->LIBR_TEMP($this->calculation_values['P3'], $this->calculation_values['XMED']);
            $this->calculation_values['LMTDLTG'] = (($this->calculation_values['T13'] - $this->calculation_values['T12']) - ($this->calculation_values['T13'] - $this->calculation_values['T9'])) / log(($this->calculation_values['T13'] - $this->calculation_values['T12']) / ($this->calculation_values['T13'] - $this->calculation_values['T9']));
            $this->calculation_values['QLMTDLTG'] = $this->calculation_values['ULTG'] * $this->calculation_values['ALTG'] * $this->calculation_values['LMTDLTG'];
            $this->calculation_values['QREFLTG'] = $this->calculation_values['GREF1'] * ($this->calculation_values['J4'] - $this->calculation_values['I13']);
            $ferr8[$d] = ($this->calculation_values['QREFLTG'] - $this->calculation_values['QLMTDLTG']) * 100 / $this->calculation_values['QLMTDLTG'];
            $d++;
        }
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

        $vam_base = new VamBaseController();

        if ($this->calculation_values['ECO'] == 'yes')
        {
            $this->calculation_values['I99'] = $this->calculation_values['I11'];
            $this->calculation_values['T99'] = $this->calculation_values['T11'];
        }
        else
        {
            $this->calculation_values['I14'] = ($this->calculation_values['GDIL2'] * $this->calculation_values['I21'] + $this->calculation_values['GDIL1'] * $this->calculation_values['I11']) / $this->calculation_values['GDIL'];
            $this->calculation_values['T14'] = $vam_base->LIBR_TEMPERATURE($this->calculation_values['XDIL'], $this->calculation_values['I14']);
            $this->calculation_values['I99'] = $this->calculation_values['I14'];
            $this->calculation_values['T99'] = $this->calculation_values['T14'];
        }

        $ferr10 = array();
        $t7 = array();
        $QLIBRHTHE = 0;
        $ht = 0;

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
            if ($this->calculation_values['ECO'] == 'yes')
            {
                $QLIBRHTHE = $this->calculation_values['GDIL1'] * ($this->calculation_values['I7'] - $this->calculation_values['I99']);
            }
            else
            {
                $QLIBRHTHE = $this->calculation_values['GDIL'] * ($this->calculation_values['I7'] - $this->calculation_values['I99']);
            }
            $this->calculation_values['I4'] = $vam_base->LIBR_ENTHALPY($this->calculation_values['T4'], $this->calculation_values['XMED']);
            $this->calculation_values['I10'] = $this->calculation_values['I4'] - ($QLIBRHTHE / $this->calculation_values['GMED']);
            $this->calculation_values['T10'] = $vam_base->LIBR_TEMPERATURE($this->calculation_values['XMED'], $this->calculation_values['I10']);
            $this->calculation_values['LMTDHTHE'] = (($this->calculation_values['T4'] - $this->calculation_values['T7']) - ($this->calculation_values['T10'] - $this->calculation_values['T99'])) / log(($this->calculation_values['T4'] - $this->calculation_values['T7']) / ($this->calculation_values['T10'] - $this->calculation_values['T99']));
            $QLMTDHTHE = $this->calculation_values['UHTHE'] * $this->calculation_values['AHTHE'] * $this->calculation_values['LMTDHTHE'];
            $ferr10[$ht] = ($QLIBRHTHE - $QLMTDHTHE) * 100 / $QLIBRHTHE;
            $ht++;
        }
    }


    public function HTG()
    {
        $vam_base = new VamBaseController();

        $this->calculation_values['QHTG'] = ($this->calculation_values['GMED'] * $this->calculation_values['I4']) + ($this->calculation_values['GREF1'] * $this->calculation_values['J4']) - ($this->calculation_values['GDIL'] * $this->calculation_values['I7']);
        $this->calculation_values['T5'] = $vam_base->LIBR_TEMP($this->calculation_values['P4'], $this->calculation_values['XDIL']);
        $this->calculation_values['LMTDHTG'] = $this->calculation_values['QHTG'] / ($this->calculation_values['AHTG'] * $this->calculation_values['UHTG']);
        $this->calculation_values['TS'] = $this->calculation_values['T4'] + ($this->calculation_values['T4'] - $this->calculation_values['T5']) / (exp(($this->calculation_values['T4'] - $this->calculation_values['T5']) / $this->calculation_values['LMTDHTG']) - 1);

        /********** $this->calculation_values['SFACTOR'] - STEAM *******/
        $SFACTOR1 = 0;
        $SFACTOR3 = 0;

        if ($this->calculation_values['TCHW12'] < 5 || ($this->calculation_values['MODEL'] < 300 && $this->calculation_values['TCHW12'] < 6.7))
        {
            $SFACTOR1 = 1.0738 - 0.0068 * $this->calculation_values['TCHW12'];
        }
        else
        {
            $SFACTOR1 = 1.0;
        }

        if ($this->calculation_values['DT'] < 14.999)
        {
            $SFACTOR3 = 1.0;
        }
        else
        {
            $SFACTOR3 = (0.0006667 * $this->calculation_values['DT']) + 1.01;
        }

        $this->calculation_values['GEXHAUSTR'] = $this->calculation_values['QHTG'] / ((($this->calculation_values['CPEX1'] + $this->calculation_values['CPEX2']) / 2) * ($this->calculation_values['TEXH1'] - $this->calculation_values['TEXH2']) * .97);
        $this->calculation_values['GEXHAUSTR'] = $this->calculation_values['GEXHAUSTR'] * $this->calculation_values['SFACTOR'] * $SFACTOR1 * $SFACTOR3;
        $this->calculation_values['QEXR'] = $this->calculation_values['GEXHAUSTR'] * (($this->calculation_values['CPEX1'] + $this->calculation_values['CPEX2']) / 2) * ($this->calculation_values['TEXH1'] - $this->calculation_values['TEXH2']) * .97;
        $this->calculation_values['QEXAV'] = $this->calculation_values['GEXHAUST'] * (($this->calculation_values['CPEX1'] + $this->calculation_values['CPEX2']) / 2) * ($this->calculation_values['TEXH1'] - $this->calculation_values['TEXH2']) * .97;

        if ($this->calculation_values['engine_type'] == 'oil')
        {
            $this->calculation_values['TEXHAUSTREQ'] = ($this->calculation_values['TEXH1'] - ($this->calculation_values['QEXR'] / ($this->calculation_values['GEXHAUST'] * (($this->calculation_values['CPEX1'] + $this->calculation_values['CPEX2']) / 2) * 0.97))) - 2;
        }
        else
        {
            $ATEXH = ($this->calculation_values['TEXH1'] - ($this->calculation_values['QEXR'] / ($this->calculation_values['GEXHAUST'] * (($this->calculation_values['CPEX1'] + $this->calculation_values['CPEX2']) / 2) * 0.97))) - 2;

            $hh = 0;
            $ferr66 = array();
            $te = array();

            $hh = 1;
            $ferr66[0] = 2;
            while (abs($ferr66[$hh - 1]) > 0.001)
            {
                if ($hh == 1)
                {
                    $te[$hh] = $ATEXH;               //$t7 is representative for $this->calculation_values['T7']//
                }
                if ($hh == 2)
                {
                    $te[$hh] = $te[$hh - 1] - 0.2;
                }
                if ($hh >= 3)
                {
                    $te[$hh] = $te[$hh - 1] + $ferr66[$hh - 1] * ($te[$hh - 1] - $te[$hh - 2]) / ($ferr66[$hh - 2] - $ferr66[$hh - 1]);
                }

                $this->calculation_values['TEXHAUSTREQ'] = $te[$hh];
                $this->calculation_values['TEXH2'] = $this->calculation_values['TEXHAUSTREQ'];
                $this->calculation_values['CPEX'] = (0.00005625 * $this->calculation_values['TEXHAUSTREQ']) + 0.251875;
                $this->calculation_values['QEXR1'] = $this->calculation_values['GEXHAUST'] * (($this->calculation_values['CPEX1'] + $this->calculation_values['CPEX']) / 2) * ($this->calculation_values['TEXH1'] - ($this->calculation_values['TEXHAUSTREQ'] + 2)) * .97;
                $ferr66[$hh] = ($this->calculation_values['QEXR'] - $this->calculation_values['QEXR1']) * 100 / $this->calculation_values['QEXR1'];
                $hh++;
            } 
            $this->calculation_values['CPEX2'] = $this->calculation_values['CPEX'];
        }

        $this->PRESSURE_DROP();
        $this->EXHAUST_CONNECTION();
    }


    public function EHTG()
    {
        $tg = 0;
        $ferr11 = array();
        $ts = array();
        $vam_base = new VamBaseController();

        $ferr11[0] = 1;
        $tg = 1;

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

            if ($this->calculation_values['TCHW2L'] < 7.0)
            {
                $this->calculation_values['KM2'] = (-0.857413 * $this->calculation_values['TCHW2L'] + 6) + 5.0;       //INCREASED FROM 4 TO 5 FEB 2009
            }
            else
            {
                $this->calculation_values['KM2'] = 5.0;
            }
            $this->calculation_values['T5'] = $vam_base->LIBR_TEMP($this->calculation_values['P4'], $this->calculation_values['XDIL']);
            $this->calculation_values['LMTDHTG'] = (($this->calculation_values['TS'] - $this->calculation_values['T5']) - ($this->calculation_values['TS'] - $this->calculation_values['T4'])) / log(($this->calculation_values['TS'] - $this->calculation_values['T5']) / ($this->calculation_values['TS'] - $this->calculation_values['T4']));
            $this->calculation_values['QLMTDHTG'] = $this->calculation_values['UHTG'] * $this->calculation_values['AHTG'] * $this->calculation_values['LMTDHTG'];
            $this->calculation_values['GSTEAM'] = $this->calculation_values['QLMTDHTG'] / (661.911 - $this->calculation_values['TS']);
            $this->HR();
            $this->calculation_values['I14'] = ($this->calculation_values['GDIL2'] * $this->calculation_values['I20'] + $this->calculation_values['GDIL1'] * $this->calculation_values['I7']) / $this->calculation_values['GDIL'];
            $this->calculation_values['T14'] = $vam_base->LIBR_TEMPERATURE($this->calculation_values['XDIL'], $this->calculation_values['I14']);
            $this->calculation_values['QHTG'] = ($this->calculation_values['GMED'] * $this->calculation_values['I4']) + ($this->calculation_values['GREF1'] * $this->calculation_values['J4']) - ($this->calculation_values['GDIL'] * $this->calculation_values['I14']);
            $ferr11[$tg] = ($this->calculation_values['QLMTDHTG'] - $this->calculation_values['QHTG']) * 100 / $this->calculation_values['QLMTDHTG'];
            $tg++;

        }
        //CONVERGENCE();
        $this->EXHAUSTHTG();

    }




    public function HR()
    {
        $i = 0;
        $vam_base = new VamBaseController();

        if ($i == 0)
            $this->calculation_values['TSTOUT1'] = 85;
        else
            $this->calculation_values['TSTOUT1'] = $this->calculation_values['T21'] + 3;

        $ferr12 = array();
        $tstout = array();

        $ferr12[0] = 2;
        $i = 1;

        while (abs($ferr12[$i - 1]) > 0.1)
        {
            if ($i == 1)
            {
                $tstout[$i] = $this->calculation_values['TSTOUT1'];
            }
            if ($i == 2)
            {
                $tstout[$i] = $tstout[$i - 1] + 0.5;
            }
            if ($i >= 3)
            {
                $tstout[$i] = $tstout[$i - 1] + $ferr12[$i - 1] * ($tstout[$i - 1] - $tstout[$i - 2]) / ($ferr12[$i - 2] - $ferr12[$i - 1]) / 2;
            }
            $this->calculation_values['TSTOUT'] = $tstout[$i];
            $this->calculation_values['QHR'] = $this->calculation_values['GSTEAM'] * ($this->calculation_values['TS'] - $this->calculation_values['TSTOUT']);
            $this->calculation_values['I20'] = $this->calculation_values['I21'] + ($this->calculation_values['QHR'] / $this->calculation_values['GDIL2']);
            $this->calculation_values['T20'] = $vam_base->LIBR_TEMPERATURE($this->calculation_values['XDIL'], $this->calculation_values['I20']);
            $this->calculation_values['LMTDHR'] = (($this->calculation_values['TS'] - 5 - $this->calculation_values['T20']) - ($this->calculation_values['TSTOUT'] - $this->calculation_values['T21'])) / log(($this->calculation_values['TS'] - 5 - $this->calculation_values['T20']) / ($this->calculation_values['TSTOUT'] - $this->calculation_values['T21']));
            $this->calculation_values['QLMTDHR'] = $this->calculation_values['UHR'] * $this->calculation_values['AHR'] * $this->calculation_values['LMTDHR'];
            $ferr12[$i] = ($this->calculation_values['QHR'] - $this->calculation_values['QLMTDHR']) / $this->calculation_values['QHR'] * 100;
            $i++;
        }
    }



    public function EXHAUSTHTG()
    {
        $SFACTOR1 = 0;
        $SFACTOR3 = 0;

        /********** $this->calculation_values['SFACTOR'] *******/
        if ($this->calculation_values['TCHW12'] < 5)
        {
            $SFACTOR1 = 1.0738 - 0.0068 * $this->calculation_values['TCHW12'];
        }
        else
        {
            $SFACTOR1 = 1.0;
        }

        if ($this->calculation_values['DT'] < 14.999)
        {
            $SFACTOR3 = 1.0;
        }
        else
        {
            $SFACTOR3 = (0.0006667 * $this->calculation_values['DT']) + 1.01;
        }

        $this->calculation_values['GSTEAM'] = $this->calculation_values['GSTEAM'] * $this->calculation_values['SFACTOR'] * $SFACTOR1 * $SFACTOR3;
        $this->calculation_values['QHEAT'] = $this->calculation_values['GSTEAM'] * (661.911 - 90);

        $this->calculation_values['QECO'] = $this->calculation_values['QHR'];
        $this->calculation_values['QEXHTG'] = $this->calculation_values['QHEAT'] - $this->calculation_values['QECO'];

        $hh = 0;
        $ferr66 = array();
        $te = array();

        $hh = 1;
        $ferr66[0] = 2;
        while (abs($ferr66[$hh - 1]) > 0.001)
        {
            if ($hh == 1)
            {
                $te[$hh] = 180;
            }
            if ($hh == 2)
            {
                $te[$hh] = $te[$hh - 1] - 1;
            }
            if ($hh >= 3)
            {
                $te[$hh] = $te[$hh - 1] + $ferr66[$hh - 1] * ($te[$hh - 1] - $te[$hh - 2]) / ($ferr66[$hh - 2] - $ferr66[$hh - 1]) / 5;
            }
            $this->calculation_values['TEXH2'] = $te[$hh];
            $this->calculation_values['CPEX2'] = (0.00005625 * $this->calculation_values['TEXH2']) + 0.251875;
            $this->calculation_values['QEXHAUST'] = $this->calculation_values['GEXHAUST'] * (($this->calculation_values['CPEX1'] + $this->calculation_values['CPEX2']) / 2) * ($this->calculation_values['TEXH1'] - $this->calculation_values['TEXH2']) * .97;
            $ferr66[$hh] = ($this->calculation_values['QEXHAUST'] - $this->calculation_values['QEXHTG']) * 100 / $this->calculation_values['QEXHAUST'];
            $hh++;
        }

        $kk = 0;
        $ferr77 = array();
        $te1 = array();

        $kk = 1;
        $ferr77[0] = 2;
        while (abs($ferr77[$kk - 1]) > 0.001)
        {
            if ($kk == 1)
            {
                $te1[$kk] = 140;
            }
            if ($kk == 2)
            {
                $te1[$kk] = $te1[$kk - 1] - 1;
            }
            if ($kk >= 3)
            {
                $te1[$kk] = $te1[$kk - 1] + $ferr77[$kk - 1] * ($te1[$kk - 1] - $te1[$kk - 2]) / ($ferr77[$kk - 2] - $ferr77[$kk - 1]);
            }
            $this->calculation_values['TEXH3'] = $te1[$kk];
            $this->calculation_values['CPEX3'] = (0.00005625 * $this->calculation_values['TEXH3']) + 0.251875;
            $this->calculation_values['QECO1'] = $this->calculation_values['GEXHAUST'] * (($this->calculation_values['CPEX2'] + $this->calculation_values['CPEX3']) / 2) * ($this->calculation_values['TEXH2'] - $this->calculation_values['TEXH3']) * .97;
            $ferr77[$kk] = ($this->calculation_values['QECO'] - $this->calculation_values['QECO1']) * 100 / $this->calculation_values['QECO'];
            $kk++;
        }

        $this->calculation_values['GEXHAUSTR'] = ($this->calculation_values['QEXHTG'] + $this->calculation_values['QECO']) / ((($this->calculation_values['CPEX1'] + $this->calculation_values['CPEX3']) / 2) * ($this->calculation_values['TEXH1'] - $this->calculation_values['TEXH3']) * .97);
        $this->calculation_values['GEXHAUSTR'] = $this->calculation_values['GEXHAUSTR'] * $this->calculation_values['SFACTOR'] * $SFACTOR1 * $SFACTOR3;
        $this->calculation_values['QEXR'] = $this->calculation_values['GEXHAUSTR'] * (($this->calculation_values['CPEX1'] + $this->calculation_values['CPEX3']) / 2) * ($this->calculation_values['TEXH1'] - $this->calculation_values['TEXH3']) * .97;
        $this->calculation_values['QEXAV'] = $this->calculation_values['GEXHAUST'] * (($this->calculation_values['CPEX1'] + $this->calculation_values['CPEX3']) / 2) * ($this->calculation_values['TEXH1'] - $this->calculation_values['TEXH3']) * .97;

        //COP = (TON * 3024) / ($this->calculation_values['GEXHAUST'] * (($this->calculation_values['CPEX1'] + $this->calculation_values['CPEX3']) / 2) * ($this->calculation_values['TEXH1'] - $this->calculation_values['TEXH3']) * .97);

        $this->PRESSURE_DROP();
        $this->EXHAUST_CONNECTION();
        //CONVERGENCE();
    }



    public function EXHAUST_CONNECTION()
    {

        $this->calculation_values['IDENEXH']=1.293*(273/(273+$this->calculation_values['TEXH1']));
        $this->calculation_values['IAEXH']=$this->calculation_values['GEXHAUST']/(3600*$this->calculation_values['IDENEXH']*25);
        $this->calculation_values['IA1']=$this->calculation_values['IAEXH'] * 28/22;
        $this->calculation_values['IEXH']=(sqrt($this->calculation_values['IA1']))*1000;

        if($this->calculation_values['IEXH']>1010)
        {
            $this->calculation_values['IEXHC']=0;
        }
        else if($this->calculation_values['IEXH']>950)
        {
            $this->calculation_values['IEXHC']=1000;
        }
        else if($this->calculation_values['IEXH']>900)
        {
            $this->calculation_values['IEXHC']=950;
        }
        else if($this->calculation_values['IEXH']>850)
        {
            $this->calculation_values['IEXHC']=900;
        }
        else if($this->calculation_values['IEXH']>800)
        {
            $this->calculation_values['IEXHC']=850;
        }
        else if($this->calculation_values['IEXH']>750)
        {
            $this->calculation_values['IEXHC']=800;
        }
        else if($this->calculation_values['IEXH']>700)
        {
            $this->calculation_values['IEXHC']=750;
        }
        else if($this->calculation_values['IEXH']>650)
        {
            $this->calculation_values['IEXHC']=700;
        }
        else if($this->calculation_values['IEXH']>600)
        {
            $this->calculation_values['IEXHC']=650;
        }
        else if($this->calculation_values['IEXH']>550)
        {
            $this->calculation_values['IEXHC']=600;
        }
        else if($this->calculation_values['IEXH']>500)
        {
            $this->calculation_values['IEXHC']=550;
        }
        else if($this->calculation_values['IEXH']>450)
        {
            $this->calculation_values['IEXHC']=500;
        }
        else if($this->calculation_values['IEXH']>400)
        {
            $this->calculation_values['IEXHC']=450;
        }
        else if($this->calculation_values['IEXH']>350)
        {
            $this->calculation_values['IEXHC']=400;
        }
        else if($this->calculation_values['IEXH']>300)
        {
            $this->calculation_values['IEXHC']=350;
        }
        else if($this->calculation_values['IEXH']>250)
        {
            $this->calculation_values['IEXHC']=300;
        }
        else if($this->calculation_values['IEXH']>200)
        {
            $this->calculation_values['IEXHC']=250;
        }
        else if($this->calculation_values['IEXH']>150)
        {
            $this->calculation_values['IEXHC']=200;
        }
        else if($this->calculation_values['IEXH']>125)
        {
            $this->calculation_values['IEXHC']=150;
        }
        else if($this->calculation_values['IEXH']>100)
        {
            $this->calculation_values['IEXHC']=125;
        }
        else if($this->calculation_values['IEXH']>80)
        {
            $this->calculation_values['IEXHC']=100;
        }
        else if($this->calculation_values['IEXH']>65)
        {
            $this->calculation_values['IEXHC']=80;
        }
        else
        {
            $this->calculation_values['IEXHC']=50;
        }
        // $this->calculation_values['ODENEXH']=1.293*(273/(273+$this->calculation_values['TEXHAUSTREQ']));
        // $this->calculation_values['OAEXH']=$this->calculation_values['GEXHAUST']/(3600*$this->calculation_values['ODENEXH']*25);
        // $this->calculation_values['OA1']=$this->calculation_values['OAEXH'] * 28/22;
        // $this->calculation_values['OEXH']=(sqrt($this->calculation_values['OA1']))*1000;
        
        // if($this->calculation_values['OEXH']>1000)
        // {
        //     $this->calculation_values['OEXHC']=0;
        // }
        // else if($this->calculation_values['OEXH']>950)
        // {
        //     $this->calculation_values['OEXHC']=950;
        // }
        // else if($this->calculation_values['OEXH']>900)
        // {
        //     $this->calculation_values['OEXHC']=950;
        // }
        // else if($this->calculation_values['OEXH']>850)
        // {
        //     $this->calculation_values['OEXHC']=900;
        // }
        // else if($this->calculation_values['OEXH']>800)
        // {
        //     $this->calculation_values['OEXHC']=850;
        // }
        // else if($this->calculation_values['OEXH']>750)
        // {
        //     $this->calculation_values['OEXHC']=800;
        // }
        // else if($this->calculation_values['OEXH']>700)
        // {
        //     $this->calculation_values['OEXHC']=750;
        // }
        // else if($this->calculation_values['OEXH']>650)
        // {
        //     $this->calculation_values['OEXHC']=700;
        // }
        // else if($this->calculation_values['OEXH']>600)
        // {
        //     $this->calculation_values['OEXHC']=650;
        // }
        // else if($this->calculation_values['OEXH']>550)
        // {
        //     $this->calculation_values['OEXHC']=600;
        // }
        // else if($this->calculation_values['OEXH']>500)
        // {
        //     $this->calculation_values['OEXHC']=550;
        // }
        // else if($this->calculation_values['OEXH']>450)
        // {
        //     $this->calculation_values['OEXHC']=500;
        // }
        // else if($this->calculation_values['OEXH']>400)
        // {
        //     $this->calculation_values['OEXHC']=450;
        // }
        // else if($this->calculation_values['OEXH']>350)
        // {
        //     $this->calculation_values['OEXHC']=400;
        // }
        // else if($this->calculation_values['OEXH']>300)
        // {
        //     $this->calculation_values['OEXHC']=350;
        // }
        // else if($this->calculation_values['OEXH']>250)
        // {
        //     $this->calculation_values['OEXHC']=300;
        // }
        // else if($this->calculation_values['OEXH']>200)
        // {
        //     $this->calculation_values['OEXHC']=250;
        // }
        // else if($this->calculation_values['OEXH']>150)
        // {
        //     $this->calculation_values['OEXHC']=200;
        // }
        // else if($this->calculation_values['OEXH']>125)
        // {
        //     $this->calculation_values['OEXHC']=150;
        // }
        // else if($this->calculation_values['OEXH']>100)
        // {
        //     $this->calculation_values['OEXHC']=125;
        // }
        // else if($this->calculation_values['OEXH']>80)
        // {
        //     $this->calculation_values['OEXHC']=100;
        // }
        // else if($this->calculation_values['OEXH']>65)
        // {
        //     $this->calculation_values['OEXHC']=80;
        // }
        // else
        // {
        //     $this->calculation_values['OEXHC']=50;
        // }

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
        $FE1 = 0;
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

        if (($this->calculation_values['MODEL'] < 1200 && $this->calculation_values['TU2'] == 3 || $this->calculation_values['TU2'] == 6 || $this->calculation_values['TU2'] == 7 || $this->calculation_values['TU2'] == 8))
        {
            $F = 1.325 / pow(log((1.53 / (3.7 * $this->calculation_values['IDE'] * 1000)) + (5.74 / pow($RE, 0.9))), 2);
            $FE1 = $F * $this->calculation_values['LE'] * $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (2 * 9.81 * $this->calculation_values['IDE']);
        }
        else if ($this->calculation_values['MODEL'] > 1200 && $this->calculation_values['TU2'] == 3 || $this->calculation_values['TU2'] == 6 || $this->calculation_values['TU2'] == 7 || $this->calculation_values['TU2'] == 8)                                         //06/11/2017   Changed for SS FInned
        {
            $F = (1.325 / pow(log((1.53 / (3.7 * $this->calculation_values['IDE'] * 1000)) + (5.74 / pow($RE, 0.9))), 2)) * ((-0.0315 * $this->calculation_values['VEA']) + 0.85);
            $FE1 = $F * $this->calculation_values['LE'] * $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (2 * 9.81 * $this->calculation_values['IDE']);

        }
        else if (($this->calculation_values['MODEL'] < 1200 && ($this->calculation_values['TU2'] == 1 || $this->calculation_values['TU2'] == 2 || $this->calculation_values['TU2'] == 0 || $this->calculation_values['TU2'] == 4 || $this->calculation_values['TU2'] == 9)))                  // 12% AS PER EXPERIMENTATION      
        {
            $F = (0.0014 + (0.137 / pow($RE, 0.32))) * 1.12;
            $FE1 = 2 * $F * $this->calculation_values['LE'] * $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (9.81 * $this->calculation_values['IDE']);
        }
        else if ($this->calculation_values['MODEL'] > 1200 && ($this->calculation_values['TU2'] == 1 || $this->calculation_values['TU2'] == 2 || $this->calculation_values['TU2'] == 4 || $this->calculation_values['TU2'] == 0 || $this->calculation_values['TU2'] == 9))
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
        if ($this->calculation_values['GLL'] == 3)
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

        $this->calculation_values['FFA'] = 1.325 / pow(log((0.0457 / (3.7 * $this->calculation_values['PIDA'] * 1000)) + (5.74 / pow($this->calculation_values['REPA'], 0.9))), 2);        //FRICTION FACTOR CAL
        //  FFD1 = 1.325 / Math.Pow(Math.Log((0.0457 / (3.7 * (ED1NB) * 1000)) + (5.74 / Math.Pow(RED1, 0.9))), 2);

        $this->calculation_values['FLP1'] = ($this->calculation_values['FFA'] * ($this->calculation_values['PSL1'] + $this->calculation_values['PSL2']) / $this->calculation_values['PIDA']) * ($this->calculation_values['VPA'] * $this->calculation_values['VPA'] / (2 * 9.81)) + ((14 * $this->calculation_values['FT']) * ($this->calculation_values['VPA'] * $this->calculation_values['VPA']) / (2 * 9.81));      //FR LOSS IN PIPE                                   
        //   FLD1 = ((FFD1 * DSL) / ED1NB) * (VD1 * VD1 / (2 * 9.81));                                  //FR LOSS IN DUCT
        $this->calculation_values['FLOT'] = (1 + 0.5 + 1 + 0.5) * ($this->calculation_values['VPA'] * $this->calculation_values['VPA'] / (2 * 9.81));                                                                   //EXIT, ENTRY LOSS

        $this->calculation_values['AFLP'] = ($this->calculation_values['FLP1'] + $this->calculation_values['FLOT']) * 1.075;               //7.5% SAFETY

        $REH = ($this->calculation_values['VAH'] * $this->calculation_values['IDA'] * $COGLY_ROWH33) / $COGLY_VISH33;
        $REL = ($this->calculation_values['VAL'] * $this->calculation_values['IDA'] * $COGLY_ROWH33) / $COGLY_VISH33;

        if (($this->calculation_values['TU5'] < 2.1 || $this->calculation_values['TU5'] == 6 || $this->calculation_values['TU5'] == 4) && $this->calculation_values['MODEL'] < 1200)
        {
            $FH = (0.0014 + (0.137 / pow($REH, 0.32))) * 1.12;
            $FL = (0.0014 + (0.137 / pow($REL, 0.32))) * 1.12;
        }
        else if (($this->calculation_values['TU5'] < 2.1 || $this->calculation_values['TU5'] == 6 || $this->calculation_values['TU5'] == 4) && $this->calculation_values['MODEL'] > 1200)
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

        if ($this->calculation_values['GLL'] == 3)
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

        if (($this->calculation_values['TV5'] < 2.1 || $this->calculation_values['TV5'] == 4 || $this->calculation_values['TV5'] == 6) && $this->calculation_values['MODEL'] < 950)
        {
            $F = (0.0014 + (0.137 / pow($RE1, 0.32))) * 1.12;
        }
        else if (($this->calculation_values['TV5'] < 2.1 || $this->calculation_values['TV5'] == 4 || $this->calculation_values['TV5'] == 6) && $this->calculation_values['MODEL'] > 950)
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

    public function CONCHECK1()
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
    }


    public function CONVERGENCE()
    {
        $j = 0;
        $CC = array();

        $CC[0][0] = $this->calculation_values['GCHW'] * $this->calculation_values['CHGLY_ROW12'] * $this->calculation_values['CHGLY_SPHT12'] * ($this->calculation_values['TCHW1H'] - $this->calculation_values['TCHW2H']) / 4187;                //EVAPORATORH
        $CC[1][0] = $this->calculation_values['UEVAH'] * $this->calculation_values['AEVAH'] * $this->calculation_values['LMTDEVAH'];
        $CC[2][0] = ($this->calculation_values['GREFH'] * ($this->calculation_values['J1H'] - $this->calculation_values['I3'])) - ($this->calculation_values['GREFL'] * ($this->calculation_values['I3'] - $this->calculation_values['I1H']));

        $CC[0][1] = $this->calculation_values['GCWAH'] * $this->calculation_values['COGLY_ROWH1'] * ($this->calculation_values['COGLY_SPHT2H'] + $this->calculation_values['COGLY_SPHT1H']) * 0.5 * ($this->calculation_values['TCW2H'] - $this->calculation_values['TCW1H']) / 4187; //ABSORBERH
        $CC[1][1] = $this->calculation_values['UABSH'] * $this->calculation_values['AABSH'] * $this->calculation_values['LMTDABSH'];
        $CC[2][1] = $this->calculation_values['GREFH'] * $this->calculation_values['J1H'] + $this->calculation_values['GCONCH'] * $this->calculation_values['I2L'] - $this->calculation_values['GDIL'] * $this->calculation_values['I2'];

        $CC[0][2] = $this->calculation_values['GCWC'] * $this->calculation_values['COGLY_ROWH1'] * ($this->calculation_values['COGLY_SPHT4'] + $this->calculation_values['COGLY_SPHT3']) * 0.5 * ($this->calculation_values['TCW4'] - $this->calculation_values['TCW3']) / 4187;           //CONDENSER
        $CC[1][2] = $this->calculation_values['UCON'] * $this->calculation_values['ACON'] * $this->calculation_values['LMTDCON'];
        $CC[2][2] = $this->calculation_values['GREF1'] * ($this->calculation_values['I22'] - $this->calculation_values['J9']) + $this->calculation_values['GREF'] * ($this->calculation_values['J9'] - $this->calculation_values['I3']);

        $CC[0][3] = $this->calculation_values['GREF1'] * ($this->calculation_values['J4'] - $this->calculation_values['I13']);                                      //LTG
        $CC[1][3] = $this->calculation_values['ULTG'] * $this->calculation_values['ALTG'] * $this->calculation_values['LMTDLTG'];
        $CC[2][3] = $this->calculation_values['GCONC'] * $this->calculation_values['I9'] + $this->calculation_values['GREF2'] * $this->calculation_values['J9'] - $this->calculation_values['GMED'] * $this->calculation_values['I10'];

        $CC[0][4] = $this->calculation_values['QHTG'];                                                    //HTG
        $CC[1][4] = $this->calculation_values['UHTG'] * $this->calculation_values['AHTG'] * $this->calculation_values['LMTDHTG'];
        if ($this->calculation_values['ECO'] == 'yes')
        {
            $CC[2][4] = $this->calculation_values['GMED'] * $this->calculation_values['I4'] + $this->calculation_values['GREF1'] * $this->calculation_values['J4'] - $this->calculation_values['GDIL'] * $this->calculation_values['I14'];
        }
        else
        {
            $CC[2][4] = $this->calculation_values['GMED'] * $this->calculation_values['I4'] + $this->calculation_values['GREF1'] * $this->calculation_values['J4'] - $this->calculation_values['GDIL'] * $this->calculation_values['I7'];
        }

        $CC[0][5] = $this->calculation_values['GDIL1'] * ($this->calculation_values['I11'] - $this->calculation_values['I2']);                                      //LTHE
        $CC[1][5] = $this->calculation_values['ULTHE'] * $this->calculation_values['ALTHE'] * $this->calculation_values['LMTDLTHE'];
        $CC[2][5] = $this->calculation_values['GCONC'] * ($this->calculation_values['I9'] - $this->calculation_values['I8']);

        $CC[0][6] = $this->calculation_values['GMED'] * ($this->calculation_values['I4'] - $this->calculation_values['I10']);                                           //HTHE
        $CC[1][6] = $this->calculation_values['UHTHE'] * $this->calculation_values['AHTHE'] * $this->calculation_values['LMTDHTHE'];
        if ($this->calculation_values['ECO'] == 'yes')
        {
            $CC[2][6] = $this->calculation_values['GDIL1'] * ($this->calculation_values['I7'] - $this->calculation_values['I99']);
        }
        else
        {
            $CC[2][6] = $this->calculation_values['GDIL'] * ($this->calculation_values['I7'] - $this->calculation_values['I99']);
        }

        $CC[0][7] = $this->calculation_values['GCHW'] * $this->calculation_values['CHGLY_ROW12'] * $this->calculation_values['CHGLY_SPHT12'] * ($this->calculation_values['TCHW1L'] - $this->calculation_values['TCHW2L']) / 4187;                //EVAPORATORL
        $CC[1][7] = $this->calculation_values['UEVAL'] * $this->calculation_values['AEVAL'] * $this->calculation_values['LMTDEVAL'];
        $CC[2][7] = $this->calculation_values['GREFL'] * ($this->calculation_values['J1L'] - $this->calculation_values['I1H']);

        $CC[0][8] = $this->calculation_values['GCWAL'] * $this->calculation_values['COGLY_ROWH1'] * ($this->calculation_values['COGLY_SPHT2L'] + $this->calculation_values['COGLY_SPHT1L']) * 0.5 * ($this->calculation_values['TCW2L'] - $this->calculation_values['TCW1L']) / 4187;  //ABSORBERL
        $CC[1][8] = $this->calculation_values['UABSL'] * $this->calculation_values['AABSL'] * $this->calculation_values['LMTDABSL'];
        $CC[2][8] = $this->calculation_values['GCONC'] * $this->calculation_values['I8'] + $this->calculation_values['GREFL'] * $this->calculation_values['J1L'] - $this->calculation_values['GDILL'] * $this->calculation_values['I2L'];

        $CC[0][9] = $this->calculation_values['GDIL2'] * ($this->calculation_values['I21'] - $this->calculation_values['I2']);                                      //DHE
        $CC[1][9] = $this->calculation_values['UDHE'] * $this->calculation_values['ADHE'] * $this->calculation_values['LMTDDHE'];
        $CC[2][9] = $this->calculation_values['GREF1'] * ($this->calculation_values['I13'] - $this->calculation_values['I22']);

        for ($j = 0; $j < 10; $j++)
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

            $CC[5][$j] = ($CC[4][$j] - $CC[3][$j]) / $CC[4][$j] * 100.0;    //R
        }

        if ($this->calculation_values['ECO'] == 'yes')
        {
            $HEATIN = ($this->calculation_values['TON'] * 3024) + $this->calculation_values['QHTG'] + $this->calculation_values['QHR'];
            $HEATOUT = $this->calculation_values['GCWAH'] * $this->calculation_values['COGLY_ROWH1'] * (($this->calculation_values['TCW2H'] * $this->calculation_values['COGLY_SPHT2H']) - ($this->calculation_values['TCW1H'] * $this->calculation_values['COGLY_SPHT1'])) / 4187 + $this->calculation_values['GCWAL'] * $this->calculation_values['COGLY_ROWH1'] * (($this->calculation_values['TCW2L'] * $this->calculation_values['COGLY_SPHT2L']) - ($this->calculation_values['TCW1L'] * $this->calculation_values['COGLY_SPHT1L'])) / 4187 + $this->calculation_values['GCWC'] * $this->calculation_values['COGLY_ROWH1'] * (($this->calculation_values['TCW4'] * $this->calculation_values['COGLY_SPHT4']) - ($this->calculation_values['TCW3'] * $this->calculation_values['COGLY_SPHT3'])) / 4187;
            $this->calculation_values['HBERROR'] = ($HEATIN - $HEATOUT) / $HEATIN * 100;
        }
        else
        {
            $HEATIN = ($this->calculation_values['TON'] * 3024) + $this->calculation_values['QHTG'];
            $HEATOUT = $this->calculation_values['GCWAH'] * $this->calculation_values['COGLY_ROWH1'] * (($this->calculation_values['TCW2H'] * $this->calculation_values['COGLY_SPHT2H']) - ($this->calculation_values['TCW1H'] * $this->calculation_values['COGLY_SPHT1'])) / 4187 + $this->calculation_values['GCWAL'] * $this->calculation_values['COGLY_ROWH1'] * (($this->calculation_values['TCW2L'] * $this->calculation_values['COGLY_SPHT2L']) - ($this->calculation_values['TCW1L'] * $this->calculation_values['COGLY_SPHT1L'])) / 4187 + $this->calculation_values['GCWC'] * $this->calculation_values['COGLY_ROWH1'] * (($this->calculation_values['TCW4'] * $this->calculation_values['COGLY_SPHT4']) - ($this->calculation_values['TCW3'] * $this->calculation_values['COGLY_SPHT3'])) / 4187;
            $this->calculation_values['HBERROR'] = ($HEATIN - $HEATOUT) / $HEATIN * 100;
        }
    }


    public function HEATBALANCE1()
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

            if ($this->calculation_values['GLL'] == 2)
            {
                $this->calculation_values['COGLY_ROWH'] = $vam_base->EG_ROW($this->calculation_values['TCW11'], $this->calculation_values['COGLY']);
                $this->calculation_values['COGLY_SPHT11'] = $vam_base->EG_SPHT($this->calculation_values['TCW11'], $this->calculation_values['COGLY']) * 1000;
                $this->calculation_values['COGLY_SPHTA4'] = $vam_base->EG_SPHT($this->calculation_values['TCWA4'], $this->calculation_values['COGLY']) * 1000;
            }
            else
            {
                $this->calculation_values['COGLY_ROWH'] = $vam_base->PG_ROW($this->calculation_values['TCW11'], $this->calculation_values['COGLY']);
                $this->calculation_values['COGLY_SPHT11'] = $vam_base->PG_SPHT($this->calculation_values['TCW11'], $this->calculation_values['COGLY']) * 1000;
                $this->calculation_values['COGLY_SPHTA4'] = $vam_base->PG_SPHT($this->calculation_values['TCWA4'], $this->calculation_values['COGLY']) * 1000;
            }

            $this->calculation_values['QCW'] = $this->calculation_values['GCW'] * $this->calculation_values['COGLY_ROWH'] * ($this->calculation_values['COGLY_SPHTA4'] + $this->calculation_values['COGLY_SPHT11']) * 0.5 * ($this->calculation_values['TCWA4'] - $this->calculation_values['TCW11']) / 4187;
            $this->calculation_values['QHTG1'] = ($this->calculation_values['TON'] * 3024) / 1.53;
            $this->calculation_values['TEXHAUSTREQ'] = $this->calculation_values['TEXH1'] - ($this->calculation_values['QHTG1'] / ($this->calculation_values['GEXHAUST'] * (($this->calculation_values['CPEX1'] + $this->calculation_values['CPEX2']) / 2) * 0.97));
            $this->calculation_values['QINPUT'] = ($this->calculation_values['TON'] * 3024) + $this->calculation_values['QHTG1'];
            $herr[$ii] = ($this->calculation_values['QINPUT'] - $this->calculation_values['QCW']) * 100 / $this->calculation_values['QINPUT'];
            $ii++;
        }
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

            if ($this->calculation_values['GLL'] == 2)
            {
                $this->calculation_values['COGLY_ROWH'] = $vam_base->EG_ROW($this->calculation_values['TCW11'], $this->calculation_values['COGLY']);
                $this->calculation_values['COGLY_SPHT11'] = $vam_base->EG_SPHT($this->calculation_values['TCW11'], $this->calculation_values['COGLY']) * 1000;
                $this->calculation_values['COGLY_SPHTA4'] = $vam_base->EG_SPHT($this->calculation_values['TCWA4'], $this->calculation_values['COGLY']) * 1000;
            }
            else
            {
                $this->calculation_values['COGLY_ROWH'] = $vam_base->PG_ROW($this->calculation_values['TCW11'], $this->calculation_values['COGLY']);
                $this->calculation_values['COGLY_SPHT11'] = $vam_base->PG_SPHT($this->calculation_values['TCW11'], $this->calculation_values['COGLY']) * 1000;
                $this->calculation_values['COGLY_SPHTA4'] = $vam_base->PG_SPHT($this->calculation_values['TCWA4'], $this->calculation_values['COGLY']) * 1000;
            }

            $this->calculation_values['QCW'] = $this->calculation_values['GCW'] * $this->calculation_values['COGLY_ROWH'] * ($this->calculation_values['COGLY_SPHTA4'] + $this->calculation_values['COGLY_SPHT11']) * 0.5 * ($this->calculation_values['TCWA4'] - $this->calculation_values['TCW11']) / 4187;
            $this->calculation_values['QHTG1'] = ($this->calculation_values['GEXHAUST'] * (($this->calculation_values['CPEX1'] + $this->calculation_values['CPEX2']) / 2) * ($this->calculation_values['TEXH1'] - $this->calculation_values['TEXHAUSTREQ']) * 0.97);
            $this->calculation_values['QINPUT'] = ($this->calculation_values['TON'] * 3024) + $this->calculation_values['QHTG1'];
            $herr[$ii] = ($this->calculation_values['QINPUT'] - $this->calculation_values['QCW']) * 100 / $this->calculation_values['QINPUT'];
            $ii++;
        }

        $jj = 1;
        $COGLY_SPHT = 0;
        $COGLY_SPHTS = 0;
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

            if ($this->calculation_values['GLL'] == 2)
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

            $QCWS = $this->calculation_values['GCW'] * $this->calculation_values['COGLY_ROWH'] * ($COGLY_SPHTS + $COGLY_SPHT) * 0.5 * ($this->calculation_values['TCWS'] - 40) / 4187;
            $this->calculation_values['QINPUTS'] = ($this->calculation_values['TON'] * 1512) + ($this->calculation_values['GEXHAUST'] * (($this->calculation_values['CPEX1'] + $this->calculation_values['CPEX2']) / 2) * ($this->calculation_values['TEXH1'] - $this->calculation_values['TEXHAUSTREQ']) * 0.97 * 0.75);
            $herr1[$jj] = ($this->calculation_values['QINPUTS'] - $QCWS) * 100 / $this->calculation_values['QINPUTS'];
            $jj++;
        }
    }


    public function HEATBALANCEECO1()
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

            if ($this->calculation_values['GLL'] == 2)
            {
                $this->calculation_values['COGLY_ROWH'] = $vam_base->EG_ROW($this->calculation_values['TCW11'], $this->calculation_values['COGLY']);
                $this->calculation_values['COGLY_SPHT11'] = $vam_base->EG_SPHT($this->calculation_values['TCW11'], $this->calculation_values['COGLY']) * 1000 / 4187;
                $this->calculation_values['COGLY_SPHTA4'] = $vam_base->EG_SPHT($this->calculation_values['TCWA4'], $this->calculation_values['COGLY']) * 1000 / 4187;
            }
            else
            {
                $this->calculation_values['COGLY_ROWH'] = $vam_base->PG_ROW($this->calculation_values['TCW11'], $this->calculation_values['COGLY']);
                $this->calculation_values['COGLY_SPHT11'] = $vam_base->PG_SPHT($this->calculation_values['TCW11'], $this->calculation_values['COGLY']) * 1000 / 4187;
                $this->calculation_values['COGLY_SPHTA4'] = $vam_base->PG_SPHT($this->calculation_values['TCWA4'], $this->calculation_values['COGLY']) * 1000 / 4187;
            }

            $this->calculation_values['QCW'] = $this->calculation_values['GCW'] * $this->calculation_values['COGLY_ROWH'] * (($this->calculation_values['TCWA4'] * $this->calculation_values['COGLY_SPHTA4']) - ($this->calculation_values['TCW11'] * $this->calculation_values['COGLY_SPHT11']));
            $this->calculation_values['QHTG1'] = ($this->calculation_values['TON'] * 3024) / 1.53;
            $this->calculation_values['TEXH3'] = $this->calculation_values['TEXH1'] - ($this->calculation_values['QHTG1'] / ($this->calculation_values['GEXHAUST'] * (($this->calculation_values['CPEX1'] + $this->calculation_values['CPEX3']) / 2) * 0.97));
            $this->calculation_values['QINPUT'] = ($this->calculation_values['TON'] * 3024) + ($this->calculation_values['GEXHAUST'] * (($this->calculation_values['CPEX1'] + $this->calculation_values['CPEX3']) / 2) * ($this->calculation_values['TEXH1'] - $this->calculation_values['TEXH3']) * 0.97);
            $herr[$ii] = ($this->calculation_values['QINPUT'] - $this->calculation_values['QCW']) * 100 / $this->calculation_values['QINPUT'];
            $ii++;
        }
    }


    public function HEATBALANCEECO()
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

            if ($this->calculation_values['GLL'] == 2)
            {
                $this->calculation_values['COGLY_ROWH'] = $vam_base->EG_ROW($this->calculation_values['TCW11'], $this->calculation_values['COGLY']);
                $this->calculation_values['COGLY_SPHT11'] = $vam_base->EG_SPHT($this->calculation_values['TCW11'], $this->calculation_values['COGLY']) * 1000 / 4187;
                $this->calculation_values['COGLY_SPHTA4'] = $vam_base->EG_SPHT($this->calculation_values['TCWA4'], $this->calculation_values['COGLY']) * 1000 / 4187;
            }
            else
            {
                $this->calculation_values['COGLY_ROWH'] = $vam_base->PG_ROW($this->calculation_values['TCW11'], $this->calculation_values['COGLY']);
                $this->calculation_values['COGLY_SPHT11'] = $vam_base->PG_SPHT($this->calculation_values['TCW11'], $this->calculation_values['COGLY']) * 1000 / 4187;
                $this->calculation_values['COGLY_SPHTA4'] = $vam_base->PG_SPHT($this->calculation_values['TCWA4'], $this->calculation_values['COGLY']) * 1000 / 4187;
            }

            $this->calculation_values['QCW'] = $this->calculation_values['GCW'] * $this->calculation_values['COGLY_ROWH'] * (($this->calculation_values['TCWA4'] * $this->calculation_values['COGLY_SPHTA4']) - ($this->calculation_values['TCW11'] * $this->calculation_values['COGLY_SPHT11']));
            $this->calculation_values['QHTG1'] = ($this->calculation_values['GEXHAUST'] * (($this->calculation_values['CPEX1'] + $this->calculation_values['CPEX3']) / 2) * ($this->calculation_values['TEXH1'] - $this->calculation_values['TEXH3']) * 0.97);
            $this->calculation_values['QINPUT'] = ($this->calculation_values['TON'] * 3024) + ($this->calculation_values['GEXHAUST'] * (($this->calculation_values['CPEX1'] + $this->calculation_values['CPEX3']) / 2) * ($this->calculation_values['TEXH1'] - $this->calculation_values['TEXH3']) * 0.97);
            $herr[$ii] = ($this->calculation_values['QINPUT'] - $this->calculation_values['QCW']) * 100 / $this->calculation_values['QINPUT'];
            $ii++;
        }

        $jj = 1;
        $COGLY_SPHT = 0;
        $COGLY_SPHTS = 0;
        $herr1 = array();
        $tcws = array();

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

            if ($this->calculation_values['GLL'] == 2)
            {
                $this->calculation_values['COGLY_ROWH'] = $vam_base->EG_ROW(40, $this->calculation_values['COGLY']);
                $COGLY_SPHT = $vam_base->EG_SPHT(40, $this->calculation_values['COGLY']) * 1000 / 4187;
                $COGLY_SPHTS = $vam_base->EG_SPHT($this->calculation_values['TCWS'], $this->calculation_values['COGLY']) * 1000 / 4187;
            }
            else
            {
                $this->calculation_values['COGLY_ROWH'] = $vam_base->PG_ROW(40, $this->calculation_values['COGLY']);
                $COGLY_SPHT = $vam_base->PG_SPHT(40, $this->calculation_values['COGLY']) * 1000 / 4187;
                $COGLY_SPHTS = $vam_base->PG_SPHT($this->calculation_values['TCWS'], $this->calculation_values['COGLY']) * 1000 / 4187;
            }

            $QCWS = $this->calculation_values['GCW'] * $this->calculation_values['COGLY_ROWH'] * (($this->calculation_values['TCWS'] * $COGLY_SPHTS) - (40 * $COGLY_SPHT));
            $this->calculation_values['QINPUTS'] = ($this->calculation_values['TON'] * 1512) + ($this->calculation_values['GEXHAUST'] * (($this->calculation_values['CPEX1'] + $this->calculation_values['CPEX3']) / 2) * ($this->calculation_values['TEXH1'] - $this->calculation_values['TEXH3']) * 0.97 * 0.75);
            $herr1[$jj] = ($this->calculation_values['QINPUTS'] - $QCWS) * 100 / $this->calculation_values['QINPUTS'];
            $jj++;
        }
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
                   if (!$this->SEPARATION_HEIGHT_LTG())
                   {
                       $this->calculation_values['Notes'] = $this->notes['NOTES_SEP_HT_LTG'];
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
                           if (($this->calculation_values['TEXH2'] - $this->calculation_values['T4']) < 15)        //03/01/2018                          //Difference between HTG Exhaust Temp and Intermediate LIBR Outlet TEMP from HTG
                           {
                                $this->calculation_values['Notes'] = $this->notes['NOTES_FAIL_EDTEMP'];
                                return false;
                           }
                           else
                           {
                               if (($this->calculation_values['TEXH2'] - $this->calculation_values['T5']) < 0)
                               {
                                   $this->calculation_values['Notes'] = $this->notes['NOTES_FAIL_EDTEMP'];
                                   return false;
                               }
                               else
                               {
                                   if (($this->calculation_values['ECO'] == 'yes') && ($this->calculation_values['QEXR'] > $this->calculation_values['QEXAV'] || $this->calculation_values['TEXHOUT'] > $this->calculation_values['TEXH3']))
                                   {
                                       $this->calculation_values['Notes'] = $this->notes['NOTES_FAIL_EDFLOW'];
                                       return false;
                                   }
                                   else if (($this->calculation_values['ECO'] == 'no') && ($this->calculation_values['QEXR'] > $this->calculation_values['QEXAV'] || $this->calculation_values['TEXHAUSTREQ'] < $this->calculation_values['TEXHOUT']))
                                   {
                                       $this->calculation_values['Notes'] = $this->notes['NOTES_FAIL_EDFLOW'];
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
        
        return true;
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
        else
        {
            return true;
        }
    }

    public function SEPARATION_HEIGHT_HTG()
    {
         $this->calculation_values['HTG_HSEP_DS'] = 0; $this->calculation_values['HTG_HSEP_DS_REQ'] = 0;

         $this->calculation_values['HTG_HSEP_DS'] = 0;

         if ($this->calculation_values['MODEL'] == 60 || $this->calculation_values['MODEL'] == 90)
         {
             $this->calculation_values['HTG_HSEP_DS'] = 229.95 / 288.56;
         }
         if ($this->calculation_values['MODEL'] == 75 || $this->calculation_values['MODEL'] == 110)
         {
             $this->calculation_values['HTG_HSEP_DS'] = 208.95 / 288.56;
         }
         if ($this->calculation_values['MODEL'] == 150)
         {
             $this->calculation_values['HTG_HSEP_DS'] = 237.8 / 319.8;
         }
         if ($this->calculation_values['MODEL'] == 175 || $this->calculation_values['MODEL'] == 210)
         {
             $this->calculation_values['HTG_HSEP_DS'] = 216.8 / 324.4;
         }
         if ($this->calculation_values['MODEL'] == 250)
         {
             $this->calculation_values['HTG_HSEP_DS'] = 195.8 / 324.4;
         }
         if ($this->calculation_values['MODEL'] == 310 || $this->calculation_values['MODEL'] == 350 || $this->calculation_values['MODEL'] == 410)
         {
             $this->calculation_values['HTG_HSEP_DS'] = 237.0 / 377;
         }
         if ($this->calculation_values['MODEL'] == 470 || $this->calculation_values['MODEL'] == 530 || $this->calculation_values['MODEL'] == 580)
         {
             $this->calculation_values['HTG_HSEP_DS'] = 220 / 426.5;
         }
         if ($this->calculation_values['MODEL'] == 630 || $this->calculation_values['MODEL'] == 710)
         {
             $this->calculation_values['HTG_HSEP_DS'] = 238.3 / 476.5;
         }
         if ($this->calculation_values['MODEL'] == 760 || $this->calculation_values['MODEL'] == 810 || $this->calculation_values['MODEL'] == 900)
         {
             $this->calculation_values['HTG_HSEP_DS'] = 275 / 526.4;
         }
         if ($this->calculation_values['MODEL'] == 1010 || $this->calculation_values['MODEL'] == 1130)
         {
             $this->calculation_values['HTG_HSEP_DS'] = 275 / 526.4;
         }
         if ($this->calculation_values['MODEL'] == 1260 || $this->calculation_values['MODEL'] == 1380)
         {
             $this->calculation_values['HTG_HSEP_DS'] = 270 / 587.3;
         }
         if ($this->calculation_values['MODEL'] == 1560 || $this->calculation_values['MODEL'] == 1690 || $this->calculation_values['MODEL'] == 1890 || $this->calculation_values['MODEL'] == 2130 || $this->calculation_values['MODEL'] == 2270 || $this->calculation_values['MODEL'] == 2560)
         {
             $this->calculation_values['HTG_HSEP_DS'] = 257 / 635.6;
         }


         $this->calculation_values['HTG_HSEP_DS_REQ'] = (($this->calculation_values['QHTG'] / 860) / $this->calculation_values['AHTG']) * 0.015;

         if ($this->calculation_values['HTG_HSEP_DS'] < $this->calculation_values['HTG_HSEP_DS_REQ'])
             return false;
         else
             return true;
    }

    public function SEPARATION_HEIGHT_LTG()
    {
        
         $this->calculation_values['LTG_HSEP_DS_REQ'] = 0;
         
         $this->calculation_values['LTG_HSEP_DS'] = 0;

         $this->calculation_values['LTG_HSEP_DS'] = 0;

         if ($this->calculation_values['MODEL'] == 60 || $this->calculation_values['MODEL'] == 90)
         {
             $this->calculation_values['LTG_HSEP_DS'] = 192.8 / 228.35;
         }
         if ($this->calculation_values['MODEL'] == 75 || $this->calculation_values['MODEL'] == 110)
         {
             $this->calculation_values['LTG_HSEP_DS'] = 173.8 / 228.35;
         }
         if ($this->calculation_values['MODEL'] == 150)
         {
             $this->calculation_values['LTG_HSEP_DS'] = 211.8 / 250.4;
         }
         if ($this->calculation_values['MODEL'] == 175 || $this->calculation_values['MODEL'] == 210)
         {
             $this->calculation_values['LTG_HSEP_DS'] = 192.8 / 250.4;
         }
         if ($this->calculation_values['MODEL'] == 250)
         {
             $this->calculation_values['LTG_HSEP_DS'] = 173.8 / 250.4;
         }
         if ($this->calculation_values['MODEL'] == 310 || $this->calculation_values['MODEL'] == 350 || $this->calculation_values['MODEL'] == 410)
         {
             $this->calculation_values['LTG_HSEP_DS'] = 205 / 393.2;
         }
         if ($this->calculation_values['MODEL'] == 470 || $this->calculation_values['MODEL'] == 530 || $this->calculation_values['MODEL'] == 580)
         {
             $this->calculation_values['LTG_HSEP_DS'] = 219.9 / 481.2;
         }
         if ($this->calculation_values['MODEL'] == 630 || $this->calculation_values['MODEL'] == 710)
         {
             $this->calculation_values['LTG_HSEP_DS'] = 239.9 / 525.2;
         }
         if ($this->calculation_values['MODEL'] == 760 || $this->calculation_values['MODEL'] == 810 || $this->calculation_values['MODEL'] == 900 || $this->calculation_values['MODEL'] == 1010 || $this->calculation_values['MODEL'] == 1130)
         {
             $this->calculation_values['LTG_HSEP_DS'] = 244.4 / 548.2;
         }
         if ($this->calculation_values['MODEL'] == 1260 || $this->calculation_values['MODEL'] == 1380)
         {
             $this->calculation_values['LTG_HSEP_DS'] = 258.9 / 617.2;
         }
         if ($this->calculation_values['MODEL'] == 1560 || $this->calculation_values['MODEL'] == 1690)
         {
             $this->calculation_values['LTG_HSEP_DS'] = 309.4 / 732.2;
         }
         if ($this->calculation_values['MODEL'] == 1890 || $this->calculation_values['MODEL'] == 2130 || $this->calculation_values['MODEL'] == 2270 || $this->calculation_values['MODEL'] == 2560)
         {
             $this->calculation_values['LTG_HSEP_DS'] = 319.4 / 806.9;
         }


         $this->calculation_values['LTG_HSEP_DS_REQ'] = (($this->calculation_values['QREFLTG'] / 860) / $this->calculation_values['ALTG']) * 0.015;

         if ($this->calculation_values['LTG_HSEP_DS'] < $this->calculation_values['LTG_HSEP_DS_REQ'])
             return false;
         else
             return true;
     }


    public function HCAP()
     {

         $this->calculation_values['HIGHCAP'] = 0;

         if ($this->calculation_values['MODEL'] == 60)
         {
             $this->calculation_values['HIGHCAP'] = 70;
         }
         else if ($this->calculation_values['MODEL'] == 75)
         {
             $this->calculation_values['HIGHCAP'] = 88;
         }
         else if ($this->calculation_values['MODEL'] == 90)
         {
             $this->calculation_values['HIGHCAP'] = 105;
         }
         else if ($this->calculation_values['MODEL'] == 110)
         {
             $this->calculation_values['HIGHCAP'] = 128;
         }
         else if ($this->calculation_values['MODEL'] == 150)
         {
             $this->calculation_values['HIGHCAP'] = 174;
         }
         else if ($this->calculation_values['MODEL'] == 175)
         {
             $this->calculation_values['HIGHCAP'] = 203;
         }
         else if ($this->calculation_values['MODEL'] == 210)
         {
             $this->calculation_values['HIGHCAP'] = 244;
         }
         else if ($this->calculation_values['MODEL'] == 250)
         {
             $this->calculation_values['HIGHCAP'] = 290;
         }
         else if ($this->calculation_values['MODEL'] == 310)
         {
             $this->calculation_values['HIGHCAP'] = 360;
         }
         else if ($this->calculation_values['MODEL'] == 350)
         {
             $this->calculation_values['HIGHCAP'] = 410;
         }
         else if ($this->calculation_values['MODEL'] == 410)
         {
             $this->calculation_values['HIGHCAP'] = 490;
         }
         else if ($this->calculation_values['MODEL'] == 470)
         {
             $this->calculation_values['HIGHCAP'] = 550;
         }
         else if ($this->calculation_values['MODEL'] == 530)
         {
             $this->calculation_values['HIGHCAP'] = 630;
         }
         else if ($this->calculation_values['MODEL'] == 580)
         {
             $this->calculation_values['HIGHCAP'] = 680;
         }
         else if ($this->calculation_values['MODEL'] == 630)
         {
             $this->calculation_values['HIGHCAP'] = 750;
         }
         else if ($this->calculation_values['MODEL'] == 710)
         {
             $this->calculation_values['HIGHCAP'] = 830;
         }
         else if ($this->calculation_values['MODEL'] == 760)
         {
             $this->calculation_values['HIGHCAP'] = 900;
         }
         else if ($this->calculation_values['MODEL'] == 810)
         {
             $this->calculation_values['HIGHCAP'] = 960;
         }
         else if ($this->calculation_values['MODEL'] == 900)
         {
             $this->calculation_values['HIGHCAP'] = 1080;
         }
         else if ($this->calculation_values['MODEL'] == 1010)
         {
             $this->calculation_values['HIGHCAP'] = 1210;
         }
         else if ($this->calculation_values['MODEL'] == 1130)
         {
             $this->calculation_values['HIGHCAP'] = 1360;
         }
         else if ($this->calculation_values['MODEL'] == 1260)
         {
             $this->calculation_values['HIGHCAP'] = 1500;
         }
         else if ($this->calculation_values['MODEL'] == 1380)
         {
             $this->calculation_values['HIGHCAP'] = 1630;
         }
         else if ($this->calculation_values['MODEL'] == 1560)
         {
             $this->calculation_values['HIGHCAP'] = 1850;
         }
         else if ($this->calculation_values['MODEL'] == 1690)
         {
             $this->calculation_values['HIGHCAP'] = 2000;
         }
         else if ($this->calculation_values['MODEL'] == 1890)
         {
             $this->calculation_values['HIGHCAP'] = 2240;
         }
         else if ($this->calculation_values['MODEL'] == 2130)
         {
             $this->calculation_values['HIGHCAP'] = 2530;
         }
         else if ($this->calculation_values['MODEL'] == 2270)
         {
             $this->calculation_values['HIGHCAP'] = 2670;
         }
         else if ($this->calculation_values['MODEL'] == 2560)
         {
             $this->calculation_values['HIGHCAP'] = 2840;
         }
         //else if ($this->calculation_values['MODEL'] == 2600)
         //{
         //    $this->calculation_values['HIGHCAP'] = 3173;
         //}
         //else if ($this->calculation_values['MODEL'] == 2800)
         //{
         //    $this->calculation_values['HIGHCAP'] = 3378;
         //}
         else
         {
             $this->calculation_values['HIGHCAP'] = 0;
         }


         if ($this->calculation_values['TON'] > $this->calculation_values['HIGHCAP'])
             return false;
         else
             return true;
     }
     
    
    public function RESULT_CALCULATE()
    {
        $notes = array();
        $selection_notes = array();
        $this->calculation_values['Notes'] = "";
        $this->calculation_values['selection_notes'] = "";

         if ($this->calculation_values['T13'] > $this->calculation_values['AT13'])
         {
            $this->calculation_values['Result'] = "FAILED";
            $this->calculation_values['Notes'] = $this->notes['NOTES_FAIL_TEMP'];
            return false;
         }           

         if ($this->calculation_values['ECO'] == 'no')
         {
             $this->calculation_values['COP'] = ($this->calculation_values['TON'] * 3024) / ($this->calculation_values['GEXHAUST'] * (($this->calculation_values['CPEX1'] + $this->calculation_values['CPEX2']) / 2) * ($this->calculation_values['TEXH1'] - $this->calculation_values['TEXHAUSTREQ']) * 0.97);

             if ($this->calculation_values['COP'] > 1.53)
             {
                 $this->HEATBALANCE1();
             }
             else
             {
                 $this->HEATBALANCE();
             }

             //Assign the output properties of chiller
             $this->calculation_values['HeatInput'] = $this->calculation_values['QHTG1'];
             $this->calculation_values['HeatRejected'] = $this->calculation_values['TON'] * 3024 + $this->calculation_values['QHTG1'];

             $this->calculation_values['CoolingWaterOutTemperature'] = $this->calculation_values['TCWA4'];
             $this->calculation_values['ExhaustGasFlowRate'] = $this->calculation_values['GEXHAUST'];
             $this->calculation_values['ExhaustConnectionDiameter'] = $this->calculation_values['IEXHC'];
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
             $this->calculation_values['ActExhaustGasTempOut'] = $this->calculation_values['TEXHAUSTREQ'];
             $this->calculation_values['AvgExhGasCp'] = ($this->calculation_values['CPEX1'] + $this->calculation_values['CPEX2']) / 2;
             $this->calculation_values['BypassFlow'] = $this->calculation_values['GCW'] - $this->calculation_values['GCWC'];
             $this->calculation_values['COP'] = ($this->calculation_values['TON'] * 3024) / $this->calculation_values['QHTG1'];

             $this->calculation_values['ModeBCapacity'] = $this->calculation_values['TON'] * 0.5;
             $this->calculation_values['ModeBChilledWaterOutTemperature'] = $this->calculation_values['TCHW11'] - ($this->calculation_values['DT'] / 2);
             $this->calculation_values['ModeBCoolingWaterInTemperature'] = "40";
             $this->calculation_values['ModeBCoolingWaterOutTemperature'] = $this->calculation_values['TCWS'];
             $this->calculation_values['ModeBExhaustGasFlowRate'] = $this->calculation_values['GEXHAUST'] * 0.75;
             $this->calculation_values['ModeBHeatInput'] = $this->calculation_values['QHTG1'] * 0.75;
             $this->calculation_values['ModeBHeatRejected'] = ($this->calculation_values['TON'] * 1512) + ($this->calculation_values['QHTG1'] * 0.75);
             $this->calculation_values['ModeBExhaustConnectionDiameter'] = $this->calculation_values['IEXHC'];
         }
         else if ($this->calculation_values['ECO'] == 'yes')
         {

             $this->calculation_values['COP'] = ($this->calculation_values['TON'] * 3024) / ($this->calculation_values['GEXHAUST'] * (($this->calculation_values['CPEX1'] + $this->calculation_values['CPEX3']) / 2) * ($this->calculation_values['TEXH1'] - $this->calculation_values['TEXH3']) * 0.97);

             if ($this->calculation_values['COP'] > 1.53)
             {
                 $this->HEATBALANCEECO1();
             }
             else
             {
                 $this->HEATBALANCEECO();
             }

             //Assign the output properties of chiller
             $this->calculation_values['HeatInput'] = $this->calculation_values['QHTG1'];
             $this->calculation_values['HeatRejected'] = $this->calculation_values['TON'] * 3024 + $this->calculation_values['QHTG1'];

             $this->calculation_values['CoolingWaterOutTemperature'] = $this->calculation_values['TCWA4'];
             $this->calculation_values['ExhaustGasFlowRate'] = $this->calculation_values['GEXHAUST'];
             $this->calculation_values['ExhaustConnectionDiameter'] = $this->calculation_values['IEXHC'];
             
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

             $this->calculation_values['ActExhaustGasTempOut'] = $this->calculation_values['TEXH3'];
             $this->calculation_values['AvgExhGasCp'] = ($this->calculation_values['CPEX1'] + $this->calculation_values['CPEX3']) / 2;
             $this->calculation_values['BypassFlow'] = $this->calculation_values['GCW'] - $this->calculation_values['GCWC'];
             $this->calculation_values['COP'] = ($this->calculation_values['TON'] * 3024) / $this->calculation_values['QHTG1'];

             $this->calculation_values['ModeBCapacity'] = $this->calculation_values['TON'] * 0.5;
             $this->calculation_values['ModeBChilledWaterOutTemperature'] = $this->calculation_values['TCHW11'] - ($this->calculation_values['DT'] / 2);
             $this->calculation_values['ModeBCoolingWaterInTemperature'] = "40";
             $this->calculation_values['ModeBCoolingWaterOutTemperature'] = $this->calculation_values['TCWS'];
             $this->calculation_values['ModeBExhaustGasFlowRate'] = $this->calculation_values['GEXHAUST'] * 0.75;
             $this->calculation_values['ModeBHeatInput'] = $this->calculation_values['QHTG1'] * 0.75;
             $this->calculation_values['ModeBHeatRejected'] = ($this->calculation_values['TON'] * 1512) + ($this->calculation_values['QHTG1'] * 0.75);
             $this->calculation_values['ModeBExhaustConnectionDiameter'] = $this->calculation_values['IEXHC'];
         }

         if (!$this->CONCHECK())
         {
            $this->calculation_values['Result'] = "FAILED";
                         
            return false;
         }

         $this->calculation_values['Result'] = "FAILED";

         if (($this->calculation_values['P3'] - $this->calculation_values['P1L']) < 35)
         {
             array_push($selection_notes,$this->notes['NOTES_LTHE_PRDROP']);
             $this->calculation_values['HHType'] = "NonStandard";
         }
         if (($this->calculation_values['P4'] - $this->calculation_values['P3']) < 350)
         {
            array_push($selection_notes,$this->notes['NOTES_HTHE_PRDROP']);
            $this->calculation_values['HHType'] = "NonStandard";
         }
         if ($this->calculation_values['VELEVA'] == 1)
         {
             array_push($selection_notes,$this->notes['NOTES_EC_EVAP']);
             $this->calculation_values['ECinEva'] = 1;
         }
         if (!$this->calculation_values['isStandard'])
         {
             array_push($selection_notes,$this->notes['NOTES_NSTD_TUBE_METAL']);

         }
         if ($this->calculation_values['TCHW12'] < 4.49)
         {
             array_push($selection_notes,$this->notes['NOTES_COST_COW_SOV']);

         }
         if ($this->calculation_values['TCHW12'] < 4.49)
         {
             array_push($selection_notes,$this->notes['NOTES_NONSTD_XSTK_MC']);
         }
         if ($this->calculation_values['GCWC'] < $this->calculation_values['GCW'])
         {
             array_push($selection_notes,$this->notes['NOTES_OUTPUT_GA']);
             $bypass = $this->notes['NOTES_OUTPUT_BYPASS'].round($this->calculation_values['GCW'] - $this->calculation_values['GCWC'], 2)."m3/hr";
             array_push($selection_notes,$bypass);
         }
              
         if ($this->calculation_values['QEXR'] < (0.95 * $this->calculation_values['QEXAV']))
         {
            array_push($selection_notes,$this->notes['NOTES_EG_REC']);
            if($this->calculation_values['ECO'] == 'yes'){
                $bypass = $this->notes['NOTES_TEMP']." ".round($this->calculation_values['TEXH3'], 2)." ??C";
            }
            else{
                $bypass = $this->notes['NOTES_TEMP']." ".round($this->calculation_values['TEXHAUSTREQ'], 2)." ??C";
            }
            
            array_push($selection_notes,$bypass);
         }
         if ($this->calculation_values['TUU'] == "ari")
         {
             array_push($selection_notes,$this->notes['NOTES_ARI']);
         }
         
         if ($this->calculation_values['IEXHC'] == 0)
         {
            array_push($selection_notes,$this->notes['NOTES_EXH_CON']);
            
         }
      
         //sk 19/02/07
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
                array_push($selection_notes,$this->notes['NOTES_RED_COW']);
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
                array_push($selection_notes,$this->notes['NOTES_RED_COW']);
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
         $this->calculation_values['selection_notes'] = $selection_notes;
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
            'economizer',
            'exhaust_gas_in',
            'gas_in_min',
            'gas_in_max',
            'exhaust_gas_out',
            'gas_out_min',
            'gas_flow',
            'gas_flow_load',
            'design_load',
            'pressure_drop',
            'engine_type',
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

        $version = DB::table('versions')->orderBy('id', 'desc')->first();
        $version_date = date('d-M-Y', strtotime($version->created_at));


        $standard_values = array('evaporator_thickness' => 0,'absorber_thickness' => 0,'condenser_thickness' => 0,'evaporator_thickness_min_range' => 0,'evaporator_thickness_max_range' => 0,'absorber_thickness_min_range' => 0,'absorber_thickness_max_range' => 0,'condenser_thickness_min_range' => 0,'condenser_thickness_max_range' => 0,'fouling_chilled_water_value' => 0,'fouling_cooling_water_value' => 0,'evaporator_thickness_change' => 1,'absorber_thickness_change' => 1,'condenser_thickness_change' => 1,'fouling_chilled_water_checked' => 0,'fouling_cooling_water_checked' => 0,'fouling_chilled_water_disabled' => 1,'fouling_cooling_water_disabled' => 1,'fouling_chilled_water_value_disabled' => 1,'fouling_cooling_water_value_disabled' => 1,'region_name'=>$region_name,'region_type'=>$region_type,'version' => $version->version,'version_date' => $version_date);


        $form_values = collect($form_values)->union($standard_values);

        return $form_values;
    }  

    public function getCalculationValues($model_number){

        $model_number = (int)$model_number;
        $chiller_calculation_values = ChillerCalculationValue::where('code',$this->model_code)->where('min_model',$model_number)->first();

        $calculation_values = $chiller_calculation_values->calculation_values;
        $calculation_values = json_decode($calculation_values,true);

        $calculation_values = array_only($calculation_values, ['LE',
            'AHR',
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
            'SL6',
            'SL7',
            'SL8',
            'TNC',
            'UHR',
            'AABS',
            'ACON',
            'ADHE',
            'AEVA',
            'AHTG',
            'ALTG',
            'AT13',
            'KABS',
            'KCON',
            'KEVA',
            'PNB1',
            'PNB2',
            'PSL2',
            'PSLI',
            'PSLO',
            'TCWA',
            'TNAA',
            'TNEV',
            'UDHE',
            'UHTG',
            'ULTG',
            'AHTHE',
            'ALTHE',
            'UHTHE',
            'ULTHE',
            'MODEL1',
            'VEMIN1',
            'TEPMAX',
            'm_maxCHWWorkPressure',
            'm_maxCOWWorkPressure',
            'm_maxHWWorkPressure',
            'm_maxSteamWorkPressure',
            'm_maxSteamDesignPressure',
            'm_DesignPressure',
            'm_maxHWDesignPressure',
            'm_dCondensateDrainPressure',
            'm_dMinCondensateDrainTemperature',
            'm_dMaxCondensateDrainTemperature',
            'ChilledConnectionDiameter',
            'CoolingConnectionDiameter',
            'Length',
            'Width',
            'Height',
            'ClearanceForTubeRemoval',
            'AbsorbentPumpMotorKW',
            'AbsorbentPumpMotorAmp',
            'RefrigerantPumpMotorKW',
            'RefrigerantPumpMotorAmp',
            'PurgePumpMotorKW',
            'PurgePumpMotorAmp',
            'A_SFACTOR',
            'B_SFACTOR',
            'A_AT13',
            'B_AT13',
            'ALTHE_F',
            'AHTHE_F',
            'AHR_F',
            'EX_AT13',
            'EX_KEVA',
            'EX_KABS',
            'EX_DryWeight',
            'USA_AbsorbentPumpMotorAmp',
            'USA_RefrigerantPumpMotorAmp',
            'USA_AbsorbentPumpMotorKW',
            'USA_RefrigerantPumpMotorKW',
            'USA_PurgePumpMotorAmp',
            'USA_PurgePumpMotorKW',
            'MCA',
            'MOP',
            'min_chilled_water_out'

        ]);

        return $calculation_values;
    }    


    public function testingE2Calculation($datas){
        
        $this->model_values = $datas;

        $vam_base = new VamBaseController();
        $this->notes = $vam_base->getNotesError();

        $this->model_values['metallurgy_standard'] = $vam_base->getBoolean($this->model_values['metallurgy_standard']);
        $this->RANGECAL();


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
