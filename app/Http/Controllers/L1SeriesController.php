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

class L1SeriesController extends Controller
{
    private $model_code = "L1";
    private $model_values;
    private $calculation_values;
    private $notes;
    private $changed_value;

    public function getL1Series(){

        $chiller_form_values = $this->getFormValues(35);
        
        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')
                                        ->where('code',$this->model_code)
                                        ->where('min_model','<=',35)->where('max_model','>=',35)->first();

                               
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


        return view('l1_series')->with('default_values',$converted_values)
                                        ->with('language_datas',$language_datas)
                                        ->with('evaporator_options',$evaporator_options)
                                        ->with('absorber_options',$absorber_options)
                                        ->with('condenser_options',$condenser_options) 
                                        ->with('chiller_metallurgy_options',$chiller_metallurgy_options)
                                        ->with('unit_set',$unit_set)
                                        ->with('units_data',$units_data)
                                        ->with('regions',$regions);
    }


    public function postAjaxL1(Request $request){

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

    public function postResetL1(Request $request){
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
        $this->RANGECAL();

        $min_chilled_water_out = Auth::user()->min_chilled_water_out;
        if($min_chilled_water_out > $this->model_values['min_chilled_water_out'])
            $this->model_values['min_chilled_water_out'] = $min_chilled_water_out;
        

        $unit_conversions = new UnitConversionController;
        $converted_values = $unit_conversions->formUnitConversion($this->model_values,$this->model_code);
    
        

        return response()->json(['status'=>true,'msg'=>'Ajax Datas','model_values'=>$converted_values,'evaporator_options'=>$evaporator_options,'absorber_options'=>$absorber_options,'condenser_options'=>$condenser_options,'chiller_metallurgy_options'=>$chiller_metallurgy_options]);

    }

    public function postL1(Request $request){

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
        $this->RANGECAL();
        

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

        // Log::info($this->calculation_values);
        $calculated_values = $unit_conversions->reportUnitConversion($this->calculation_values,$this->model_code);

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
        
        $view = view("reports.l1_report", ['name' => $name,'phone' => $phone,'project' => $project,'calculation_values' => $calculation_values,'evaporator_name' => $evaporator_name,'absorber_name' => $absorber_name,'condenser_name' => $condenser_name,'unit_set' => $unit_set,'units_data' => $units_data,'language_datas' => $language_datas])->render();

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

        $redirect_url = route('download.l1report', ['user_report_id' => $user_report->id,'type' => $report_type]);
        
        return response()->json(['status'=>true,'msg'=>'Ajax Datas','redirect_url'=>$redirect_url]);
        
    }

    public function downloadReport($user_report_id,$type){

        $user_report = UserReport::find($user_report_id);
        if(!$user_report){
            return response()->json(['status'=>false,'msg'=>'Invalid Report']);
        }

        if($type == 'save_word'){
            $report_controller = new ReportController();
            $file_name = $report_controller->wordFormatL1($user_report_id,$this->model_code);

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


        $pdf = PDF::loadView('reports.report_l1_pdf', ['name' => $name,'phone' => $phone,'project' => $project,'calculation_values' => $calculation_values,'evaporator_name' => $evaporator_name,'absorber_name' => $absorber_name,'condenser_name' => $condenser_name,'unit_set' => $unit_set,'units_data' => $units_data,'language_datas' => $language_datas,'language' => $language]);

        return $pdf->download('l1.pdf');

    }

    public function WATERPROP(){

        $vam_base = new VamBaseController();
        
        if ($this->calculation_values['GL'] == 2)
        {
            $this->calculation_values['CHGLY_VIS12'] = $vam_base->EG_VISCOSITY($this->calculation_values['TCHW12'], $this->calculation_values['CHGLY']);
            $this->calculation_values['CHGLY_VIS12'] = $this->calculation_values['CHGLY_VIS12'] / 1000;
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
            $this->calculation_values['CHGLY_VIS12'] = $vam_base->PG_VISCOSITY($this->calculation_values['TCHW12'], $this->calculation_values['CHGLY']);
            $this->calculation_values['CHGLY_VIS12'] = $this->calculation_values['CHGLY_VIS12'] / 1000;
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

    public function VELOCITY(){
        $model_number =(int)$this->calculation_values['MODEL'];

        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$this->model_code)
                                        ->where('min_model','<=',$model_number)->where('max_model','>',$model_number)->first();

        $chiller_options = $chiller_metallurgy_options->chillerOptions;
        
        $evaporator_option = $chiller_options->where('type', 'eva')->where('value',$this->calculation_values['TU2'])->first();
        $absorber_option = $chiller_options->where('type', 'abs')->where('value',$this->calculation_values['TU5'])->first();
        $condenser_option = $chiller_options->where('type', 'con')->where('value',$this->calculation_values['TV5'])->first();

        $this->calculation_values['VAMIN'] = $absorber_option->metallurgy->abs_min_velocity;          
        $this->calculation_values['VAMAX'] = $absorber_option->metallurgy->abs_max_velocity;
        $this->calculation_values['VCMIN'] = 1.0;
        $this->calculation_values['VCMAX'] = $condenser_option->metallurgy->con_max_velocity;

        $this->CWVELOCITY();

        if ($this->calculation_values['GHOT'] != 0)
        {
            $this->HWVELOCITY();
        }

        $this->calculation_values['VEMIN'] = $evaporator_option->metallurgy->eva_min_velocity;
        $this->calculation_values['VEMAX'] = $evaporator_option->metallurgy->eva_max_velocity;

        $this->calculation_values['GCHW'] = $this->calculation_values['TON'] * 3024 / (($this->calculation_values['TCHW11'] - $this->calculation_values['TCHW12']) * $this->calculation_values['CHGLY_ROW12'] * $this->calculation_values['CHGLY_SPHT12'] / 4187);

        $this->calculation_values['TEP'] = 1;
        do
        {
            $this->calculation_values['VEA'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * (($this->calculation_values['TNEV'] / 2) / $this->calculation_values['TEP']));
            if ($this->calculation_values['VEA'] < $this->calculation_values['VEMIN'])
                $this->calculation_values['TEP'] = $this->calculation_values['TEP'] + 1;


        } while ($this->calculation_values['VEA'] < $this->calculation_values['VEMIN'] && $this->calculation_values['TEP'] <= 4);

        if ($this->calculation_values['TEP'] > $this->calculation_values['TEPMAX'])
        {

            $this->calculation_values['TEP'] = $this->calculation_values['TEPMAX'];
            $this->calculation_values['VEA'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * (($this->calculation_values['TNEV'] / 2) / $this->calculation_values['TEP']));

            if ($this->calculation_values['VEA'] < $this->calculation_values['VEMIN'])
            {
                return  array('status' => false,'msg' => $this->notes['NOTES_CHW_VELO']);
            }
        }

        if ($this->calculation_values['VEA'] > $this->calculation_values['VEMAX'])
        {
            if ($this->calculation_values['TEP'] > 1)
            {
                $this->calculation_values['TEP'] = $this->calculation_values['TEP'] - 1;
                $this->calculation_values['VEA'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * (($this->calculation_values['TNEV'] / 2) / $this->calculation_values['TEP']));
            }
            else
            {

                if ($this->calculation_values['TEP'] == 1)
                {
                    return  array('status' => false,'msg' => $this->notes['NOTES_CHW_VELO_HI']);
                }
                //}
            }

            // PR_DROP_DATA();
            $this->PR_DROP_CHILL();

            if ($this->calculation_values['FLE'] > 12)
            {
                //if (MODEL < 750 && TU2 < 2.1)
                //{
                //    $this->calculation_values['VEMIN'] = 0.45;
                //}
                //else
                {
                    $this->calculation_values['VEMIN'] = 1;
                }
                $this->calculation_values['TEP'] = 1;
                do
                {
                    $this->calculation_values['VEA'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * (($this->calculation_values['TNEV'] / 2) / $this->calculation_values['TEP']));
                    if ($this->calculation_values['VEA'] < $this->calculation_values['VEMIN'])
                        $this->calculation_values['TEP'] = $this->calculation_values['TEP'] + 1;
                } while ($this->calculation_values['VEA'] < $this->calculation_values['VEMIN'] && $this->calculation_values['TEP'] <= 4);

                if ($this->calculation_values['TEP'] > 4)
                {
                    $this->calculation_values['TEP'] = 4;
                    $this->calculation_values['VEA'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * (($this->calculation_values['TNEV'] / 2) / $this->calculation_values['TEP']));
                }
            }

        }
        return  array('status' => true,'msg' => "chilled water velocity");


    }

    public function CWVELOCITY(){
        $this->calculation_values['TAP'] = 0;
        do
        {
            $this->calculation_values['TAP'] = $this->calculation_values['TAP'] + 1;
            $this->calculation_values['VA'] = $this->calculation_values['GCW'] / (((3600 * 3.141593 * $this->calculation_values['IDA'] * $this->calculation_values['IDA']) / 4.0) * ($this->calculation_values['TNAA'] / $this->calculation_values['TAP']));
        } while ($this->calculation_values['VA'] < $this->calculation_values['VAMAX']);
        //}

        
        if ($this->calculation_values['VA'] > ($this->calculation_values['VAMAX']) && $this->calculation_values['TAP'] != 1)
        {
            $this->calculation_values['TAP'] = $this->calculation_values['TAP'] - 1;
            $this->calculation_values['VA'] = $this->calculation_values['GCW'] / (((3600 * 3.141593 * $this->calculation_values['IDA'] * $this->calculation_values['IDA']) / 4.0) * ($this->calculation_values['TNAA'] / $this->calculation_values['TAP']));
        }

        if ($this->calculation_values['TAP'] == 1)          //PARAFLOW
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

    public function HWVELOCITY(){
        for ($this->calculation_values['TGP'] = 2; $this->calculation_values['TGP'] <= 8; $this->calculation_values['TGP'] += 2)
        {
            $this->calculation_values['VG'] = $this->calculation_values['GHOT'] / 3600 / (3.142 / 4 * $this->calculation_values['IDG'] * $this->calculation_values['IDG'] * $this->calculation_values['TNG'] / $this->calculation_values['TGP']);
            if ($this->calculation_values['VG'] > 1.25)
            {
                break;
            }
        }


        if ($this->calculation_values['VG'] < 1.25)
        {

            $this->calculation_values['TGP'] = 8;                                                            //6+6 passes after 4+4. 5+5 pass is not given in Gen
            $this->calculation_values['VG'] = $this->calculation_values['GHOT'] / 3600 / (3.142 / 4 * $this->calculation_values['IDG'] * $this->calculation_values['IDG'] * $this->calculation_values['TNG'] / $this->calculation_values['TGP']);
        }


        if ($this->calculation_values['VG'] > 2.78 && $this->calculation_values['TGP'] != 2)
        {
            $this->calculation_values['TGP'] = 2;
            $this->calculation_values['VG'] = $this->calculation_values['GHOT'] / 3600 / (3.142 / 4 * $this->calculation_values['IDG'] * $this->calculation_values['IDG'] * $this->calculation_values['TNG'] / $this->calculation_values['TGP']);
        }
    }

    public function PR_DROP_CHILL(){
        $vam_base = new VamBaseController();

        // $this->calculation_values['PIDE1'] = ($this->calculation_values['PODE1'] - (2 * $this->calculation_values['THPE1'])) / 1000;

        $this->calculation_values['VPE1'] = ($this->calculation_values['GCHW'] * 4) / (3.141593 * $this->calculation_values['PIDE1'] * $this->calculation_values['PIDE1'] * 3600);

        $this->calculation_values['TME'] = ($this->calculation_values['TCHW11'] + $this->calculation_values['TCHW12']) / 2.0;

        if ($this->calculation_values['GL'] == 3)
        {
            $this->calculation_values['CHGLY_ROW22'] = $vam_base->PG_ROW($this->calculation_values['TME'], $this->calculation_values['CHGLY']);
            $this->calculation_values['CHGLY_VIS22'] = $vam_base->PG_VISCOSITY($this->calculation_values['TME'], $this->calculation_values['CHGLY']) / 1000;
        }
        else
        {
            $this->calculation_values['CHGLY_ROW22'] = $vam_base->EG_ROW($this->calculation_values['TME'], $this->calculation_values['CHGLY']);
            $this->calculation_values['CHGLY_VIS22'] = $vam_base->EG_VISCOSITY($this->calculation_values['TME'], $this->calculation_values['CHGLY']) / 1000;
        }

        $this->calculation_values['REPE1'] = ($this->calculation_values['PIDE1'] * $this->calculation_values['VPE1'] * $this->calculation_values['CHGLY_ROW22']) / $this->calculation_values['CHGLY_VIS22'];

        $this->calculation_values['FF1'] = 1.325 / pow(log((0.0457 / (3.7 * $this->calculation_values['PIDE1'] * 1000)) + (5.74 / pow($this->calculation_values['REPE1'], 0.9))), 2);

        $this->calculation_values['FL1'] = ($this->calculation_values['FF1'] * ($this->calculation_values['SL1'] + $this->calculation_values['SL8']) / $this->calculation_values['PIDE1']) * ($this->calculation_values['VPE1'] * $this->calculation_values['VPE1'] / (2 * 9.81));
        $this->calculation_values['FL5'] = ($this->calculation_values['VPE1'] * $this->calculation_values['VPE1'] / (2 * 9.81)) + (0.5 * $this->calculation_values['VPE1'] * $this->calculation_values['VPE1'] / (2 * 9.81));

        $this->calculation_values['FLP'] = $this->calculation_values['FL1'] + $this->calculation_values['FL5'];

        $this->calculation_values['RE'] = ($this->calculation_values['VEA'] * $this->calculation_values['IDE'] * $this->calculation_values['CHGLY_ROW22']) / $this->calculation_values['CHGLY_VIS22'];                                                   //REYNOLDS NO IN TUBES

        if ($this->calculation_values['TU2'] == 1 || $this->calculation_values['TU2'] == 2 || $this->calculation_values['TU2'] == 4 || $this->calculation_values['TU2'] == 8 || $this->calculation_values['TU2'] == 0)                   // 12% AS PER EXPERIMENTATION      
        {
            $this->calculation_values['F'] = (0.0014 + (0.137 / pow($this->calculation_values['RE'], 0.32))) * 1.12;
            $this->calculation_values['FE1'] = 2 * $this->calculation_values['F'] * $this->calculation_values['LE'] * $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (9.81 * $this->calculation_values['IDE']);
        }            
        else
        {
            $this->calculation_values['F'] = 0.0014 + (0.125 / pow($this->calculation_values['RE'], 0.32));
            $this->calculation_values['FE1'] = 2 * $this->calculation_values['F'] * $this->calculation_values['LE'] * $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (9.81 * $this->calculation_values['IDE']);
        }

        $this->calculation_values['FE2'] = $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (4 * 9.81);
        $this->calculation_values['FE3'] = $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (2 * 9.81);
        $this->calculation_values['FE4'] = (($this->calculation_values['FE1'] + $this->calculation_values['FE2'] + $this->calculation_values['FE3']) * $this->calculation_values['TEP']) * 2;     //EVAPORATOR TUBE LOSS FOR DOUBLE ABS
        $this->calculation_values['FLE'] = $this->calculation_values['FLP'] + $this->calculation_values['FE4'];                //TOTAL FRICTION LOSS IN CHILLED WATER CKT
        $this->calculation_values['PDE'] = $this->calculation_values['FLE'] + $this->calculation_values['SHE'];                    //PRESSURE DROP IN CHILLED WATER CKT
    }

    public function CALCULATIONS(){
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
                $this->calculation_values['TAPH'] = floor(($this->calculation_values['TAP'] / 2));
                $this->calculation_values['TAPL'] = floor(($this->calculation_values['TAP'] / 2));
            }
        }


        $this->calculation_values['VAH'] = $this->calculation_values['GCWAH'] / (((3600 * 3.141593 * $this->calculation_values['IDA'] * $this->calculation_values['IDA']) / 4.0) * (($this->calculation_values['TNAA'] / 2) / $this->calculation_values['TAPH']));
        $this->calculation_values['VAL'] = $this->calculation_values['GCWAL'] / (((3600 * 3.141593 * $this->calculation_values['IDA'] * $this->calculation_values['IDA']) / 4.0) * (($this->calculation_values['TNAA'] / 2) / $this->calculation_values['TAPL']));

        $this->calculation_values['GCWCMAX'] = 3.141593 / 4 * ($this->calculation_values['IDC'] * $this->calculation_values['IDC']) * $this->calculation_values['TNC'] * $this->calculation_values['VCMAX'] * 3600 / $this->calculation_values['TCP'];
        if ($this->calculation_values['GCW'] > $this->calculation_values['GCWCMAX'])
            $this->calculation_values['GCWC'] = $this->calculation_values['GCWCMAX'];
        else
            $this->calculation_values['GCWC'] = $this->calculation_values['GCW'];

        $this->calculation_values['VC'] = ($this->calculation_values['GCWC'] * 4) / (3.141593 * $this->calculation_values['IDC'] * $this->calculation_values['IDC'] * $this->calculation_values['TNC'] * 3600 / $this->calculation_values['TCP']);
        $this->calculation_values['VEA'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * (($this->calculation_values['TNEV'] / 2) / $this->calculation_values['TEP']));
  

        $this->DERATE_KEVA();
        $this->DERATE_KABSH();
        $this->DERATE_KABSL();
        $this->DERATE_KCON();



        if ($this->calculation_values['GHOT'] != 0)
        {
            $this->DERATE_GEN();
        }

        if ($this->calculation_values['TCHW12'] <= 5.0)
        {
            $this->calculation_values['KM3'] = (0.0343 * $this->calculation_values['TCHW12']) + 0.82;
        }
        else
        {
            {
                $this->calculation_values['KM3'] = 1;
            }
        }

        $this->calculation_values['TCHW1H'] = $this->calculation_values['TCHW11'];
        $this->calculation_values['TCHW2L'] = $this->calculation_values['TCHW12'];
        $this->calculation_values['TCW1A'] = $this->calculation_values['TCW11'];

        $this->calculation_values['DT'] = $this->calculation_values['TCHW11'] - $this->calculation_values['TCHW12'];

        if ($this->calculation_values['DT'] >= 11)
        {
            $this->calculation_values['KM4'] = 1.11 - 0.01 * $this->calculation_values['DT'];
        }
        else
        {
            $this->calculation_values['KM4'] = 1;
        }

        $this->calculation_values['KEVA'] = $this->calculation_values['KEVA'] * $this->calculation_values['KM3'] * $this->calculation_values['KM4'];
        // $this->calculation_values['KEVAL'] = $this->calculation_values['KEVA'] * $this->calculation_values['KM3'] * $this->calculation_values['KM4'];
        $this->calculation_values['KABS'] = $this->calculation_values['KABS'] * $this->calculation_values['KM3'] * $this->calculation_values['KM4'];

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
            $this->calculation_values['UABSH'] = $this->calculation_values['UABSH'] * 0.9;
            $this->calculation_values['UABSL'] = $this->calculation_values['UABSL'] * 0.9;
        }

        /********************************************************/

        if ($this->calculation_values['TUU'] != "ari")
        {
            $this->EVAPORATOR();
            $this->HWGEN();
            $this->CONCHECK1();
        }
        else
        {
            $a = 1;
            $this->calculation_values['UEVA'] = 1.0 / ((1.0 / $this->calculation_values['KEVA']) + ($this->calculation_values['FFCHW1'] * 0.5));
            $this->calculation_values['UEVAH'] = 1.0 / ((1.0 / $this->calculation_values['KEVA']) + ($this->calculation_values['FFCHW1'] * 0.5));
            $this->calculation_values['UEVAL'] = 1.0 / ((1.0 / $this->calculation_values['KEVA']) + ($this->calculation_values['FFCHW1'] * 0.5));
            $this->calculation_values['UABSH'] = 1.0 / ((1.0 / $this->calculation_values['KABSH']) + ($this->calculation_values['FFCOW1'] * 0.5));
            $this->calculation_values['UABSL'] = 1.0 / ((1.0 / $this->calculation_values['KABSL']) + ($this->calculation_values['FFCOW1'] * 0.5));
            $this->calculation_values['UCON'] = 1.0 / ((1.0 / $this->calculation_values['KCON']) + ($this->calculation_values['FFCOW1'] * 0.5));

            if ($this->calculation_values['TAP'] == 1)
            {
                $this->calculation_values['UABSH'] = $this->calculation_values['UABSH'] * 0.9;
                $this->calculation_values['UABSL'] = $this->calculation_values['UABSL'] * 0.9;
            }

            $this->EVAPORATOR();
            $this->HWGEN();

            $t11 = array();
            $t3n1 = array();
            $t12 = array();
            $t3n2 = array();
            $TCW14 = $this->calculation_values['TCW4'];

            do
            {
                $this->CONCHECK1();
                if ($this->calculation_values['XCONC'] > $this->calculation_values['KM'])
                {
                    break;
                }
                $t11[$a] = $this->calculation_values['T1'];
                $t3n1[$a] = $this->calculation_values['T3'];
                $this->calculation_values['ARISSP'] = ($this->calculation_values['TCHW12'] - $this->calculation_values['T1']) * 1.8;
                $this->calculation_values['ARIR'] = ($this->calculation_values['TCHW11'] - $this->calculation_values['TCHW12']) * 1.8;
                $this->calculation_values['ARILMTD'] = $this->calculation_values['ARIR'] / log(1 + ($this->calculation_values['ARIR'] / $this->calculation_values['ARISSP']));
                $this->calculation_values['ARICHWA'] = 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['LE'] * $this->calculation_values['TNEV'];
                $this->calculation_values['ARIILMTD'] = (5 * $this->calculation_values['FFCHW1']) * ($this->calculation_values['TON'] * 3024 * 3.968 / ($this->calculation_values['ARICHWA'] * 3.28084 * 3.28084));
                $this->calculation_values['ARIZ'] = $this->calculation_values['ARIR'] / ($this->calculation_values['ARILMTD'] - $this->calculation_values['ARIILMTD']);
                $this->calculation_values['ARITDA'] = $this->calculation_values['ARISSP'] - ($this->calculation_values['ARIR'] / (exp($this->calculation_values['ARIZ']) - 1));
                $this->calculation_values['ARITCHWI'] = $this->calculation_values['TCHW11'] - ($this->calculation_values['ARITDA'] / 1.8);
                $this->calculation_values['ARITCHWO'] = $this->calculation_values['TCHW12'] - ($this->calculation_values['ARITDA'] / 1.8);

                $this->calculation_values['ARISSPC'] = ($this->calculation_values['T3'] - $TCW14) * 1.8;
                $this->calculation_values['ARIRC'] = ($TCW14 - $this->calculation_values['TCW11']) * 1.8;
                $this->calculation_values['ALMTDC'] = $this->calculation_values['ARIRC'] / log(1 + ($this->calculation_values['ARIRC'] / $this->calculation_values['ARISSPC']));
                $this->calculation_values['ARICOWA'] = 3.141593 * $this->calculation_values['LE'] * ($this->calculation_values['IDA'] * $this->calculation_values['TNAA'] + $this->calculation_values['IDC'] * $this->calculation_values['TNC']);
                $this->calculation_values['AILMTDC'] = (5 * $this->calculation_values['FFCOW1']) * ($this->calculation_values['GCW'] * $this->calculation_values['COGLY_ROWH1'] * ($this->calculation_values['COGLY_SPHT1'] / 4187) * ($this->calculation_values['TCW4'] - $this->calculation_values['TCW11']) * 3.968 / ($this->calculation_values['ARICOWA'] * 3.28084 * 3.28084));
                $this->calculation_values['ARIZC'] = $this->calculation_values['ARIRC'] / ($this->calculation_values['ALMTDC'] - $this->calculation_values['AILMTDC']);
                $this->calculation_values['ARITDAC'] = $this->calculation_values['ARISSPC'] - ($this->calculation_values['ARIRC'] / (exp($this->calculation_values['ARIZC']) - 1));
                $this->calculation_values['ARITCWI'] = $this->calculation_values['TCW11'] + ($this->calculation_values['ARITDAC'] / 1.8);

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
                    $this->calculation_values['UABSH'] = $this->calculation_values['UABSH'] * 0.9;
                    $this->calculation_values['UABSL'] = $this->calculation_values['UABSL'] * 0.9;
                }

                $this->calculation_values['TCHW1H'] = $this->calculation_values['ARITCHWI'];
                $this->calculation_values['TCHW2L'] = $this->calculation_values['ARITCHWO'];
                $this->calculation_values['TCW1A'] = $this->calculation_values['ARITCWI'];

                $this->EVAPORATOR();
                $this->HWGEN();

                $t12[$a] = $this->calculation_values['T1'];
                $t3n2[$a] = $this->calculation_values['T3'];
            } while ((abs($t11[$a] - $t12[$a]) > 0.005) || (abs($t3n1[$a] - $t3n2[$a]) > 0.005));
        }

        $this->PRESSURE_DROP();
    }

