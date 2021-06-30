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

    public function postResetH1(Request $request){
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
                $chiller_form_values['fouling_factor']="ari";
            else
                $chiller_form_values['fouling_factor']="standard";

        }


        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$this->model_code)->where('min_model','<=',$model_number)->where('max_model','>=',$model_number)->first();
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

    public function postH1(Request $request){

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

        $this->updateInputs();

        try {
            $this->WATERPROP();
            $velocity_status = $this->VELOCITY();
        } 
        catch (\Exception $e) {
            // Log::info($e);

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
            // Log::info($e);

            return response()->json(['status'=>false,'msg'=>$this->notes['NOTES_ERROR']]);
        }
        

        $calculated_values = $unit_conversions->reportUnitConversion($this->calculation_values,$this->model_code);

        // log::info($this->calculation_values);

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

        

        $vam_base = new VamBaseController();
        $language_datas = $vam_base->getLanguageDatas();
        $units_data = $vam_base->getUnitsData();

        $view = view("reports.report_h1", ['name' => $name,'phone' => $phone,'project' => $project,'calculation_values' => $calculation_values,'evaporator_name' => $evaporator_name,'absorber_name' => $absorber_name,'condenser_name' => $condenser_name,'unit_set' => $unit_set,'units_data' => $units_data,'language_datas' => $language_datas])->render();

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

        $redirect_url = route('download.h1report', ['user_report_id' => $user_report->id,'type' => $report_type]);
        
        return response()->json(['status'=>true,'msg'=>'Ajax Datas','redirect_url'=>$redirect_url]);
        
    }

    public function downloadReport($user_report_id,$type){

        $user_report = UserReport::find($user_report_id);
        if(!$user_report){
            return response()->json(['status'=>false,'msg'=>'Invalid Report']);
        }

        if($type == 'save_word'){
            $report_controller = new ReportController();
            $file_name = $report_controller->wordFormatH1($user_report_id,$this->model_code);

            // $file_name = "S2-Steam-Fired-Series-".Auth::user()->id.".docx";
            return response()->download(storage_path($file_name));
        }

        $calculation_values = json_decode($user_report->calculation_values,true);
        
        $name = $user_report->name;
        $project = $user_report->project;
        $phone = $user_report->phone;

        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$this->model_code)
                                        ->where('min_model','<=',$calculation_values['MODEL'])->where('max_model','>=',$calculation_values['MODEL'])->first();

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




        $pdf = PDF::loadView('reports.report_pdf_h1', ['name' => $name,'phone' => $phone,'project' => $project,'calculation_values' => $calculation_values,'evaporator_name' => $evaporator_name,'absorber_name' => $absorber_name,'condenser_name' => $condenser_name,'unit_set' => $unit_set,'units_data' => $units_data,'language_datas' => $language_datas,'language' => $language]);

        return $pdf->download('h1.pdf');

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
        $this->calculation_values['HotWaterFlow'] = 0;
        $this->calculation_values['HotWaterFrictionLoss'] = 0;

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

        if ($this->calculation_values['TCW11'] <= 32.0)
            $this->calculation_values['TCWA'] = $this->calculation_values['TCW11'];
        else
            $this->calculation_values['TCWA'] = 32.0;
            

        // $this->calculation_values['SFACTOR'] = $this->calculation_values['A_SFACTOR'] - ($this->calculation_values['B_SFACTOR'] * $this->calculation_values['TCWA']);



        $this->calculation_values['IDG'] = 0.0168;              //19 MM 1 THK SS PLAIN TUBES
        

       if($this->calculation_values['region_type'] == 2 || $this->calculation_values['region_type'] == 3)
       {
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

        $this->calculation_values['AEVAL'] = $this->calculation_values['AEVAH'] = $this->calculation_values['AEVA'] / 2;
        $this->calculation_values['AABSL'] = $this->calculation_values['AABSH'] = $this->calculation_values['AABS'] / 2;

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

    private function WATERPROP()
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


    private function VELOCITY()
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
            $this->calculation_values['TCW1H'] = $this->calculation_values['TCW11'];
            $TCW14 = $this->calculation_values['TCW4'];
            $t11 = array();
            $t3n1 = array();
            $t12 = array();
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
                $AILMTDC = (5 * $this->calculation_values['FFCOW1']) * ($this->calculation_values['GCW'] * $this->calculation_values['COGLY_ROWH1'] * ($this->calculation_values['COGLY_SPHT1'] / 4187) * ($this->calculation_values['TCW4'] - $this->calculation_values['TCW11']) * 3.968 / ($ARICOWA * 3.28084 * 3.28084));
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
                $this->calculation_values['TCW1'] = $ARITCWI;
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

        $vam_base = new VamBaseController();
        
        $tchw2h = array();
        $err1 = array();
        $ferr1 = array();

        if ($this->calculation_values['TCW11'] <= 32.0)
            $this->calculation_values['TCWA'] = $this->calculation_values['TCW11'];
        else
            $this->calculation_values['TCWA'] = 32.0;

        $this->calculation_values['GDIL'] = 70 * $this->calculation_values['MOD1'];
        $this->calculation_values['QEVA'] = $this->calculation_values['TON'] * 3024;
        $this->calculation_values['GCHW'] = $this->calculation_values['TON'] * 3024 / (($this->calculation_values['TCHW1H'] - $this->calculation_values['TCHW2L']) * $this->calculation_values['CHGLY_ROW12'] * $this->calculation_values['CHGLY_SPHT12'] / 4187);
        $this->calculation_values['LMTDEVA'] = $this->calculation_values['QEVA'] / ($this->calculation_values['UEVA'] * $this->calculation_values['AEVA']);
        $this->calculation_values['T1'] = $this->calculation_values['TCHW2L'] - ($this->calculation_values['TCHW1H'] - $this->calculation_values['TCHW2L']) / (exp(($this->calculation_values['TCHW1H'] - $this->calculation_values['TCHW2L']) / $this->calculation_values['LMTDEVA']) - 1);

        $QAB = $this->calculation_values['QEVA'] * (1 + 1 / 0.7) * 0.65;
        $QCO = $this->calculation_values['QEVA'] * (1 + 1 / 0.7) * 0.35;

        $this->calculation_values['ATCW2'] = $this->calculation_values['TCW11'] + $QAB / ($this->calculation_values['GCW'] * 1000);
        $this->calculation_values['ATCW3'] = $this->calculation_values['ATCW2'] + $QCO / ($this->calculation_values['GCW'] * 1000);
        $this->calculation_values['LMTDCO'] = $QCO / ($this->calculation_values['KCON'] * $this->calculation_values['ACON']);
        $this->calculation_values['AT3'] = $this->calculation_values['ATCW3'] + ($this->calculation_values['ATCW3'] - $this->calculation_values['ATCW2']) / (exp(($this->calculation_values['ATCW3'] - $this->calculation_values['ATCW2']) / $this->calculation_values['LMTDCO']) - 1);

        $this->calculation_values['TCHW1H'] = $this->calculation_values['TCHW11'];
        $this->calculation_values['TCHW2L'] = $this->calculation_values['TCHW12'];

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
        $err = array();
        $ferr = array();
        $vam_base1 = new VamBaseController();

        $this->calculation_values['s'] = 0;
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
            $this->calculation_values['XDIL'] = $vam_base1->LIBR_CONC($this->calculation_values['T2'], $this->calculation_values['P1H']);
            $this->calculation_values['I2'] = $vam_base1->LIBR_ENTHALPY($this->calculation_values['T2'], $this->calculation_values['XDIL']);
            $this->CONDENSER();
            $this->LTHE();             //*******FOR FINDING $this->calculation_values['T8'] **************//
            $this->calculation_values['I8'] = $vam_base1->LIBR_ENTHALPY($this->calculation_values['T8'], $this->calculation_values['XCONC']);
            $this->calculation_values['I2'] = $vam_base1->LIBR_ENTHALPY($this->calculation_values['T2'], $this->calculation_values['XDIL']);
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
        $vam_base2 = new VamBaseController();

        if ($this->calculation_values['s'] == 0)
        {
            $this->calculation_values['AT3'] = $this->calculation_values['AT3'];
        }
        else
            $this->calculation_values['AT3'] = $this->calculation_values['T3'];
        $ferrr[0] = 2;
        $this->calculation_values['s'] = 1;
        while (abs($ferrr[$this->calculation_values['s'] - 1]) > 0.05)
        {
            if ($this->calculation_values['s'] == 1)
            {
                $t3[$this->calculation_values['s']] = $this->calculation_values['AT3'];    //******REPRESENTATIVE FOR $this->calculation_values['T3']***********//
            }
            if ($this->calculation_values['s'] == 2)
            {
                $t3[$this->calculation_values['s']] = $this->calculation_values['AT3'] + 0.2;
            }
            if ($this->calculation_values['s'] > 2)
            {
                $t3[$this->calculation_values['s']] = $t3[$this->calculation_values['s'] - 1] + $error[$this->calculation_values['s'] - 1] * ($t3[$this->calculation_values['s'] - 1] - $t3[$this->calculation_values['s'] - 2]) / ($error[$this->calculation_values['s'] - 2] - $error[$this->calculation_values['s'] - 1]) / 2;
            }
            $this->calculation_values['T3'] = $t3[$this->calculation_values['s']];
            $this->calculation_values['P3'] = $vam_base2->LIBR_PRESSURE($this->calculation_values['T3'], 0);
            $this->calculation_values['I3'] = 100 + $this->calculation_values['T3'];

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
            $error[$this->calculation_values['s']] = ($this->calculation_values['QCWCON'] - $this->calculation_values['QLMTDCON']);
            $ferrr[$this->calculation_values['s']] = ($this->calculation_values['QCWCON'] - $this->calculation_values['QLMTDCON']) / $this->calculation_values['QCWCON'] * 100;
            $this->calculation_values['s']++;
        }
    }

    public function LTHE()
    {
        $n = 0;
        $fmerr = array();
        $merr = array();
        $t8 = array();
        $vam_base = new VamBaseController();

        $n = 1;
        $fmerr[0] = 2;
        while (abs($fmerr[$n - 1]) > 0.05)
        {
            if ($n == 1)
            {
                $t8[$n] = $this->calculation_values['T6'] + 5;
            }
            else if ($n == 2)
            {
                $t8[$n] = $this->calculation_values['T6'] + 5.5;
            }
            else if ($n > 2)
            {
                $t8[$n] = $t8[$n - 1] + $merr[$n - 1] * ($t8[$n - 1] - $t8[$n - 2]) / ($merr[$n - 2] - $merr[$n - 1]);
            }
            $this->calculation_values['T8'] = $t8[$n];
            $this->calculation_values['I8'] = $vam_base->LIBR_ENTHALPY($this->calculation_values['T8'], $this->calculation_values['XCONC']);
            $this->calculation_values['QHECON'] = $this->calculation_values['GCONC'] * ($this->calculation_values['I4'] - $this->calculation_values['I8']);
            $this->calculation_values['I7'] = $this->calculation_values['I2'] + $this->calculation_values['QHECON'] / $this->calculation_values['GDIL'];
            $this->calculation_values['T7'] = $vam_base->LIBR_TEMPERATURE($this->calculation_values['XDIL'], $this->calculation_values['I7']);
            $this->calculation_values['LMTDHE'] = (($this->calculation_values['T4'] - $this->calculation_values['T7']) - ($this->calculation_values['T8'] - $this->calculation_values['T2'])) / log(($this->calculation_values['T4'] - $this->calculation_values['T7']) / ($this->calculation_values['T8'] - $this->calculation_values['T2']));
            $this->calculation_values['QLMTDHE'] = $this->calculation_values['AHE'] * $this->calculation_values['UHE'] * $this->calculation_values['LMTDHE'];
            $merr[$n] = $this->calculation_values['QHECON'] - $this->calculation_values['QLMTDHE'];
            $fmerr[$n] = ($this->calculation_values['QHECON'] - $this->calculation_values['QLMTDHE']) / $this->calculation_values['QHECON'] * 100;
            $n++;
        }
    }

    public function CWABSHOUT()
    {
        $tcw2h = array();
        $error1 = array();
        $ferrr1 = array();

        $vam_base3 = new VamBaseController();

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
            if ($this->calculation_values['GLL'] == 2)
            {
                $this->calculation_values['COGLY_ROWH1'] = $vam_base3->EG_ROW($this->calculation_values['TCW1H'], $this->calculation_values['COGLY']);
                $this->calculation_values['COGLY_SPHT1'] = $vam_base3->EG_SPHT($this->calculation_values['TCW1H'], $this->calculation_values['COGLY']) * 1000;
                $this->calculation_values['COGLY_SPHT2'] = $vam_base3->EG_SPHT($this->calculation_values['TCW2H'], $this->calculation_values['COGLY']) * 1000;
            }
            else
            {
                $this->calculation_values['COGLY_ROWH1'] = $vam_base3->PG_ROW($this->calculation_values['TCW1H'], $this->calculation_values['COGLY']);
                $this->calculation_values['COGLY_SPHT1'] = $vam_base3->PG_SPHT($this->calculation_values['TCW1H'], $this->calculation_values['COGLY']) * 1000;
                $this->calculation_values['COGLY_SPHT2'] = $vam_base3->PG_SPHT($this->calculation_values['TCW2H'], $this->calculation_values['COGLY']) * 1000;
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
        $ferrr3[0] = 5;
        $k = 1;
        $vam_base = new VamBaseController();

        while (abs($ferrr3[$k - 1]) > .05)
        {
            if ($k == 1)
            {
                $tcw4[$k] = $this->calculation_values['TCW3'] + 2;
            }
            if ($k == 2)
            {
                $tcw4[$k] = $tcw4[$k - 1] + 2.2;
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

    public function HTG()
    {            
        $vam_base2 = new VamBaseController();

        $ROWH = 0;
        $this->calculation_values['HTEMP'] = 0;
        $this->calculation_values['HWI'] = 0;
        $this->calculation_values['J4'] = $vam_base2->WATER_VAPOUR_ENTHALPY($this->calculation_values['T4'], $this->calculation_values['P3']);
        $this->calculation_values['QHTG'] = $this->calculation_values['GCONC'] * $this->calculation_values['I4'] + $this->calculation_values['GREF'] * $this->calculation_values['J4'] - $this->calculation_values['GDIL'] * $this->calculation_values['I7'];
        $this->calculation_values['T5'] = $vam_base2->LIBR_TEMP($this->calculation_values['P3'], $this->calculation_values['XDIL']);
        
        $this->calculation_values['CPH2'] = $this->HT_SPHT($this->calculation_values['THW1']);
        $this->calculation_values['CPH3'] = $this->HT_SPHT($this->calculation_values['THW2']);
        $this->calculation_values['CPHAV'] = ($this->calculation_values['CPH2'] + $this->calculation_values['CPH3']) / 2;
        $ROWH = $this->WATER_DENSITY($this->calculation_values['THW1']);

        $this->calculation_values['GHOT'] = $this->calculation_values['QHTG'] / ($this->calculation_values['CPHAV'] * ($this->calculation_values['THW1'] - $this->calculation_values['THW2']) * $ROWH);
        if ($this->calculation_values['TCHW12'] < 5 || ($this->calculation_values['MODEL'] < 300 && $this->calculation_values['TCHW12'] < 6.7))
        {
            $this->calculation_values['SFACTOR2'] = 1.0738 - 0.0068 * $this->calculation_values['TCHW12'];
        }
        else
        {
            $this->calculation_values['SFACTOR2'] = 1.0;
        }

        $this->calculation_values['GHOT'] = $this->calculation_values['GHOT'] * 1.02 * $this->calculation_values['SFACTOR'] * $this->calculation_values['SFACTOR2']; // CORRECTION FOR CP AND DENSITY PLUS 2% SAFETY - REVISED FRM 3% TO 2% IN JAN08//
                                
        $this->calculation_values['TGP'] = 1;
        do
        {
            $this->calculation_values['VG'] = $this->calculation_values['GHOT'] / (((3600 * 3.141593 * $this->calculation_values['IDG'] * $this->calculation_values['IDG']) / 4.0) * ($this->calculation_values['TNG'] / $this->calculation_values['TGP']));
            if ($this->calculation_values['VG'] < 1.3)
                $this->calculation_values['TGP'] = $this->calculation_values['TGP'] + 1;

            if ($this->calculation_values['TGP'] > 4)
            {
                $this->calculation_values['TGP'] = 4;
                $this->calculation_values['VG'] = $this->calculation_values['GHOT'] / (((3600 * 3.141593 * $this->calculation_values['IDG'] * $this->calculation_values['IDG']) / 4.0) * ($this->calculation_values['TNG'] / $this->calculation_values['TGP']));
                break;
            }

        } while ($this->calculation_values['VG'] < 1.3);

        if ($this->calculation_values['VG'] < 1.3)
        {
            $this->calculation_values['HWI'] = 2;
        }
        if ($this->calculation_values['VG'] < 0.8)
        {
            $this->calculation_values['UGEN'] = $this->calculation_values['UGEN']*(1 - ((0.8 - $this->calculation_values['VG']) * 0.5));
        }
        if ($this->calculation_values['VG'] < 0.4)
        {
            $this->calculation_values['HTEMP'] = 1;
        }
        $this->calculation_values['LMTDGEN'] = $this->calculation_values['QHTG'] / ($this->calculation_values['AGEN'] * $this->calculation_values['UGEN']);
        $R1 = ($this->calculation_values['T5'] - $this->calculation_values['T4']) / ($this->calculation_values['THW2'] - $this->calculation_values['THW1']);
        $S1 = ($this->calculation_values['THW2'] - $this->calculation_values['THW1']) / ($this->calculation_values['T5'] - $this->calculation_values['THW1']);
        $FR11 = sqrt($R1 * $R1 + 1.0) * log((1 - $S1) / (1 - $R1 * $S1));
        $FR12 = ($R1 - 1) * log((2 - ($S1 * ($R1 + 1 - sqrt($R1 * $R1 + 1)))) / (2 - ($S1 * ($R1 + 1 + sqrt($R1 * $R1 + 1)))));
        $FR1 = $FR11 / $FR12;
        $this->calculation_values['LMTDGENA'] = (($this->calculation_values['THW1'] - $this->calculation_values['T4']) - ($this->calculation_values['THW2'] - $this->calculation_values['T5'])) / log(($this->calculation_values['THW1'] - $this->calculation_values['T4']) / ($this->calculation_values['THW2'] - $this->calculation_values['T5'])) * $FR1;   
        $this->PRESSURE_DROP();
    }

    public function WATER_DENSITY($TH)
    {
        if ($TH < 100.1)
        {
            $this->calculation_values['XMU'] = (-6.325 - 0.033974 * $TH + 2.829 * pow(10, -4) * $TH * $TH - 1.8309 * pow(10, -6) * pow($TH, 3.0) + 5.5184 * pow(10, -9) * pow($TH, 4.0));
            $this->calculation_values['MU'] = exp($this->calculation_values['XMU']);
            $this->calculation_values['NU']  = exp(-13.232 - 0.034086 * $TH + 2.9287 * pow(10, -4) * $TH * $TH - 1.9052 * pow(10, -6) * pow($TH, 3.0) + 5.8 * pow(10, -9) * pow($TH, 4.0));
            $this->calculation_values['ROWH1']  = $this->calculation_values['MU'] / $this->calculation_values['NU'];
        }
        else
        {
            $this->calculation_values['ROWH1'] = (1.001 * 1000 - 0.0842 * $TH - 3.72402 / 1000 * $TH * $TH + 3.65121 / 1000000 * $TH * $TH * $TH);
        }
        return ($this->calculation_values['ROWH1']);
    }

    public function PRESSURE_DROP()
    {
        $this->calculation_values['TEP'] = $this->calculation_values['TP'];
        $this->calculation_values['VE'] = $this->calculation_values['VEA'];
        $this->calculation_values['VA'] = $this->calculation_values['GCW'] / (((3600 * 3.141593 * $this->calculation_values['IDA'] * $this->calculation_values['IDA']) / 4.0) * ($this->calculation_values['TNAA'] / $this->calculation_values['TAP']));
        $this->calculation_values['VC'] = $this->calculation_values['GCWC'] / (((3600 * 3.141593 * $this->calculation_values['IDC'] * $this->calculation_values['IDC']) / 4.0) * ($this->calculation_values['TNC'] / $this->calculation_values['TCP']));

        // PR_DROP_DATA();
        $this->PR_DROP_CHILL();
        $this->PR_DROP_COW();
        $this->PR_DROP_HW();
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

    public function PR_DROP_HW()
    {
        $this->PR_HW_DATA();

        $TMHW = ($this->calculation_values['THW1'] + $this->calculation_values['THW2']) / 2;
        $VISG = $this->WATER_VISCOSITY($TMHW);
        $ROWH = $this->WATER_DENSITY($TMHW);

        $GPID = ($this->calculation_values['PODG'] - (2 * $this->calculation_values['THH'])) / 1000;
        $VGP = $this->calculation_values['GHOT'] / (3.1415 * $GPID * $GPID / 4) / 3600;

        $SLH = 2 * $GPID;                                                              //NOZZLE LENGTH
        $REH = ($GPID * $VGP * $ROWH) / $VISG;                                                //REYNOLDS NO IN PIPE

        $FFH = 1.325 / pow(log((0.0457 / (3.7 * $GPID * 1000)) + (5.74 / pow($REH, 0.9))), 2);      //FR FACTOR FOR LENGTH      

        $GL2 = (($FFH * $SLH * 2) / $GPID) * ($VGP * $VGP / (2 * 9.81));                               //PIPE LOSS IN LENGTH
        $GL3 = ($VGP * $VGP / (2 * 9.81)) + (0.5 * $VGP * $VGP / (2 * 9.81));                         //PIPE LOSS AT ENTRY AND EXIT
        $this->calculation_values['GLP'] = $GL2 + $GL3;                                                            //TOTAL FR LOSS IN PIPE

        $this->calculation_values['VG'] = $this->calculation_values['GHOT'] / (((3600 * 3.141593 * $this->calculation_values['IDG'] * $this->calculation_values['IDG']) / 4.0) * ($this->calculation_values['TNG'] / $this->calculation_values['TGP']));

        $REG = ($ROWH * $this->calculation_values['VG'] * $this->calculation_values['IDG']) / $VISG;                  //REYNOLDS NO IN TUBES

        if ($this->calculation_values['HWI'] == 2)
        {
            $this->calculation_values['FG'] = ((1.325 / pow(log((0.02 / (3.7 * $this->calculation_values['IDG'] * 1000)) + (5.74 / pow($REG, 0.9))), 2)) * ((-0.1305 * $this->calculation_values['VG']) + 3.5)) * 1.12;
            $FLG = ( $this->calculation_values['FG'] * $this->calculation_values['LE'] * $this->calculation_values['VG'] * $this->calculation_values['VG']) / ($this->calculation_values['IDG'] * 9.81 * 2);
        }
        else
        {
            //$this->calculation_values['FG'] = (1.325 / pow(log((0.02 / (3.7 * $this->calculation_values['IDG'] * 1000)) + (5.74 / pow($REG, 0.9))), 2)) * 1.12;
            $this->calculation_values['FG'] = 0.0014 + (0.137 / pow($REG, 0.32)) * 1.12;
            $FLG = (4 * $this->calculation_values['FG'] * $this->calculation_values['LE'] * $this->calculation_values['VG'] * $this->calculation_values['VG']) / ($this->calculation_values['IDG'] * 9.81 * 2);
        }
        //double $this->calculation_values['FG'] = 0.0014 + (0.137 / pow($REG, 0.32));
        
        $EXLG = $this->calculation_values['VG'] * $this->calculation_values['VG'] / (4 * 9.81);                 //EXIT LOSS
        $ENLG = $this->calculation_values['VG'] * $this->calculation_values['VG'] / (2 * 9.81);                 //ENTRY LOSS
        $this->calculation_values['TFLG'] = ($FLG + $EXLG + $ENLG) * $this->calculation_values['TGP'];               //TOTAL FR LOSS IN TUBES

        $this->calculation_values['GFL'] = $this->calculation_values['GLP'] + $this->calculation_values['TFLG'];                           //TOTAL FR LOSS IN HW
    }

    public function PR_HW_DATA()
    {
        if ($this->calculation_values['GHOT'] < 0.99)
        {
            $this->calculation_values['PNBH'] = 25; $this->calculation_values['PODG'] = 33.4; $this->calculation_values['THH'] = 3.38;
        }
        else if ($this->calculation_values['GHOT'] > 0.99 && $this->calculation_values['GHOT'] < 1.99)
        {
            $this->calculation_values['PNBH'] = 32; $this->calculation_values['PODG'] = 42.2; $this->calculation_values['THH'] = 3.56;
        }
        else if ($this->calculation_values['GHOT'] > 1.99 && $this->calculation_values['GHOT'] < 3.499)
        {
            $this->calculation_values['PNBH'] = 40; $this->calculation_values['PODG'] = 48.3; $this->calculation_values['THH'] = 3.68;
        }
        else if ($this->calculation_values['GHOT'] > 3.499 && $this->calculation_values['GHOT'] < 6.99)
        {
            $this->calculation_values['PNBH'] = 50; $this->calculation_values['PODG'] = 60.3; $this->calculation_values['THH'] = 3.91;
        }
        else if ($this->calculation_values['GHOT'] > 6.99 && $this->calculation_values['GHOT'] < 29.99)
        {
            $this->calculation_values['PNBH'] = 80; $this->calculation_values['PODG'] = 88.9; $this->calculation_values['THH'] = 5.49;
        }
        else if ($this->calculation_values['GHOT'] > 29.99 && $this->calculation_values['GHOT'] < 64.99)
        {
            $this->calculation_values['PNBH'] = 100; $this->calculation_values['PODG'] = 114.3; $this->calculation_values['THH'] = 6.02;
        }
        else if ($this->calculation_values['GHOT'] > 64.99 && $this->calculation_values['GHOT'] < 154.99)
        {
            $this->calculation_values['PNBH'] = 150; $this->calculation_values['PODG'] = 168.3; $this->calculation_values['THH'] = 7.11;
        }
        else if ($this->calculation_values['GHOT'] > 154.99 && $this->calculation_values['GHOT'] < 294.99)
        {
            $this->calculation_values['PNBH'] = 200; $this->calculation_values['PODG'] = 219.1; $this->calculation_values['THH'] = 8.18;
        }
        else if ($this->calculation_values['GHOT'] > 294.99 && $this->calculation_values['GHOT'] < 434.99)
        {
            $this->calculation_values['PNBH'] = 250; $this->calculation_values['PODG'] = 273; $this->calculation_values['THH'] = 9.27;
        }
        else if ($this->calculation_values['GHOT'] > 434.99 && $this->calculation_values['GHOT'] < 609.99)
        {
            $this->calculation_values['PNBH'] = 300; $this->calculation_values['PODG'] = 323.8; $this->calculation_values['THH'] = 10.31;
        }
        else if ($this->calculation_values['GHOT'] > 609.99 && $this->calculation_values['GHOT'] < 769.99)
        {
            $this->calculation_values['PNBH'] = 350; $this->calculation_values['PODG'] = 355.6; $this->calculation_values['THH'] = 11.13;
        }
        else if ($this->calculation_values['GHOT'] > 769.99 && $this->calculation_values['GHOT'] < 999.99)
        {
            $this->calculation_values['PNBH'] = 400; $this->calculation_values['PODG'] = 406.4; $this->calculation_values['THH'] = 12.7;
        }
        else if ($this->calculation_values['GHOT'] > 999.99 && $this->calculation_values['GHOT'] < 1269.99)
        {
            $this->calculation_values['PNBH'] = 450; $this->calculation_values['PODG'] = 457.2; $this->calculation_values['THH'] = 14.2;
        }
        else if ($this->calculation_values['GHOT'] > 1269.99 && $this->calculation_values['GHOT'] < 1589.99)
        {
            $this->calculation_values['PNBH'] = 500; $this->calculation_values['PODG'] = 508; $this->calculation_values['THH'] = 15.09;
        }
        else if ($this->calculation_values['GHOT'] > 1589.99)
        {
            $this->calculation_values['PNBH'] = 600; $this->calculation_values['PODG'] = 609.6; $this->calculation_values['THH'] = 17.48;
        }
    }

    public function WATER_VISCOSITY($TH)
    {
        if ($TH < 100.1)
        {
            $this->calculation_values['MUEH'] = (exp(-6.325 - 0.033974 * $TH + 0.0002829 * $TH * $TH - 0.0000018309 * pow($TH, 3) + 0.0000000055184 * pow($TH, 4))) * 1000;
        }
        else
        {
            $this->calculation_values['MUEH'] = 41.049 * pow($TH, -1.0813);
        }

        return ($this->calculation_values['MUEH'] / 1000);
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

        $CC[0][0] = $this->calculation_values['GCHW'] * ($this->calculation_values['TCHW1H'] - $this->calculation_values['TCHW2H']) * $this->calculation_values['CHGLY_ROW12'] * $this->calculation_values['CHGLY_SPHT12'] / 4187;
        $CC[1][0] = $this->calculation_values['UEVAH'] * $this->calculation_values['AEVAH'] * $this->calculation_values['LMTDEVAH'];                                                                   //EVAH
        $CC[2][0] = ($this->calculation_values['GREFH'] * ($this->calculation_values['J1H'] - $this->calculation_values['I3'])) - ($this->calculation_values['GREFL'] * ($this->calculation_values['I3'] - $this->calculation_values['I1H']));

        $CC[0][1] = $this->calculation_values['GCWAH'] * $this->calculation_values['COGLY_ROWH1'] * ($this->calculation_values['COGLY_SPHT2'] + $this->calculation_values['COGLY_SPHT1']) * 0.5 * ($this->calculation_values['TCW2H'] - $this->calculation_values['TCW1H']) / 4187;
        $CC[1][1] = $this->calculation_values['UABSH'] * $this->calculation_values['AABSH'] * $this->calculation_values['LMTDABSH'];                                                                //ABSH
        $CC[2][1] = ($this->calculation_values['GREFH'] * $this->calculation_values['J1H']) + ($this->calculation_values['GCONCH'] * $this->calculation_values['I2L']) - ($this->calculation_values['GDIL'] * $this->calculation_values['I2']);

        $CC[0][2] = $this->calculation_values['GCWC'] * $this->calculation_values['COGLY_ROWH1'] * ($this->calculation_values['COGLY_SPHT4'] + $this->calculation_values['COGLY_SPHT3']) * 0.5 * ($this->calculation_values['TCW4'] - $this->calculation_values['TCW3']) / 4187;
        $CC[1][2] = $this->calculation_values['UCON'] * $this->calculation_values['ACON'] * $this->calculation_values['LMTDCON'];
        $CC[2][2] = $this->calculation_values['GREF'] * ($this->calculation_values['J4'] - (100 + $this->calculation_values['T3']));

        $CC[0][3] = $this->calculation_values['GCONC'] * $this->calculation_values['I4'] + $this->calculation_values['GREF'] * $this->calculation_values['J4'] - $this->calculation_values['GDIL'] * $this->calculation_values['I7'];
        $CC[1][3] = $this->calculation_values['UGEN'] * $this->calculation_values['AGEN'] * $this->calculation_values['LMTDGEN'];
        $CC[2][3] = $this->calculation_values['QHTG'];

        $CC[0][4] = $this->calculation_values['GCONC'] * ($this->calculation_values['I4'] - $this->calculation_values['I8']);                   //HX
        $CC[1][4] = $this->calculation_values['UHE'] * $this->calculation_values['AHE'] * $this->calculation_values['LMTDHE'];
        $CC[2][4] = $this->calculation_values['GDIL'] * ($this->calculation_values['I7'] - $this->calculation_values['I2']);

        $CC[0][5] = $this->calculation_values['GCWAL'] * $this->calculation_values['COGLY_ROWH1'] * ($this->calculation_values['COGLY_SPHT2L'] + $this->calculation_values['COGLY_SPHT1L']) * 0.5 * ($this->calculation_values['TCW2L'] - $this->calculation_values['TCW1L']) / 4187;  //ABSORBERL
        $CC[1][5] = $this->calculation_values['UABSL'] * $this->calculation_values['AABSL'] * $this->calculation_values['LMTDABSL'];
        $CC[2][5] = $this->calculation_values['GCONC'] * $this->calculation_values['I8'] + $this->calculation_values['GREFL'] * $this->calculation_values['J1L'] - $this->calculation_values['GDILL'] * $this->calculation_values['I2L'];

        $CC[0][6] = $this->calculation_values['GCHW'] * $this->calculation_values['CHGLY_ROW12'] * $this->calculation_values['CHGLY_SPHT12'] * ($this->calculation_values['TCHW1L'] - $this->calculation_values['TCHW2L']) / 4187;                        //EVAPORATORL
        $CC[1][6] = $this->calculation_values['UEVAL'] * $this->calculation_values['AEVAL'] * $this->calculation_values['LMTDEVAL'];
        $CC[2][6] = $this->calculation_values['GREFL'] * ($this->calculation_values['J1L'] - $this->calculation_values['I1H']);

        for ($j = 0; $j < 7; $j++)
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

        $MIN = ($this->calculation_values['QEVAH'] + $this->calculation_values['QEVAL'] + $this->calculation_values['QHTG']);
        $MAX = ($this->calculation_values['GCWAH'] * $this->calculation_values['COGLY_ROWH1'] * ($this->calculation_values['COGLY_SPHT2'] + $this->calculation_values['COGLY_SPHT1']) * 0.5 * ($this->calculation_values['TCW2H'] - $this->calculation_values['TCW1H']) / 4187) + ($this->calculation_values['GCWAL'] * $this->calculation_values['COGLY_ROWH1'] * ($this->calculation_values['COGLY_SPHT2L'] + $this->calculation_values['COGLY_SPHT1L']) * 0.5 * ($this->calculation_values['TCW2L'] - $this->calculation_values['TCW1L']) / 4187) + ($this->calculation_values['GCWC'] * $this->calculation_values['COGLY_ROWH1'] * ($this->calculation_values['COGLY_SPHT4'] + $this->calculation_values['COGLY_SPHT3']) * 0.5 * ($this->calculation_values['TCW4'] - $this->calculation_values['TCW3']) / 4187);            
        $this->calculation_values['ERROR'] = ($MAX - $MIN) / $MAX * 100.0;
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
            return;
        }

        $ROWH = $this->WATER_DENSITY($this->calculation_values['THW1']);

        $this->calculation_values['COP'] = ($this->calculation_values['TON'] * 3024) / ($this->calculation_values['GHOT'] * ($this->calculation_values['CPHAV'] * ($this->calculation_values['THW1'] - $this->calculation_values['THW2']) * $ROWH));

        if ($this->calculation_values['COP'] > 0.83)
        {
            $this->HEATBALANCE1();
        }
        else
        {
            $this->HEATBALANCE();
        }

        //Assign the output properties of chiller
        $this->calculation_values['HeatInput'] = $this->calculation_values['QHTG1'];
        $this->calculation_values['HeatRejected'] = ($this->calculation_values['TON'] * 3024) + $this->calculation_values['QHTG1'];

        $this->calculation_values['CoolingWaterOutTemperature'] = $this->calculation_values['TCWA4'];
        $this->calculation_values['HotWaterFlow'] = $this->calculation_values['GHOT'];
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
        $this->calculation_values['HotWaterFrictionLoss'] =$this->calculation_values['GFL'];
        $this->calculation_values['ChilledWaterFlow'] = round($this->calculation_values['GCHW'],1);


        $this->calculation_values['GeneratorPasses'] =$this->calculation_values['TGP'];
        $this->calculation_values['HotWaterConnectionDiameter'] =$this->calculation_values['PNBH'];
        $this->calculation_values['BypassFlow'] = $this->calculation_values['GCW'] - $this->calculation_values['GCWC'];
        $this->calculation_values['COP'] = ($this->calculation_values['TON'] * 3024) / $this->calculation_values['QHTG1'];

        $this->calculation_values['ModeBCapacity'] = $this->calculation_values['TON'] * 0.5;
        $this->calculation_values['ModeBChilledWaterOutTemperature'] = $this->calculation_values['TCHW11'] - ($this->calculation_values['DT'] / 2);
        $this->calculation_values['ModeBCoolingWaterInTemperature'] = "40";
        $this->calculation_values['ModeBCoolingWaterOutTemperature'] = $this->calculation_values['TCWS'];
        $this->calculation_values['ModeBHotWaterOutTemperature'] = $this->calculation_values['THW2B'];
        $this->calculation_values['ModeBHeatInput'] = $this->calculation_values['QHTG1'] * 0.75;
        $this->calculation_values['ModeBHeatRejected'] = ($this->calculation_values['TON'] * 1512) + ($this->calculation_values['QHTG1'] * 0.75);

        $this->calculation_values['Result'] = "FAILED";

        if (($this->calculation_values['P3'] - $this->calculation_values['P1L']) < 40)
        {
            array_push($selection_notes,$this->notes['NOTES_LTHE_PRDROP']);
            $this->calculation_values['HHType'] = "NonStandard";
        }
        if (!$this->calculation_values['isStandard'])
        {
            array_push($selection_notes,$this->notes['NOTES_NSTD_TUBE_METAL']);

        }
        if ($this->calculation_values['TCHW12'] < 4.49)
        {
            array_push($selection_notes,$this->notes['NOTES_COST_COW_SOV']);
        }
        if ($this->calculation_values['HWI'] == 2)
        {
            array_push($selection_notes,$this->notes['NOTES_HW_INSERTS_PRESENT']);
        }
        if ($this->calculation_values['TCHW12'] < 4.49)
        {
            array_push($selection_notes,$this->notes['NOTES_NONSTD_XSTK_MC']);
        }           
        if ($this->calculation_values['GCWC'] < $this->calculation_values['GCW'])
        {
            $bypass = $this->notes['NOTES_OUTPUT_GA'].round($this->calculation_values['GCW'] - $this->calculation_values['GCWC'], 2)."m3/hr";
            array_push($selection_notes,$bypass);
            if (($this->calculation_values['FLA'] + $this->calculation_values['FC4']) > 12)
            {
                array_push($selection_notes,$this->notes['NOTES_PR_DR_ER']);
            }
        }
        
        if ($this->calculation_values['TUU'] == "ari")
        {
            array_push($selection_notes,$this->notes['NOTES_ARI']);
        }
      
        array_push($notes,$this->notes['NOTES_INSUL']);
        array_push($notes,$this->notes['NOTES_NON_INSUL']);
        array_push($notes,$this->notes['NOTES_ROOM_TEMP']);
        array_push($notes,$this->notes['NOTES_CUSTOM']);
        
        if ($this->calculation_values['XCONC'] < ($this->calculation_values['KM'] - 1.5))
        {
            if ($this->calculation_values['LMTDGEN'] < ($this->calculation_values['LMTDGENA'] - 4))
            {
                $this->calculation_values['Result'] = "OverDesigned";
            }
            else if ($this->calculation_values['LMTDGEN'] < ($this->calculation_values['LMTDGENA'] - 2))
            {
                $this->calculation_values['Result'] = "GoodSelection";
            }
            else
            {
                $this->calculation_values['Result'] = "Optimal";
            }
        }
        if ($this->calculation_values['XCONC'] < ($this->calculation_values['KM'] - 0.8) && $this->calculation_values['XCONC'] > ($this->calculation_values['KM'] - 1.5))
        {
            if ($this->calculation_values['LMTDGEN'] < ($this->calculation_values['LMTDGENA'] - 2))
            {
                array_push($selection_notes,$this->notes['NOTES_RED_COW']);
                $this->calculation_values['Result'] = "GoodSelection"; 
            }
            else
            {
                $this->calculation_values['Result'] = "Optimal";
            }
        }
        if ($this->calculation_values['XCONC'] < ($this->calculation_values['KM'] - 0.4) && $this->calculation_values['XCONC'] > ($this->calculation_values['KM'] - 0.8))
        {
            if ($this->calculation_values['LMTDGEN'] < ($this->calculation_values['LMTDGENA'] - 2))
            {
                $this->calculation_values['Result'] = "GoodSelection";
            }
            else
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

        if (!$this->LMTDCHECK() || abs($this->calculation_values['ERROR']) > 1)
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
                if (!$this->SEPARATION_HEIGHT_GEN())
                {
                    $this->calculation_values['Notes'] = $this->notes['NOTES_SEP_HT_GEN'];
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
                            if (($this->calculation_values['THW2'] - $this->calculation_values['T5']) < 0.0)
                            {
                                $this->calculation_values['Notes'] = $this->notes['NOTES_FAIL_HW_OUTTEMP'];
                                return false;
                            }
                            else
                            {
                                if ($this->calculation_values['LMTDGEN'] > ($this->calculation_values['LMTDGENA'] - 0.5))
                                {
                                    $this->calculation_values['Notes'] = $this->notes['NOTES_FAIL_HW_INOUT'];
                                    return false; 
                                }
                                else
                                {
                                    if ($this->calculation_values['HTEMP'] == 1)
                                    {
                                        $this->calculation_values['HTEMP'] = 0;
                                        $this->calculation_values['Notes'] = $this->notes['NOTES_HW_VELO'];
                                        return false; 
                                    }
                                    else
                                    {
                                        if ($this->calculation_values['VG'] > 3)
                                        {
                                            $this->calculation_values['Notes'] = $this->notes['NOTES_HW_VEL_HI'];
                                            return false; 
                                        }
                                        else
                                        {
                                            if (($this->calculation_values['TCHW12'] >= 3.5 && $this->calculation_values['T1'] < 0.5) || ($this->calculation_values['TCHW12'] < 3.5 && $this->calculation_values['T1'] < (-3.99)))
                                            {
                                                $this->calculation_values['Notes'] = $this->notes['NOTES_REF_TEMP'];
                                                return false;
                                            }
                                            else
                                            {
                                                if ($this->calculation_values['TON'] < ($this->calculation_values['MOD1'] * 0.35))
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
        else if (!isset($this->calculation_values['LMTDABSH']) || is_nan($this->calculation_values['LMTDABSH']) || $this->calculation_values['LMTDABSH'] < 0)
        {
            return false;
        }
        else if (!isset($this->calculation_values['LMTDABSL']) || is_nan($this->calculation_values['LMTDABSL']) || $this->calculation_values['LMTDABSL'] < 0)
        {
            return false;
        }
        else if (!isset($this->calculation_values['LMTDEVAL']) || is_nan($this->calculation_values['LMTDEVAL']) || $this->calculation_values['LMTDEVAL'] < 0)
        {
            return false;
        }
        else if (!isset($this->calculation_values['LMTDCON']) || is_nan($this->calculation_values['LMTDCON']) || $this->calculation_values['LMTDCON'] < 0)
        {
            return false;
        }
        else if (!isset($this->calculation_values['LMTDGEN']) || is_nan($this->calculation_values['LMTDGEN']) || $this->calculation_values['LMTDGEN'] < 0)
        {
            return false;
        }
        else if (!isset($this->calculation_values['LMTDGENA']) || is_nan($this->calculation_values['LMTDGENA']) || $this->calculation_values['LMTDGENA'] < 0)
        {
            return false;
        }
        else if (!isset($this->calculation_values['LMTDHE']) || is_nan($this->calculation_values['LMTDHE']) || $this->calculation_values['LMTDHE'] < 0)
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
        else
        {
            $HIGHCAP = 0;
        }


        if ($this->calculation_values['TON'] > $HIGHCAP)
            return false;
        else
            return true;
    }

    public function SEPARATION_HEIGHT_GEN()
    {
        $this->calculation_values['GEN_HSEP_DS'] = 0;

        if ($this->calculation_values['MODEL'] == 130 || $this->calculation_values['MODEL'] == 160 || $this->calculation_values['MODEL'] == 210 || $this->calculation_values['MODEL'] == 250)
        {
            $this->calculation_values['GEN_HSEP_DS'] = 386.3 / 547.3;
        }
        if ($this->calculation_values['MODEL'] == 310 || $this->calculation_values['MODEL'] == 350 || $this->calculation_values['MODEL'] == 410)
        {
            $this->calculation_values['GEN_HSEP_DS'] = 381.1 / 595;
        }
        if ($this->calculation_values['MODEL'] == 470 || $this->calculation_values['MODEL'] == 530 || $this->calculation_values['MODEL'] == 580)
        {
            $this->calculation_values['GEN_HSEP_DS'] = 404.9 / 661;
        }
        if ($this->calculation_values['MODEL'] == 630 || $this->calculation_values['MODEL'] == 710)
        {
            $this->calculation_values['GEN_HSEP_DS'] = 405.1 / 667.3;
        }
        if ($this->calculation_values['MODEL'] == 760 || $this->calculation_values['MODEL'] == 810 || $this->calculation_values['MODEL'] == 900 || $this->calculation_values['MODEL'] == 1010 || $this->calculation_values['MODEL'] == 1130)
        {
            $this->calculation_values['GEN_HSEP_DS'] = 458.5 / 727.3;
        }
        if ($this->calculation_values['MODEL'] == 1260 || $this->calculation_values['MODEL'] == 1380)
        {
            $this->calculation_values['GEN_HSEP_DS'] = 482.9 / 775.3;
        }
        if ($this->calculation_values['MODEL'] == 1560 || $this->calculation_values['MODEL'] == 1690)
        {
            $this->calculation_values['GEN_HSEP_DS'] = 531 / 799.3;
        }
        if ($this->calculation_values['MODEL'] == 1890 || $this->calculation_values['MODEL'] == 2130)
        {
            $this->calculation_values['GEN_HSEP_DS'] = 531 / 799.3;
        }
        if ($this->calculation_values['MODEL'] == 2270 || $this->calculation_values['MODEL'] == 2560)
        {
            $this->calculation_values['GEN_HSEP_DS'] = 531 / 864.3;
        }
        
        $this->calculation_values['GEN_HSEP_DS_REQ'] = (($this->calculation_values['QHTG']/860) / $this->calculation_values['AGEN']) * 0.015;


        if (($this->calculation_values['QHTG'] / 860) / $this->calculation_values['AGEN'] > 49.0 * 1.05)
            return false;
        else
            return true;
    }

    public function HEATBALANCE()
    {
        $tcwa4 = array();
        $herr = array();
        $ii = 1;

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
                $this->calculation_values['COGLY_SPHTH'] = $vam_base->EG_SPHT($this->calculation_values['TCW11'], $this->calculation_values['COGLY']) * 1000;
                $this->calculation_values['COGLY_SPHTA4'] = $vam_base->EG_SPHT($this->calculation_values['TCWA4'], $this->calculation_values['COGLY']) * 1000;
            }
            else
            {
                $this->calculation_values['COGLY_ROWH'] = $vam_base->PG_ROW($this->calculation_values['TCW11'], $this->calculation_values['COGLY']);
                $this->calculation_values['COGLY_SPHTH'] = $vam_base->PG_SPHT($this->calculation_values['TCW11'], $this->calculation_values['COGLY']) * 1000;
                $this->calculation_values['COGLY_SPHTA4'] = $vam_base->PG_SPHT($this->calculation_values['TCWA4'], $this->calculation_values['COGLY']) * 1000;
            }

            $this->calculation_values['QCWR'] = $this->calculation_values['GCW'] * $this->calculation_values['COGLY_ROWH'] * ($this->calculation_values['COGLY_SPHTA4'] + $this->calculation_values['COGLY_SPHTH']) * 0.5 * ($this->calculation_values['TCWA4'] - $this->calculation_values['TCW11']) / 4187;
            $ROWHH = $this->WATER_DENSITY($this->calculation_values['THW1']);
            $this->calculation_values['QHTG1'] = $this->calculation_values['GHOT'] * $ROWHH * $this->calculation_values['CPHAV'] * ($this->calculation_values['THW1'] - $this->calculation_values['THW2']);
            $this->calculation_values['QINPUT'] = ($this->calculation_values['TON'] * 3024) + ($this->calculation_values['QHTG1']);
            $herr[$ii] = ($this->calculation_values['QINPUT'] - $this->calculation_values['QCWR']) * 100 / $this->calculation_values['QINPUT'];
            $ii++;
        }
        $jj = 1;
        $COGLY_SPHT = 0;
        $COGLY_SPHTS = 0;
        $herr1 = array();
        $tcws = array();

        $herr1[0] = 2;
        while (abs($herr1[$jj - 1]) > 0.001)
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
            $ROWHH = $this->WATER_DENSITY($this->calculation_values['THW1']);

            $kk = 1;
            $herr2 = array();
            $thw2b = array();

            $herr2[0] = 2;
            while (abs($herr2[$kk - 1]) > 0.001)
            {
                if ($kk == 1)
                {
                    $thw2b[$kk] = $this->calculation_values['THW1'] - (($this->calculation_values['THW1'] - $this->calculation_values['THW2']) * 0.75);
                }
                if ($kk == 2)
                {
                    $thw2b[$kk] = $thw2b[$kk - 1] + 0.5;
                }
                if ($kk > 2)
                {
                    $thw2b[$kk] = $thw2b[$kk - 1] + $herr2[$kk - 1] * ($thw2b[$kk - 1] - $thw2b[$kk - 2]) / ($herr2[$kk - 2] - $herr2[$kk - 1]);
                }

                $this->calculation_values['THW2B'] = $thw2b[$kk];
                $this->calculation_values['CPH2'] = $this->HT_SPHT($this->calculation_values['THW1']);
                $this->calculation_values['CPH3B'] = $this->HT_SPHT($this->calculation_values['THW2B']);
                $this->calculation_values['CPHAVB'] = ($this->calculation_values['CPH2'] + $this->calculation_values['CPH3B']) / 2;
                $this->calculation_values['QHTGB'] = ($this->calculation_values['GHOT'] * $ROWHH * $this->calculation_values['CPHAVB'] * ($this->calculation_values['THW1'] - $this->calculation_values['THW2B']));
                $herr2[$kk] = ($this->calculation_values['QHTGB'] - ($this->calculation_values['QHTG1'] * 0.75)) * 100 / $this->calculation_values['QHTGB'];
                $kk++;
            }
            $this->calculation_values['QINPUTS'] = ($this->calculation_values['TON'] * 1512) + ($this->calculation_values['QHTG1'] * 0.75);
            $herr1[$jj] = ($this->calculation_values['QINPUTS'] - $QCWS) * 100 / $this->calculation_values['QINPUTS'];
            $jj++;
        }
    }

    public function HT_SPHT($TH)
    {
        $CPHT = array();
        $NY0 = 0.0;
        $NY1 = 0.0;
        if ($TH < 100.1)
        {
            $this->calculation_values['CPH1'] = 4.217 - 2.949 * pow(10, -3) * $TH + 7.624 * pow(10, -5) * $TH * $TH - 7.858 * pow(10, -7) * pow($TH, 3.0) + 3.181 * pow(10, -9) * pow($TH, 4.0);
        }
        else
        {
            $REM4 = fmod($TH, 10);
            $x0 = intval(($TH - $REM4));
            $x1 = $x0 + 10;

            $CPHT[0] = 4.218;
            $CPHT[10] = 4.194;
            $CPHT[20] = 4.182;
            $CPHT[30] = 4.179;
            $CPHT[40] = 4.179;
            $CPHT[50] = 4.181;
            $CPHT[60] = 4.185;
            $CPHT[70] = 4.191;
            $CPHT[80] = 4.198;
            $CPHT[90] = 4.207;
            $CPHT[100] = 4.218;
            $CPHT[110] = 4.23;
            $CPHT[120] = 4.244;
            $CPHT[130] = 4.262;
            $CPHT[140] = 4.282;
            $CPHT[150] = 4.306;
            $CPHT[160] = 4.334;
            $CPHT[170] = 4.366;
            $CPHT[180] = 4.403;
            $CPHT[190] = 4.446;
            $CPHT[200] = 4.494;
            $CPHT[210] = 4.55;
            $CPHT[220] = 4.613;
            $CPHT[230] = 4.685;
            $CPHT[240] = 4.769;
            $CPHT[260] = 4.985;
            $CPHT[270] = 5.134;
            $CPHT[280] = 5.307;
            $CPHT[290] = 5.52;
            $CPHT[300] = 5.794;
            $CPHT[310] = 6.143;
            $CPHT[320] = 6.604;
            $CPHT[330] = 7.241;
            $CPHT[340] = 8.225;

            $NY0 = $CPHT[$x0];
            $NY1 = $CPHT[$x1];
            $Y0 = $NY0;
            $Y1 = $NY1;
            $YY11 = ($TH - $x1) / ($x0 - $x1) * $Y0;
            $YY22 = ($TH - $x0) / ($x1 - $x0) * $Y1;
            $YY2 = $YY11 + $YY22;
            $this->calculation_values['CPH1'] = $YY2;
        }
        return ($this->calculation_values['CPH1'] / 4.187);
    }

    public function HEATBALANCE1()
    {
        $tcwa4 = array();
        $herr = array();
        $ii = 1;

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

            if ($this->calculation_values['GLL'] == 2)
            {
                $this->calculation_values['COGLY_ROWH'] = $vam_base->EG_ROW($this->calculation_values['TCW11'], $this->calculation_values['COGLY']);
                $this->calculation_values['COGLY_SPHTH'] = $vam_base->EG_SPHT($this->calculation_values['TCW11'], $this->calculation_values['COGLY']) * 1000;
                $this->calculation_values['COGLY_SPHTA4'] = $vam_base->EG_SPHT($this->calculation_values['TCWA4'], $this->calculation_values['COGLY']) * 1000;
            }
            else
            {
                $this->calculation_values['COGLY_ROWH'] = $vam_base->PG_ROW($this->calculation_values['TCW11'], $this->calculation_values['COGLY']);
                $this->calculation_values['COGLY_SPHTH'] = $vam_base->PG_SPHT($this->calculation_values['TCW11'], $this->calculation_values['COGLY']) * 1000;
                $this->calculation_values['COGLY_SPHTA4'] = $vam_base->PG_SPHT($this->calculation_values['TCWA4'], $this->calculation_values['COGLY']) * 1000;
            }

            $this->calculation_values['QCWR'] = $this->calculation_values['GCW'] * $this->calculation_values['COGLY_ROWH'] * ($this->calculation_values['COGLY_SPHTA4'] + $this->calculation_values['COGLY_SPHTH']) * 0.5 * ($this->calculation_values['TCWA4'] - $this->calculation_values['TCW11']) / 4187;
            $ROWHH = $this->WATER_DENSITY($this->calculation_values['THW1']);
            $this->calculation_values['QHTG1'] = ($this->calculation_values['TON'] * 3024) / 0.83;
            $this->calculation_values['GHOT'] = $this->calculation_values['QHTG1'] / ($ROWHH * $this->calculation_values['CPHAV'] * ($this->calculation_values['THW1'] - $this->calculation_values['THW2']));
            $this->calculation_values['QINPUT'] = ($this->calculation_values['TON'] * 3024) + ($this->calculation_values['QHTG1']);
            $herr[$ii] = ($this->calculation_values['QINPUT'] - $this->calculation_values['QCWR']) * 100 / $this->calculation_values['QINPUT'];
            $ii++;
        }
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

    public function loadSpecSheetData(){
        $model_number = floatval($this->calculation_values['MODEL']);

        if($this->calculation_values['region_type'] == 2 ||$this->calculation_values['region_type'] == 3)
        {

            $DryWeight1 = $this->calculation_values['DryWeight'] * $this->calculation_values['EX_DryWeight'];

            $ex_DryWeight =  $DryWeight1 - $this->calculation_values['DryWeight'] ;

            $this->calculation_values['DryWeight'] = $DryWeight1; 
            $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + $ex_DryWeight;
            $this->calculation_values['OperatingWeight'] =$this->calculation_values['OperatingWeight'] + $ex_DryWeight;
            $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + $ex_DryWeight;
        }

        if($this->calculation_values['region_type'] == 2)
        {
            $this->calculation_values['TotalPowerConsumption'] = (1.732 * 460 * ($this->calculation_values['USA_AbsorbentPumpMotorAmp'] + $this->calculation_values['USA_RefrigerantPumpMotorAmp'] + $this->calculation_values['USA_PurgePumpMotorAmp']) / 1000) + 1;

            $this->calculation_values['PowerSupply'] = "460 V( ±10%), 60 Hz (±5%), 3 Phase+N";
        }
        else
        {
            $this->calculation_values['TotalPowerConsumption'] = (1.732 * 415 * ($this->calculation_values['AbsorbentPumpMotorAmp'] + $this->calculation_values['RefrigerantPumpMotorAmp'] + $this->calculation_values['PurgePumpMotorAmp']) / 1000) + 1;

            $this->calculation_values['PowerSupply'] = "415 V( ±10%), 50 Hz (±5%), 3 Phase+N";

        }

        switch ($model_number) {
            case 60:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC H1 M1";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC H1 M1";
                }

                break;

            case 75:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC H1 M2";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC H1 M2";
                }

                break;    

            case 90:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC H1 N1";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC H1 N1";
                }

                break;     

            case 110:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC H1 N2";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC H1 N2";
                }

                break;     

            case 150:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC H1 N3";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC H1 N3";
                }

                break;      

            case 175:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC H1 N4";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC H1 N4";
                }

                break;     


            case 210:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC H1 P1";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC H1 P1";
                }
                break;     

            case 250:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC H1 P2";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC H1 P2";
                }

                break; 

            case 310:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC H1 D3";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC H1 D3";
                }

            break;
            case 350:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {

                    $this->model_values['model_name'] = "TZC H1 D4";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC H1 D4";
                }

            break;
            case 410:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC H1 E1";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC H1 E1";

                }
 
            break;

            case 470:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {

                    $this->model_values['model_name'] = "TZC H1 E2";
                }
                else
                {

                    $this->model_values['model_name'] = "TAC H1 E2";
                }

            break;

            case 530:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {

                    $this->model_values['model_name'] = "TZC H1 E3";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC H1 E3";

                }
 
            break;

            case 580:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC H1 E4";     
                }
                else
                {
                    $this->model_values['model_name'] = "TAC H1 E4";
                }
  

            break;

            case 630:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC H1 E5";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC H1 E5";
                }

            break;

            case 710:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC H1 E6";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC H1 E6";

                }

            break;

            case 760:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {

                    $this->model_values['model_name'] = "TZC H1 F1";
                }
                else
                {

                    $this->model_values['model_name'] = "TAC H1 F1";

                }

            break;

            case 810:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {   
                    $this->model_values['model_name'] = "TZC H1 F2";  
                }
                else
                {
                    $this->model_values['model_name'] = "TAC H1 F2"; 
                }

            break;

            case 900:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC H1 F3"; 
                }
                else
                {
                    $this->model_values['model_name'] = "TAC H1 F3";
                }


            break;

            case 1010:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {

                    $this->model_values['model_name'] = "TZC H1 G1";

                }
                else
                {

                    $this->model_values['model_name'] = "TAC H1 G1";

                }

            break;

            case 1130:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC H1 G2";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC H1 G2";

                }
 
            break;

            case 1260:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {

                    $this->model_values['model_name'] = "TZC H1 G3"; 
                }
                else
                {

                    $this->model_values['model_name'] = "TAC H1 G3";
                }

            break;

            case 1380:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC H1 G4";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC H1 G4";
                }

            break;

            case 1560:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC H1 G5";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC H1 G5";
                }

            break;

            case 1690:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC H1 G6";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC H1 G6";
                }

            break;

            case 1890:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC H1 H1";

                }
                else
                {
                    $this->model_values['model_name'] = "TAC H1 H1";
                }

            break;

            case 2130:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC H1 H2";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC H1 H2";
                }
 
            break;

            case 2270:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC H1 J1";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC H1 J1";
                }
  
            break;

            case 2560:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC H1 J2";
                }
                else
                {

                    $this->model_values['model_name'] = "TAC H1 J2";

                }

            break;

            default:

            break;
        }
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
                
        
        // "HOT_WATER_IN":
        if(!(($this->model_values['hot_water_in'] >= $this->model_values['min_hot_water_in']) && ($this->model_values['hot_water_in'] <=$this->model_values['max_hot_water_in'])))
        {
            return array('status' => false,'msg' => $this->notes['NOTES_HWIT_OR']);
        }


        // "HOT_WATER_OUT":
        if($this->model_values['hot_water_out'] >= $this->model_values['hot_water_in'])
        {
            return array('status' => false,'msg' => $this->notes['NOTES_HWO_HWI']);
        }
        if($this->model_values['hot_water_out'] < $this->model_values['min_hot_water_out'])
        {
            return array('status' => false,'msg' => $this->notes['NOTES_HWOT_MV']);
        }
            
        return array('status' => true,'msg' => "process run successfully");

        
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

    public function getCalculationValues($model_number){

        $model_number = (int)$model_number;
        $chiller_calculation_values = ChillerCalculationValue::where('code',$this->model_code)->where('min_model',$model_number)->first();

        $calculation_values = $chiller_calculation_values->calculation_values;
        $calculation_values = json_decode($calculation_values,true);

        $calculation_values = array_only($calculation_values, ['LE',
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
            'AABS',
            'ACON',
            'AEVA',
            'AGEN',
            'KABS',
            'KCON',
            'KEVA',
            'PNB1',
            'PNB2',
            'PSL2',
            'PSLI',
            'PSLO',
            'TNAA',
            'TNEV',
            'UGEN',
            'AHE',
            'UHE',
            'VEMIN1',
            'TEPMAX',
            'm_maxCHWWorkPressure',
            'm_maxCOWWorkPressure',
            'm_maxHWWorkPressure',
            'm_DesignPressure',
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
            'AbsorbentPumpMotorKW',
            'AbsorbentPumpMotorAmp',
            'RefrigerantPumpMotorKW',
            'RefrigerantPumpMotorAmp',
            'PurgePumpMotorKW',
            'PurgePumpMotorAmp',
            'SFACTOR',
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
            'ODC',
            'MOD1',
            'all_work_pr_hw',
            'TNG',
            'min_chilled_water_out'

        ]);

        return $calculation_values;
    }

    public function testingH1Calculation($datas){
     
        $this->model_values = $datas;
        $vam_base = new VamBaseController();
        $this->model_values['metallurgy_standard'] = $vam_base->getBoolean($this->model_values['metallurgy_standard']);
        $this->updateInputs();

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