    public function DERATE_KEVA(){

        $vam_base = new VamBaseController();

        $this->calculation_values['KEVA2'] = $this->calculation_values['KEVA'];
        $this->calculation_values['GLY_VIS'] = $vam_base->EG_VISCOSITY($this->calculation_values['TCHW12'], 0);
        $this->calculation_values['GLY_VIS'] = $this->calculation_values['GLY_VIS'] / 1000;
        $this->calculation_values['GLY_TCON'] = $vam_base->EG_THERMAL_CONDUCTIVITY($this->calculation_values['TCHW12'], 0);
        $this->calculation_values['GLY_ROW'] = $vam_base->EG_ROW($this->calculation_values['TCHW12'], 0);
        $this->calculation_values['GLY_SPHT'] = $vam_base->EG_SPHT($this->calculation_values['TCHW12'], 0) * 1000;

        if ( $this->calculation_values['TU2'] == 3 || $this->calculation_values['TU2'] == 6 || $this->calculation_values['TU2'] == 7 || $this->calculation_values['TU2'] == 9)
        {
            $this->calculation_values['VEVA'] = 0.7;
        }
        else
        {
            $this->calculation_values['VEVA'] = 1.5;
        }
        

        $this->calculation_values['RE'] = $this->calculation_values['GLY_ROW'] * $this->calculation_values['VEVA'] * $this->calculation_values['IDE'] / $this->calculation_values['GLY_VIS'];
        $this->calculation_values['PR'] = $this->calculation_values['GLY_VIS'] * $this->calculation_values['GLY_SPHT'] / $this->calculation_values['GLY_TCON'];
        $this->calculation_values['NU1'] = 0.023 * pow($this->calculation_values['RE'], 0.8) * pow($this->calculation_values['PR'], 0.3);
        $this->calculation_values['HI1'] = ($this->calculation_values['NU1'] * $this->calculation_values['GLY_TCON'] / $this->calculation_values['IDE']) * 3600 / 4187;

        if ($this->calculation_values['TU2'] == 3 || $this->calculation_values['TU2'] == 6 || $this->calculation_values['TU2'] == 7 || $this->calculation_values['TU2'] == 9 )
        {
            $this->calculation_values['HI1'] = $this->calculation_values['HI1'] * 2;
        }

        if ($this->calculation_values['TU2'] == 2.0 || $this->calculation_values['TU2'] == 0 || $this->calculation_values['TU2'] == 6)
            $this->calculation_values['R1'] = log($this->calculation_values['ODE'] / $this->calculation_values['IDE']) * $this->calculation_values['ODE'] / (2 * 340);
        if ($this->calculation_values['TU2'] == 1.0 || $this->calculation_values['TU2'] == 9)
            $this->calculation_values['R1'] = log($this->calculation_values['ODE'] / $this->calculation_values['IDE']) * $this->calculation_values['ODE'] / (2 * 37);
        if ($this->calculation_values['TU2'] == 4.0 || $this->calculation_values['TU2'] == 3)
            $this->calculation_values['R1'] = log($this->calculation_values['ODE'] / $this->calculation_values['IDE']) * $this->calculation_values['ODE'] / (2 * 21);
        if ($this->calculation_values['TU2'] == 5.0 || $this->calculation_values['TU2'] == 7 || $this->calculation_values['TU2'] == 8)
            $this->calculation_values['R1'] = log($this->calculation_values['ODE'] / $this->calculation_values['IDE']) * $this->calculation_values['ODE'] / (2 * 15);

        $this->calculation_values['HO'] = 1 / (1 / $this->calculation_values['KEVA'] - (1 * $this->calculation_values['ODE'] / ($this->calculation_values['HI1'] * $this->calculation_values['IDE'])) - $this->calculation_values['R1']);
        if ($this->calculation_values['VEA'] < $this->calculation_values['VEVA'])
        {
            $this->calculation_values['RE'] = $this->calculation_values['CHGLY_ROW12'] * $this->calculation_values['VEA'] * $this->calculation_values['IDE'] / $this->calculation_values['CHGLY_VIS12'];
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

        $this->calculation_values['KEVA'] = 1 / ((1 * $this->calculation_values['ODE'] / ($this->calculation_values['HI'] * $this->calculation_values['IDE'])) + $this->calculation_values['R1'] + 1 / $this->calculation_values['HO']);
        if ($this->calculation_values['CHGLY'] != 0)
        {
            $this->calculation_values['KEVA'] = $this->calculation_values['KEVA'] * 0.99;
        }

    }

    public function DERATE_KABSH(){

        $vam_base = new VamBaseController();


        $this->calculation_values['KABS2'] = $this->calculation_values['KABS'];
        $this->calculation_values['GLY_VIS'] = $vam_base->EG_VISCOSITY($this->calculation_values['TCW11'], 0);
        $this->calculation_values['GLY_VIS'] = $this->calculation_values['GLY_VIS'] / 1000;
        $this->calculation_values['GLY_TCON'] = $vam_base->EG_THERMAL_CONDUCTIVITY($this->calculation_values['TCW11'], 0);
        $this->calculation_values['GLY_ROW'] = $vam_base->EG_ROW($this->calculation_values['TCW11'], 0);
        $this->calculation_values['GLY_SPHT'] = $vam_base->EG_SPHT($this->calculation_values['TCW11'], 0) * 1000;

        $this->calculation_values['VABS'] = 1.5;

        $this->calculation_values['RE'] = $this->calculation_values['GLY_ROW'] * $this->calculation_values['VABS'] * $this->calculation_values['IDA'] / $this->calculation_values['GLY_VIS'];
        $this->calculation_values['PR'] = $this->calculation_values['GLY_VIS'] * $this->calculation_values['GLY_SPHT'] / $this->calculation_values['GLY_TCON'];
        $this->calculation_values['NU1'] = 0.023 * pow($this->calculation_values['RE'], 0.8) * pow($this->calculation_values['PR'], 0.4);
        $this->calculation_values['HI1'] = ($this->calculation_values['NU1'] * $this->calculation_values['GLY_TCON'] / $this->calculation_values['IDA']) * 3600 / 4187;
        if ($this->calculation_values['TU5'] == 2.0 || $this->calculation_values['TU5'] == 0)
            $this->calculation_values['R1'] = log($this->calculation_values['ODA'] / $this->calculation_values['IDA']) * $this->calculation_values['ODA'] / (2 * 340);
        if ($this->calculation_values['TU5'] == 1.0)
            $this->calculation_values['R1'] = log($this->calculation_values['ODA'] / $this->calculation_values['IDA']) * $this->calculation_values['ODA'] / (2 * 37);
        if ($this->calculation_values['TU5'] == 6.0)
            $this->calculation_values['R1'] = log($this->calculation_values['ODA'] / $this->calculation_values['IDA']) * $this->calculation_values['ODA'] / (2 * 21);
        if ($this->calculation_values['TU5'] == 7.0 || $this->calculation_values['TU5'] == 5)
            $this->calculation_values['R1'] = log($this->calculation_values['ODA'] / $this->calculation_values['IDA']) * $this->calculation_values['ODA'] / (2 * 15);
        $this->calculation_values['HO'] = 1 / (1 / $this->calculation_values['KABS'] - (1 * $this->calculation_values['ODA'] / ($this->calculation_values['HI1'] * $this->calculation_values['IDA'])) - $this->calculation_values['R1']);
        if ($this->calculation_values['VAH'] < $this->calculation_values['VABS'])
        {
            $this->calculation_values['RE'] = $this->calculation_values['COGLY_ROWH1'] * $this->calculation_values['VAH'] * $this->calculation_values['IDA'] / $this->calculation_values['COGLY_VISH1'];
        }
        else
        {
            $this->calculation_values['RE'] = $this->calculation_values['COGLY_ROWH1'] * $this->calculation_values['VABS'] * $this->calculation_values['IDA'] / $this->calculation_values['COGLY_VISH1'];
        }
        $this->calculation_values['PR'] = $this->calculation_values['COGLY_VISH1'] * $this->calculation_values['COGLY_SPHT1'] / $this->calculation_values['COGLY_TCONH1'];
        $this->calculation_values['NU1'] = 0.023 * pow($this->calculation_values['RE'], 0.8) * pow($this->calculation_values['PR'], 0.4);
        $this->calculation_values['HI'] = ($this->calculation_values['NU1'] * $this->calculation_values['COGLY_TCONH1'] / $this->calculation_values['IDA']) / 4187 * 3600;

        $this->calculation_values['KABSH'] = 1 / ((1 * $this->calculation_values['ODA'] / ($this->calculation_values['HI'] * $this->calculation_values['IDA'])) + $this->calculation_values['R1'] + 1 / $this->calculation_values['HO']);
        if ($this->calculation_values['COGLY'] != 0)
        {
            $this->calculation_values['KABSH'] = $this->calculation_values['KABSH'] * 0.99;
        }
    }

    public function DERATE_KABSL(){

        $vam_base = new VamBaseController();

        $this->calculation_values['KABS2'] = $this->calculation_values['KABS'];
        $this->calculation_values['GLY_VIS'] = $vam_base->EG_VISCOSITY($this->calculation_values['TCW11'], 0);
        $this->calculation_values['GLY_VIS'] = $this->calculation_values['GLY_VIS'] / 1000;
        $this->calculation_values['GLY_TCON'] = $vam_base->EG_THERMAL_CONDUCTIVITY($this->calculation_values['TCW11'], 0);
        $this->calculation_values['GLY_ROW'] = $vam_base->EG_ROW($this->calculation_values['TCW11'], 0);
        $this->calculation_values['GLY_SPHT'] = $vam_base->EG_SPHT($this->calculation_values['TCW11'], 0) * 1000;

        $this->calculation_values['VABS'] = 1.5;

        $this->calculation_values['RE'] = $this->calculation_values['GLY_ROW'] * $this->calculation_values['VABS'] * $this->calculation_values['IDA'] / $this->calculation_values['GLY_VIS'];
        $this->calculation_values['PR'] = $this->calculation_values['GLY_VIS'] * $this->calculation_values['GLY_SPHT'] / $this->calculation_values['GLY_TCON'];
        $this->calculation_values['NU1'] = 0.023 * pow($this->calculation_values['RE'], 0.8) * pow($this->calculation_values['PR'], 0.4);
        $this->calculation_values['HI1'] = ($this->calculation_values['NU1'] * $this->calculation_values['GLY_TCON'] / $this->calculation_values['IDA']) * 3600 / 4187;
        if ($this->calculation_values['TU5'] == 2.0 || $this->calculation_values['TU5'] == 0)
            $this->calculation_values['R1'] = log($this->calculation_values['ODA'] / $this->calculation_values['IDA']) * $this->calculation_values['ODA'] / (2 * 340);
        if ($this->calculation_values['TU5'] == 1.0)
            $this->calculation_values['R1'] = log($this->calculation_values['ODA'] / $this->calculation_values['IDA']) * $this->calculation_values['ODA'] / (2 * 37);
        if ($this->calculation_values['TU5'] == 6.0)
            $this->calculation_values['R1'] = log($this->calculation_values['ODA'] / $this->calculation_values['IDA']) * $this->calculation_values['ODA'] / (2 * 21);
        if ($this->calculation_values['TU5'] == 7.0 || $this->calculation_values['TU5'] == 5)
            $this->calculation_values['R1'] = log($this->calculation_values['ODA'] / $this->calculation_values['IDA']) * $this->calculation_values['ODA'] / (2 * 15);
        $this->calculation_values['HO'] = 1 / (1 / $this->calculation_values['KABS'] - (1 * $this->calculation_values['ODA'] / ($this->calculation_values['HI1'] * $this->calculation_values['IDA'])) - $this->calculation_values['R1']);
        if ($this->calculation_values['VAL'] < $this->calculation_values['VABS'])
        {
            $this->calculation_values['RE'] = $this->calculation_values['COGLY_ROWH1'] * $this->calculation_values['VAL'] * $this->calculation_values['IDA'] / $this->calculation_values['COGLY_VISH1'];
        }
        else
        {
            $this->calculation_values['RE'] = $this->calculation_values['COGLY_ROWH1'] * $this->calculation_values['VABS'] * $this->calculation_values['IDA'] / $this->calculation_values['COGLY_VISH1'];
        }
        $this->calculation_values['PR'] = $this->calculation_values['COGLY_VISH1'] * $this->calculation_values['COGLY_SPHT1'] / $this->calculation_values['COGLY_TCONH1'];
        $this->calculation_values['NU1'] = 0.023 * pow($this->calculation_values['RE'], 0.8) * pow($this->calculation_values['PR'], 0.4);
        $this->calculation_values['HI'] = ($this->calculation_values['NU1'] * $this->calculation_values['COGLY_TCONH1'] / $this->calculation_values['IDA']) / 4187 * 3600;

        $this->calculation_values['KABSL'] = 1 / ((1 * $this->calculation_values['ODA'] / ($this->calculation_values['HI'] * $this->calculation_values['IDA'])) + $this->calculation_values['R1'] + 1 / $this->calculation_values['HO']); ;
        if ($this->calculation_values['COGLY'] != 0)
        {
            $this->calculation_values['KABSL'] = $this->calculation_values['KABSL'] * 0.99;
        }

    }

    public function DERATE_KCON(){
        $vam_base = new VamBaseController();

        $this->calculation_values['KCON2'] = $this->calculation_values['KCON'];
        $this->calculation_values['GLY_VIS'] = $vam_base->EG_VISCOSITY($this->calculation_values['TCW11'], 0);
        $this->calculation_values['GLY_VIS'] = $this->calculation_values['GLY_VIS'] / 1000;
        $this->calculation_values['GLY_TCON'] = $vam_base->EG_THERMAL_CONDUCTIVITY($this->calculation_values['TCW11'], 0);
        $this->calculation_values['GLY_ROW'] = $vam_base->EG_ROW($this->calculation_values['TCW11'], 0);
        $this->calculation_values['GLY_SPHT'] = $vam_base->EG_SPHT($this->calculation_values['TCW11'], 0) * 1000;

        $this->calculation_values['VCON'] = 1.4;

        $this->calculation_values['RE'] = $this->calculation_values['GLY_ROW'] * $this->calculation_values['VCON'] * $this->calculation_values['IDC'] / $this->calculation_values['GLY_VIS'];
        $this->calculation_values['PR'] = $this->calculation_values['GLY_VIS'] * $this->calculation_values['GLY_SPHT'] / $this->calculation_values['GLY_TCON'];
        $this->calculation_values['NU1'] = 0.023 * pow($this->calculation_values['RE'], 0.8) * pow($this->calculation_values['PR'], 0.4);
        $this->calculation_values['HI1'] = ($this->calculation_values['NU1'] * $this->calculation_values['GLY_TCON'] / $this->calculation_values['IDC']) * 3600 / 4187;
        if ($this->calculation_values['TV5'] == 2.0 || $this->calculation_values['TV5'] == 0)
            $this->calculation_values['R1'] = log($this->calculation_values['ODC'] / $this->calculation_values['IDC']) * $this->calculation_values['ODC'] / (2 * 340);
        if ($this->calculation_values['TV5'] == 1.0)
            $this->calculation_values['R1'] = log($this->calculation_values['ODC'] / $this->calculation_values['IDC']) * $this->calculation_values['ODC'] / (2 * 37);
        if ($this->calculation_values['TV5'] == 3.0)
            $this->calculation_values['R1'] = log($this->calculation_values['ODC'] / $this->calculation_values['IDC']) * $this->calculation_values['ODC'] / (2 * 21);
        if ($this->calculation_values['TV5'] == 5.0 || $this->calculation_values['TV5'] == 4)
            $this->calculation_values['R1'] = log($this->calculation_values['ODC'] / $this->calculation_values['IDC']) * $this->calculation_values['ODC'] / (2 * 15);

        $this->calculation_values['HO'] = 1 / (1 / $this->calculation_values['KCON'] - (1 * $this->calculation_values['ODC'] / ($this->calculation_values['HI1'] * $this->calculation_values['IDC'])) - $this->calculation_values['R1']);
        if ($this->calculation_values['VC'] < $this->calculation_values['VCON'])
        {
            $this->calculation_values['RE'] = $this->calculation_values['COGLY_ROWH1'] * $this->calculation_values['VC'] * $this->calculation_values['IDC'] / $this->calculation_values['COGLY_VISH1'];
        }
        else
        {
            $this->calculation_values['RE'] = $this->calculation_values['COGLY_ROWH1'] * $this->calculation_values['VCON'] * $this->calculation_values['IDC'] / $this->calculation_values['COGLY_VISH1'];
        }
        $this->calculation_values['PR'] = $this->calculation_values['COGLY_VISH1'] * $this->calculation_values['COGLY_SPHT1'] / $this->calculation_values['COGLY_TCONH1'];
        $this->calculation_values['NU1'] = 0.023 * pow($this->calculation_values['RE'], 0.8) * pow($this->calculation_values['PR'], 0.4);
        $this->calculation_values['HI'] = ($this->calculation_values['NU1'] * $this->calculation_values['COGLY_TCONH1'] / $this->calculation_values['IDC']) / 4187 * 3600;
        $this->calculation_values['KCON'] = 1 / ((1 * $this->calculation_values['ODC'] / ($this->calculation_values['HI'] * $this->calculation_values['IDC'])) + $this->calculation_values['R1'] + 1 / $this->calculation_values['HO']);
        if ($this->calculation_values['COGLY'] != 0)
        {
            $this->calculation_values['KCON'] = $this->calculation_values['KCON'] * 0.99;
        }
    }


    public function DERATE_GEN(){

        $this->calculation_values['FACT1'] = $this->calculation_values['FACT2'] = 1;

        if ($this->calculation_values['VG'] < 1.25)
        {
            $this->calculation_values['FACT1'] = ((5000 / 11) * $this->calculation_values['VG'] + (4750 / 11)) / 1000;
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



        $this->calculation_values['UGEN'] = $this->calculation_values['UGEN'] * $this->calculation_values['FACT1'] * $this->calculation_values['FACT2'];

        if ($this->calculation_values['UGEN'] > 1100)
        {
            $this->calculation_values['UGEN'] = 1100;
        }
        else if ($this->calculation_values['UGEN'] < 750)
        {
            $this->calculation_values['UGEN'] = 750;
        }

        if ($this->calculation_values['THW1'] > 105 || $this->calculation_values['TG2'] == 2)
        {
            $this->calculation_values['UGEN'] = $this->calculation_values['UGEN'] * 0.95;   //CUNI or SS Metallurgy above 105 DEG C
        }

        $this->calculation_values['UGEN'] = 1 / ((1 / $this->calculation_values['UGEN']) + $this->calculation_values['FFHOW1']);


    }

    public function EVAPORATOR(){
        $vam_base = new VamBaseController();

        $ferr1 = array();
        $tchw2h = array();
        $err1 = array();

        
        if ($this->calculation_values['TCHW12'] < 3.5)
        {
            $this->calculation_values['SFACTOR'] = $this->calculation_values['A_SFACTOR'] - (($this->calculation_values['B_SFACTOR'] - $this->calculation_values['TCHW12']) * 2 / 100);
            $this->calculation_values['GHW'] = $this->calculation_values['GHOT'] * $this->calculation_values['SFACTOR'] * $this->calculation_values['C_SFACTOR'];
        }
        else
        {
            $this->calculation_values['GHW'] = $this->calculation_values['GHOT'] * $this->calculation_values['C_SFACTOR'];
        }

        $this->calculation_values['CW'] = 1;     //1 - Absorber entry    2 - Condenser entry            
        $this->calculation_values['GDIL'] = $this->calculation_values['MOD1'] * 60;
        $this->calculation_values['QEVA'] = $this->calculation_values['TON'] * 3024;
        $this->calculation_values['GCHW'] = $this->calculation_values['QEVA'] / (($this->calculation_values['TCHW1H'] - $this->calculation_values['TCHW2L']) * $this->calculation_values['CHGLY_ROW12'] * $this->calculation_values['CHGLY_SPHT12'] / 4187);
        $this->calculation_values['LMTDEVA'] = $this->calculation_values['QEVA'] / ($this->calculation_values['UEVA'] * $this->calculation_values['AEVA']);
        $this->calculation_values['T1'] = $this->calculation_values['TCHW2L'] - ($this->calculation_values['TCHW1H'] - $this->calculation_values['TCHW2L']) / (exp(($this->calculation_values['TCHW1H'] - $this->calculation_values['TCHW2L']) / $this->calculation_values['LMTDEVA']) - 1);

        $this->calculation_values['QAB'] = $this->calculation_values['QEVA'] * (1 + 1 / 0.7) * 0.65;
        $this->calculation_values['QCO'] = $this->calculation_values['QEVA'] * (1 + 1 / 0.7) * 0.35;
        if ($this->calculation_values['CW'] == 2)
        {
            $this->calculation_values['TCW3'] = $this->calculation_values['TCW1A'];
            $this->calculation_values['ATCW3'] = $this->calculation_values['TCW1A'] + $this->calculation_values['QCO'] / ($this->calculation_values['GCW'] * 1000);
            $this->calculation_values['ATCW2'] = $this->calculation_values['ATCW3'] + $this->calculation_values['QAB'] / ($this->calculation_values['GCW'] * 1000);
            $this->calculation_values['LMTDCO'] = $this->calculation_values['QCO'] / ($this->calculation_values['KCON'] * $this->calculation_values['ACON']);
            $this->calculation_values['AT3'] = $this->calculation_values['ATCW3'] + ($this->calculation_values['ATCW3'] - $this->calculation_values['TCW1A']) / (exp(($this->calculation_values['ATCW3'] - $this->calculation_values['TCW1A']) / $this->calculation_values['LMTDCO']) - 1);
        }
        else
        {
            
            $this->calculation_values['TCW1H'] = $this->calculation_values['TCW1A'];
            
            $this->calculation_values['ATCW2'] = $this->calculation_values['TCW1A'] + $this->calculation_values['QAB'] / ($this->calculation_values['GCW'] * 1000);
            $this->calculation_values['ATCW3'] = $this->calculation_values['ATCW2'] + $this->calculation_values['QCO'] / ($this->calculation_values['GCW'] * 1000);
            $this->calculation_values['LMTDCO'] = $this->calculation_values['QCO'] / ($this->calculation_values['KCON'] * $this->calculation_values['ACON']);
            $this->calculation_values['AT3'] = $this->calculation_values['ATCW3'] + ($this->calculation_values['ATCW3'] - $this->calculation_values['ATCW2']) / (exp(($this->calculation_values['ATCW3'] - $this->calculation_values['ATCW2']) / $this->calculation_values['LMTDCO']) - 1);
        }
        $this->calculation_values['DT'] = $this->calculation_values['TCHW1H'] - $this->calculation_values['TCHW2L'];

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
        $this->calculation_values['p'] = 1;
        while (abs($ferr1[$this->calculation_values['p'] - 1]) > 0.1)
        {
            if ($this->calculation_values['p'] == 1)
            {
                if ($this->calculation_values['DT'] > 9)
                {
                    $tchw2h[$this->calculation_values['p']] = $this->calculation_values['ATCHW2H'] - 2.5;
                }
                else
                {
                    $tchw2h[$this->calculation_values['p']] = $this->calculation_values['ATCHW2H'] + 0.1;
                }
            }
            if ($this->calculation_values['p'] == 2)
            {
                $tchw2h[$this->calculation_values['p']] = $tchw2h[$this->calculation_values['p'] - 1] + 0.1;
            }
            if ($this->calculation_values['p'] >= 3)
            {
                if (($this->calculation_values['TON'] / $this->calculation_values['MODEL']) < 0.5)
                {
                    $tchw2h[$this->calculation_values['p']] = $tchw2h[$this->calculation_values['p'] - 1] + $err1[$this->calculation_values['p'] - 1] * ($tchw2h[$this->calculation_values['p'] - 1] - $tchw2h[$this->calculation_values['p'] - 2]) / ($err1[$this->calculation_values['p'] - 2] - $err1[$this->calculation_values['p'] - 1]) / 4;
                }
                else
                {
                    $tchw2h[$this->calculation_values['p']] = $tchw2h[$this->calculation_values['p'] - 1] + $err1[$this->calculation_values['p'] - 1] * ($tchw2h[$this->calculation_values['p'] - 1] - $tchw2h[$this->calculation_values['p'] - 2]) / ($err1[$this->calculation_values['p'] - 2] - $err1[$this->calculation_values['p'] - 1]) / 2;
                }
            }
            $this->calculation_values['TCHW2H'] = $tchw2h[$this->calculation_values['p']];
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
            $err1[$this->calculation_values['p']] = ($this->calculation_values['QLMTDABSH'] - $this->calculation_values['QABSH']);
            $ferr1[$this->calculation_values['p']] = ($this->calculation_values['QLMTDABSH'] - $this->calculation_values['QABSH']) / $this->calculation_values['QLMTDABSH'] * 100;
            $this->calculation_values['p']++;
        }
    }

    public function ABSORBER(){
        $vam_base = new VamBaseController();

        $ferr = array();
        $t2 = array();
        $err = array();

        $this->calculation_values['s'] = 0;
        $ferr[0] = 1;
        $this->calculation_values['m'] = 1;

        while (abs($ferr[$this->calculation_values['m'] - 1]) > 0.05)
        {

            if ($this->calculation_values['m'] == 1)
            {
                $t2[$this->calculation_values['m']] = $this->calculation_values['ATCW2'] + 2.5;
            }
            if ($this->calculation_values['m'] == 2)
            {
                $t2[$this->calculation_values['m']] = $t2[$this->calculation_values['m'] - 1] + 0.5;
            }
            if ($this->calculation_values['m'] > 2)
            {
                $t2[$this->calculation_values['m']] = $t2[$this->calculation_values['m'] - 1] + $err[$this->calculation_values['m'] - 1] * ($t2[$this->calculation_values['m'] - 1] - $t2[$this->calculation_values['m'] - 2]) / ($err[$this->calculation_values['m'] - 2] - $err[$this->calculation_values['m'] - 1]) / 2;
            }


            $this->calculation_values['T2'] = $t2[$this->calculation_values['m']];
            $this->calculation_values['XDIL'] = $vam_base->LIBR_CONC($this->calculation_values['T2'], $this->calculation_values['P1H']);
            $this->calculation_values['I2'] = $vam_base->LIBR_ENTHALPY($this->calculation_values['T2'], $this->calculation_values['XDIL']);
            $this->CONDENSER();
            $this->LTHE();


            $this->calculation_values['I8'] = $vam_base->LIBR_ENTHALPY($this->calculation_values['T8'], $this->calculation_values['XCONC']);

            $this->calculation_values['QABSL'] = ($this->calculation_values['GCONC'] * $this->calculation_values['I8']) + ($this->calculation_values['J1L'] * $this->calculation_values['GREFL']) - ($this->calculation_values['GDILL'] * $this->calculation_values['I2L']);
            $err[$this->calculation_values['m']] = ($this->calculation_values['QABSL'] - $this->calculation_values['QLMTDABSL']);
            $ferr[$this->calculation_values['m']] = ($this->calculation_values['QABSL'] - $this->calculation_values['QLMTDABSL']) / $this->calculation_values['QABSL'] * 100;
            $this->calculation_values['m']++;
        }

    } 


    public function CONDENSER(){
       $vam_base = new VamBaseController();

       $ferrr = array();
       $t3 = array();
       $error = array();
       
       if ($this->calculation_values['s'] == 0)
       {
           $this->calculation_values['AT3'] = $this->calculation_values['AT3'];
       }
       else{
           $this->calculation_values['AT3'] = $this->calculation_values['T3'];
       }
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
               $t3[$this->calculation_values['s']] = $t3[$this->calculation_values['s'] - 1] + 0.2;
           }
           if ($this->calculation_values['s'] > 2)
           {
               $t3[$this->calculation_values['s']] = $t3[$this->calculation_values['s'] - 1] + $error[$this->calculation_values['s'] - 1] * ($t3[$this->calculation_values['s'] - 1] - $t3[$this->calculation_values['s'] - 2]) / ($error[$this->calculation_values['s'] - 2] - $error[$this->calculation_values['s'] - 1]) / 2;
           }

           $this->calculation_values['T3'] = $t3[$this->calculation_values['s']];
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

           if ($this->calculation_values['CW'] == 2)
           {
               $this->CWCONOUT();
               $this->calculation_values['TCW1H'] = (($this->calculation_values['GCWC'] * $this->calculation_values['TCW4']) + (($this->calculation_values['GCW'] - $this->calculation_values['GCWC']) * $this->calculation_values['TCW3'])) / $this->calculation_values['GCW'];
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

           }
           else
           {
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
           }

           $this->calculation_values['LMTDCON'] = (($this->calculation_values['T3'] - $this->calculation_values['TCW3']) - ($this->calculation_values['T3'] - $this->calculation_values['TCW4'])) / log(($this->calculation_values['T3'] - $this->calculation_values['TCW3']) / ($this->calculation_values['T3'] - $this->calculation_values['TCW4']));
           $this->calculation_values['QLMTDCON'] = $this->calculation_values['ACON'] * $this->calculation_values['UCON'] * $this->calculation_values['LMTDCON'];
           $error[$this->calculation_values['s']] = ($this->calculation_values['QCWCON'] - $this->calculation_values['QLMTDCON']);
           $ferrr[$this->calculation_values['s']] = ($this->calculation_values['QCWCON'] - $this->calculation_values['QLMTDCON']) / $this->calculation_values['QCWCON'] * 100;
           $this->calculation_values['s']++;
       } 
    }

    public function CWCONOUT(){
        $vam_base = new VamBaseController();

        $tcw4 = array();
        $error3 = array();
        $ferrr3 = array();

        $ferrr3[0] = 5;
        $this->calculation_values['k'] = 1;
        while (abs($ferrr3[$this->calculation_values['k'] - 1]) > .05)
        {
            if ($this->calculation_values['k'] == 1)
            {
                $tcw4[$this->calculation_values['k']] = $this->calculation_values['TCW3'] + 1.5;
            }
            if ($this->calculation_values['k'] == 2)
            {
                $tcw4[$this->calculation_values['k']] = $this->calculation_values['TCW3'] + 1.7;
            }
            if ($this->calculation_values['k'] > 2)
            {
                $tcw4[$this->calculation_values['k']] = $tcw4[$this->calculation_values['k'] - 1] + $error3[$this->calculation_values['k'] - 1] * ($tcw4[$this->calculation_values['k'] - 1] - $tcw4[$this->calculation_values['k'] - 2]) / ($error3[$this->calculation_values['k'] - 2] - $error3[$this->calculation_values['k'] - 1]);
            }

            $this->calculation_values['TCW4'] = $tcw4[$this->calculation_values['k']];
            if ($this->calculation_values['GL'] == 2)
            {
                $this->calculation_values['COGLY_VISH3'] = $vam_base->EG_VISCOSITY($this->calculation_values['TCW3'], $this->calculation_values['COGLY']) / 1000;
                $this->calculation_values['COGLY_TCONH3'] = $vam_base->EG_THERMAL_CONDUCTIVITY($this->calculation_values['TCW3'], $this->calculation_values['COGLY']);
                $this->calculation_values['COGLY_ROWH3'] = $vam_base->EG_ROW($this->calculation_values['TCW3'], $this->calculation_values['COGLY']);
                $this->calculation_values['COGLY_SPHT3'] = $vam_base->EG_SPHT($this->calculation_values['TCW3'], $this->calculation_values['COGLY']) * 1000;

                $this->calculation_values['COGLY_VISH4'] = $vam_base->EG_VISCOSITY($this->calculation_values['TCW4'], $this->calculation_values['COGLY']) / 1000;
                $this->calculation_values['COGLY_TCONH4'] = $vam_base->EG_THERMAL_CONDUCTIVITY($this->calculation_values['TCW4'], $this->calculation_values['COGLY']);
                $this->calculation_values['COGLY_ROWH4'] = $vam_base->EG_ROW($this->calculation_values['TCW4'], $this->calculation_values['COGLY']);
                $this->calculation_values['COGLY_SPHT4'] = $vam_base->EG_SPHT($this->calculation_values['TCW4'], $this->calculation_values['COGLY']) * 1000;
            }
            else
            {
                $this->calculation_values['COGLY_VISH3'] = $vam_base->PG_VISCOSITY($this->calculation_values['TCW3'], $this->calculation_values['COGLY']) / 1000;
                $this->calculation_values['COGLY_TCONH3'] = $vam_base->PG_THERMAL_CONDUCTIVITY($this->calculation_values['TCW3'], $this->calculation_values['COGLY']);
                $this->calculation_values['COGLY_ROWH3'] = $vam_base->PG_ROW($this->calculation_values['TCW3'], $this->calculation_values['COGLY']);
                $this->calculation_values['COGLY_SPHT3'] = $vam_base->PG_SPHT($this->calculation_values['TCW3'], $this->calculation_values['COGLY']) * 1000;

                $this->calculation_values['COGLY_VISH4'] = $vam_base->PG_VISCOSITY($this->calculation_values['TCW4'], $this->calculation_values['COGLY']) / 1000;
                $this->calculation_values['COGLY_TCONH4'] = $vam_base->PG_THERMAL_CONDUCTIVITY($this->calculation_values['TCW4'], $this->calculation_values['COGLY']);
                $this->calculation_values['COGLY_ROWH4'] = $vam_base->PG_ROW($this->calculation_values['TCW4'], $this->calculation_values['COGLY']);
                $this->calculation_values['COGLY_SPHT4'] = $vam_base->PG_SPHT($this->calculation_values['TCW4'], $this->calculation_values['COGLY']) * 1000;
            }
            $this->calculation_values['QCWCON'] = $this->calculation_values['GCWC'] * $this->calculation_values['COGLY_ROWH1'] * ($this->calculation_values['TCW4'] * $this->calculation_values['COGLY_SPHT4'] - $this->calculation_values['TCW3'] * $this->calculation_values['COGLY_SPHT3']) / 4187;
            $this->calculation_values['T4'] = $vam_base->LIBR_TEMP($this->calculation_values['P3'], $this->calculation_values['XCONC']);
            $this->calculation_values['I4'] = $vam_base->LIBR_ENTHALPY($this->calculation_values['T4'], $this->calculation_values['XCONC']);
            $this->calculation_values['J4'] = $vam_base->WATER_VAPOUR_ENTHALPY($this->calculation_values['T4'], $this->calculation_values['P3']);
            $this->calculation_values['QREFCON'] = $this->calculation_values['GREF'] * ($this->calculation_values['J4'] - $this->calculation_values['I3']);
            $error3[$this->calculation_values['k']] = ($this->calculation_values['QREFCON'] - $this->calculation_values['QCWCON']);
            $ferrr3[$this->calculation_values['k']] = ($this->calculation_values['QREFCON'] - $this->calculation_values['QCWCON']) / $this->calculation_values['QREFCON'] * 100;
            $this->calculation_values['k']++;
        }
    }   


    public function LTHE(){

        $vam_base = new VamBaseController();

        $fmerr = array();
        $t8 = array();
        $merr = array();

        $this->calculation_values['n'] = 1;
        $fmerr[0] = 2;
        while (abs($fmerr[$this->calculation_values['n'] - 1]) > 0.05)
        {
            if ($this->calculation_values['n'] == 1)
            {
                $t8[$this->calculation_values['n']] = $this->calculation_values['T6'] + 5;
            }
            if ($this->calculation_values['n'] == 2)
            {
                $t8[$this->calculation_values['n']] = $this->calculation_values['T6'] + 5.5;
            }
            if ($this->calculation_values['n'] > 2)
            {
                $t8[$this->calculation_values['n']] = $t8[$this->calculation_values['n'] - 1] + $merr[$this->calculation_values['n'] - 1] * ($t8[$this->calculation_values['n'] - 1] - $t8[$this->calculation_values['n'] - 2]) / ($merr[$this->calculation_values['n'] - 2] - $merr[$this->calculation_values['n'] - 1]);
            }
            $this->calculation_values['T8'] = $t8[$this->calculation_values['n']];
            $this->calculation_values['I8'] = $vam_base->LIBR_ENTHALPY($this->calculation_values['T8'], $this->calculation_values['XCONC']);
            $this->calculation_values['QHECON'] = $this->calculation_values['GCONC'] * ($this->calculation_values['I4'] - $this->calculation_values['I8']);
            $this->calculation_values['I7'] = $this->calculation_values['I2'] + $this->calculation_values['QHECON'] / $this->calculation_values['GDIL'];
            $this->calculation_values['T7'] = $vam_base->LIBR_TEMPERATURE($this->calculation_values['XDIL'], $this->calculation_values['I7']);
            $this->calculation_values['LMTDHE'] = (($this->calculation_values['T4'] - $this->calculation_values['T7']) - ($this->calculation_values['T8'] - $this->calculation_values['T2'])) / log(($this->calculation_values['T4'] - $this->calculation_values['T7']) / ($this->calculation_values['T8'] - $this->calculation_values['T2']));
            $this->calculation_values['QLMTDHE'] = $this->calculation_values['AHE'] * $this->calculation_values['UHE'] * $this->calculation_values['LMTDHE'];
            $merr[$this->calculation_values['n']] = $this->calculation_values['QHECON'] - $this->calculation_values['QLMTDHE'];
            $fmerr[$this->calculation_values['n']] = ($this->calculation_values['QHECON'] - $this->calculation_values['QLMTDHE']) / $this->calculation_values['QHECON'] * 100;
            $this->calculation_values['n']++;
        }
    }

    public function CWABSHOUT(){
        $vam_base = new VamBaseController();

        $ferr4 = array();
        $tcw2h = array();
        $ferr4[0] = 2;
        $this->calculation_values['w'] = 1;
        while (abs($ferr4[$this->calculation_values['w'] - 1]) > 0.1)
        {
            if ($this->calculation_values['w'] == 1)
            {
                $tcw2h[$this->calculation_values['w']] = $this->calculation_values['TCW1H'] + 1.0;
            }
            if ($this->calculation_values['w'] == 2)
            {
                $tcw2h[$this->calculation_values['w']] = $tcw2h[$this->calculation_values['w'] - 1] + 0.5;
            }
            if ($this->calculation_values['w'] >= 3)
            {
                $tcw2h[$this->calculation_values['w']] = $tcw2h[$this->calculation_values['w'] - 1] + $ferr4[$this->calculation_values['w'] - 1] * ($tcw2h[$this->calculation_values['w'] - 1] - $tcw2h[$this->calculation_values['w'] - 2]) / ($ferr4[$this->calculation_values['w'] - 2] - $ferr4[$this->calculation_values['w'] - 1]);
            }
            if ($tcw2h[$this->calculation_values['w']] > $this->calculation_values['T6H'] && $this->calculation_values['w'] > 2)
            {
                $tcw2h[$this->calculation_values['w']] = $tcw2h[$this->calculation_values['w'] - 1] + $ferr4[$this->calculation_values['w'] - 1] * ($tcw2h[$this->calculation_values['w'] - 1] - $tcw2h[$this->calculation_values['w'] - 2]) / ($ferr4[$this->calculation_values['w'] - 2] - $ferr4[$this->calculation_values['w'] - 1]) / 5;
            }

            $this->calculation_values['TCW2H'] = $tcw2h[$this->calculation_values['w']];

            if ($this->calculation_values['GL'] == 2)
            {
                $this->calculation_values['COGLY_SPHT2H'] = $vam_base->EG_SPHT($this->calculation_values['TCW2H'], $this->calculation_values['COGLY']) * 1000;
            }
            else
            {
                $this->calculation_values['COGLY_SPHT2H'] = $vam_base->PG_SPHT($this->calculation_values['TCW2H'], $this->calculation_values['COGLY']) * 1000;
            }

            $this->calculation_values['QCWABSH'] = $this->calculation_values['GCWAH'] * $this->calculation_values['COGLY_ROWH1'] * (($this->calculation_values['TCW2H'] * $this->calculation_values['COGLY_SPHT2H']) - ($this->calculation_values['TCW1H'] * $this->calculation_values['COGLY_SPHT1'])) / 4187;
            $this->calculation_values['LMTDABSH'] = (($this->calculation_values['T6H'] - $this->calculation_values['TCW2H']) - ($this->calculation_values['T2'] - $this->calculation_values['TCW1H'])) / log(($this->calculation_values['T6H'] - $this->calculation_values['TCW2H']) / ($this->calculation_values['T2'] - $this->calculation_values['TCW1H']));
            $this->calculation_values['QLMTDABSH'] = $this->calculation_values['UABSH'] * $this->calculation_values['AABSH'] * $this->calculation_values['LMTDABSH'];
            $ferr4[$this->calculation_values['w']] = ($this->calculation_values['QCWABSH'] - $this->calculation_values['QLMTDABSH']) * 100 / $this->calculation_values['QCWABSH'];
            $this->calculation_values['w']++;
        }
    }

    public function CWABSLOUT(){
        $vam_base = new VamBaseController();

        $ferr5 = array();
        $tcw2l = array();
        $ferr5[0] = 2;
        $this->calculation_values['c'] = 1;
        while (abs($ferr5[$this->calculation_values['c'] - 1]) > 0.1)
        {
            if ($this->calculation_values['c'] == 1)
            {
                $tcw2l[$this->calculation_values['c']] = $this->calculation_values['TCW1L'] + 1.0;
            }
            if ($this->calculation_values['c'] == 2)
            {
                $tcw2l[$this->calculation_values['c']] = $tcw2l[$this->calculation_values['c'] - 1] + 0.5;
            }
            if ($this->calculation_values['c'] >= 3)
            {
                $tcw2l[$this->calculation_values['c']] = $tcw2l[$this->calculation_values['c'] - 1] + $ferr5[$this->calculation_values['c'] - 1] * ($tcw2l[$this->calculation_values['c'] - 1] - $tcw2l[$this->calculation_values['c'] - 2]) / ($ferr5[$this->calculation_values['c'] - 2] - $ferr5[$this->calculation_values['c'] - 1]) / 3;
            }
            if ($tcw2l[$this->calculation_values['c']] > $this->calculation_values['T6'] && $this->calculation_values['c'] > 2)
            {
                $tcw2l[$this->calculation_values['c']] = $tcw2l[$this->calculation_values['c'] - 1] + $ferr5[$this->calculation_values['c'] - 1] * ($tcw2l[$this->calculation_values['c'] - 1] - $tcw2l[$this->calculation_values['c'] - 2]) / ($ferr5[$this->calculation_values['c'] - 2] - $ferr5[$this->calculation_values['c'] - 1]) / 5;
            }
            $this->calculation_values['TCW2L'] = $tcw2l[$this->calculation_values['c']];

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
            $ferr5[$this->calculation_values['c']] = ($this->calculation_values['QCWABSL'] - $this->calculation_values['QLMTDABSL']) * 100 / $this->calculation_values['QCWABSL'];
            $this->calculation_values['c']++;
        }

    }

    public function HWGEN(){
        $vam_base = new VamBaseController();
        $ferr4 = array();
        $thw1h = array();

        $ferr4[0] = 1;
        $this->calculation_values['yx'] = 1;
        while (abs($ferr4[$this->calculation_values['yx'] - 1]) > 0.05)
        {
            if ($this->calculation_values['yx'] == 1)
            {
                $thw1h[$this->calculation_values['yx']] = $this->calculation_values['THW1'] - 1;
            }
            if ($this->calculation_values['yx'] == 2)
            {
                $thw1h[$this->calculation_values['yx']] = $thw1h[$this->calculation_values['yx'] - 1] - 0.5;
            }
            if ($this->calculation_values['yx'] >= 3)
            {
                $thw1h[$this->calculation_values['yx']] = $thw1h[$this->calculation_values['yx'] - 1] + $ferr4[$this->calculation_values['yx'] - 1] * ($thw1h[$this->calculation_values['yx'] - 1] - $thw1h[$this->calculation_values['yx'] - 2]) / ($ferr4[$this->calculation_values['yx'] - 2] - $ferr4[$this->calculation_values['yx'] - 1]) / 2;
            }
            $this->calculation_values['THW2'] = $thw1h[$this->calculation_values['yx']];

            if ($this->calculation_values['GL'] == 2)
            {
                $this->calculation_values['CHWGLY_ROW11'] = $vam_base->EG_ROW($this->calculation_values['THW1'], $this->calculation_values['HWGLY']);
                $this->calculation_values['CHWGLY_SPHT11'] = $vam_base->EG_SPHT($this->calculation_values['THW1'], $this->calculation_values['HWGLY']);
                $this->calculation_values['CHWGLY_SPHT1H'] = $vam_base->EG_SPHT($this->calculation_values['THW2'], $this->calculation_values['HWGLY']);
            }
            else
            {
                $this->calculation_values['CHWGLY_ROW11'] = $vam_base->PG_ROW($this->calculation_values['THW1'], $this->calculation_values['HWGLY']);
                $this->calculation_values['CHWGLY_SPHT11'] = $vam_base->PG_SPHT($this->calculation_values['THW1'], $this->calculation_values['HWGLY']);
                $this->calculation_values['CHWGLY_SPHT1H'] = $vam_base->PG_SPHT($this->calculation_values['THW2'], $this->calculation_values['HWGLY']);
            }

            $this->calculation_values['QHW'] = $this->calculation_values['GHW'] * $this->calculation_values['CHWGLY_ROW11'] * ($this->calculation_values['THW1'] - $this->calculation_values['THW2']) * ($this->calculation_values['CHWGLY_SPHT11'] + $this->calculation_values['CHWGLY_SPHT1H']) * 0.5 / 4.187;
            $this->calculation_values['QGEN'] = ($this->calculation_values['GREF'] * $this->calculation_values['J4']) + ($this->calculation_values['GCONC'] * $this->calculation_values['I4']) - ($this->calculation_values['GDIL'] * $this->calculation_values['I7']);
            $ferr4[$this->calculation_values['yx']] = ($this->calculation_values['QHW'] - $this->calculation_values['QGEN']) * 100 / $this->calculation_values['QHW'];
            $this->calculation_values['yx']++;
        }
        $this->calculation_values['LMTDGEN'] = $this->calculation_values['QGEN'] / ($this->calculation_values['UGEN'] * $this->calculation_values['AGEN']);
        $this->calculation_values['T5'] = $vam_base->LIBR_TEMP($this->calculation_values['P3'], $this->calculation_values['XDIL']);
        $this->calculation_values['LMTDGENA'] = (($this->calculation_values['THW1'] - $this->calculation_values['T4']) - ($this->calculation_values['THW2'] - $this->calculation_values['T5'])) / log(($this->calculation_values['THW1'] - $this->calculation_values['T4']) / ($this->calculation_values['THW2'] - $this->calculation_values['T5']));
        $this->calculation_values['COP'] = ($this->calculation_values['TON'] * 3024) / ($this->calculation_values['GHOT'] * $this->calculation_values['CHWGLY_ROW11'] * ($this->calculation_values['THW1'] - $this->calculation_values['THW2']) * ($this->calculation_values['CHWGLY_SPHT11'] + $this->calculation_values['CHWGLY_SPHT1H']) * 0.5 / 4.187);


    }

    public function CONCHECK1(){
        if ($this->calculation_values['MODEL'] < 80)
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
        else if ($this->calculation_values['MODEL'] > 80)
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


    public function PRESSURE_DROP(){
        // PR_DROP_DATA();

        $this->calculation_values['VEA'] = $this->calculation_values['GCHW'] / 3600 / (3.142 / 4 * $this->calculation_values['IDE'] * $this->calculation_values['IDE'] * ($this->calculation_values['TNEV'] / 2) / $this->calculation_values['TEP']);
        $this->calculation_values['VA'] = $this->calculation_values['GCW'] / 3600 / (3.142 / 4 * $this->calculation_values['IDA'] * $this->calculation_values['IDA'] * $this->calculation_values['TNAA'] / $this->calculation_values['TAP']);
        $this->calculation_values['VC'] = $this->calculation_values['GCWC'] / 3600 / (3.142 / 4 * $this->calculation_values['IDC'] * $this->calculation_values['IDC'] * $this->calculation_values['TNC'] / $this->calculation_values['TCP']);


        // PR_DROP_DATA();
        $this->PR_DROP_CHILL();
        $this->PR_DROP_COW();
        $this->PR_DROP_HW();

    }


    public function PR_DROP_HW()
    {
        $vam_base = new VamBaseController();

        // $this->calculation_values['PIDG'] = ($this->calculation_values['PODG'] - 2 * $this->calculation_values['PTK']) / 1000;
        $this->calculation_values['VPG'] = ($this->calculation_values['GHOT'] * 4) / (3.14153 * $this->calculation_values['PIDG'] * $this->calculation_values['PIDG'] * 3600); //PIPE VELOCITY
        $this->calculation_values['TMG'] = ($this->calculation_values['THW1'] + $this->calculation_values['THW2']) / 2.0;



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



        $this->calculation_values['REPG'] = ($this->calculation_values['PIDG'] * $this->calculation_values['VPG'] * $this->calculation_values['GLY_ROW']) / $this->calculation_values['VISG']; //REYNOLDS NO IN PIPE



        $this->calculation_values['FFH'] = 1.325 / pow(log((0.0457 / (3.7 * $this->calculation_values['PIDG'] * 1000)) + (5.74 / pow($this->calculation_values['REPG'], 0.9))), 2);



        $this->calculation_values['FGP1'] = (($this->calculation_values['GSL1'] + $this->calculation_values['GSL2']) * $this->calculation_values['FFH'] / $this->calculation_values['PIDG']) * ($this->calculation_values['VPG'] * $this->calculation_values['VPG'] / (2 * 9.81));
        $this->calculation_values['FGP2'] = (($this->calculation_values['VPG'] * $this->calculation_values['VPG'] / (2 * 9.81)) + (0.5 * $this->calculation_values['VPG'] * $this->calculation_values['VPG'] / (2 * 9.81)));


        $this->calculation_values['FGP'] = $this->calculation_values['FGP1'] + $this->calculation_values['FGP2']; //FR LOSS IN PIPES

        $this->calculation_values['RE'] = ($this->calculation_values['GLY_ROW'] * $this->calculation_values['VG'] * $this->calculation_values['IDG']) / $this->calculation_values['VISG'];


        $this->calculation_values['F'] = $this->calculation_values['F1'] = 0.0014 + (0.137 / pow($this->calculation_values['RE'], 0.32)) * 1.12; //Friction Factor


        $this->calculation_values['FG1'] = 2 * $this->calculation_values['F'] * $this->calculation_values['LE'] * $this->calculation_values['VG'] * $this->calculation_values['VG'] / (9.81 * $this->calculation_values['IDG']);
        $this->calculation_values['FG2'] = $this->calculation_values['VG'] * $this->calculation_values['VG'] / (4 * 9.81);
        $this->calculation_values['FG3'] = $this->calculation_values['VG'] * $this->calculation_values['VG'] / (2 * 9.81);
        $this->calculation_values['FG5'] = ($this->calculation_values['FG1'] + $this->calculation_values['FG2'] + $this->calculation_values['FG3']) * $this->calculation_values['TGP']; //FR LOSS IN TUBES



        $this->calculation_values['GFL'] = $this->calculation_values['FGP'] + $this->calculation_values['FG5']; //TOTAL FRICTION LOSS IN HW
        $this->calculation_values['PDG'] = $this->calculation_values['GFL'] + $this->calculation_values['SHG'];



    }

    public function PIPE_SIZE(){
        $vam_base = new VamBaseController();

        $NB = $this->calculation_values['PNB1'];
        $pid_ft3 = $vam_base->PIPE_ID($NB);
        $this->calculation_values['PIDE1'] = $pid_ft3['PID'];
        $this->calculation_values['PODE1'] = $pid_ft3['POD'];
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
       
        $NB = $this->calculation_values['GENNB'];
        $pid_ft3 = $vam_base->PIPE_ID($NB);
        $this->calculation_values['PIDG'] = $pid_ft3['PID'];
        $this->calculation_values['PODG'] = $pid_ft3['POD'];
        $this->calculation_values['GENNB'] = $NB;

    }


    public function PR_DROP_COW(){
        $vam_base = new VamBaseController();

        $this->calculation_values['PIDA'] = ($this->calculation_values['PODA'] - (2 * $this->calculation_values['THPA'])) / 1000;
        $this->calculation_values['APA'] = 3.141593 * $this->calculation_values['PIDA'] * $this->calculation_values['PIDA'] / 4;
        $this->calculation_values['VPA'] = ($this->calculation_values['GCW'] * 4) / (3.141593 * $this->calculation_values['PIDA'] * $this->calculation_values['PIDA'] * 3600);

        //VD1 = $this->calculation_values['GCW'] / (3600 * DW1 * DH1);

        $this->calculation_values['TMA'] = ($this->calculation_values['TCW1H'] + $this->calculation_values['TCW2H'] + $this->calculation_values['TCW1L'] + $this->calculation_values['TCW2L']) / 4.0;

        if ($this->calculation_values['GL'] == 3)
        {
            $this->calculation_values['COGLY_ROWH33'] = $vam_base->PG_ROW($this->calculation_values['TMA'], $this->calculation_values['COGLY']);
            $this->calculation_values['COGLY_VISH33'] = $vam_base->PG_VISCOSITY($this->calculation_values['TMA'], $this->calculation_values['COGLY']) / 1000;
        }
        else
        {
            $this->calculation_values['COGLY_ROWH33'] = $vam_base->EG_ROW($this->calculation_values['TMA'], $this->calculation_values['COGLY']);
            $this->calculation_values['COGLY_VISH33'] = $vam_base->EG_VISCOSITY($this->calculation_values['TMA'], $this->calculation_values['COGLY']) / 1000;
        }

        $this->calculation_values['REPA'] = ($this->calculation_values['PIDA'] * $this->calculation_values['VPA'] * $this->calculation_values['COGLY_ROWH33']) / $this->calculation_values['COGLY_VISH33'];          //REYNOLDS NO IN PIPE1  
        // RED1 = ((ED1NB) * VD1 * $this->calculation_values['COGLY_ROWH33']) / $this->calculation_values['COGLY_VISH33'];            //REYNOLDS NO IN DUCT1

        $this->calculation_values['FFA'] = 1.325 / pow(log((0.0457 / (3.7 * $this->calculation_values['PIDA'] * 1000)) + (5.74 / pow($this->calculation_values['REPA'], 0.9))), 2);     //FRICTION FACTOR CAL
        //  FFD1 = 1.325 / pow(log((0.0457 / (3.7 * (ED1NB) * 1000)) + (5.74 / pow(RED1, 0.9))), 2);

        $this->calculation_values['FLP1'] = ($this->calculation_values['FFA'] * ($this->calculation_values['PSL1'] + $this->calculation_values['PSL2']) / $this->calculation_values['PIDA']) * ($this->calculation_values['VPA'] * $this->calculation_values['VPA'] / (2 * 9.81)) + ((14 * $this->calculation_values['FT']) * ($this->calculation_values['VPA'] * $this->calculation_values['VPA']) / (2 * 9.81));        //FR LOSS IN PIPE                                   
        //   FLD1 = ((FFD1 * DSL) / ED1NB) * (VD1 * VD1 / (2 * 9.81));                                  //FR LOSS IN DUCT
        $this->calculation_values['FLOT'] = (1 + 0.5 + 1 + 0.5) * ($this->calculation_values['VPA'] * $this->calculation_values['VPA'] / (2 * 9.81));                                                                  //EXIT, ENTRY LOSS

        $this->calculation_values['AFLP'] = ($this->calculation_values['FLP1'] + $this->calculation_values['FLOT']) * 1.075;               //7.5% SAFETY

        $this->calculation_values['REH'] = ($this->calculation_values['VAH'] * $this->calculation_values['IDA'] * $this->calculation_values['COGLY_ROWH33']) / $this->calculation_values['COGLY_VISH33'];
        $this->calculation_values['REL'] = ($this->calculation_values['VAL'] * $this->calculation_values['IDA'] * $this->calculation_values['COGLY_ROWH33']) / $this->calculation_values['COGLY_VISH33'];

        if ($this->calculation_values['TU5'] < 2.1 || $this->calculation_values['TU5'] == 6.0 || $this->calculation_values['TU5'] == 5)
        {
            $this->calculation_values['FH'] = (0.0014 + (0.137 / pow($this->calculation_values['REH'], 0.32))) * 1.12;
            $this->calculation_values['FL'] = (0.0014 + (0.137 / pow($this->calculation_values['REL'], 0.32))) * 1.12;
        }            
        else
        {
            $this->calculation_values['FH'] = 0.0014 + (0.125 / pow($this->calculation_values['REH'], 0.32));
            $this->calculation_values['FL'] = 0.0014 + (0.125 / pow($this->calculation_values['REL'], 0.32));
        }

        $this->calculation_values['FA1H'] = 2 * $this->calculation_values['FH'] * $this->calculation_values['LE'] * $this->calculation_values['VAH'] * $this->calculation_values['VAH'] / (9.81 * $this->calculation_values['IDA']);
        $this->calculation_values['FA2H'] = $this->calculation_values['VAH'] * $this->calculation_values['VAH'] / (4 * 9.81);
        $this->calculation_values['FA3H'] = $this->calculation_values['VAH'] * $this->calculation_values['VAH'] / (2 * 9.81);
        $this->calculation_values['FA4H'] = ($this->calculation_values['FA1H'] + $this->calculation_values['FA2H'] + $this->calculation_values['FA3H']) * $this->calculation_values['TAPH'];                 //FRICTION LOSS IN ABSH TUBES

        $this->calculation_values['FA1L'] = 2 * $this->calculation_values['FL'] * $this->calculation_values['LE'] * $this->calculation_values['VAL'] * $this->calculation_values['VAL'] / (9.81 * $this->calculation_values['IDA']);
        $this->calculation_values['FA2L'] = $this->calculation_values['VAL'] * $this->calculation_values['VAL'] / (4 * 9.81);
        $this->calculation_values['FA3L'] = $this->calculation_values['VAL'] * $this->calculation_values['VAL'] / (2 * 9.81);
        $this->calculation_values['FA4L'] = ($this->calculation_values['FA1L'] + $this->calculation_values['FA2L'] + $this->calculation_values['FA3L']) * $this->calculation_values['TAPL'];                 //FRICTION LOSS IN ABSL TUBES

        if ($this->calculation_values['TAP'] == 1)
        {
            $this->calculation_values['FLA'] = $this->calculation_values['FA4H'] + $this->calculation_values['AFLP'];      //PARAFLOW WILL HAVE ONE ENTRY, ONE EXIT, ONE TUBE FRICTION LOSS
        }
        else
        {
            $this->calculation_values['FLA'] = $this->calculation_values['FA4H'] + $this->calculation_values['FA4L'] + $this->calculation_values['AFLP'];
        }
        $this->calculation_values['TMC'] = ($this->calculation_values['TCW3'] + $this->calculation_values['TCW4']) / 2.0;

        if ($this->calculation_values['GL'] == 3)
        {
            $this->calculation_values['COGLY_ROWH33'] = $vam_base->PG_ROW($this->calculation_values['TMC'], $this->calculation_values['COGLY']); ;
            $this->calculation_values['COGLY_VISH33'] = $vam_base->PG_VISCOSITY($this->calculation_values['TMC'], $this->calculation_values['COGLY']) / 1000; ;
        }
        else
        {
            $this->calculation_values['COGLY_ROWH33'] = $vam_base->EG_ROW($this->calculation_values['TMC'], $this->calculation_values['COGLY']);
            $this->calculation_values['COGLY_VISH33'] = $vam_base->EG_VISCOSITY($this->calculation_values['TMC'], $this->calculation_values['COGLY']) / 1000;
        }
        $this->calculation_values['RE1'] = ($this->calculation_values['VC'] * $this->calculation_values['IDC'] * $this->calculation_values['COGLY_ROWH33']) / $this->calculation_values['COGLY_VISH33'];


        if ($this->calculation_values['TV5'] < 2.1 || $this->calculation_values['TV5'] == 3 || $this->calculation_values['TV5'] == 4)
        {
            $this->calculation_values['F'] = (0.0014 + (0.137 / pow($this->calculation_values['RE1'], 0.32))) * 1.12;
        }
        else
        {
            $this->calculation_values['F'] = 0.0014 + (0.125 / pow($this->calculation_values['RE1'], 0.32));
        }

        $this->calculation_values['FC1'] = 2 * $this->calculation_values['F'] * $this->calculation_values['LE'] * $this->calculation_values['VC'] * $this->calculation_values['VC'] / (9.81 * $this->calculation_values['IDC']);
        $this->calculation_values['FC2'] = $this->calculation_values['VC'] * $this->calculation_values['VC'] / (4 * 9.81);
        $this->calculation_values['FC3'] = $this->calculation_values['VC'] * $this->calculation_values['VC'] / (2 * 9.81);
        $this->calculation_values['FC4'] = ($this->calculation_values['FC1'] + $this->calculation_values['FC2'] + $this->calculation_values['FC3']) * $this->calculation_values['TCP'];                      //FRICTION LOSS IN CONDENSER TUBES
        $this->calculation_values['FLC'] = $this->calculation_values['FC4'];

        $this->calculation_values['PDA'] = $this->calculation_values['FLA'] + $this->calculation_values['SHA'] + $this->calculation_values['FC4'];
    }

    public function CONVERGENCE(){
        $j = 0;
        $CC = array();
        $CC[0][0] = $this->calculation_values['GCHW'] * $this->calculation_values['CHGLY_ROW12'] * $this->calculation_values['CHGLY_SPHT12'] * ($this->calculation_values['TCHW1H'] - $this->calculation_values['TCHW2H']) / 4187;                //EVAPORATORH
        $CC[1][0] = $this->calculation_values['UEVAH'] * $this->calculation_values['AEVAH'] * $this->calculation_values['LMTDEVAH'];
        $CC[2][0] = ($this->calculation_values['GREFH'] * ($this->calculation_values['J1H'] - $this->calculation_values['I3'])) - ($this->calculation_values['GREFL'] * ($this->calculation_values['I3'] - $this->calculation_values['I1H']));

        $CC[0][1] = $this->calculation_values['GCHW'] * $this->calculation_values['CHGLY_ROW12'] * $this->calculation_values['CHGLY_SPHT12'] * ($this->calculation_values['TCHW1L'] - $this->calculation_values['TCHW2L']) / 4187;                //EVAPORATORL
        $CC[1][1] = $this->calculation_values['UEVAL'] * $this->calculation_values['AEVAL'] * $this->calculation_values['LMTDEVAL'];
        $CC[2][1] = $this->calculation_values['GREFL'] * ($this->calculation_values['J1L'] - $this->calculation_values['I1H']);

        $CC[0][2] = $this->calculation_values['GCWAH'] * $this->calculation_values['COGLY_ROWH1'] * (($this->calculation_values['TCW2H'] * $this->calculation_values['COGLY_SPHT2H']) - ($this->calculation_values['TCW1H'] * $this->calculation_values['COGLY_SPHT1'])) / 4187; //ABSORBERH
        $CC[1][2] = $this->calculation_values['UABSH'] * $this->calculation_values['AABSH'] * $this->calculation_values['LMTDABSH'];
        $CC[2][2] = $this->calculation_values['GREFH'] * $this->calculation_values['J1H'] + $this->calculation_values['GCONCH'] * $this->calculation_values['I2L'] - $this->calculation_values['GDIL'] * $this->calculation_values['I2'];

        $CC[0][3] = $this->calculation_values['GCWAL'] * $this->calculation_values['COGLY_ROWH1'] * (($this->calculation_values['TCW2L'] * $this->calculation_values['COGLY_SPHT2L']) - ($this->calculation_values['TCW1L'] * $this->calculation_values['COGLY_SPHT1L'])) / 4187;  //ABSORBERL
        $CC[1][3] = $this->calculation_values['UABSL'] * $this->calculation_values['AABSL'] * $this->calculation_values['LMTDABSL'];
        $CC[2][3] = $this->calculation_values['GCONC'] * $this->calculation_values['I8'] + $this->calculation_values['GREFL'] * $this->calculation_values['J1L'] - $this->calculation_values['GDILL'] * $this->calculation_values['I2L'];

        $CC[0][4] = $this->calculation_values['GCWC'] * $this->calculation_values['COGLY_ROWH1'] * ($this->calculation_values['TCW4'] * $this->calculation_values['COGLY_SPHT4'] - $this->calculation_values['TCW3'] * $this->calculation_values['COGLY_SPHT3']) / 4187;       // CONDENSER
        $CC[1][4] = $this->calculation_values['UCON'] * $this->calculation_values['ACON'] * $this->calculation_values['LMTDCON'];
        $CC[2][4] = $this->calculation_values['GREF'] * ($this->calculation_values['J4'] - (100 + $this->calculation_values['T3']));

        $CC[0][5] = $this->calculation_values['GHW'] * $this->calculation_values['CHWGLY_ROW11'] * ($this->calculation_values['THW1'] - $this->calculation_values['THW2']) * ($this->calculation_values['CHWGLY_SPHT11'] + $this->calculation_values['CHWGLY_SPHT1H']) * 0.5 / 4.187;       // GENERATOR
        $CC[1][5] = $this->calculation_values['UGEN'] * $this->calculation_values['AGEN'] * $this->calculation_values['LMTDGEN'];
        $CC[2][5] = ($this->calculation_values['GCONC'] * $this->calculation_values['I4']) + ($this->calculation_values['GREF'] * $this->calculation_values['J4']) - ($this->calculation_values['GDIL'] * $this->calculation_values['I7']);

        $CC[0][6] = $this->calculation_values['GCONC'] * ($this->calculation_values['I4'] - $this->calculation_values['I8']);                   //LTHE
        $CC[1][6] = $this->calculation_values['UHE'] * $this->calculation_values['AHE'] * $this->calculation_values['LMTDHE'];
        $CC[2][6] = $this->calculation_values['GDIL'] * ($this->calculation_values['I7'] - $this->calculation_values['I2']);


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

        if (($this->calculation_values['QEVA'] + $this->calculation_values['QGEN']) <= ($this->calculation_values['QABSH'] + $this->calculation_values['QABSL'] + $this->calculation_values['QREFCON']))
        {
            $this->calculation_values['MIN'] = ($this->calculation_values['QEVA'] + $this->calculation_values['QGEN']); $this->calculation_values['MAX'] = ($this->calculation_values['QABSH'] + $this->calculation_values['QABSL'] + $this->calculation_values['QREFCON']);
        }
        else
        {
            $this->calculation_values['MIN'] = ($this->calculation_values['QABSH'] + $this->calculation_values['QABSL'] + $this->calculation_values['QREFCON']); $this->calculation_values['MAX'] = ($this->calculation_values['QEVA'] + $this->calculation_values['QGEN']);
        }
        $this->calculation_values['ERROR'] = ($this->calculation_values['MAX'] - $this->calculation_values['MIN']) / $this->calculation_values['MAX'] * 100.0;
        $this->calculation_values['HEATIN'] = ($this->calculation_values['QGEN']) + ($this->calculation_values['TON'] * 3024);

        $this->calculation_values['HEATOUT'] = $this->calculation_values['GCWAH'] * $this->calculation_values['COGLY_ROWH1'] * (($this->calculation_values['TCW2H'] * $this->calculation_values['COGLY_SPHT2H']) - ($this->calculation_values['TCW1H'] * $this->calculation_values['COGLY_SPHT1'])) / 4187 + $this->calculation_values['GCWAL'] * $this->calculation_values['COGLY_ROWH1'] * (($this->calculation_values['TCW2L'] * $this->calculation_values['COGLY_SPHT2L']) - ($this->calculation_values['TCW1L'] * $this->calculation_values['COGLY_SPHT1L'])) / 4187 + $this->calculation_values['GCWC'] * $this->calculation_values['COGLY_ROWH1'] * (($this->calculation_values['TCW4'] * $this->calculation_values['COGLY_SPHT4']) - ($this->calculation_values['TCW3'] * $this->calculation_values['COGLY_SPHT3'])) / 4187;
        //$this->calculation_values['HEATOUT']=(GCW*$this->calculation_values['COGLY_ROWH1']*(TCW2*COGLY_SPHT2-TCW1*$this->calculation_values['COGLY_SPHT1'])/4187)+($this->calculation_values['GCWC']*$this->calculation_values['COGLY_ROWH1']*($this->calculation_values['TCW4']*$this->calculation_values['COGLY_SPHT4']-$this->calculation_values['TCW3']*$this->calculation_values['COGLY_SPHT3'])/4187);
        $this->calculation_values['HBERROR'] = ($this->calculation_values['HEATIN'] - $this->calculation_values['HEATOUT']) / $this->calculation_values['HEATIN'] * 100;

    }

    public function CONCHECK(){
        $this->LMTDCHECK();
        $this->HCAP();
        //UPORTION();

        $this->CONCHECK1();

        if (!$this->LMTDCHECK() || abs($this->calculation_values['HBERROR']) > 1)
        {
            $this->calculation_values['Notes'] = $this->notes['NOTES_ERROR'];
            return false;
        }
        else
        {
            if ( $this->calculation_values['TON'] > $this->calculation_values['HIGHCAP'])
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
                    if ($this->calculation_values['LMTDGEN'] > $this->calculation_values['LMTDGENA'])
                    {
                        $this->calculation_values['Notes'] = $this->notes['NOTES_HW_TEMP_LESS'];
                        return false;
                    }
                      else
                        {
                            if ($this->calculation_values['TGP']==2 && $this->calculation_values['VG'] > 2.78)
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
                                    if (($this->calculation_values['TCHW12'] >= 3.5 && $this->calculation_values['T1'] < 1.499) || ($this->calculation_values['TCHW12'] < 3.5 && $this->calculation_values['T1'] < (-2.499)))
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

        return true;
    }

    public function LMTDCHECK(){

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
        else if (!isset($this->calculation_values['LMTDCON']) || $this->calculation_values['LMTDCON'] < 0)
        {
            return false;
        }
        else if (!isset($this->calculation_values['LMTDHE']) || $this->calculation_values['LMTDHE'] < 0)
        {
            return false;
        }
        else if (!isset($this->calculation_values['LMTDGEN']) || $this->calculation_values['LMTDGEN'] < 0)
        {
            return false;
        }
        else if (!isset($this->calculation_values['LMTDGENA']) || $this->calculation_values['LMTDGENA'] < 0)
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    public function HCAP(){
        $this->calculation_values['HIGHCAP'] = 0;

        if ($this->calculation_values['MODEL'] < 300)
        {
            $this->calculation_values['HIGHCAP'] = $this->calculation_values['MODEL'] * 1.4;
        }
        else
        {
            $this->calculation_values['HIGHCAP'] = 0;
        }
    }


    public function RESULT_CALCULATE(){
        $notes = array();
        $this->calculation_values['Notes'] = "";

        if (!$this->CONCHECK())
        {
            $this->calculation_values['Result'] = "FAILED";
            return false;
        }

        $this->HEATBALANCE();

        $this->calculation_values['HeatInput'] = $this->calculation_values['GHOT'] * $this->calculation_values['ROWH1'] * ($this->calculation_values['CPH2'] + $this->calculation_values['CPH3']) * 0.5 * ($this->calculation_values['THW1'] - $this->calculation_values['THW2']) / 4187;
        $this->calculation_values['HeatRejected'] = ($this->calculation_values['TON'] * 3024) + ($this->calculation_values['GHOT'] * $this->calculation_values['ROWH1'] * ($this->calculation_values['CPH2'] + $this->calculation_values['CPH3']) * 0.5 * ($this->calculation_values['THW1'] - $this->calculation_values['THW2']) / 4187);
        $this->calculation_values['COP'] = ($this->calculation_values['TON'] * 3024) / ($this->calculation_values['GHOT'] * $this->calculation_values['ROWH1'] * ($this->calculation_values['CPH2'] + $this->calculation_values['CPH3']) * 0.5 * ($this->calculation_values['THW1'] - $this->calculation_values['THW2']) / 4187);

        $this->calculation_values['CoolingWaterOutTemperature'] = $this->calculation_values['TCWA4'];

        $this->calculation_values['EvaporatorPasses'] = $this->calculation_values['TEP'] . "+" . $this->calculation_values['TEP'];
        if ( $this->calculation_values['TAP'] == 1)
        {
             $this->calculation_values['AbsorberPasses'] =  $this->calculation_values['TAPH']. ",".  $this->calculation_values['TAPL'];
        }
        else
        {
             $this->calculation_values['AbsorberPasses'] =  $this->calculation_values['TAPH']. "+" . $this->calculation_values['TAPL'];
        }
        $this->calculation_values['CondenserPasses'] =  $this->calculation_values['TCP'];
        $this->calculation_values['GeneratorPasses'] =  $this->calculation_values['TGP'];

        $this->calculation_values['ChilledFrictionLoss'] =  $this->calculation_values['FLE'];
        $this->calculation_values['CoolingFrictionLoss'] = (( $this->calculation_values['FLA'] +  $this->calculation_values['FC4']));
        $this->calculation_values['ChilledPressureDrop'] =  $this->calculation_values['PDE'];
        $this->calculation_values['CoolingPressureDrop'] =  $this->calculation_values['PDA'];
        $this->calculation_values['ChilledWaterFlow'] =  $this->calculation_values['GCHW'];
        $this->calculation_values['BypassFlow'] =  $this->calculation_values['GCW'] -  $this->calculation_values['GCWC'];

        $this->calculation_values['HotWaterFlow'] = $this->calculation_values['GHOT'];
        $this->calculation_values['HotWaterFrictionLoss'] = $this->calculation_values['GFL'];
        $this->calculation_values['HotWaterPressureDrop'] = $this->calculation_values['PDG'];

        $this->calculation_values['Result'] = "FAILED";

        if ($this->calculation_values['TUU'] == 'ari')
        {
            array_push($notes,$this->notes['NOTES_ARI']);
        }
        if ($this->calculation_values['THW1'] > 99)
        {
            array_push($notes,$this->notes['NOTES_PL_EC_CERTAPP']);
            array_push($notes,$this->notes['NOTES_CONF_WT']);
        }    
        if (($this->calculation_values['P3'] - $this->calculation_values['P1L']) < 40)
        {
            array_push($notes,$this->notes['NOTES_LTHE_PRDROP']);
            $this->calculation_values['HHType'] = "NonStandard";
        }
        if (!$this->calculation_values['isStandard'])
        {
            array_push($notes,$this->notes['NOTES_NSTD_TUBE_METAL']);
        }            
        if ($this->calculation_values['TCHW12'] < 4.49)
        {
            array_push($notes,$this->notes['NOTES_COST_COW_SOV']);
            array_push($notes,$this->notes['NOTES_NONSTD_XSTK_MC']);
        }            
        //if (CW == 2)
        //{
        //    notes.Add(LocalizedNote(NOTES_COWIL_COND));
        //    notes.Add(LocalizedNote(NOTES_NONSTD_GA));            }
        //else
        //{
        //    notes.Add(LocalizedNote(NOTES_COWIL_ABS));                
        //}         

        array_push($notes,$this->notes['NOTES_INSUL']);
        array_push($notes,$this->notes['NOTES_NON_INSUL']);
        array_push($notes,$this->notes['NOTES_ROOM_TEMP']);
        array_push($notes,$this->notes['NOTES_CUSTOM']);
        //SK 1/3/07

        if ($this->calculation_values['THW1'] > 105)
        {
            array_push($notes,$this->notes['NOTES_NONSTD_GEN_MET']);
        }

        if ($this->calculation_values['LMTDGEN'] < ($this->calculation_values['LMTDGENA'] - 2))
        {
            if ($this->calculation_values['XCONC'] < ($this->calculation_values['KM'] - 0.8))
            {
                $this->calculation_values['Result'] = "OverDesigned";
            }
            if ($this->calculation_values['XCONC'] < ($this->calculation_values['KM'] - 0.4) && $this->calculation_values['XCONC'] > ($this->calculation_values['KM'] - 0.8))
            {

                array_push($notes,$this->notes['NOTES_RED_COW']);
                $this->calculation_values['Result'] = "GoodSelection";
            }
            if ($this->calculation_values['XCONC'] < $this->calculation_values['KM'] && $this->calculation_values['XCONC'] > ($this->calculation_values['KM'] - 0.4))
            {
                $this->calculation_values['Result'] = "Optimal";
            }
        }               
        else
        {
            if ($this->calculation_values['LMTDGEN'] < ($this->calculation_values['LMTDGENA'] - 1))
            {
                if ($this->calculation_values['XCONC'] < ($this->calculation_values['KM'] - 0.8))
                {
                    array_push($notes,$this->notes['NOTES_RED_COW']);
                    $this->calculation_values['Result'] = "GoodSelection";
                }
                if ($this->calculation_values['XCONC'] < $this->calculation_values['KM'] && $this->calculation_values['XCONC'] > ($this->calculation_values['KM'] - 0.8))
                {
                    $this->calculation_values['Result'] = "Optimal";
                }
            }
           else
            {
                $this->calculation_values['Result'] = "Optimal";
            }
        }


        $this->calculation_values['notes'] = $notes;



    }


    public function HEATBALANCE(){
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
            
            if ($this->calculation_values['GL'] == 2)
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

            $this->calculation_values['QCWR'] = $this->calculation_values['GCW'] * $this->calculation_values['COGLY_ROWH'] * ($this->calculation_values['COGLY_SPHTA4'] + $this->calculation_values['COGLY_SPHT11']) * 0.5 * ($this->calculation_values['TCWA4'] - $this->calculation_values['TCW11']) / 4187;
            if ($this->calculation_values['GL'] == 2)
            {
                $this->calculation_values['CPH3'] = $vam_base->EG_SPHT($this->calculation_values['THW2'], $this->calculation_values['HWGLY']) * 1000;
                $this->calculation_values['CPH2'] = $vam_base->EG_SPHT($this->calculation_values['THW1'], $this->calculation_values['HWGLY']) * 1000;
                $this->calculation_values['ROWH1'] = $vam_base->EG_ROW($this->calculation_values['THW1'], $this->calculation_values['HWGLY']);
            }
            else
            {
                $this->calculation_values['CPH3'] = $vam_base->PG_SPHT($this->calculation_values['THW2'], $this->calculation_values['HWGLY']) * 1000;
                $this->calculation_values['CPH2'] = $vam_base->PG_SPHT($this->calculation_values['THW1'], $this->calculation_values['HWGLY']) * 1000;
                $this->calculation_values['ROWH1'] = $vam_base->PG_ROW($this->calculation_values['THW1'], $this->calculation_values['HWGLY']);
            }
            $this->calculation_values['QINPUT'] = ($this->calculation_values['TON'] * 3024) + ($this->calculation_values['GHOT'] * $this->calculation_values['ROWH1'] * ($this->calculation_values['CPH2'] + $this->calculation_values['CPH3']) * 0.5 * ($this->calculation_values['THW1'] - $this->calculation_values['THW2']) / 4187);
            $herr[$ii] = ($this->calculation_values['QINPUT'] - $this->calculation_values['QCWR']) * 100 / $this->calculation_values['QINPUT'];
            $ii++;
        }  

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

                break;  
            
            case "EVAPORATOR_TUBE_TYPE":
                             
                $this->model_values['evaporator_thickness_change'] = true;
                if (floatval($this->model_values['chilled_water_out']) < 3.5 && floatval($this->model_values['chilled_water_out']) > 0.99 && floatval($this->model_values['glycol_chilled_water']) == 0)
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
                    $range_calculation = $this->RANGECAL();
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
                    $range_calculation = $this->RANGECAL();
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
                                        ->where('min_model','<=',(int)$this->model_values['model_number'])->where('max_model','>=',(int)$this->model_values['model_number'])->first();

            $this->model_values['evaporator_material_value'] = $chiller_metallurgy_options->eva_default_value;
            // $this->model_values['evaporator_thickness'] = $this->default_model_values['evaporator_thickness'];
            $this->model_values['absorber_material_value'] = $chiller_metallurgy_options->abs_default_value;
            // $this->model_values['absorber_thickness'] = $this->default_model_values['absorber_thickness'];
            $this->model_values['condenser_material_value'] = $chiller_metallurgy_options->con_default_value;
            // $this->model_values['condenser_thickness'] = $this->default_model_values['condenser_thickness'];
        }

    }


    public function RANGECAL(){
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
        $this->PIPE_SIZE();
        $PIDA = floatval($this->calculation_values['PIDA']);
        

        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$this->model_code)
                                        ->where('min_model','<=',$model_number)->where('max_model','>',$model_number)->first();

        $chiller_options = $chiller_metallurgy_options->chillerOptions;
        
        // $evaporator_option = $chiller_options->where('type', 'eva')->where('value',$this->model_values['evaporator_material_value'])->first();
        $absorber_option = $chiller_options->where('type', 'abs')->where('value',$this->calculation_values['TU5'])->first();
        $condenser_option = $chiller_options->where('type', 'con')->where('value',$this->calculation_values['TV5'])->first();


        $TCP = 2;
        $VAMIN = $absorber_option->metallurgy->abs_min_velocity;          
        $VAMAX = $absorber_option->metallurgy->abs_max_velocity;
        $VCMIN = 1.0;
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


        // $PIDA = ($PODA - (2 * $THPA)) / 1000;
        $APA = 3.141593 * $PIDA * $PIDA / 4;

        if ($model_number < 201)  //change
        {
            $GCWPMAX = $APA * 4 * 3600;
        }

        

        if ($FMAX1 > $GCWPMAX)
        {
            $FMAX1 = $GCWPMAX;
        }

        if ($model_number < 360 && $GCWCMAX < $FMAX1)
        {
            $FMAX1 = $GCWCMAX;
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

    public function RANGECAL1($model_number,$chilled_water_out,$capacity)
    {
        $TCHW12 = $chilled_water_out;
        $TON = $capacity;

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
        

        return $GCWMIN1;
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

        


        $this->calculation_values['GCW'] = $this->model_values['cooling_water_flow'];
        $this->calculation_values['TCW11'] = $this->model_values['cooling_water_in'];
        $this->calculation_values['GL'] = $this->model_values['glycol_selected']; 
        $this->calculation_values['CHGLY'] = $this->model_values['glycol_chilled_water']; 
        $this->calculation_values['COGLY'] = $this->model_values['glycol_cooling_water'];

        $this->calculation_values['HWGLY'] = $this->model_values['glycol_hot_water']; 

        $this->calculation_values['TCHW11'] = $this->model_values['chilled_water_in']; 
        $this->calculation_values['TCHW12'] = $this->model_values['chilled_water_out'];
        $this->calculation_values['THW1'] = $this->model_values['hot_water_in'];
        $this->calculation_values['GHOT'] = $this->model_values['hot_water_flow'];
        $this->calculation_values['GHW'] = $this->calculation_values['GHOT'];

        if($this->calculation_values['THW1'] > 105){
            $this->calculation_values['TG2'] = 2;
            $this->calculation_values['TG3'] = 0.8;

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
        $this->calculation_values['TCHW2L'] = $this->calculation_values['TCHW12'];

        // $this->calculation_values['EVAPDROP'] = 0;
        
        // Extra Parametes Testing

        if($this->calculation_values['region_type'] == 1){
            $this->calculation_values['SS_FACTOR'] = 1;
        }
        else{
            $this->calculation_values['SS_FACTOR'] = 0.96;
        }
        

        $this->DATA();

        $this->THICKNESS();
    }

    private function DATA(){

        $this->calculation_values['AEVAH'] = $this->calculation_values['AEVAL'] = $this->calculation_values['AEVA'] * 0.5;
        $this->calculation_values['AABSH'] = $this->calculation_values['AABSL'] = $this->calculation_values['AABS'] * 0.5;

        $this->calculation_values['KEVA1'] = 1 / ((1 / $this->calculation_values['KEVA']) - (0.65 / 340000));

        if ($this->calculation_values['TU2'] == 1 || $this->calculation_values['TU2'] == 9)
            $this->calculation_values['KEVA'] = (1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 37000))) * 0.95;
        if ($this->calculation_values['TU2'] == 2 || $this->calculation_values['TU2'] == 6)
            $this->calculation_values['KEVA'] = 1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 340000.0));
        if ($this->calculation_values['TU2'] == 4)
            $this->calculation_values['KEVA'] = (1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 21000.0))) * $this->calculation_values['SS_FACTOR'];
        if ($this->calculation_values['TU2'] == 7 || $this->calculation_values['TU2'] == 8)
            $this->calculation_values['KEVA'] = (1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 15000.0))) * 0.93;
        if ($this->calculation_values['TU2'] == 3)
            $this->calculation_values['KEVA'] = 1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 21000.0)) * $this->calculation_values['SS_FACTOR'];              //Changed to $this->calculation_values['KEVA1'] from 1600 on 06/11/2017 as tube metallurgy is changed
        if ($this->calculation_values['TU2'] == 5)
            $this->calculation_values['KEVA'] = 1 / ((1 / 1600.0) + ($this->calculation_values['TU3'] / 15000.0));

        /********* DETERMINATION OF $this->calculation_values['KABS'] FOR NONSTD. SELECTION****/
        $this->calculation_values['KABS1'] = 1 / ((1 / $this->calculation_values['KABS']) - (0.65 / 340000));
        if ($this->calculation_values['TU5'] == 1)
        {
            $this->calculation_values['KABS'] = 1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 37000)) * 0.95;
        }
        else
        {
            if ($this->calculation_values['TU5'] == 2)
                $this->calculation_values['KABS'] = 1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 340000));
            if ($this->calculation_values['TU5'] == 5)
                $this->calculation_values['KABS'] = (1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 15000))) * 0.93;
            if ($this->calculation_values['TU5'] == 6)
                $this->calculation_values['KABS'] = (1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 21000))) * 0.93;
            else
            {
                $this->calculation_values['KABS1'] = 1240;
                //if ($this->calculation_values['TU5'] == 3)
                //    $this->calculation_values['KABS'] = 1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 37000));
                //if ($this->calculation_values['TU5'] == 4)
                //    $this->calculation_values['KABS'] = 1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 340000));
                //if ($this->calculation_values['TU5'] == 5)
                //    $this->calculation_values['KABS'] = 1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 21000));
                if ($this->calculation_values['TU5'] == 7)
                    $this->calculation_values['KABS'] = 1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 15000));
            }
        }

        /********** DETERMINATION OF $this->calculation_values['KCON'] IN NONSTD. SELECTION*******/
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
            if ($this->calculation_values['TV5'] == 5)
                $this->calculation_values['KCON'] = 1 / ((1 / $this->calculation_values['KCON1']) + ($this->calculation_values['TV6'] / 15000));
        }
        if ($this->calculation_values['TV5'] == 0)
        {
            $this->calculation_values['KCON'] = 3000 * 2;
        }
    }

    private function THICKNESS(){
        //EVA TUBE
  
        $this->calculation_values['THE'] = $this->calculation_values['TU3'];

        if ($this->calculation_values['TU2'] < 2.1 || $this->calculation_values['TU2'] == 4 || $this->calculation_values['TU2'] == 8)
        {
            $this->calculation_values['IDE'] = $this->calculation_values['ODE'] - (2.0 * ($this->calculation_values['THE'] + 0.1) / 1000);
        }
        else
        {
            $this->calculation_values['IDE'] = $this->calculation_values['ODE'] - (2.0 * $this->calculation_values['THE'] / 1000);
        }

        //ABS TUBE

        $this->calculation_values['THA'] = $this->calculation_values['TU6'];
        if ($this->calculation_values['TU5'] < 2.1 || $this->calculation_values['TU5'] == 6.0 || $this->calculation_values['TU5'] == 5)
        {
            $this->calculation_values['IDA'] = $this->calculation_values['ODA'] - (2.0 * ($this->calculation_values['THA'] + 0.1) / 1000);
        }
        else
        {
            $this->calculation_values['IDA'] = $this->calculation_values['ODA'] - (2.0 * $this->calculation_values['THA'] / 1000);
        }

        //CON TUBE

        $this->calculation_values['THC'] = $this->calculation_values['TV6'];

        if ($this->calculation_values['TV5'] == 1 || $this->calculation_values['TV5'] == 3 || $this->calculation_values['TV5'] == 4)
        {
            $this->calculation_values['IDC'] = $this->calculation_values['ODC'] - (2.0 * ($this->calculation_values['THC'] + 0.1) / 1000);
        }
        else
        {
            $this->calculation_values['IDC'] = $this->calculation_values['ODC'] - (2.0 * $this->calculation_values['THC'] / 1000);
        }

        //GEN TUBE
        $this->calculation_values['THG'] = $this->calculation_values['TG3'] + 0.1;
        $this->calculation_values['IDG'] = $this->calculation_values['ODG'] - (2.0 * $this->calculation_values['THG'] / 1000);
    }

    public function loadSpecSheetData(){
        $model_number = floatval($this->calculation_values['MODEL']);
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
            case 35:
                if ($this->calculation_values['TCHW2L'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC L1 M1";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC L1 M1";
                }

                break;
            case 45:
                if ($this->calculation_values['TCHW2L'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC L1 M2";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC L1 M2";
                }
                
                break;    

            case 60:
                if ($this->calculation_values['TCHW2L'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC L1 N1";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC L1 N1";
                }
                
                break; 
            case 70:
                if ($this->calculation_values['TCHW2L'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC L1 N2";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC L1 N2";
                }

                break;
            case 90:
                if ($this->calculation_values['TCHW2L'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC L1 N3";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC L1 N3";
                }    
                break;
            case 110:
                if ($this->calculation_values['TCHW2L'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC L1 N4";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC L1 N4";
                }    
                break;
            case 135: 
                if ($this->calculation_values['TCHW2L'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC L1 P1";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC L1 P1";
                }
                break;
            case 160:
                if ($this->calculation_values['TCHW2L'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC L1 P2";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC L1 P2";
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
            'SL8',
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
            'KEVA',
            'PNB1',
            'PSL2',
            'PSLI',
            'PSLO',
            'TNAA',
            'TNEV',
            'TNG',
            'UGEN',
            'AHE',
            'UHE',
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
            'AbsorbentPumpMotorKW',
            'AbsorbentPumpMotorAmp',
            'RefrigerantPumpMotorKW',
            'RefrigerantPumpMotorAmp',
            'PurgePumpMotorKW',
            'PurgePumpMotorAmp',
            'A_SFACTOR',
            'B_SFACTOR',
            'C_SFACTOR',
            'USA_AbsorbentPumpMotorKW',
            'USA_AbsorbentPumpMotorAmp',
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
            'TCPMAX',
            'min_chilled_water_out',
            'MOD1'
            
        ]);

        return $calculation_values;
    }


    public function testingL1Calculation($datas){
        
        $this->model_values = $datas;

        $vam_base = new VamBaseController();
        $this->notes = $vam_base->getNotesError();

        $this->model_values['metallurgy_standard'] = $vam_base->getBoolean($this->model_values['metallurgy_standard']);
        $this->RANGECAL();


        $this->calculation_values['msg'] = '';
       // try {
           $this->WATERPROP();

           $velocity_status = $this->VELOCITY();

       // } 
       // catch (\Exception $e) {
       //      $this->calculation_values['msg'] = $this->notes['NOTES_ERROR'];
          
       // }
       

       if(isset($velocity_status['status']) && !$velocity_status['status']){
            $this->calculation_values['msg'] = $velocity_status['msg'];
       }



       // try {
           $this->CALCULATIONS();

           $this->CONVERGENCE();

           $this->RESULT_CALCULATE();
       
           $this->loadSpecSheetData();
       // }
       // catch (\Exception $e) {

       //      $this->calculation_values['msg'] = $this->notes['NOTES_ERROR'];
          
       // }

        // Log::info($this->calculation_values);   
        return $this->calculation_values;
        // return response()->json(['status'=>true,'msg'=>'Ajax Datas','calculation_values'=>$this->calculation_values]);

    
    }
}
