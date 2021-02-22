<?php

namespace App\Http\Controllers;


use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\VamBaseController;
use App\Http\Controllers\UnitConversionController;
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

class DoubleSteamController extends Controller
{
    
	private $model_values;
	private $default_model_values;
	private $model_code = "D_S2";
	private $calculation_values;
    private $notes;
    private $changed_value;

    
    public function getDoubleEffectS2(){

        // $min_model_value = ChillerCalculationValue::where('code',$this->model_code)->pluck('min_model');

        $chiller_form_values = $this->getFormValues(60);


    	$chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')
                                        ->where('code',$this->model_code)
    									->where('min_model','<=',60)->where('max_model','>',60)->first();

                              
    	$chiller_options = $chiller_metallurgy_options->chillerOptions;
    	
    	$evaporator_options = $chiller_options->where('type', 'eva');
    	$absorber_options = $chiller_options->where('type', 'abs');
    	$condenser_options = $chiller_options->where('type', 'con');

        $unit_set_id = Auth::user()->unit_set_id;
        $unit_set = UnitSet::find($unit_set_id);

        $region_type = Auth::user()->region_type;

        if($region_type == 2)
            $region_name = Auth::user()->region->name;
        else
            $region_name = '';


        $standard_values = array('evaporator_thickness' => 0,'absorber_thickness' => 0,'condenser_thickness' => 0,'evaporator_thickness_min_range' => 0,'evaporator_thickness_max_range' => 0,'absorber_thickness_min_range' => 0,'absorber_thickness_max_range' => 0,'condenser_thickness_min_range' => 0,'condenser_thickness_max_range' => 0,'region_name'=>$region_name,'region_type'=>$region_type);

        $default_values = collect($chiller_form_values)->union($standard_values);

        


        $unit_conversions = new UnitConversionController;
        
        $converted_values = $unit_conversions->formUnitConversion($default_values,$this->model_code);

        $min_chilled_water_out = Auth::user()->min_chilled_water_out;
        if($min_chilled_water_out > $converted_values['min_chilled_water_out'])
            $converted_values['min_chilled_water_out'] = $min_chilled_water_out;
  
        $regions = Region::all();
    	// return $evaporator_options;


        $vam_base = new VamBaseController();
        $language_datas = $vam_base->getLanguageDatas();
        $units_data = $vam_base->getUnitsData();

        // return $language_datas;

		return view('double_steam_s2')->with('default_values',$converted_values)
                                        ->with('unit_set',$unit_set)
                                        ->with('units_data',$units_data)
										->with('evaporator_options',$evaporator_options)
										->with('absorber_options',$absorber_options)
										->with('condenser_options',$condenser_options)
                                        ->with('chiller_metallurgy_options',$chiller_metallurgy_options) 
										->with('language_datas',$language_datas) 
                                        ->with('regions',$regions);
	}

	public function calculateDoubleEffectS2(Request $request){
		return $request->all();
	}

	public function postAjaxDoubleEffectS2(Request $request){

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

        $converted_values = $unit_conversions->formUnitConversion($this->model_values,$this->model_code);
       

		return response()->json(['status'=>true,'msg'=>'Ajax Datas','model_values'=>$converted_values,'changed_value'=>$this->changed_value]);
	}
    
	public function postDoubleEffectS2(Request $request){

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

		// $validate_attributes = array('CAPACITY','CHILLED_WATER_IN','CHILLED_WATER_OUT','EVAPORATOR_TUBE_TYPE','GLYCOL_TYPE_CHANGED','GLYCOL_CHILLED_WATER','GLYCOL_COOLING_WATER','COOLING_WATER_IN','COOLING_WATER_FLOW','EVAPORATOR_THICKNESS','ABSORBER_THICKNESS','CONDENSER_THICKNESS','FOULING_CHILLED_VALUE','FOULING_COOLING_VALUE','STEAM_PRESSURE');	
		
		// foreach ($validate_attributes as $key => $validate_attribute) {
		// 	$attribute_validator = $this->validateChillerAttribute($validate_attribute);

		// 	if(!$attribute_validator['status'])
		// 		return response()->json(['status'=>false,'msg'=>$attribute_validator['msg'],'input_target'=>strtolower($validate_attribute)]);
		// }									

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
    public function postAjaxDoubleEffectS2Region(Request $request){

        $model_values = $request->input('values');

        $model_number =(int)$model_values['model_number'];
        $chiller_form_values = $this->getFormValues($model_number);

        $unit_conversions = new UnitConversionController;
        // $model_values = $unit_conversions->calculationUnitConversion($model_values);

        $chiller_form_values['region_type'] = $model_values['region_type'];
        if($model_values['region_type'] == 2 || $model_values['region_type'] == 3)
        {
           
            $chiller_form_values['capacity'] =  $chiller_form_values['USA_capacity'];
            $chiller_form_values['chilled_water_in'] =  $chiller_form_values['USA_chilled_water_in'];
            $chiller_form_values['chilled_water_out'] =  $chiller_form_values['USA_chilled_water_out'];
            $chiller_form_values['cooling_water_in'] =  $chiller_form_values['USA_cooling_water_in'];
            $chiller_form_values['cooling_water_flow'] =  $chiller_form_values['USA_cooling_water_flow'];
    
        }
 


        // update user values with model values
        // $region_name = $model_values['region_name'];
        
        $standard_values = array('evaporator_thickness' => 0,'absorber_thickness' => 0,'condenser_thickness' => 0,'evaporator_thickness_min_range' => 0,'evaporator_thickness_max_range' => 0,'absorber_thickness_min_range' => 0,'absorber_thickness_max_range' => 0,'condenser_thickness_min_range' => 0,'condenser_thickness_max_range' => 0,'fouling_chilled_water_value' => 0,'fouling_cooling_water_value' => 0,'evaporator_thickness_change' => 1,'absorber_thickness_change' => 1,'condenser_thickness_change' => 1,'fouling_chilled_water_checked' => 0,'fouling_cooling_water_checked' => 0,'fouling_chilled_water_disabled' => 1,'fouling_cooling_water_disabled' => 1,'fouling_chilled_water_value_disabled' => 1,'fouling_cooling_water_value_disabled' => 1);


        $default_values = collect($chiller_form_values)->union($standard_values);
        $this->model_values = $default_values;
        // $this->castToBoolean();

        $range_calculation = $this->RANGECAL();
        
        $converted_values = $unit_conversions->formUnitConversion($this->model_values,$this->model_code);

        $min_chilled_water_out = Auth::user()->min_chilled_water_out;
        $converted_values['min_chilled_water_out'] = $min_chilled_water_out;

        return response()->json(['status'=>true,'msg'=>'Ajax Datas','model_values'=>$converted_values]);
    }

	public function postResetDoubleEffectS2(Request $request){
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


		$chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$this->model_code)->where('min_model','<=',$model_number)->where('max_model','>',$model_number)->first();
        //$queries = DB::getQueryLog();


		$chiller_options = $chiller_metallurgy_options->chillerOptions;
		
		$evaporator_options = $chiller_options->where('type', 'eva');
		$absorber_options = $chiller_options->where('type', 'abs');
		$condenser_options = $chiller_options->where('type', 'con');


        $unit_set_id = Auth::user()->unit_set_id;
        $unit_set = UnitSet::find($unit_set_id);
        $standard_values = array('evaporator_thickness' => 0,'absorber_thickness' => 0,'condenser_thickness' => 0,'evaporator_thickness_min_range' => 0,'evaporator_thickness_max_range' => 0,'absorber_thickness_min_range' => 0,'absorber_thickness_max_range' => 0,'condenser_thickness_min_range' => 0,'condenser_thickness_max_range' => 0,'fouling_chilled_water_value' => 0,'fouling_cooling_water_value' => 0,'evaporator_thickness_change' => 1,'absorber_thickness_change' => 1,'condenser_thickness_change' => 1,'fouling_chilled_water_checked' => 0,'fouling_cooling_water_checked' => 0,'fouling_chilled_water_disabled' => 1,'fouling_cooling_water_disabled' => 1,'fouling_chilled_water_value_disabled' => 1,'fouling_cooling_water_value_disabled' => 1);

       

       $chiller_form_values = collect($chiller_form_values)->union($standard_values);

            
        $this->model_values = $chiller_form_values;

        $this->castToBoolean();
        $range_calculation = $this->RANGECAL();

        $unit_conversions = new UnitConversionController;
        $converted_values = $unit_conversions->formUnitConversion($this->model_values,$this->model_code);
 
        $min_chilled_water_out = Auth::user()->min_chilled_water_out;
        if($min_chilled_water_out > $converted_values['min_chilled_water_out'])
            $converted_values['min_chilled_water_out'] = $min_chilled_water_out;
       

		return response()->json(['status'=>true,'msg'=>'Ajax Datas','model_values'=>$converted_values,'evaporator_options'=>$evaporator_options,'absorber_options'=>$absorber_options,'condenser_options'=>$condenser_options,'chiller_metallurgy_options'=>$chiller_metallurgy_options]);

	}
    public function modulNumberDoubleEffectS2(){
        // $model_values = $this->model_values;
        $model_number =(int)$this->model_values['model_number'];

        if(empty($this->model_values['metallurgy_standard'])){
            // $this->model_values['evaporator_material_value']=$model_values['evaporator_material_value'];
            // $this->model_values['absorber_material_value']=$model_values['absorber_material_value'];
            // $this->model_values['condenser_material_value']=$model_values['condenser_material_value'];

            $this->model_values['metallurgy_standard'] = false;
            
        }
        else{
            
            $chiller_metallurgy_options = ChillerMetallurgyOption::where('code',$this->model_code)->where('min_model','<=',$model_number)->where('max_model','>',$model_number)->first();

            $this->model_values['evaporator_material_value']=$chiller_metallurgy_options->eva_default_value;
            $this->model_values['absorber_material_value']=$chiller_metallurgy_options->abs_default_value;
            $this->model_values['condenser_material_value']=$chiller_metallurgy_options->con_default_value;


            $this->model_values['evaporator_thickness_change'] = true;
            $this->model_values['absorber_thickness_change'] = true;
            $this->model_values['condenser_thickness_change'] = true;

           
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
        
        $view = view("report", ['name' => $name,'phone' => $phone,'project' => $project,'calculation_values' => $calculation_values,'evaporator_name' => $evaporator_name,'absorber_name' => $absorber_name,'condenser_name' => $condenser_name,'unit_set' => $unit_set,'units_data' => $units_data,'language_datas' => $language_datas])->render();

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

        $redirect_url = route('download.report', ['user_report_id' => $user_report->id,'type' => $report_type]);
        
        return response()->json(['status'=>true,'msg'=>'Ajax Datas','redirect_url'=>$redirect_url]);
        
    }

    public function downloadReport($user_report_id,$type){

        $user_report = UserReport::find($user_report_id);
        if(!$user_report){
            return response()->json(['status'=>false,'msg'=>'Invalid Report']);
        }

        if($type == 'save_word'){
            $word_download = $this->wordFormat($user_report_id);

            $file_name = "S2-Steam-Fired-Serices-".Auth::user()->id.".docx";
            return response()->download(storage_path($file_name));
        }

        $calculation_values = json_decode($user_report->calculation_values,true);
        
        $name = $user_report->name;
        $project = $user_report->project;
        $phone = $user_report->phone;

        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$this->model_code)
                                        ->where('min_model','<=',$calculation_values['MODEL'])->where('max_model','>',$calculation_values['MODEL'])->first();

        $chiller_options = $chiller_metallurgy_options->chillerOptions;
        
        $evaporator_option = $chiller_options->where('type', 'eva')->where('value',$calculation_values['TU2'])->first();
        $absorber_option = $chiller_options->where('type', 'abs')->where('value',$calculation_values['TU5'])->first();
        $condenser_option = $chiller_options->where('type', 'con')->where('value',$calculation_values['TV5'])->first();

        $evaporator_name = $evaporator_option->metallurgy->display_name;
        $absorber_name = $absorber_option->metallurgy->display_name;
        $condenser_name = $condenser_option->metallurgy->display_name;

        $unit_set_id = Auth::user()->unit_set_id;
        $unit_set = UnitSet::find($unit_set_id);

        $language = $user_report->language;


        $vam_base = new VamBaseController();
        $language_datas = $vam_base->getLanguageDatas();
        $units_data = $vam_base->getUnitsData();




        $pdf = PDF::loadView('reports.report_s2_pdf', ['name' => $name,'phone' => $phone,'project' => $project,'calculation_values' => $calculation_values,'evaporator_name' => $evaporator_name,'absorber_name' => $absorber_name,'condenser_name' => $condenser_name,'unit_set' => $unit_set,'units_data' => $units_data,'language_datas' => $language_datas,'language' => $language]);

        return $pdf->download('s2.pdf');

    }
    
	public function castToBoolean(){
  
		$this->model_values['metallurgy_standard'] = $this->getBoolean($this->model_values['metallurgy_standard']);

		$this->model_values['evaporator_thickness_change'] = $this->getBoolean($this->model_values['evaporator_thickness_change']);
	    $this->model_values['absorber_thickness_change'] = $this->getBoolean($this->model_values['absorber_thickness_change']);
	    $this->model_values['condenser_thickness_change'] = $this->getBoolean($this->model_values['condenser_thickness_change']);
	    $this->model_values['fouling_chilled_water_checked'] = $this->getBoolean($this->model_values['fouling_chilled_water_checked']);
	    $this->model_values['fouling_cooling_water_checked'] = $this->getBoolean($this->model_values['fouling_cooling_water_checked']);
	    $this->model_values['fouling_chilled_water_disabled'] = $this->getBoolean($this->model_values['fouling_chilled_water_disabled']);
	    $this->model_values['fouling_cooling_water_disabled'] = $this->getBoolean($this->model_values['fouling_cooling_water_disabled']);
	    $this->model_values['fouling_chilled_water_value_disabled'] = $this->getBoolean($this->model_values['fouling_chilled_water_value_disabled']);
	    $this->model_values['fouling_cooling_water_value_disabled'] = $this->getBoolean($this->model_values['fouling_cooling_water_value_disabled']);
	}

	public function getBoolean($value){
		
        // if($value == true || $value == "true" || $value == "1" || $value == 1){
        //  return true;
        // }

        // return false;

	    if($value == "true" || $value == "1" || $value == 1 || $value == "TRUE"){
	   		return true;
	   	}
	   	else{
	   		return "0";
	   	}
	}

	public function updateInputs(){

        $model_number = (int)$this->model_values['model_number'];
		$calculation_values = $this->getCalculationValues($model_number);
        //$this->calculation_values = $chiller_data;
        //$this->modulNumberDoubleEffectS2();
		$this->calculation_values = $calculation_values;

        $this->calculation_values['region_type'] = $this->model_values['region_type'];
        $this->calculation_values['model_name'] = $this->model_values['model_name'];
        // $chiller_calculation_values = ChillerCalculationValue::where('code',$this->model_code)->where('min_model',(int)$this->model_values['model_number'])->first();

        // $calculation_values = $chiller_calculation_values->calculation_values;
        // $default_values = json_decode($calculation_values,true);

        // $this->calculation_values['ALTHE'] = $default_values['ALTHE'];
        // $this->calculation_values['AHTHE'] = $default_values['AHTHE'];
        // $this->calculation_values['AHR'] = $default_values['AHR'];
        // $this->calculation_values['KCON'] = $default_values['KCON'];

        
        // $constant_data = $this->getConstantData();
        // $this->calculation_values = array_merge($this->calculation_values,$constant_data);

        $vam_base = new VamBaseController();

        $pid_ft3 = $vam_base->PIPE_ID($this->calculation_values['PNB']);
        $this->calculation_values['PODA'] = $pid_ft3['PODA'];
        $this->calculation_values['THPA'] = $pid_ft3['THPA'];

        
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

	private function DATA()
    {


        if ($this->calculation_values['TCW11'] <= 32.0)
            $this->calculation_values['TCWA'] = $this->calculation_values['TCW11'];
        else
            $this->calculation_values['TCWA'] = 32.0;

        $this->calculation_values['SFACTOR'] = $this->calculation_values['A_SFACTOR'] - ($this->calculation_values['B_SFACTOR'] * $this->calculation_values['TCWA']);

        /************** MAX GEN TEMPERATURE *********/


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
        // $this->calculation_values['KCON'] = 3000 * 1.4;

        // $this->calculation_values['ULTHE'] = 450; 
        // $this->calculation_values['UHTHE'] = 1400; 
        // $this->calculation_values['UDHE'] = 400; 
        // $this->calculation_values['UHR'] = 700;      //UHTG = 1750;

        // if ($this->calculation_values['MODEL'] < 1200)
        // {
        //     $this->calculation_values['ULTG'] = 1850;
        //     $this->calculation_values['UHTG'] = 1750;
        // }
        // else
        // {
        //     $this->calculation_values['ULTG'] = 1790; 
        //     $this->calculation_values['UHTG'] = 1625;
        // }

        // if ($this->calculation_values['MODEL'] < 1200)
        // {
        //     $this->calculation_values['ODE'] = 0.016;
        //     $this->calculation_values['ODA'] = 0.016;

        //     if ($this->calculation_values['MODEL'] > 950)
        //     {
        //         $this->calculation_values['ODC'] = 0.019;
        //     }
        //     else
        //     {
        //         $this->calculation_values['ODC'] = 0.016;
        //     }
        // }
        // else
        // {
        //     $this->calculation_values['ODE'] = 0.019;
        //     $this->calculation_values['ODA'] = 0.019;
        //     $this->calculation_values['ODC'] = 0.019;
        // }
        /******** DETERMINATION OF KEVA FOR NON STD.SELECTION*****/
        // if ($this->calculation_values['MODEL'] < 750)
        // {
        //     // $this->calculation_values['KEVA1'] = 1 / ((1 / $this->calculation_values['KEVA']) - (0.57 / 340000.0));
        // }
        // else
        // {
        //     $this->calculation_values['KEVA1'] = 1 / ((1 / $this->calculation_values['KEVA']) - (0.65 / 340000.0));
        // }

        $this->calculation_values['KEVA1'] = 1 / ((1 / $this->calculation_values['KEVA']) - (0.65 / 340000.0));




        if ($this->calculation_values['TU2'] == 2 || $this->calculation_values['TU2'] == 7)
            $this->calculation_values['KEVA'] = 1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 340000.0));
        if ($this->calculation_values['TU2'] == 1 || $this->calculation_values['TU2'] == 6)
            $this->calculation_values['KEVA'] = 1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 37000.0));
        if ($this->calculation_values['TU2'] == 4)
            $this->calculation_values['KEVA'] = (1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 21000.0))) * 0.93;
        if ($this->calculation_values['TU2'] == 3)
            $this->calculation_values['KEVA'] = 1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 21000.0)) * 0.93;              //Changed to KEVA1 from 1600 on 06/11/2017 as tube metallurgy is changed
        if ($this->calculation_values['TU2'] == 5)
            $this->calculation_values['KEVA'] = 1 / ((1 / 1600.0) + ($this->calculation_values['TU3'] / 15000.0));



        /********* VARIATION OF KABS WITH CON METALLURGY ****/
        // if ($this->calculation_values['TU2'] == 6 || $this->calculation_values['TU2'] == 7 || $this->calculation_values['TU2'] == 8)
        // {
        //     if ($this->calculation_values['TV5'] == 1)
        //         $this->calculation_values['KM5'] = 1;
        //     else if ($this->calculation_values['TV5'] == 2)
        //         $this->calculation_values['KM5'] = 1;
        //     else if ($this->calculation_values['TV5'] == 3)
        //         $this->calculation_values['KM5'] = 1;
        //     else if ($this->calculation_values['TV5'] == 4)
        //         $this->calculation_values['KM5'] = 1;
        //     else if ($this->calculation_values['TV5'] == 5)
        //         $this->calculation_values['KM5'] = 1;
        //     else
        //         $this->calculation_values['KM5'] = 1;
        // }
        // else
        //     $this->calculation_values['KM5'] = 1;


        $this->calculation_values['KM5'] = 1;
        /********* DETERMINATION OF KABS FOR NONSTD. SELECTION****/
        $this->calculation_values['KABS1'] = 1 / ((1 / $this->calculation_values['KABS']) - (0.65 / 340000));
        if ($this->calculation_values['TU5'] == 1)
        {
            $this->calculation_values['KABS'] = 1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 37000));
        }
        else
        {
            if ($this->calculation_values['TU5'] == 2)
                $this->calculation_values['KABS'] = 1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 340000));
            if ($this->calculation_values['TU5'] == 4)
                $this->calculation_values['KABS'] = (1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 21000))) * 0.93;
            else
            {
                $this->calculation_values['KABS1'] = 1240;
                // if ($this->calculation_values['TU5'] == 3)
                //     $this->calculation_values['KABS'] = 1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 37000));
                // if ($this->calculation_values['TU5'] == 4)
                //     $this->calculation_values['KABS'] = 1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 340000));
                if ($this->calculation_values['TU5'] == 3)
                    $this->calculation_values['KABS'] = 1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 21000));
                if ($this->calculation_values['TU5'] == 5)
                    $this->calculation_values['KABS'] = 1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 15000));
            }
        }
        $this->calculation_values['KABS'] = $this->calculation_values['KABS'] * $this->calculation_values['KM5'];


        /********** DETERMINATION OF KCON IN NONSTD. SELECTION*******/
        $this->calculation_values['KCON1'] = 1 / ((1 / $this->calculation_values['KCON']) - (0.65 / 340000));         //Changed from 0.57 to 0.65 on 06/11/2017

        if ($this->calculation_values['TV5'] == 1)
        {
            //KCON1 = 4000;
            $this->calculation_values['KCON'] = 1 / ((1 / $this->calculation_values['KCON1']) + ($this->calculation_values['TV6'] / 37000));
        }
        else if ($this->calculation_values['TV5'] == 2 )
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

       

        $this->calculation_values['AEVAH'] = $this->calculation_values['AEVA'] / 2;
        $this->calculation_values['AEVAL'] = $this->calculation_values['AEVA'] / 2;
        $this->calculation_values['AABSH'] = $this->calculation_values['AABS'] / 2;
        $this->calculation_values['AABSL'] = $this->calculation_values['AABS'] / 2;
    }

    private function THICKNESS()
    {
        // $this->calculation_values['THE'] = "";
        // $this->calculation_values['THA'] = 0; 
        // $this->calculation_values['THC'] = 0;

        /********** EVA THICKNESS *********/
        // if ($this->model_values['metallurgy_standard'])
        // {
        //     // if ($this->calculation_values['MODEL'] < 750)
        //     //     $this->calculation_values['THE'] = 0.57;
        //     // else
        //     //     $this->calculation_values['THE'] = 0.65;

        //     $evaporator_material = $this->getMetallurgyValues('eva');
        //     $this->calculation_values['THE'] = $evaporator_material->default_thickness;
        // }
        // else
        // {
            
        // }

        /********** ABS THICKNESS *********/
        // if ($this->model_values['metallurgy_standard'])
        // {
        //     // $this->calculation_values['THA'] = 0.65;
        //     $absorber_material = $this->getMetallurgyValues('abs');
        //     $this->calculation_values['THA'] = $absorber_material->default_thickness;
        // }
        // else
        // {
            
        // }

        /********** COND THICKNESS *********/
        // if ($this->model_values['metallurgy_standard'])
        // {
        //     // $this->calculation_values['THC'] = .65;
        //     $condenser_material = $this->getMetallurgyValues('con');
        //     $this->calculation_values['THC'] = $condenser_material->default_thickness;
        // }
        // else
        // {
            
        // }

        $this->calculation_values['THE'] = $this->calculation_values['TU3'];
        $this->calculation_values['THA'] = $this->calculation_values['TU6'];
        $this->calculation_values['THC'] = $this->calculation_values['TV6'];


        if($this->calculation_values['TU2']==4 || $this->calculation_values['TU2'] == 6 || $this->calculation_values['TU2'] == 7)
            $this->calculation_values['IDE'] = $this->calculation_values['ODE'] - ((2.0 * ($this->calculation_values['THE'] + 0.1)) / 1000.0);
        else
            $this->calculation_values['IDE'] = $this->calculation_values['ODE'] - ((2.0 * $this->calculation_values['THE']) / 1000.0);

        if ($this->calculation_values['TU5']  ==  2.0 || $this->calculation_values['TU5'] == 1.0 || $this->calculation_values['TU5'] == 4.0)
            $this->calculation_values['IDA'] = $this->calculation_values['ODA'] - ((2.0 * ($this->calculation_values['THA'] + 0.1)) / 1000.0);
        else
            $this->calculation_values['IDA'] = $this->calculation_values['ODA'] - ((2.0 * $this->calculation_values['THA']) / 1000.0);

        if ($this->calculation_values['TV5'] == 1 || $this->calculation_values['TV5'] == 2 || $this->calculation_values['TV5'] == 4)
            $this->calculation_values['IDC'] = $this->calculation_values['ODC'] - ((2.0 * ($this->calculation_values['THC'] + 0.1)) / 1000.0);       
        else                
            $this->calculation_values['IDC'] = $this->calculation_values['ODC'] - ((2.0 * $this->calculation_values['THC']) / 1000.0);





        // if ($this->calculation_values['MODEL'] < 750)
        // { 
        //     if($this->calculation_values['TU2'] == 4)
        //         $this->calculation_values['IDE'] = $this->calculation_values['ODE'] - ((2.0 * ($this->calculation_values['THE'] + 0.1)) / 1000.0);
        //     else
        //         $this->calculation_values['IDE'] = $this->calculation_values['ODE'] - ((2.0 * $this->calculation_values['THE']) / 1000.0);

        //     if ($this->calculation_values['TU5'] < 2.1 || $this->calculation_values['TU5'] == 6)
        //         $this->calculation_values['IDA'] = $this->calculation_values['ODA'] - ((2.0 * ($this->calculation_values['THA'] + 0.1)) / 1000.0);
        //     else
        //         $this->calculation_values['IDA'] = $this->calculation_values['ODA'] - ((2.0 * $this->calculation_values['THA']) / 1000.0);

        //     if ($this->calculation_values['TV5'] == 1 || $this->calculation_values['TV5'] == 2 || $this->calculation_values['TV5'] == 0 || $this->calculation_values['TV5'] == 4)
        //         $this->calculation_values['IDC'] = $this->calculation_values['ODC'] - ((2.0 * ($this->calculation_values['THC'] + 0.1)) / 1000.0);                
        //     else                
        //         $this->calculation_values['IDC'] = $this->calculation_values['ODC'] - ((2.0 * $this->calculation_values['THC']) / 1000.0);
            
        // }
        // else
        // {
        //     if ($this->calculation_values['TU2'] < 2.1 || $this->calculation_values['TU2']==4)
        //         $this->calculation_values['IDE'] = $this->calculation_values['ODE'] - ((2.0 * ($this->calculation_values['THE'] + 0.1)) / 1000.0);
        //     else
        //         $this->calculation_values['IDE'] = $this->calculation_values['ODE'] - ((2.0 * $this->calculation_values['THE']) / 1000.0);

        //     if ($this->calculation_values['TU5'] < 2.1|| $this->calculation_values['TU5'] == 6)
        //         $this->calculation_values['IDA'] = $this->calculation_values['ODA'] - ((2.0 * ($this->calculation_values['THA'] + 0.1)) / 1000.0);
        //     else
        //         $this->calculation_values['IDA'] = $this->calculation_values['ODA'] - ((2.0 * $this->calculation_values['THA']) / 1000.0);

        //     if ($this->calculation_values['TV5'] == 1 || $this->calculation_values['TV5'] == 2 || $this->calculation_values['TV5'] == 0 || $this->calculation_values['TV5'] == 4)
        //         $this->calculation_values['IDC'] = $this->calculation_values['ODC'] - ((2.0 * ($this->calculation_values['THC'] + 0.1)) / 1000.0);
        //     else
        //         $this->calculation_values['IDC'] = $this->calculation_values['ODC'] - ((2.0 * $this->calculation_values['THC']) / 1000.0);


        // }
    }


    public function WATERPROP(){

        $vam_base = new VamBaseController();
        
        if (intval($this->calculation_values['GL']) == 2)
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


	public function validateChillerAttribute($attribute){

		switch (strtoupper($attribute))
		{
            case "MODEL_NUMBER":
                $this->modulNumberDoubleEffectS2();
                
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
                    // 	$this->model_values['evaporator_thickness_change'] = false;
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
            if (floatval($this->model_values['evaporator_material_value']) != 3)
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

            if(($cooling_water_flow > $min_range) && ($cooling_water_flow < $max_range)){
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
                
        

         // "STEAM_PRESSURE":
        if (!(($this->model_values['steam_pressure'] >= $this->model_values['steam_pressure_min_range']) && ($this->model_values['steam_pressure'] <= $this->model_values['steam_pressure_max_range'])))
        {
            return array('status' => false,'msg' => $this->notes['NOTES_STMPR_RANGE']);
        }
            


        return array('status' => true,'msg' => "process run successfully");

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

    public function VELOCITY(){
           

        $IDA = floatval($this->calculation_values['IDA']);
        $TNAA = floatval($this->calculation_values['TNAA']);


        $GCW = floatval($this->calculation_values['GCW']);
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

        if ($this->calculation_values['VA'] > ($this->calculation_values['VAMAX'] + 0.01) && $this->calculation_values['TAP'] != 1)
        {

            $this->calculation_values['TAP'] = $this->calculation_values['TAP'] - 1;
            $this->calculation_values['VA'] = $this->calculation_values['GCW'] / (((3600 * 3.141593 * $this->calculation_values['IDA'] * $this->calculation_values['IDA']) / 4.0) * ($this->calculation_values['TNAA'] / $this->calculation_values['TAP']));
       
        }
        if ($this->calculation_values['TAP'] == 1)           //PARAFLOW
        {
            $this->calculation_values['GCWAH'] = 0.5 * $GCW;
            $this->calculation_values['GCWAL'] = 0.5 * $GCW;
        }
        else                //SERIES FLOW
        {
            $this->calculation_values['GCWAH'] = $GCW;
            $this->calculation_values['GCWAL'] = $GCW;
        }

        /**************** CONDENSER VELOCITY ******************/
        $this->calculation_values['TCP'] = 1;
        $this->calculation_values['GCWCMAX'] = 3.141593 / 4 * ($this->calculation_values['IDC'] * $this->calculation_values['IDC']) * $this->calculation_values['TNC'] * $this->calculation_values['VCMAX'] * 3600 / $this->calculation_values['TCP'];
        if ($GCW > $this->calculation_values['GCWCMAX'])
            $this->calculation_values['GCWC'] = $this->calculation_values['GCWCMAX'];
        else
            $this->calculation_values['GCWC'] = $GCW;

        if($this->calculation_values['MODEL'] < 300){
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
        else{
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
        else{
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


            if ($this->calculation_values['VEA'] > $this->calculation_values['VEMAX'])                        // 14 FEB 2012
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


        $vam_base = new VamBaseController();

        $pid_ft1 = $vam_base->PIPE_ID($this->calculation_values['PNB1']);
        $this->calculation_values['PIDE1'] = $pid_ft1['PID'];
        $this->calculation_values['FT1'] = $pid_ft1['FT'];

        $pid_ft2 = $vam_base->PIPE_ID($this->calculation_values['PNB2']);
        $this->calculation_values['PIDE2'] = $pid_ft2['PID'];
        $this->calculation_values['FT2'] = $pid_ft2['FT'];

        $pid_ft = $vam_base->PIPE_ID($this->calculation_values['PNB']);
        $this->calculation_values['PIDA'] = $pid_ft['PID'];
        $this->calculation_values['FT'] = $pid_ft['FT'];

        $this->PR_DROP_CHILL();

        if ($this->calculation_values['FLE'] > 12)
        {
             if ($this->calculation_values['MODEL'] < 750 && $this->calculation_values['TU2'] < 2.1)
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

    public function PR_DROP_CHILL(){
        $vam_base = new VamBaseController();

        $this->calculation_values['CHGLY_ROW22'] = 0;
        $this->calculation_values['CHGLY_VIS22'] = 0;
        $this->calculation_values['FE1'] = 0;
        $this->calculation_values['F'] = 0;

        $this->calculation_values['VPE1'] = ($this->calculation_values['GCHW'] * 4) / (3.141593 * $this->calculation_values['PIDE1'] * $this->calculation_values['PIDE1'] * 3600);

        if($this->calculation_values['MODEL'] > 300){
            $this->calculation_values['VPE2'] = (0.5 * $this->calculation_values['GCHW'] * 4) / (3.141593 * $this->calculation_values['PIDE2'] * $this->calculation_values['PIDE2'] * 3600);
            $this->calculation_values['VPBR'] = (0.5 * $this->calculation_values['GCHW'] * 4) / (3.141593 * $this->calculation_values['PIDE1'] * $this->calculation_values['PIDE1'] * 3600);
        }
        else{
            $this->calculation_values['VPE2'] = 0;
            $this->calculation_values['VPBR'] = 0;

        }
        

       //PIPE1
       
       //  VPE1 = (GCHW * 4) / (3.141593 * PIDE1 * PIDE1 * 3600);            //VELOCITY IN PIPE1
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
       if($this->calculation_values['MODEL'] > 300){
            $this->calculation_values['REPE2'] = ($this->calculation_values['PIDE2'] * $this->calculation_values['VPE2'] * $this->calculation_values['CHGLY_ROW22']) / $this->calculation_values['CHGLY_VIS22'];
            $this->calculation_values['REBR'] = ($this->calculation_values['PIDE1'] * $this->calculation_values['VPBR'] * $this->calculation_values['CHGLY_ROW22']) / $this->calculation_values['CHGLY_VIS22'];
       }
       else{
            $this->calculation_values['REPE2'] = 0;
            $this->calculation_values['REBR'] = 0;
       }    
                  //REYNOLDS NO IN PIPE1
       
       $this->calculation_values['FF1'] = 1.325 / pow(log((0.0457 / (3.7 * $this->calculation_values['PIDE1'] * 1000)) + (5.74 / pow($this->calculation_values['REPE1'], 0.9))), 2);       //FRICTION FACTOR CAL

       if($this->calculation_values['MODEL'] > 300){
            $this->calculation_values['FF2'] = 1.325 / pow(log((0.0457 / (3.7 * $this->calculation_values['PIDE2'] * 1000)) + (5.74 / pow($this->calculation_values['REPE2'], 0.9))), 2);
            $this->calculation_values['FF3'] = 1.325 / pow(log((0.0457 / (3.7 * $this->calculation_values['PIDE1'] * 1000)) + (5.74 / pow($this->calculation_values['REBR'], 0.9))), 2);
       }
       else{
            $this->calculation_values['FF2'] = 0;
            $this->calculation_values['FF3'] = 0;
       }
       


       $this->calculation_values['FL1'] = ($this->calculation_values['FF1'] * ($this->calculation_values['SL1'] + $this->calculation_values['SL8']) / $this->calculation_values['PIDE1']) * ($this->calculation_values['VPE1'] * $this->calculation_values['VPE1'] / (2 * 9.81));

       if($this->calculation_values['MODEL'] > 300){
            $this->calculation_values['FL2'] = ($this->calculation_values['FF2'] * ($this->calculation_values['SL3'] + $this->calculation_values['SL4'] + $this->calculation_values['SL5'] + $this->calculation_values['SL6']) / $this->calculation_values['PIDE2']) * ($this->calculation_values['VPE2'] * $this->calculation_values['VPE2'] / (2 * 9.81));
           $this->calculation_values['FL3'] = ($this->calculation_values['FF3'] * ($this->calculation_values['SL2'] + $this->calculation_values['SL7']) / $this->calculation_values['PIDE1']) * ($this->calculation_values['VPBR'] * $this->calculation_values['VPBR'] / (2 * 9.81));
           $this->calculation_values['FL4'] = (2 * $this->calculation_values['FT1'] * 20 * $this->calculation_values['VPBR'] * $this->calculation_values['VPBR'] / (2 * 9.81)) + (2 * $this->calculation_values['FT2'] * 60 * $this->calculation_values['VPE2'] * $this->calculation_values['VPE2'] / (2 * 9.81)) + (2 * $this->calculation_values['FT2'] * 14 * $this->calculation_values['VPE2'] * $this->calculation_values['VPE2'] / (2 * 9.81));
           $this->calculation_values['FL5'] = ($this->calculation_values['VPE2'] * $this->calculation_values['VPE2'] / (2 * 9.81)) + (0.5 * $this->calculation_values['VPE2'] * $this->calculation_values['VPE2'] / (2 * 9.81));
       }
       else{
            $this->calculation_values['FL2'] = 0;
            $this->calculation_values['FL3'] = 0;
            $this->calculation_values['FL4'] = 0;
            $this->calculation_values['FL5'] = ($this->calculation_values['VPE1'] * $this->calculation_values['VPE1'] / (2 * 9.81)) + (0.5 * $this->calculation_values['VPE1'] * $this->calculation_values['VPE1'] / (2 * 9.81));
       }

       

       $this->calculation_values['FLP'] = $this->calculation_values['FL1'] + $this->calculation_values['FL2'] + $this->calculation_values['FL3'] + $this->calculation_values['FL4'] + $this->calculation_values['FL5'];      //EVAPORATOR PIPE LOSS

       $this->calculation_values['RE'] = ($this->calculation_values['VEA'] * $this->calculation_values['IDE'] * $this->calculation_values['CHGLY_ROW22']) / $this->calculation_values['CHGLY_VIS22']; 


       if (($this->calculation_values['MODEL'] < 1200 && ($this->calculation_values['TU2'] == 1 || $this->calculation_values['TU2'] == 2 || $this->calculation_values['TU2'] == 3 )))
       {
           $this->calculation_values['F'] = 1.325 / pow(log((1.53 / (3.7 * $this->calculation_values['IDE'] * 1000)) + (5.74 / pow($this->calculation_values['RE'], 0.9))), 2);
           $this->calculation_values['FE1'] = $this->calculation_values['F'] * $this->calculation_values['LE'] * $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (2 * 9.81 * $this->calculation_values['IDE']);
       }
       else if ($this->calculation_values['MODEL'] > 1200 && ($this->calculation_values['TU2'] == 1 || $this->calculation_values['TU2'] == 2 || $this->calculation_values['TU2'] == 3 ))                                         //06/11/2017   Changed for SS FInned
       {
           $this->calculation_values['F'] = (1.325 / pow(log((1.53 / (3.7 * $this->calculation_values['IDE'] * 1000)) + (5.74 / pow($this->calculation_values['RE'], 0.9))), 2)) * ((-0.0315 * $this->calculation_values['VEA']) + 0.85);
           $this->calculation_values['FE1'] = $this->calculation_values['F'] * $this->calculation_values['LE'] * $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (2 * 9.81 * $this->calculation_values['IDE']);

       }
       else if (($this->calculation_values['MODEL'] < 1200  && ($this->calculation_values['TU2'] == 4 || $this->calculation_values['TU2'] == 6 || $this->calculation_values['TU2'] == 7)))                  // 12% AS PER EXPERIMENTATION      
       {
           $this->calculation_values['F'] = (0.0014 + (0.137 / pow($this->calculation_values['RE'], 0.32))) * 1.12;
           $this->calculation_values['FE1'] = 2 * $this->calculation_values['F'] * $this->calculation_values['LE'] * $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (9.81 * $this->calculation_values['IDE']);
       }
       else if ($this->calculation_values['MODEL'] > 1200 && ($this->calculation_values['TU2'] == 4 || $this->calculation_values['TU2'] == 6 || $this->calculation_values['TU2'] == 7))
       {
           $this->calculation_values['F'] = 0.0014 + (0.137 / pow($this->calculation_values['RE'], 0.32));
           $this->calculation_values['FE1'] = 2 * $this->calculation_values['F'] * $this->calculation_values['LE'] * $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (9.81 * $this->calculation_values['IDE']);
       }
       else
       {
           $this->calculation_values['F'] = 0.0014 + (0.125 / pow($this->calculation_values['RE'], 0.32));
           $this->calculation_values['FE1'] = 2 * $this->calculation_values['F'] * $this->calculation_values['LE'] * $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (9.81 * $this->calculation_values['IDE']);
       }

       $this->calculation_values['FE2'] = $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (4 * 9.81);
       $this->calculation_values['FE3'] = $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (2 * 9.81);
       $this->calculation_values['FE4'] = (($this->calculation_values['FE1'] + $this->calculation_values['FE2'] + $this->calculation_values['FE3']) * $this->calculation_values['TP']) * 2;      //EVAPORATOR TUBE LOSS FOR DOUB$this->calculation_values['LE'] ABS

       $this->calculation_values['FLE'] = $this->calculation_values['FLP'] + $this->calculation_values['FE4'];                //TOTAL FRICTION LOSS IN CHIL$this->calculation_values['LE']D WATER CKT
       $this->calculation_values['PDE'] = $this->calculation_values['FLE'] + $this->calculation_values['SHE'];                    //P$this->calculation_values['RE']SSU$this->calculation_values['RE'] DROP IN CHIL$this->calculation_values['LE']D WATER CKT

       
    }

    public function PR_DROP_COW()
    {
        $COGLY_ROWH33 = 0; $COGLY_VISH33 = 0; $FH = 0; $FL = 0;$F = 0;
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
        //  FFD1 = 1.325 / pow(Math.Log((0.0457 / (3.7 * (ED1NB) * 1000)) + (5.74 / pow(RED1, 0.9))), 2);

        $this->calculation_values['FLP1'] = ($this->calculation_values['FFA'] * ($this->calculation_values['PSL1'] + $this->calculation_values['PSL2']) / $this->calculation_values['PIDA']) * ($this->calculation_values['VPA'] * $this->calculation_values['VPA'] / (2 * 9.81)) + ((14 * $this->calculation_values['FT']) * ($this->calculation_values['VPA'] * $this->calculation_values['VPA']) / (2 * 9.81));        //FR LOSS IN PIPE                                   
        //   $FLD1 = ((FFD1 * DSL) / ED1NB) * (VD1 * VD1 / (2 * 9.81));                                  //FR LOSS IN DUCT
        $this->calculation_values['FLOT'] = (1 + 0.5 + 1 + 0.5) * ($this->calculation_values['VPA'] * $this->calculation_values['VPA'] / (2 * 9.81));                                                                  //EXIT, ENTRY LOSS

        $this->calculation_values['AFLP'] = ($this->calculation_values['FLP1'] + $this->calculation_values['FLOT']) * 1.075;               //7.5% SAFETY

        $REH = ($this->calculation_values['VAH'] * $this->calculation_values['IDA'] * $COGLY_ROWH33) / $COGLY_VISH33;
        $REL = ($this->calculation_values['VAL'] * $this->calculation_values['IDA'] * $COGLY_ROWH33) / $COGLY_VISH33;

        if (($this->calculation_values['TU5'] < 2.1 || $this->calculation_values['TU5'] == 4) && $this->calculation_values['MODEL'] < 1200)
        {
            $FH = (0.0014 + (0.137 / pow($REH, 0.32))) * 1.12;
            $FL = (0.0014 + (0.137 / pow($REL, 0.32))) * 1.12;
            
        }
        else if (($this->calculation_values['TU5'] < 2.1 || $this->calculation_values['TU5'] == 4) && $this->calculation_values['MODEL'] > 1200)
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
            $this->calculation_values['FLA'] = $FA4H + $this->calculation_values['AFLP'];      //PARA$FLOW WILL HAVE ONE ENTRY, ONE EXIT, ONE TUBE FRICTION LOSS
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
        $this->calculation_values['FC4'] = ($FC1 + $FC2 + $FC3) * $this->calculation_values['TCP'];                        //FRICTION LOSS IN CONDENSER TUBES
        $FLC = $this->calculation_values['FC4'];

    


        $this->calculation_values['PDA'] = $this->calculation_values['FLA'] + $this->calculation_values['SHA'] + $this->calculation_values['FC4'];  
    }


    public function CALCULATIONS(){
        $this->calculation_values['CW'] = 0;
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


        if ($this->calculation_values['region_type'] !== 2 || $this->calculation_values['region_type'] !== 3)
        {
            if ($this->calculation_values['PST1'] < 6.01)
            {
                if ($this->calculation_values['MODEL'] == 130)
                    $this->calculation_values['AHTG'] = 15.5;
                if ($this->calculation_values['MODEL'] == 160)
                    $this->calculation_values['AHTG'] = 17.2;
                if ($this->calculation_values['MODEL'] == 210) 
                    $this->calculation_values['AHTG'] = 23.1;
                if ($this->calculation_values['MODEL'] == 250) 
                    $this->calculation_values['AHTG'] = 25.6;
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
                //if ($this->calculation_values['MODEL'] == 2600)     $this->calculation_values['AHTG'] = 259.4 * 1.2;
                //if ($this->calculation_values['MODEL'] == 2800)     $this->calculation_values['AHTG'] = 269.4 * 1.2;

                //if ($this->calculation_values['PST1'] < 5.01)
                //{
                //    CW = 2;
                //}
            }
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
                $KM3 = (0.0343 * $this->calculation_values['TCHW12']) + 0.82;
            }
            else
            {
                {
                    $KM3 = 1;
                }
            }
        }

        $this->calculation_values['TCHW1H'] = $this->calculation_values['TCHW11'];
        $this->calculation_values['TCHW2L'] = $this->calculation_values['TCHW12'];
        $this->calculation_values['TCW1H'] = $this->calculation_values['TCW11'];

        $this->calculation_values['DT'] = $this->calculation_values['TCHW1H'] - $this->calculation_values['TCHW2L'];
        if($this->calculation_values['DT'] >= 11){
            $KM4 = 1.11 - (0.01 * $this->calculation_values['DT']);
        }
        else{
            $KM4 = 1;
        }

        $this->calculation_values['KEVA'] = $this->calculation_values['KEVA'] * $KM3 * $KM4;
        $this->calculation_values['KABSH'] = $this->calculation_values['KABSH'] * $KM3 * $KM4;
        $this->calculation_values['KABSL'] = $this->calculation_values['KABSL'] * $KM3 * $KM4;

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

        //UEVAH = UEVAH * 0.96;
        //UEVAL = UEVAL * 0.96;
        //$this->calculation_values['UABSH'] = $this->calculation_values['UABSH'] * 0.96;
        //$this->calculation_values['UABSL'] = $this->calculation_values['UABSL'] * 0.96;
        /*************************/  

        

        if ($this->calculation_values['TUU'] != 'ari')
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
        }
        else{
            $a = 1;
            $this->calculation_values['TSTOUT'] = 85;

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

    public function CONCHECK1()
    {
        if ($this->calculation_values['MODEL'] < 275)
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
        else if ($this->calculation_values['MODEL'] > 275)
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

    public function EVAPORATOR()
    {

        $this->calculation_values['i'] = 0; $this->calculation_values['q'] = 0; $this->calculation_values['r'] = 0;
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

        // if (CW == 2)
        // {
        //     TCW3 = $this->calculation_values['TCW11'];
        //     $ATCW3 = TCW3 + $QCO / ($this->calculation_values['GCW'] * 1000);
        //     $this->calculation_values['ATCW2'] = $ATCW3 + $QAB / ($this->calculation_values['GCW'] * 1000);
        //     $LMTDCO = $QCO / ($this->calculation_values['KCON'] * $this->calculation_values['ACON']);
        //     $this->calculation_values['AT3'] = $ATCW3 + ($ATCW3 - TCW3) / (exp(($ATCW3 - TCW3) / $LMTDCO) - 1);

        // }

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
                    $tchw2h[$p] = $tchw2h[$p - 1] + $err1[$p - 1] * ($tchw2h[$p - 1] - $tchw2h[$p - 2]) / ($err1[$p - 2] - $err1[$p - 1]) / 4;
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
        // else
        // {
        //     $this->calculation_values['T2'] = $this->calculation_values['T2'];
        // }

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
            $this->calculation_values['q'] = $this->calculation_values['q'] + 1;
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

            // if ($this->calculation_values['CW'] == 2)
            // {
            //     CWCONOUT();
            //     $this->calculation_values['TCW1H'] = (($this->calculation_values['GCWC'] * $this->calculation_values['TCW4']) + (($this->calculation_values['GCW'] - $this->calculation_values['GCWC']) * $this->calculation_values['TCW3'])) / $this->calculation_values['GCW'];
            //     CWABSHOUT();
            //     if ($this->calculation_values['TAP'] == 1)
            //     {
            //         $this->calculation_values['TCW1L'] = $this->calculation_values['TCW1H'];
            //     }
            //     else
            //     {
            //         $this->calculation_values['TCW1L'] = $this->calculation_values['TCW2H'];
            //     }
            //     CWABSLOUT();

            // }
            // else
            // {
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
            // }

            

            $this->calculation_values['T9'] = $vam_base2->LIBR_TEMP($this->calculation_values['P3'], $this->calculation_values['XCONC']);
            $this->calculation_values['J9'] = $vam_base2->WATER_VAPOUR_ENTHALPY($this->calculation_values['T9'], $this->calculation_values['P3']);
            $this->calculation_values['I9'] = $vam_base2->LIBR_ENTHALPY($this->calculation_values['T9'], $this->calculation_values['XCONC']);

            $this->DHE();
            $this->LTHE();
            $this->HTHE();


            $this->calculation_values['QLTG'] = ($this->calculation_values['GCONC'] * $this->calculation_values['I9']) + ($this->calculation_values['GREF2'] * $this->calculation_values['J9']) - ($this->calculation_values['GMED'] * $this->calculation_values['I10']);
            $ferr3[$this->calculation_values['r']] = ($this->calculation_values['QLMTDLTG'] - $this->calculation_values['QLTG']) / $this->calculation_values['QLMTDLTG'] * 100;
            $this->calculation_values['r'] = $this->calculation_values['r'] + 1;

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

            $this->calculation_values['QCWABSH'] = $this->calculation_values['GCWAH'] * $this->calculation_values['COGLY_ROWH1'] * (($this->calculation_values['TCW2H'] * $this->calculation_values['COGLY_SPHT2H']) - ($this->calculation_values['TCW1H'] * $this->calculation_values['COGLY_SPHT1H'])) / 4187;

            $this->calculation_values['LMTDABSH'] = (($this->calculation_values['T6H'] - $this->calculation_values['TCW2H']) - ($this->calculation_values['T2'] - $this->calculation_values['TCW1H'])) / log(($this->calculation_values['T6H'] - $this->calculation_values['TCW2H']) / ($this->calculation_values['T2'] - $this->calculation_values['TCW1H']));
            $this->calculation_values['QLMTDABSH'] = $this->calculation_values['UABSH'] * $this->calculation_values['AABSH'] * $this->calculation_values['LMTDABSH'];
            $ferr4[$s] = ($this->calculation_values['QCWABSH'] - $this->calculation_values['QLMTDABSH']) * 100 / $this->calculation_values['QCWABSH'];
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

            $this->calculation_values['QCWABSL'] = $this->calculation_values['GCWAL'] * $this->calculation_values['COGLY_ROWH1'] * (($this->calculation_values['TCW2L'] * $this->calculation_values['COGLY_SPHT2L']) - ($this->calculation_values['TCW1L'] * $this->calculation_values['COGLY_SPHT1L'])) / 4187;

            $this->calculation_values['LMTDABSL'] = (($this->calculation_values['T6'] - $this->calculation_values['TCW2L']) - ($this->calculation_values['T2L'] - $this->calculation_values['TCW1L'])) / log(($this->calculation_values['T6'] - $this->calculation_values['TCW2L']) / ($this->calculation_values['T2L'] - $this->calculation_values['TCW1L']));
            $this->calculation_values['QLMTDABSL'] = $this->calculation_values['UABSL'] * $this->calculation_values['AABSL'] * $this->calculation_values['LMTDABSL'];
            $ferr5[$m] = ($this->calculation_values['QCWABSL'] - $this->calculation_values['QLMTDABSL']) * 100 / $this->calculation_values['QCWABSL'];
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

            $this->calculation_values['LMTDCON'] = (($this->calculation_values['T3'] - $this->calculation_values['TCW3']) - ($this->calculation_values['T3'] - $this->calculation_values['TCW4'])) / log(($this->calculation_values['T3'] - $this->calculation_values['TCW3']) / ($this->calculation_values['T3'] - $this->calculation_values['TCW4']));
            
            $this->calculation_values['QLMTDCON'] = $this->calculation_values['UCON'] * $this->calculation_values['ACON'] * $this->calculation_values['LMTDCON'];
            $this->calculation_values['QCON'] = $this->calculation_values['GCWC'] * $this->calculation_values['COGLY_ROWH1'] * (($this->calculation_values['TCW4'] * $this->calculation_values['COGLY_SPHT4']) - ($this->calculation_values['TCW3'] * $this->calculation_values['COGLY_SPHT3'])) / 4187;

            $ferr6[$b] = ($this->calculation_values['QLMTDCON'] - $this->calculation_values['QCON']) * 100 / $this->calculation_values['QLMTDCON'];
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
                $t22[$c] = $t22[$c - 1] + 1;
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

            $this->calculation_values['QREFDHE'] = $this->calculation_values['GREF1'] * ($this->calculation_values['I13'] - $this->calculation_values['I22']);
            $this->calculation_values['I21'] = $this->calculation_values['I2'] + ($this->calculation_values['QREFDHE'] / $this->calculation_values['GDIL2']);
            $this->calculation_values['T21'] = $vam_base->LIBR_TEMPERATURE($this->calculation_values['XDIL'], $this->calculation_values['I21']);
            $this->calculation_values['LMTDDHE'] = (($this->calculation_values['T13'] - $this->calculation_values['T21']) - ($this->calculation_values['T22'] - $this->calculation_values['T2'])) / log(($this->calculation_values['T13'] - $this->calculation_values['T21']) / ($this->calculation_values['T22'] - $this->calculation_values['T2']));
            $this->calculation_values['QLIBRDHE'] = $this->calculation_values['UDHE'] * $this->calculation_values['ADHE'] * $this->calculation_values['LMTDDHE'];
            $ferr7[$c] = ($this->calculation_values['QREFDHE'] - $this->calculation_values['QLIBRDHE']) * 100 / $this->calculation_values['QREFDHE'];
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
            $this->calculation_values['QLIBRLTHE'] = $this->calculation_values['GCONC'] * ($this->calculation_values['I9'] - $this->calculation_values['I8']);
            $this->calculation_values['I11'] = ($this->calculation_values['QLIBRLTHE'] / $this->calculation_values['GDIL1']) + $this->calculation_values['I2'];
            $this->calculation_values['T11'] = $vam_base->LIBR_TEMPERATURE($this->calculation_values['XDIL'], $this->calculation_values['I11']);
            $this->calculation_values['LMTDLTHE'] = (($this->calculation_values['T9'] - $this->calculation_values['T11']) - ($this->calculation_values['T8'] - $this->calculation_values['T2'])) / log(($this->calculation_values['T9'] - $this->calculation_values['T11']) / ($this->calculation_values['T8'] - $this->calculation_values['T2']));
            $this->calculation_values['QLMTDLTHE'] = $this->calculation_values['ULTHE'] * $this->calculation_values['ALTHE'] * $this->calculation_values['LMTDLTHE'];
            $ferr9[$h] = ($this->calculation_values['QLMTDLTHE'] - $this->calculation_values['QLIBRLTHE']) * 100 / $this->calculation_values['QLMTDLTHE'];

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
            $this->calculation_values['QLIBRHTHE'] = $this->calculation_values['GDIL1'] * ($this->calculation_values['I7'] - $this->calculation_values['I11']);
            $this->calculation_values['I4'] = $vam_base->LIBR_ENTHALPY($this->calculation_values['T4'], $this->calculation_values['XMED']);

            
            
            $this->calculation_values['I10'] = $this->calculation_values['I4'] - ($this->calculation_values['QLIBRHTHE'] / $this->calculation_values['GMED']);
            $this->calculation_values['T10'] = $vam_base->LIBR_TEMPERATURE($this->calculation_values['XMED'], $this->calculation_values['I10']);
            $this->calculation_values['LMTDHTHE'] = (($this->calculation_values['T4'] - $this->calculation_values['T7']) - ($this->calculation_values['T10'] - $this->calculation_values['T11'])) / log(($this->calculation_values['T4'] - $this->calculation_values['T7']) / ($this->calculation_values['T10'] - $this->calculation_values['T11']));
            $this->calculation_values['QLMTDHTHE'] = $this->calculation_values['UHTHE'] * $this->calculation_values['AHTHE'] * $this->calculation_values['LMTDHTHE'];
            $ferr10[$ht] = ($this->calculation_values['QLIBRHTHE'] - $this->calculation_values['QLMTDHTHE']) * 100 / $this->calculation_values['QLIBRHTHE'];
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

            //if (MODEL < 1200)
            //{
            //    if ($this->calculation_values['TCHW2L'] < 7.0)
            //    {
            //        $this->calculation_values['KM2'] = (-0.857413 * $this->calculation_values['TCHW2L'] + 6); // +5;        //INCREASED FROM 4 TO 5 FEB 2009
            //        if ($this->calculation_values['PST1'] < 6.01 && $this->calculation_values['PST1'] >= 5.01)
            //        {
            //            $this->calculation_values['KM2'] = (-0.857413 * $this->calculation_values['TCHW2L'] + 6); // +4;
            //        }
            //    }
            //    else
            //    {
            //        $this->calculation_values['KM2'] = 0; // 5;
            //        if ($this->calculation_values['PST1'] < 6.1 && $this->calculation_values['PST1'] >= 5.01)
            //        {
            //            $this->calculation_values['KM2'] = 0; // 4;
            //        }
            //    }
            //}

            //else
            //{
            //    if ($this->calculation_values['TCHW2L'] < 7.0)
            //    {
            //        $this->calculation_values['KM2'] = (-0.857413 * $this->calculation_values['TCHW2L'] + 6) + 0;// 4.5;        //INCREASED FROM 4 TO 5 FEB 2009
            //    }
            //    else
            //    {
            //        $this->calculation_values['KM2'] = 0;// 4.5;
            //    }
            //}
            
            //$this->calculation_values['KM2'] = 4.285178;
            if ($this->calculation_values['TCHW2L'] < 7.0)
            {
                $this->calculation_values['KM2'] = (-0.857413 * $this->calculation_values['TCHW2L'] + 6);     //INCREASED FROM 4 TO 5 FEB 2009

            }

            $PS1 = $vam_base->STEAM_PRESSURE(($this->calculation_values['TSMIN'] + $this->calculation_values['KM2']));       //IN kg/cm2.g



            $this->calculation_values['PS'] = $PS1 + 0.5; // 0.7;

            $this->calculation_values['T5'] = $vam_base->LIBR_TEMP($this->calculation_values['P4'], $this->calculation_values['XDIL']);
            $this->calculation_values['LMTDHTG'] = (($this->calculation_values['TS'] - $this->calculation_values['T5']) - ($this->calculation_values['TS'] - $this->calculation_values['T4'])) / log(($this->calculation_values['TS'] - $this->calculation_values['T5']) / ($this->calculation_values['TS'] - $this->calculation_values['T4']));
            $this->calculation_values['QLMTDHTG'] = $this->calculation_values['UHTG'] * $this->calculation_values['AHTG'] * $this->calculation_values['LMTDHTG'];
            $this->calculation_values['HSTEAM'] = 639.427333 + (4.7783887 * ($this->calculation_values['PST1'] + 1.0)) - (0.3413875 * ($this->calculation_values['PST1'] + 1.0) * ($this->calculation_values['PST1'] + 1.0)) + (0.009782 * ($this->calculation_values['PST1'] + 1.0) * ($this->calculation_values['PST1'] + 1.0) * ($this->calculation_values['PST1'] + 1.0));
            $this->calculation_values['GSTEAM'] = $this->calculation_values['QLMTDHTG'] / ($this->calculation_values['HSTEAM'] - $this->calculation_values['TS']);
            $this->HR();
            $this->calculation_values['I14'] = ($this->calculation_values['GDIL2'] * $this->calculation_values['I20'] + $this->calculation_values['GDIL1'] * $this->calculation_values['I7']) / $this->calculation_values['GDIL'];
            $this->calculation_values['T14'] = $vam_base->LIBR_TEMPERATURE($this->calculation_values['XDIL'], $this->calculation_values['I14']);
            $this->calculation_values['QHTG'] = ($this->calculation_values['GMED'] * $this->calculation_values['I4']) + ($this->calculation_values['GREF1'] * $this->calculation_values['J4']) - ($this->calculation_values['GDIL'] * $this->calculation_values['I14']);

            $ferr11[$tg] = ($this->calculation_values['QLMTDHTG'] - $this->calculation_values['QHTG']) * 100 / $this->calculation_values['QLMTDHTG'];
            $tg++;
        }

        /********** SFACTOR - STEAM *******/
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


        $this->calculation_values['GSTEAM'] = $this->calculation_values['GSTEAM'] * $this->calculation_values['SFACTOR'] * $SFACTOR1 * $SFACTOR2 * $SFACTOR3;

        // PRESSURE_DROP();


        $vam_base = new VamBaseController();

        $pid_ft1 = $vam_base->PIPE_ID($this->calculation_values['PNB1']);
        $this->calculation_values['PIDE1'] = $pid_ft1['PID'];
        $this->calculation_values['FT1'] = $pid_ft1['FT'];

        $pid_ft2 = $vam_base->PIPE_ID($this->calculation_values['PNB2']);
        $this->calculation_values['PIDE2'] = $pid_ft2['PID'];
        $this->calculation_values['FT2'] = $pid_ft2['FT'];

        $pid_ft = $vam_base->PIPE_ID($this->calculation_values['PNB']);
        $this->calculation_values['PIDA'] = $pid_ft['PID'];
        $this->calculation_values['FT'] = $pid_ft['FT'];

        $this->PR_DROP_CHILL();

        $this->PR_DROP_COW();
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
                $tstout[$this->calculation_values['i']] = $this->calculation_values['TSTOUT1'];
            }
            if ($this->calculation_values['i'] == 2)
            {
                $tstout[$this->calculation_values['i']] = $tstout[$this->calculation_values['i'] - 1] + 0.5;
            }
            if($this->calculation_values['i'] >= 3)
            {
                $tstout[$this->calculation_values['i']] = $tstout[$this->calculation_values['i'] - 1] + $ferr12[$this->calculation_values['i'] - 1] * ($tstout[$this->calculation_values['i'] - 1] - $tstout[$this->calculation_values['i'] - 2]) / ($ferr12[$this->calculation_values['i'] - 2] - $ferr12[$this->calculation_values['i'] - 1]) / 2;
            }
            $this->calculation_values['TSTOUT'] = $tstout[$this->calculation_values['i']];
            $this->calculation_values['QHR'] = $this->calculation_values['GSTEAM'] * ($this->calculation_values['TSMIN'] - $this->calculation_values['TSTOUT']);
            $this->calculation_values['I20'] = $this->calculation_values['I21'] + ($this->calculation_values['QHR'] / $this->calculation_values['GDIL2']);
            $this->calculation_values['T20'] = $vam_base->LIBR_TEMPERATURE($this->calculation_values['XDIL'], $this->calculation_values['I20']);
            $this->calculation_values['LMTDHR'] = (($this->calculation_values['TSMIN'] - 5 - $this->calculation_values['T20']) - ($this->calculation_values['TSTOUT'] - $this->calculation_values['T21'])) / log(($this->calculation_values['TSMIN'] - 5 - $this->calculation_values['T20']) / ($this->calculation_values['TSTOUT'] - $this->calculation_values['T21']));
            $this->calculation_values['QLMTDHR'] = $this->calculation_values['UHR'] * $this->calculation_values['AHR'] * $this->calculation_values['LMTDHR'];
            $ferr12[$this->calculation_values['i']] = ($this->calculation_values['QHR'] - $this->calculation_values['QLMTDHR']) / $this->calculation_values['QHR'] * 100;
            $this->calculation_values['i'] = $this->calculation_values['i'] + 1;
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

        // if ($this->calculation_values['MODEL'] < 750 && $this->calculation_values['TU2'] == 0)                            //12.13.2011
        // {
        //     $VEVA = 0.7;
        // }
        // else 
        if (($this->calculation_values['MODEL'] < 1200 && ($this->calculation_values['TU2'] == 1 || $this->calculation_values['TU2'] == 2 || $this->calculation_values['TU2'] == 3)))
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
        if ($this->calculation_values['TU2'] < 2.1 && $this->calculation_values['MODEL'] < 750 && $this->calculation_values['VELEVA'] == 0)
        {
            $HI1 = $HI1 * 2;
        }
        //R = log($this->calculation_values['ODE'] / $this->calculation_values['IDE']) * $this->calculation_values['ODE'] / (2 * 340);
       // $HO = 1 / (1 / $this->calculation_values['KEVA'] - ($this->calculation_values['ODE'] / ($HI1 * $this->calculation_values['IDE'])) - R);

        if ($this->calculation_values['TU2'] == 2.0 || $this->calculation_values['TU2'] == 7)
            $R1 = log($this->calculation_values['ODE'] / $this->calculation_values['IDE']) * $this->calculation_values['ODE'] / (2 * 340);
        if ($this->calculation_values['TU2'] == 1.0 || $this->calculation_values['TU2'] == 6)
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

        if ($this->calculation_values['TU2'] < 2.1 && $this->calculation_values['MODEL'] < 750 && $this->calculation_values['VELEVA'] == 0)
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
        //R = log($this->calculation_values['ODA'] / $this->calculation_values['IDA']) * $this->calculation_values['ODA'] / (2 * 340);
        //HO = 1 / (1 / KABS - ($this->calculation_values['ODA'] / (HI1 * $this->calculation_values['IDA'])) - R);

        if ($this->calculation_values['TU5'] == 2.0)
            $R1 = log($this->calculation_values['ODA'] / $this->calculation_values['IDA']) * $this->calculation_values['ODA'] / (2 * 340);
        else if ($this->calculation_values['TU5'] == 1.0)
            $R1 = log($this->calculation_values['ODA'] / $this->calculation_values['IDA']) * $this->calculation_values['ODA'] / (2 * 37);
        else if ($this->calculation_values['TU5'] == 3.0 || $this->calculation_values['TU5'] == 4.0)
            $R1 = log($this->calculation_values['ODA'] / $this->calculation_values['IDA']) * $this->calculation_values['ODA'] / (2 * 21);
        else if ($this->calculation_values['TU5'] == 5.0)
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
        //R = log($this->calculation_values['ODA'] / $this->calculation_values['IDA']) * $this->calculation_values['ODA'] / (2 * 340);
       // HO = 1 / (1 / KABS - ($this->calculation_values['ODA'] / (HI1 * $this->calculation_values['IDA'])) - R);

        if ($this->calculation_values['TU5'] == 2.0)
            $R1 = log($this->calculation_values['ODA'] / $this->calculation_values['IDA']) * $this->calculation_values['ODA'] / (2 * 340);
        else if ($this->calculation_values['TU5'] == 1.0)
            $R1 = log($this->calculation_values['ODA'] / $this->calculation_values['IDA']) * $this->calculation_values['ODA'] / (2 * 37);
        else if ($this->calculation_values['TU5'] == 3.0 || $this->calculation_values['TU5'] == 4.0)
            $R1 = log($this->calculation_values['ODA'] / $this->calculation_values['IDA']) * $this->calculation_values['ODA'] / (2 * 21);
        else if ($this->calculation_values['TU5'] == 5.0)
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
        //R = log($this->calculation_values['ODC'] / $this->calculation_values['IDC']) * $this->calculation_values['ODC'] / (2 * 340);
        //HO = 1 / (1 / KCON - ($this->calculation_values['ODC'] / (HI1 * $this->calculation_values['IDC'])) - R);

        if ($this->calculation_values['TV5'] == 2.0)
            $R1 = log($this->calculation_values['ODC'] / $this->calculation_values['IDC']) * $this->calculation_values['ODC'] / (2 * 340);
        if ($this->calculation_values['TV5'] == 1.0)
            $R1 = log($this->calculation_values['ODC'] / $this->calculation_values['IDC']) * $this->calculation_values['ODC'] / (2 * 37);
        if ($this->calculation_values['TV5'] == 3.0 || $this->calculation_values['TV5'] == 4.0)
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
 


	    // if ($model_number < 1200)
	    // {
	    //     $VAMIN = 1.33;			//Velocity limit reduced to accomodate more range of cow flow
	    //     $VAMAX = 2.65;
	    //     if ($model_number > 950)
	    //     {
	    //         $VCMIN = 1.0;
	    //         $VCMAX = 2.78;
	    //     }
	    //     else
	    //     {
	    //         $VCMIN = 1.0;			
	    //         $VCMAX = 2.65;
	    //     }                
	    // }
	    // else
	    // {
	    //     $VAMIN = 1.39;
	    //     $VAMAX = 2.78;
	    //     $VCMIN = 1.00;
	    //     $VCMAX = 2.78;
	    // }


	    $GCWMIN = 3.141593 / 4 * $IDC * $IDC * $VCMIN * $TNC * 3600 / $TCP;		//min required flow in condenser
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

	   	// $range_values = array_sort($range_values);


	   	// for ($i=0; $i < $INIT; $i++) { 
	   	// 	$range_values .= "(".$FMIN[$i]." - ".$FMAX[$i].")<br>";
	   	// }

	   	$this->model_values['cooling_water_ranges'] = $range_values;

	    return array('status' => true,'msg' => "process run successfully");
	}

    public function CONVERGENCE()
    {
        $CC = array();
        $j = 0;

        $CC[0][0] = $this->calculation_values['GCHW'] * $this->calculation_values['CHGLY_ROW12'] * $this->calculation_values['CHGLY_SPHT12'] * ($this->calculation_values['TCHW1H'] - $this->calculation_values['TCHW2H']) / 4187;                        //EVAPORATORH
        $CC[1][0] = $this->calculation_values['UEVAH'] * $this->calculation_values['AEVAH'] * $this->calculation_values['LMTDEVAH'];
        $CC[2][0] = ($this->calculation_values['GREFH'] * ($this->calculation_values['J1H'] - $this->calculation_values['I3'])) - ($this->calculation_values['GREFL'] * ($this->calculation_values['I3'] - $this->calculation_values['I1H']));

        $CC[0][1] = $this->calculation_values['GCWAH'] * $this->calculation_values['COGLY_ROWH1'] * (($this->calculation_values['TCW2H'] * $this->calculation_values['COGLY_SPHT2H']) - ($this->calculation_values['TCW1H'] * $this->calculation_values['COGLY_SPHT1H'])) / 4187;  //ABSORBERH

        $CC[1][1] = $this->calculation_values['UABSH'] * $this->calculation_values['AABSH'] * $this->calculation_values['LMTDABSH'];
        $CC[2][1] = $this->calculation_values['GREFH'] * $this->calculation_values['J1H'] + $this->calculation_values['GCONCH'] * $this->calculation_values['I2L'] - $this->calculation_values['GDIL'] * $this->calculation_values['I2'];

        $CC[0][2] = $this->calculation_values['GCWC'] * $this->calculation_values['COGLY_ROWH1'] * (($this->calculation_values['TCW4'] * $this->calculation_values['COGLY_SPHT4']) - ($this->calculation_values['TCW3'] * $this->calculation_values['COGLY_SPHT3'])) / 4187;       //CONDENSER

        $CC[1][2] = $this->calculation_values['UCON'] * $this->calculation_values['ACON'] * $this->calculation_values['LMTDCON'];
        $CC[2][2] = $this->calculation_values['GREF1'] * ($this->calculation_values['I22'] - $this->calculation_values['J9']) + $this->calculation_values['GREF'] * ($this->calculation_values['J9'] - $this->calculation_values['I3']);

        $CC[0][3] = $this->calculation_values['GREF1'] * ($this->calculation_values['J4'] - $this->calculation_values['I13']);                                                                  //LTG
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

        $CC[0][8] = $this->calculation_values['GCWAL'] * $this->calculation_values['COGLY_ROWH1'] * (($this->calculation_values['TCW2L'] * $this->calculation_values['COGLY_SPHT2L']) - ($this->calculation_values['TCW1L'] * $this->calculation_values['COGLY_SPHT1L'])) / 4187;  //ABSORBERL

        $CC[1][8] = $this->calculation_values['UABSL'] * $this->calculation_values['AABSL'] * $this->calculation_values['LMTDABSL'];
        $CC[2][8] = $this->calculation_values['GCONC'] * $this->calculation_values['I8'] + $this->calculation_values['GREFL'] * $this->calculation_values['J1L'] - $this->calculation_values['GDILL'] * $this->calculation_values['I2L'];

        $CC[0][9] = $this->calculation_values['GDIL2'] * ($this->calculation_values['I21'] - $this->calculation_values['I2']);                                                                  //DHE
        $CC[1][9] = $this->calculation_values['UDHE'] * $this->calculation_values['ADHE'] * $this->calculation_values['LMTDDHE'];
        $CC[2][9] = $this->calculation_values['GREF1'] * ($this->calculation_values['I13'] - $this->calculation_values['I22']);

        $CC[0][10] = $this->calculation_values['QHR'];                                                                                //HR
        $CC[1][10] = $this->calculation_values['GDIL2'] * ($this->calculation_values['I20'] - $this->calculation_values['I21']);
        $CC[2][10] = $this->calculation_values['UHR'] * $this->calculation_values['AHR'] * $this->calculation_values['LMTDHR'];

        for ($j = 0; $j < 11; $j++)
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

        $HEATIN = ($this->calculation_values['TON'] * 3024) + $this->calculation_values['QHTG'] + $this->calculation_values['QHR'];
        $HEATOUT = ($this->calculation_values['GCWAH'] * $this->calculation_values['COGLY_ROWH1'] * (($this->calculation_values['TCW2H'] * $this->calculation_values['COGLY_SPHT2H']) - ($this->calculation_values['TCW1H'] * $this->calculation_values['COGLY_SPHT1H'])) / 4187)+($this->calculation_values['GCWAL'] * $this->calculation_values['COGLY_ROWH1'] * (($this->calculation_values['TCW2L'] * $this->calculation_values['COGLY_SPHT2L']) - ($this->calculation_values['TCW1L'] * $this->calculation_values['COGLY_SPHT1L'])) / 4187)+($this->calculation_values['GCWC'] * $this->calculation_values['COGLY_ROWH1'] * (($this->calculation_values['TCW4'] * $this->calculation_values['COGLY_SPHT4']) - ($this->calculation_values['TCW3'] * $this->calculation_values['COGLY_SPHT3'])) / 4187);
        $this->calculation_values['HBERROR'] = ($HEATIN - $HEATOUT) / $HEATIN * 100;
    }

    public function RESULT_CALCULATE(){
        $notes = array();
        $this->calculation_values['Notes'] = "";

        if ($this->calculation_values['T13'] > $this->calculation_values['AT13'])
        {   
            $this->calculation_values['Result'] = "FAILED";
            $this->calculation_values['Notes'] = $this->notes['NOTES_FAIL_TEMP'];
            return false;
        }
        if (!$this->CONCHECK())
        {
            //if (TAP == 1)
            //{
            //    chiller.Notes = new string[] {LocalizedNote(NOTES_RED_CW_FLOW)};
            //}
            $this->calculation_values['Result'] = "FAILED";
            
            return false;
        }

        $this->calculation_values['COP'] = ($this->calculation_values['TON'] * 3024) / ($this->calculation_values['GSTEAM'] * ($this->calculation_values['HSTEAM'] - 90));

        if ($this->calculation_values['COP'] > 1.53)
        {
            $this->HEATBALANCE1();

            $this->calculation_values['HeatInput'] = $this->calculation_values['TON'] * 3024 / 1.53;
            $this->calculation_values['HeatRejected'] = $this->calculation_values['TON'] * 3024 + $this->calculation_values['TON'] * 3024 / 1.53;

            $this->calculation_values['CoolingWaterOutTemperature'] = TCWA4;
            $this->calculation_values['SteamConsumption'] = $this->calculation_values['HeatInput'] / ($this->calculation_values['HSTEAM'] - 90.0);

            $this->calculation_values['GSTEAM'] = $this->calculation_values['SteamConsumption'];

            $this->calculation_values['COP'] = ($this->calculation_values['TON'] * 3024) / ($this->calculation_values['GSTEAM'] * ($this->calculation_values['HSTEAM'] - 90));
        }
        else
        {
            $this->HEATBALANCE();

            $this->calculation_values['HeatInput'] = $this->calculation_values['GSTEAM'] * ($this->calculation_values['HSTEAM'] - 90.0);
            $this->calculation_values['HeatRejected'] = $this->calculation_values['TON'] * 3024 + $this->calculation_values['GSTEAM'] * ($this->calculation_values['HSTEAM'] - 90.0);

            $this->calculation_values['CoolingWaterOutTemperature'] = round($this->calculation_values['TCWA4'],1);
            $this->calculation_values['SteamConsumption'] = round($this->calculation_values['GSTEAM'],1);

            $this->calculation_values['COP'] = ($this->calculation_values['TON'] * 3024) / ($this->calculation_values['GSTEAM'] * ($this->calculation_values['HSTEAM'] - 90));
         }

        //Assign the output properties of chiller

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


        $this->calculation_values['Result'] = "FAILED";

        if ($this->calculation_values['CW'] == 2)
        {
            array_push($notes,$this->notes['NOTES_COWIL_COND']);

        }
        if ($this->calculation_values['PST1'] < 6.01)
        {
            array_push($notes,$this->notes['NOTES_LTHE_PRDROP']);
                       
        }
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
                
        if ($this->calculation_values['TUU'] == "ari")
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

        if ($this->calculation_values['Result'] == "FAILED" && $this->calculation_values['TAP'] == 1)
        {
            array_push($notes,$this->notes['NOTES_RED_CW_FLOW']);

        }

        $this->calculation_values['notes'] = $notes;
        
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
                            if ($this->calculation_values['PS'] > $this->calculation_values['PST1'])
                            {
                                // $this->calculation_values['Notes'] = "NOTES_FAIL_SDPRESS";
                                $this->calculation_values['Notes'] = $this->notes['NOTES_FAIL_SPRESS'] . round(($this->calculation_values['PS'] + 0.05), 2) . " kg/sq.cm";
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
        else if (!isset($this->calculation_values['LMTDCON']) || $this->calculation_values['LMTDCON'] < 0)
        {
            return false;
        }
        else if (!isset($this->calculation_values['LMTDHTG']) || $this->calculation_values['LMTDHTG'] < 0)
        {
            return false;
        }
        else if (!isset($this->calculation_values['LMTDLTG']) || $this->calculation_values['LMTDLTG'] < 0)
        {
            return false;
        }
        else if (!isset($this->calculation_values['LMTDLTHE']) || $this->calculation_values['LMTDLTHE'] < 0)
        {
            return false;
        }
        else if (!isset($this->calculation_values['LMTDHTHE']) || $this->calculation_values['LMTDHTHE'] < 0)
        {
            return false;
        }
        else if (!isset($this->calculation_values['LMTDDHE']) || $this->calculation_values['LMTDDHE'] < 0)
        {
            return false;
        }
        else if (!isset($this->calculation_values['LMTDHR']) || $this->calculation_values['LMTDHR'] < 0)
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

    public function SEPARATION_HEIGHT_HTG()
    {
        $this->calculation_values['HTG_HSEP_DS'] = 0; $this->calculation_values['HTG_HSEP_DS_REQ'] = 0; 


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
        //ELIMINATOR AREA FOR VAP FLOW IN LTG, WITH REVISED ELIMINATOR WIDTH FOR THU SERIES
        $this->calculation_values['LTG_HSEP_DS_REQ'] = 0;
        
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
    public function HEATBALANCE1() 
    {
        if ($this->calculation_values['MODEL'] < 750.0)
        {
            if ($this->calculation_values['TCHW12'] < 6.699 &&$this->calculation_values['TCHW12']  > 4.99)
                $this->calculation_values['KM1']  = 1.8824 - 0.1765 * $this->calculation_values['TCHW12'];
            else
            {
                if ($this->calculation_values['TCHW12'] <= 4.99 &&$this->calculation_values['TCHW12']  > 4.5)
                    $this->calculation_values['KM1'] = 1.0;
                else
                {
                    if ($this->calculation_values['TCHW12'] <= 4.5 && $this->calculation_values['TCHW12'] > 3.49)
                        $this->calculation_values['KM1'] = 1.0 + (4.5 - $this->calculation_values['TCHW12']) * 0.2;
                    else
                    {
                        if ($this->calculation_values['TCHW12'] < 3.5)
                        {
                            $this->calculation_values['KM1'] = 1.2;
                        }
                        else
                        {
                            $this->calculation_values['KM1'] = 0.7;
                        }
                    }
                }
            }
           $this->calculation_values['GCWMIN1']  =$this->calculation_values['TON']  * $this->calculation_values['KM1'];
        }
        else
        {

            if ($this->calculation_values['TCHW12'] < 6.699 && $this->calculation_values['TCHW12'] > 4.99)
                $this->calculation_values['KM1'] = 1.8824 - 0.1765 * $this->calculation_values['TCHW12'];
            else
            {
                if ($this->calculation_values['TCHW12'] <= 4.99 && $this->calculation_values['TCHW12'] > 4.5)
                    $this->calculation_values['KM1'] = 1.0;
                else
                {
                    if ($this->calculation_values['TCHW12'] <= 4.5 && $this->calculation_values['TCHW12'] > 3.49)
                        $this->calculation_values['KM1'] = 1.0 + (4.5 - $this->calculation_values['TCHW12']) * 0.2;
                    else
                    {
                        if ($this->calculation_values['TCHW12'] < 3.5)
                        {
                            $this->calculation_values['KM1'] = 1.2;
                        }
                        else
                        {
                            $this->calculation_values['KM1'] = 0.7;
                        }
                    }
                }
            }
            $this->calculation_values['GCWMIN1'] =$this->calculation_values['TON']  * $this->calculation_values['KM1'];
        }
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

            $this->calculation_values['QCW'] = $this->calculation_values['GCW'] * $this->calculation_values['COGLY_ROWH'] * ($this->calculation_values['COGLY_SPHTA4'] + $COGLY_SPHT11) * 0.5 * ($this->calculation_values['TCWA4'] - $this->calculation_values['TCW11']) / 4187;
            $this->calculation_values['QINPUT'] = ($this->calculation_values['TON'] * 3024) + ($this->calculation_values['GSTEAM'] * ($this->calculation_values['HSTEAM'] - 90));
            $herr[$ii] = ($this->calculation_values['QINPUT'] - $this->calculation_values['QCW']) * 100 / $this->calculation_values['QINPUT'];
            $ii++;
        }

        $jj = 1;
        $this->calculation_values['COGLY_SPHT'] = 0; $this->calculation_values['COGLY_SPHTS'] = 0;
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
                $this->calculation_values['COGLY_SPHT'] = $vam_base->EG_SPHT(40, $this->calculation_values['COGLY']) * 1000;
                $this->calculation_values['COGLY_SPHTS'] = $vam_base->EG_SPHT($this->calculation_values['TCWS'], $this->calculation_values['COGLY']) * 1000;
            }
            else
            {
                $this->calculation_values['COGLY_ROWH'] = $vam_base->PG_ROW(40, $this->calculation_values['COGLY']);
                $this->calculation_values['COGLY_SPHT'] = $vam_base->PG_SPHT(40, $this->calculation_values['COGLY']) * 1000;
                $this->calculation_values['COGLY_SPHTS'] = $vam_base->PG_SPHT($this->calculation_values['TCWS'], $this->calculation_values['COGLY']) * 1000;
            }

            $QCWS = $this->calculation_values['GCW'] * $this->calculation_values['COGLY_ROWH'] * ($this->calculation_values['COGLY_SPHTS'] + $this->calculation_values['COGLY_SPHT']) * 0.5 * ($this->calculation_values['TCWS'] - 40) / 4187;
            $this->calculation_values['QINPUTS'] = ($this->calculation_values['TON'] * 1512) + ($this->calculation_values['GSTEAM'] * ($this->calculation_values['HSTEAM'] - 90) * 0.75);
            $herr1[$jj] = ($this->calculation_values['QINPUTS'] - $QCWS) * 100 / $this->calculation_values['QINPUTS'];
            $jj++;
        }
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

    public function getMetallurgyValues($type){
        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$this->model_code)
                                        ->where('min_model','<=',(int)$this->model_values['model_number'])->where('max_model','>',(int)$this->model_values['model_number'])->first();

        $chiller_options = $chiller_metallurgy_options->chillerOptions;
        
        if($type = 'eva'){
            $option = $chiller_options->where('type', 'eva')->where('value',$this->model_values['evaporator_material_value'])->first();
        }
        elseif ($type = 'abs') {
            $option = $chiller_options->where('type', 'abs')->where('value',$this->model_values['absorber_material_value'])->first();
        }
        else{
            $option = $chiller_options->where('type', 'con')->where('value',$this->model_values['condenser_material_value'])->first();
        }
        

        $metallurgy = $option->metallurgy;
        return $metallurgy;
    }


    
	// public function getEvaporatorOptions($model_number){
	// 	$eva_options = array();
	// 	$model_number = floatval($model_number);

	// 	if($model_number < 750){
	// 		$eva_options[] = array('name' => 'CuNi (90:10,95:5) Finned','value' => '1');
	// 		$eva_options[] = array('name' => 'Cu Finned','value' => '2');
	// 	}
	// 	else{
	// 		$eva_options[] = array('name' => 'CuNi (90:10,95:5) Mini Finned','value' => '1');
	// 		$eva_options[] = array('name' => 'Cu Mini Finned','value' => '2');
	// 	}

	// 	$eva_options[] = array('name' => 'SS Finned','value' => '3');
	// 	$eva_options[] = array('name' => 'SS Mini Finned','value' => '4');
	// 	$eva_options[] = array('name' => 'Titanium Plain','value' => '5');

	// 	return $eva_options;

	// }

	// public function getAbsorberOptions(){
	// 	$abs_options = array();

	// 	$abs_options[] = array('name' => 'CuNi (90:10,95:5) Mini Finned','value' => '1');
	// 	$abs_options[] = array('name' => 'Cu Mini Finned','value' => '2');
	// 	$abs_options[] = array('name' => 'SS Plain ERW','value' => '5');
	// 	$abs_options[] = array('name' => 'SS Mini finned','value' => '6');
	// 	$abs_options[] = array('name' => 'Titanium Plain','value' => '7');

	// 	return $abs_options;

	// }

	// public function getCondenserOptions(){
	// 	$con_options = array();

	// 	$con_options[] = array('name' => 'CuNi (90:10,95:5) Mini Finned','value' => '1');
	// 	$con_options[] = array('name' => 'Cu Mini Finned','value' => '2');
	// 	$con_options[] = array('name' => 'SS Plain ERW','value' => '3');
	// 	$con_options[] = array('name' => 'SS Mini finned','value' => '4');
	// 	$con_options[] = array('name' => 'Titanium Plain','value' => '5');

	// 	return $con_options;

	// }


	public function getChillerData()
	{


        $chiller_calculation_values = ChillerCalculationValue::where('code',$this->model_code)->where('min_model',$this->model_values['model_number'])->first();
        
        $calculation_values = $chiller_calculation_values->calculation_values;
        $calculation_values = json_decode($calculation_values,true);

        return $calculation_values;


	}

    // public function getConstantData()
    // {

    //     return array('VEMIN1' => '0.9','TEPMAX' => '4','m_maxCHWWorkPressure' => 8,'m_maxCOWWorkPressure' => 8,'m_maxHWWorkPressure' => 8,'m_maxSteamWorkPressure' => 10.5,'m_maxSteamDesignPressure' => 10,'m_DesignPressure' => 10.5,'m_maxHWDesignPressure' =>8,'m_dCondensateDrainPressure' =>1,'m_dMinCondensateDrainTemperature' =>80,'m_dMaxCondensateDrainTemperature' =>100);

    // }

    

    public function loadSpecSheetData(){
        $model_number = floatval($this->calculation_values['MODEL']);

        switch ($model_number) {
            case 60:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC S2 M1";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC S2 M1";
                }

                if($this->calculation_values['region_type'] == 2 || $this->calculation_values['region_type'] == 3)
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

                break;

            case 75:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC S2 M2";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC S2 M2";
                }

                if($this->calculation_values['region_type'] == 2 || $this->calculation_values['region_type'] == 3)
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

                break;    

            case 90:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC S2 N1";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC S2 N1";
                }

                if($this->calculation_values['region_type'] == 2 || $this->calculation_values['region_type'] == 3)
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

                break;     

            case 110:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC S2 N2";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC S2 N2";
                }

                if($this->calculation_values['region_type'] == 2 || $this->calculation_values['region_type'] == 3)
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

                break;     

            case 150:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC S2 N3";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC S2 N3";
                }

                if($this->calculation_values['region_type'] == 2 || $this->calculation_values['region_type'] == 3)
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

                break;      

            case 175:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC S2 N4";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC S2 N4";
                }

                if($this->calculation_values['region_type'] == 2 || $this->calculation_values['region_type'] == 3)
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

                break;     


            case 210:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC S2 P1";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC S2 P1";
                }

                if($this->calculation_values['region_type'] == 2 || $this->calculation_values['region_type'] == 3)
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

                break;     

            case 250:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    $this->model_values['model_name'] = "TZC S2 P2";
                }
                else
                {
                    $this->model_values['model_name'] = "TAC S2 P2";
                }

                if($this->calculation_values['region_type'] == 2 || $this->calculation_values['region_type'] == 3)
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

                break;  

            case 310:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    if ($this->calculation_values['PST1'] < 6.01)
                    {
                        $this->model_values['model_name'] = "TZC S2 D3 N";
                    }
                    else
                    {
                        $this->model_values['model_name'] = "TZC S2 D3";
                    }
                }
                else
                {
                    if ($this->calculation_values['PST1'] < 6.01)
                    {
                        $this->model_values['model_name'] = "TAC S2 D3 N";
                    }
                    else
                    {
                        $this->model_values['model_name'] = "TAC S2 D3";
                    }
                }


                if($this->calculation_values['region_type'] == 2 || $this->calculation_values['region_type'] == 3)
                {

                    if ($this->calculation_values['TCHW12'] < 3.5)
                    {
                        $this->model_values['model_name'] = "TZC S2 D3";                        
                    }
                    else
                    {
                        $this->model_values['model_name'] = "TAC S2 D3";                        
                    }    


                    $DryWeight1 = $this->calculation_values['DryWeight'] * $this->calculation_values['EX_DryWeight'];

                    $ex_DryWeight =  $DryWeight1 - $this->calculation_values['DryWeight'] ;

                    $this->calculation_values['DryWeight'] = $DryWeight1; 
                    $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + $ex_DryWeight;
                    $this->calculation_values['OperatingWeight'] =$this->calculation_values['OperatingWeight'] + $ex_DryWeight;
                    $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + $ex_DryWeight;
                }
                else{
                    if ($this->calculation_values['PST1'] < 6.01)
                    {
                        $this->calculation_values['Length'] = $this->calculation_values['Length'] + 50;
                        $this->calculation_values['Width'] = $this->calculation_values['Width'] + 50;
                        $this->calculation_values['Height'] = $this->calculation_values['Height'] + 50;
                        // $this->calculation_values['ClearanceForTubeRemoval'] = 3710;

                        $this->calculation_values['DryWeight'] = $this->calculation_values['DryWeight'] + 0.1;
                        $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + 0.1;
                        $this->calculation_values['OperatingWeight'] = $this->calculation_values['OperatingWeight'] + 0.1;
                        $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + 0.1;

                    }
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

                break;
            case 350:
                if ($this->calculation_values['TCHW12'] < 3.5)
                {
                    if ($this->calculation_values['PST1'] < 6.01)
                    {
                        $this->model_values['model_name'] = "TZC S2 D4 N";
                    }
                    else
                    {
                        $this->model_values['model_name'] = "TZC S2 D4";
                    }
                }
                else
                {
                    if ($this->calculation_values['PST1'] < 6.01)
                    {
                        $this->model_values['model_name'] = "TAC S2 D4 N";
                    }
                    else
                    {
                        $this->model_values['model_name'] = "TAC S2 D4";
                    }
                }


                if($this->calculation_values['region_type'] == 2 || $this->calculation_values['region_type'] == 3)
                {



                    if ($this->calculation_values['TCHW12'] < 3.5)
                    {
                        $this->model_values['model_name'] = "TZC S2 D4";                        
                    }
                    else
                    {
                        $this->model_values['model_name'] = "TAC S2 D4";                        
                    }

                    $DryWeight1 = $this->calculation_values['DryWeight'] * $this->calculation_values['EX_DryWeight'];

                    $ex_DryWeight =  $DryWeight1 - $this->calculation_values['DryWeight'] ;

                    $this->calculation_values['DryWeight'] = $DryWeight1; 
                    $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + $ex_DryWeight;
                    $this->calculation_values['OperatingWeight'] =$this->calculation_values['OperatingWeight'] + $ex_DryWeight;
                    $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + $ex_DryWeight;
                }
                else{
                    if ($this->calculation_values['PST1'] < 6.01)
                    {
                        $this->calculation_values['Length'] = $this->calculation_values['Length'] + 50;
                        $this->calculation_values['Width'] = $this->calculation_values['Width'] + 50;
                        $this->calculation_values['Height'] = $this->calculation_values['Height'] + 50;
                        // $this->calculation_values['ClearanceForTubeRemoval'] = 3710;

                        $this->calculation_values['DryWeight'] = $this->calculation_values['DryWeight'] + 0.1;
                        $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + 0.1;
                        $this->calculation_values['OperatingWeight'] = $this->calculation_values['OperatingWeight'] + 0.1;
                        $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + 0.1;

                    }
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

                
                break;
            case 410:
                    if ($this->calculation_values['TCHW12'] < 3.5)
                    {
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->model_values['model_name'] = "TZC S2 E1 N";
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TZC S2 E1";
                        }
                    }
                    else
                    {
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->model_values['model_name'] = "TAC S2 E1 N";
                        }
                        else
                        {
                           $this->model_values['model_name'] = "TAC S2 E1";
                        }
                    }

                    if($this->calculation_values['region_type'] == 2|| $this->calculation_values['region_type'] == 3 )
                    {

                        if ($this->calculation_values['TCHW12'] < 3.5)
                        {
                            $this->model_values['model_name'] = "TZC S2 E1";                        
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TAC S2 E1";                        
                        }

                        $DryWeight1 = $this->calculation_values['DryWeight'] * $this->calculation_values['EX_DryWeight'];

                        $ex_DryWeight =  $DryWeight1 - $this->calculation_values['DryWeight'] ;

                        $this->calculation_values['DryWeight'] = $DryWeight1; 
                        $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + $ex_DryWeight;
                        $this->calculation_values['OperatingWeight'] =$this->calculation_values['OperatingWeight'] + $ex_DryWeight;
                        $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + $ex_DryWeight;
                    }
                    else{
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->calculation_values['Length'] = $this->calculation_values['Length'] + 50;
                            $this->calculation_values['Width'] = $this->calculation_values['Width'] + 50;
                            $this->calculation_values['Height'] = $this->calculation_values['Height'] + 50;
                            // $this->calculation_values['ClearanceForTubeRemoval'] = 3710;

                            $this->calculation_values['DryWeight'] = $this->calculation_values['DryWeight'] + 0.1;
                            $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + 0.1;
                            $this->calculation_values['OperatingWeight'] = $this->calculation_values['OperatingWeight'] + 0.1;
                            $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + 0.1;

                        }
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

                  
                    break;

                case 470:
                    if ($this->calculation_values['TCHW12'] < 3.5)
                    {
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->model_values['model_name'] = "TZC S2 E2 N";
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TZC S2 E2";
                        }
                    }
                    else
                    {
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->model_values['model_name'] = "TAC S2 E2 N";
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TAC S2 E2";
                        }
                    }
                    if($this->calculation_values['region_type'] == 2 || $this->calculation_values['region_type'] == 3)
                    {


                        if ($this->calculation_values['TCHW12'] < 3.5)
                        {
                            $this->model_values['model_name'] = "TZC S2 E2";                        
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TAC S2 E2";                        
                        }

                        $DryWeight1 = $this->calculation_values['DryWeight'] * $this->calculation_values['EX_DryWeight'];

                        $ex_DryWeight =  $DryWeight1 - $this->calculation_values['DryWeight'] ;

                        $this->calculation_values['DryWeight'] = $DryWeight1; 
                        $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + $ex_DryWeight;
                        $this->calculation_values['OperatingWeight'] =$this->calculation_values['OperatingWeight'] + $ex_DryWeight;
                        $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + $ex_DryWeight;
                    }
                    else{
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->calculation_values['Length'] = $this->calculation_values['Length'] + 50;
                            $this->calculation_values['Width'] = $this->calculation_values['Width'] + 50;
                            $this->calculation_values['Height'] = $this->calculation_values['Height'] + 50;
                            // $this->calculation_values['ClearanceForTubeRemoval'] = 3710;

                            $this->calculation_values['DryWeight'] = $this->calculation_values['DryWeight'] + 0.1;
                            $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + 0.1;
                            $this->calculation_values['OperatingWeight'] = $this->calculation_values['OperatingWeight'] + 0.1;
                            $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + 0.1;

                        }
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

                    break;

                case 530:
                    if ($this->calculation_values['TCHW12'] < 3.5)
                    {
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->model_values['model_name'] = "TZC S2 E3 N";
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TZC S2 E3";
                        }
                    }
                    else
                    {
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                           $this->model_values['model_name'] = "TAC S2 E3 N";
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TAC S2 E3";
                        }
                    }
                    if($this->calculation_values['region_type'] == 2 || $this->calculation_values['region_type'] == 3)
                    {



                        if ($this->calculation_values['TCHW12'] < 3.5)
                        {
                            $this->model_values['model_name'] = "TZC S2 E3";                        
                        }
                        else
                        {
                           $this->model_values['model_name'] = "TAC S2 E3";                        
                        }

                        $DryWeight1 = $this->calculation_values['DryWeight'] * $this->calculation_values['EX_DryWeight'];

                        $ex_DryWeight =  $DryWeight1 - $this->calculation_values['DryWeight'] ;

                        $this->calculation_values['DryWeight'] = $DryWeight1; 
                        $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + $ex_DryWeight;
                        $this->calculation_values['OperatingWeight'] =$this->calculation_values['OperatingWeight'] + $ex_DryWeight;
                        $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + $ex_DryWeight;
                    }
                    else{
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->calculation_values['Length'] = $this->calculation_values['Length'] + 50;
                            $this->calculation_values['Width'] = $this->calculation_values['Width'] + 50;
                            $this->calculation_values['Height'] = $this->calculation_values['Height'] + 50;
                            // $this->calculation_values['ClearanceForTubeRemoval'] = 3710;

                            $this->calculation_values['DryWeight'] = $this->calculation_values['DryWeight'] + 0.1;
                            $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + 0.1;
                            $this->calculation_values['OperatingWeight'] = $this->calculation_values['OperatingWeight'] + 0.1;
                            $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + 0.1;

                        }
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
                    break;

                case 580:
                    if ($this->calculation_values['TCHW12'] < 3.5)
                    {
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->model_values['model_name'] = "TZC S2 E4 N";
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TZC S2 E4";
                        }
                    }
                    else
                    {
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->model_values['model_name'] = "TAC S2 E4 N";
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TAC S2 E4";
                        }
                    }
                    if($this->calculation_values['region_type'] == 2 || $this->calculation_values['region_type'] == 3)
                    {

                        if ($this->calculation_values['TCHW12'] < 3.5)
                        {
                            $this->model_values['model_name'] = "TZC S2 E4";                        
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TAC S2 E4";                       
                        }

                        $DryWeight1 = $this->calculation_values['DryWeight'] * $this->calculation_values['EX_DryWeight'];

                        $ex_DryWeight =  $DryWeight1 - $this->calculation_values['DryWeight'] ;

                        $this->calculation_values['DryWeight'] = $DryWeight1; 
                        $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + $ex_DryWeight;
                        $this->calculation_values['OperatingWeight'] =$this->calculation_values['OperatingWeight'] + $ex_DryWeight;
                        $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + $ex_DryWeight;
                    }
                    else{
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->calculation_values['Length'] = $this->calculation_values['Length'] + 50;
                            $this->calculation_values['Width'] = $this->calculation_values['Width'] + 50;
                            $this->calculation_values['Height'] = $this->calculation_values['Height'] + 50;
                            // $this->calculation_values['ClearanceForTubeRemoval'] = 3710;

                            $this->calculation_values['DryWeight'] = $this->calculation_values['DryWeight'] + 0.1;
                            $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + 0.1;
                            $this->calculation_values['OperatingWeight'] = $this->calculation_values['OperatingWeight'] + 0.1;
                            $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + 0.1;

                        }
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

                    break;

                case 630:
                    if ($this->calculation_values['TCHW12'] < 3.5)
                    {
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->model_values['model_name'] = "TZC S2 E5 N";
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TZC S2 E5";
                        }
                    }
                    else
                    {
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->model_values['model_name'] = "TAC S2 E5 N";
                        }
                        else
                        {
                           $this->model_values['model_name'] = "TAC S2 E5";
                        }
                    }
                    if($this->calculation_values['region_type'] == 2 || $this->calculation_values['region_type'] == 3)
                    {

                        if ($this->calculation_values['TCHW12'] < 3.5)
                        {
                            $this->model_values['model_name'] = "TZC S2 E5";                        
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TAC S2 E5";                        
                        }

                        $DryWeight1 = $this->calculation_values['DryWeight'] * $this->calculation_values['EX_DryWeight'];

                        $ex_DryWeight =  $DryWeight1 - $this->calculation_values['DryWeight'] ;

                        $this->calculation_values['DryWeight'] = $DryWeight1; 
                        $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + $ex_DryWeight;
                        $this->calculation_values['OperatingWeight'] =$this->calculation_values['OperatingWeight'] + $ex_DryWeight;
                        $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + $ex_DryWeight;
                    }
                    else{
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->calculation_values['Length'] = $this->calculation_values['Length'] + 50;
                            $this->calculation_values['Width'] = $this->calculation_values['Width'] + 50;
                            $this->calculation_values['Height'] = $this->calculation_values['Height'] + 50;
                            // $this->calculation_values['ClearanceForTubeRemoval'] = 3710;

                            $this->calculation_values['DryWeight'] = $this->calculation_values['DryWeight'] + 0.1;
                            $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + 0.1;
                            $this->calculation_values['OperatingWeight'] = $this->calculation_values['OperatingWeight'] + 0.1;
                            $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + 0.1;

                        }
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

                    break;

                case 710:
                    if ($this->calculation_values['TCHW12'] < 3.5)
                    {
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->model_values['model_name'] = "TZC S2 E6 N";
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TZC S2 E6";
                        }
                    }
                    else
                    {
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->model_values['model_name'] = "TAC S2 E6 N";
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TAC S2 E6";
                        }
                    }
                    if($this->calculation_values['region_type'] == 2 || $this->calculation_values['region_type'] == 3)
                    {

                        if ($this->calculation_values['TCHW12'] < 3.5)
                        {
                            $this->model_values['model_name'] = "TZC S2 E6";                        
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TAC S2 E6";                        
                        }

                        $DryWeight1 = $this->calculation_values['DryWeight'] * $this->calculation_values['EX_DryWeight'];

                        $ex_DryWeight =  $DryWeight1 - $this->calculation_values['DryWeight'] ;

                        $this->calculation_values['DryWeight'] = $DryWeight1; 
                        $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + $ex_DryWeight;
                        $this->calculation_values['OperatingWeight'] =$this->calculation_values['OperatingWeight'] + $ex_DryWeight;
                        $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + $ex_DryWeight;
                    }
                    else{
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->calculation_values['Length'] = $this->calculation_values['Length'] + 50;
                            $this->calculation_values['Width'] = $this->calculation_values['Width'] + 50;
                            $this->calculation_values['Height'] = $this->calculation_values['Height'] + 50;
                            // $this->calculation_values['ClearanceForTubeRemoval'] = 3710;

                            $this->calculation_values['DryWeight'] = $this->calculation_values['DryWeight'] + 0.1;
                            $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + 0.1;
                            $this->calculation_values['OperatingWeight'] = $this->calculation_values['OperatingWeight'] + 0.1;
                            $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + 0.1;

                        }
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
                    break;

                case 760:
                    if ($this->calculation_values['TCHW12'] < 3.5)
                    {
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                           $this->model_values['model_name'] = "TZC S2 F1 N";
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TZC S2 F1";
                        }
                    }
                    else
                    {
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->model_values['model_name'] = "TAC S2 F1 N";
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TAC S2 F1";
                        }
                    }
                    if($this->calculation_values['region_type'] == 2 || $this->calculation_values['region_type'] == 3 )
                    {

                        if ($this->calculation_values['TCHW12'] < 3.5)
                        {
                            $this->model_values['model_name'] = "TZC S2 F1";
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TAC S2 F1";
                        }

                        $DryWeight1 = $this->calculation_values['DryWeight'] * $this->calculation_values['EX_DryWeight'];

                        $ex_DryWeight =  $DryWeight1 - $this->calculation_values['DryWeight'] ;

                        $this->calculation_values['DryWeight'] = $DryWeight1; 
                        $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + $ex_DryWeight;
                        $this->calculation_values['OperatingWeight'] =$this->calculation_values['OperatingWeight'] + $ex_DryWeight;
                        $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + $ex_DryWeight;
                    }
                    else{
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->calculation_values['Length'] = $this->calculation_values['Length'] + 50;
                            $this->calculation_values['Width'] = $this->calculation_values['Width'] + 50;
                            $this->calculation_values['Height'] = $this->calculation_values['Height'] + 50;
                            // $this->calculation_values['ClearanceForTubeRemoval'] = 3710;

                            $this->calculation_values['DryWeight'] = $this->calculation_values['DryWeight'] + 0.1;
                            $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + 0.1;
                            $this->calculation_values['OperatingWeight'] = $this->calculation_values['OperatingWeight'] + 0.1;
                            $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + 0.1;

                        }
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

                    break;

                case 810:
                    if ($this->calculation_values['TCHW12'] < 3.5)
                    {
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->model_values['model_name'] = "TZC S2 F2 N";
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TZC S2 F2";
                        }
                    }
                    else
                    {
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->model_values['model_name'] = "TAC S2 F2 N";
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TAC S2 F2";
                        }
                    }
                    if($this->calculation_values['region_type'] == 2 || $this->calculation_values['region_type'] == 3)
                    {

                        if ($this->calculation_values['TCHW12'] < 3.5)
                        {
                            $this->model_values['model_name'] = "TZC S2 F2";                        
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TAC S2 F2";                        
                        }

                        $DryWeight1 = $this->calculation_values['DryWeight'] * $this->calculation_values['EX_DryWeight'];

                        $ex_DryWeight =  $DryWeight1 - $this->calculation_values['DryWeight'] ;

                        $this->calculation_values['DryWeight'] = $DryWeight1; 
                        $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + $ex_DryWeight;
                        $this->calculation_values['OperatingWeight'] =$this->calculation_values['OperatingWeight'] + $ex_DryWeight;
                        $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + $ex_DryWeight;
                    }
                    else{
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->calculation_values['Length'] = $this->calculation_values['Length'] + 50;
                            $this->calculation_values['Width'] = $this->calculation_values['Width'] + 50;
                            $this->calculation_values['Height'] = $this->calculation_values['Height'] + 50;
                            // $this->calculation_values['ClearanceForTubeRemoval'] = 3710;

                            $this->calculation_values['DryWeight'] = $this->calculation_values['DryWeight'] + 0.1;
                            $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + 0.1;
                            $this->calculation_values['OperatingWeight'] = $this->calculation_values['OperatingWeight'] + 0.1;
                            $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + 0.1;

                        }
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

                    break;

                case 900:
                    if ($this->calculation_values['TCHW12'] < 3.5)
                    {
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->model_values['model_name'] = "TZC S2 F3 N";
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TZC S2 F3";
                        }
                    }
                    else
                    {
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->model_values['model_name'] = "TAC S2 F3 N";
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TAC S2 F3";
                        }
                    }
                    if($this->calculation_values['region_type'] == 2 || $this->calculation_values['region_type'] == 3)
                    {

                        if ($this->calculation_values['TCHW12'] < 3.5)
                        {
                            $this->model_values['model_name'] = "TZC S2 F3";                        
                        }
                        else
                        {
                           $this->model_values['model_name'] = "TAC S2 F3";                        
                        }

                        $DryWeight1 = $this->calculation_values['DryWeight'] * $this->calculation_values['EX_DryWeight'];

                        $ex_DryWeight =  $DryWeight1 - $this->calculation_values['DryWeight'] ;

                        $this->calculation_values['DryWeight'] = $DryWeight1; 
                        $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + $ex_DryWeight;
                        $this->calculation_values['OperatingWeight'] =$this->calculation_values['OperatingWeight'] + $ex_DryWeight;
                        $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + $ex_DryWeight;
                    }
                    else{
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->calculation_values['Length'] = $this->calculation_values['Length'] + 50;
                            $this->calculation_values['Width'] = $this->calculation_values['Width'] + 50;
                            $this->calculation_values['Height'] = $this->calculation_values['Height'] + 50;
                            // $this->calculation_values['ClearanceForTubeRemoval'] = 3710;

                            $this->calculation_values['DryWeight'] = $this->calculation_values['DryWeight'] + 0.1;
                            $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + 0.1;
                            $this->calculation_values['OperatingWeight'] = $this->calculation_values['OperatingWeight'] + 0.1;
                            $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + 0.1;

                        }
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

                    break;

                case 1010:
                    if ($this->calculation_values['TCHW12'] < 3.5)
                    {
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->model_values['model_name']  = "TZC S2 G1 N";
                        }
                        else
                        {
                           $this->model_values['model_name'] = "TZC S2 G1";
                        }
                    }
                    else
                    {
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                           $this->model_values['model_name'] = "TAC S2 G1 N";
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TAC S2 G1";
                        }
                    }
                    if($this->calculation_values['region_type'] == 2 || $this->calculation_values['region_type'] == 3)
                    {


                        if ($this->calculation_values['TCHW12'] < 3.5)
                        {
                            $this->model_values['model_name'] = "TZC S2 G1";                       
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TAC S2 G1";                        
                        }

                        $DryWeight1 = $this->calculation_values['DryWeight'] * $this->calculation_values['EX_DryWeight'];

                        $ex_DryWeight =  $DryWeight1 - $this->calculation_values['DryWeight'] ;

                        $this->calculation_values['DryWeight'] = $DryWeight1; 
                        $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + $ex_DryWeight;
                        $this->calculation_values['OperatingWeight'] =$this->calculation_values['OperatingWeight'] + $ex_DryWeight;
                        $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + $ex_DryWeight;
                    }
                    else{
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->calculation_values['Length'] = $this->calculation_values['Length'] + 50;
                            $this->calculation_values['Width'] = $this->calculation_values['Width'] + 50;
                            $this->calculation_values['Height'] = $this->calculation_values['Height'] + 50;
                            // $this->calculation_values['ClearanceForTubeRemoval'] = 3710;

                            $this->calculation_values['DryWeight'] = $this->calculation_values['DryWeight'] + 0.1;
                            $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + 0.1;
                            $this->calculation_values['OperatingWeight'] = $this->calculation_values['OperatingWeight'] + 0.1;
                            $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + 0.1;

                        }
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
                    break;

                case 1130:
                    if ($this->calculation_values['TCHW12'] < 3.5)
                    {
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                           $this->model_values['model_name'] = "TZC S2 G2 N";
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TZC S2 G2";
                        }
                    }
                    else
                    {
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                           $this->model_values['model_name'] = "TAC S2 G2 N";
                        }
                        else
                        {
                           $this->model_values['model_name'] = "TAC S2 G2";
                        }
                    }
                    if($this->calculation_values['region_type'] == 2 || $this->calculation_values['region_type'] == 3 )
                    {

                        if ($this->calculation_values['TCHW12'] < 3.5)
                        {
                            $this->model_values['model_name'] = "TZC S2 G2";                        
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TAC S2 G2";                        
                        }

                        $DryWeight1 = $this->calculation_values['DryWeight'] * $this->calculation_values['EX_DryWeight'];

                        $ex_DryWeight =  $DryWeight1 - $this->calculation_values['DryWeight'] ;

                        $this->calculation_values['DryWeight'] = $DryWeight1; 
                        $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + $ex_DryWeight;
                        $this->calculation_values['OperatingWeight'] =$this->calculation_values['OperatingWeight'] + $ex_DryWeight;
                        $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + $ex_DryWeight;
                    }
                    else{
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->calculation_values['Length'] = $this->calculation_values['Length'] + 50;
                            $this->calculation_values['Width'] = $this->calculation_values['Width'] + 50;
                            $this->calculation_values['Height'] = $this->calculation_values['Height'] + 50;
                            // $this->calculation_values['ClearanceForTubeRemoval'] = 3710;

                            $this->calculation_values['DryWeight'] = $this->calculation_values['DryWeight'] + 0.1;
                            $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + 0.1;
                            $this->calculation_values['OperatingWeight'] = $this->calculation_values['OperatingWeight'] + 0.1;
                            $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + 0.1;

                        }
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

                    break;

                case 1260:
                    if ($this->calculation_values['TCHW12'] < 3.5)
                    {
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->model_values['model_name'] = "TZC S2 G3 N";
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TZC S2 G3";
                        }
                    }
                    else
                    {
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->model_values['model_name'] = "TAC S2 G3 N";
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TAC S2 G3";
                        }
                    }
                    if($this->calculation_values['region_type'] == 2 || $this->calculation_values['region_type'] == 3)
                    {

                        if ($this->calculation_values['TCHW12'] < 3.5)
                        {
                            $this->model_values['model_name'] = "TZC S2 G3";                        
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TAC S2 G3";                        
                        }

                        $DryWeight1 = $this->calculation_values['DryWeight'] * $this->calculation_values['EX_DryWeight'];

                        $ex_DryWeight =  $DryWeight1 - $this->calculation_values['DryWeight'] ;

                        $this->calculation_values['DryWeight'] = $DryWeight1; 
                        $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + $ex_DryWeight;
                        $this->calculation_values['OperatingWeight'] =$this->calculation_values['OperatingWeight'] + $ex_DryWeight;
                        $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + $ex_DryWeight;
                    }
                    else{
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->calculation_values['Length'] = $this->calculation_values['Length'] + 50;
                            $this->calculation_values['Width'] = $this->calculation_values['Width'] + 50;
                            $this->calculation_values['Height'] = $this->calculation_values['Height'] + 50;
                            // $this->calculation_values['ClearanceForTubeRemoval'] = 3710;

                            $this->calculation_values['DryWeight'] = $this->calculation_values['DryWeight'] + 0.1;
                            $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + 0.1;
                            $this->calculation_values['OperatingWeight'] = $this->calculation_values['OperatingWeight'] + 0.1;
                            $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + 0.1;

                        }
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

                    break;

                case 1380:
                    if ($this->calculation_values['TCHW12'] < 3.5)
                    {
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                           $this->model_values['model_name'] = "TZC S2 G4 N";
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TZC S2 G4";
                        }
                    }
                    else
                    {
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->model_values['model_name'] = "TAC S2 G4 N";
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TAC S2 G4";
                        }
                    }
                    if($this->calculation_values['region_type'] == 2 || $this->calculation_values['region_type'] == 3)
                    {

                        if ($this->calculation_values['TCHW12'] < 3.5)
                        {
                            $this->model_values['model_name'] = "TZC S2 G4";                        
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TAC S2 G4";                        
                        }

                        $DryWeight1 = $this->calculation_values['DryWeight'] * $this->calculation_values['EX_DryWeight'];

                        $ex_DryWeight =  $DryWeight1 - $this->calculation_values['DryWeight'] ;

                        $this->calculation_values['DryWeight'] = $DryWeight1; 
                        $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + $ex_DryWeight;
                        $this->calculation_values['OperatingWeight'] =$this->calculation_values['OperatingWeight'] + $ex_DryWeight;
                        $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + $ex_DryWeight;
                    }
                    else{
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->calculation_values['Length'] = $this->calculation_values['Length'] + 50;
                            $this->calculation_values['Width'] = $this->calculation_values['Width'] + 50;
                            $this->calculation_values['Height'] = $this->calculation_values['Height'] + 50;
                            // $this->calculation_values['ClearanceForTubeRemoval'] = 3710;

                            $this->calculation_values['DryWeight'] = $this->calculation_values['DryWeight'] + 0.1;
                            $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + 0.1;
                            $this->calculation_values['OperatingWeight'] = $this->calculation_values['OperatingWeight'] + 0.1;
                            $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + 0.1;

                        }
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

                   
                    break;

                case 1560:
                    if ($this->calculation_values['TCHW12'] < 3.5)
                    {
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->model_values['model_name'] = "TZC S2 G5 N";
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TZC S2 G5";
                        }
                    }
                    else
                    {
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->model_values['model_name'] = "TAC S2 G5 N";
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TAC S2 G5";
                        }
                    }
                    if($this->calculation_values['region_type'] == 2 || $this->calculation_values['region_type'] == 3 )
                    {


                        if ($this->calculation_values['PST1'] < 3.5)
                        {
                            $this->model_values['model_name'] = "TZC S2 G5";                        
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TAC S2 G5";                        
                        }

                        $DryWeight1 = $this->calculation_values['DryWeight'] * $this->calculation_values['EX_DryWeight'];

                        $ex_DryWeight =  $DryWeight1 - $this->calculation_values['DryWeight'] ;

                        $this->calculation_values['DryWeight'] = $DryWeight1; 
                        $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + $ex_DryWeight;
                        $this->calculation_values['OperatingWeight'] =$this->calculation_values['OperatingWeight'] + $ex_DryWeight;
                        $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + $ex_DryWeight;
                    }
                    else{
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->calculation_values['Length'] = $this->calculation_values['Length'] + 50;
                            $this->calculation_values['Width'] = $this->calculation_values['Width'] + 50;
                            $this->calculation_values['Height'] = $this->calculation_values['Height'] + 50;
                            // $this->calculation_values['ClearanceForTubeRemoval'] = 3710;

                            $this->calculation_values['DryWeight'] = $this->calculation_values['DryWeight'] + 0.1;
                            $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + 0.1;
                            $this->calculation_values['OperatingWeight'] = $this->calculation_values['OperatingWeight'] + 0.1;
                            $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + 0.1;

                        }
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

                    break;

                case 1690:
                    if ($this->calculation_values['TCHW12'] < 3.5)
                    {
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->model_values['model_name'] = "TZC S2 G6 N";
                        }
                        else
                        {
                           $this->model_values['model_name'] = "TZC S2 G6";
                        }
                    }
                    else
                    {
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->model_values['model_name'] = "TAC S2 G6 N";
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TAC S2 G6";
                        }
                    }
                    if($this->calculation_values['region_type'] == 2 || $this->calculation_values['region_type'] == 3 )
                    {

                        if ($this->calculation_values['TCHW12'] < 3.5)
                        {
                            $this->model_values['model_name'] = "TZC S2 G6";                       
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TAC S2 G6";                        
                        }

                        $DryWeight1 = $this->calculation_values['DryWeight'] * $this->calculation_values['EX_DryWeight'];

                        $ex_DryWeight =  $DryWeight1 - $this->calculation_values['DryWeight'] ;

                        $this->calculation_values['DryWeight'] = $DryWeight1; 
                        $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + $ex_DryWeight;
                        $this->calculation_values['OperatingWeight'] =$this->calculation_values['OperatingWeight'] + $ex_DryWeight;
                        $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + $ex_DryWeight;
                    }
                    else{
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->calculation_values['Length'] = $this->calculation_values['Length'] + 50;
                            $this->calculation_values['Width'] = $this->calculation_values['Width'] + 50;
                            $this->calculation_values['Height'] = $this->calculation_values['Height'] + 50;
                            // $this->calculation_values['ClearanceForTubeRemoval'] = 3710;

                            $this->calculation_values['DryWeight'] = $this->calculation_values['DryWeight'] + 0.1;
                            $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + 0.1;
                            $this->calculation_values['OperatingWeight'] = $this->calculation_values['OperatingWeight'] + 0.1;
                            $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + 0.1;

                        }
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
                    break;

                case 1890:
                    if ($this->calculation_values['TCHW12'] < 3.5)
                    {
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->model_values['model_name'] = "TZC S2 H1 N";
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TZC S2 H1";
                        }
                    }
                    else
                    {
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->model_values['model_name'] = "TAC S2 H1 N";
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TAC S2 H1";
                        }
                    }

                    if($this->calculation_values['region_type'] == 2 || $this->calculation_values['region_type'] == 3)
                    {

                        if ($this->calculation_values['TCHW12'] < 3.5)
                        {
                            $this->model_values['model_name'] = "TZC S2 H1";
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TAC S2 H1";
                        }

                        $DryWeight1 = $this->calculation_values['DryWeight'] * $this->calculation_values['EX_DryWeight'];

                        $ex_DryWeight =  $DryWeight1 - $this->calculation_values['DryWeight'] ;

                        $this->calculation_values['DryWeight'] = $DryWeight1; 
                        $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + $ex_DryWeight;
                        $this->calculation_values['OperatingWeight'] =$this->calculation_values['OperatingWeight'] + $ex_DryWeight;
                        $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + $ex_DryWeight;
                    }
                    else{
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->calculation_values['Length'] = $this->calculation_values['Length'] + 50;
                            $this->calculation_values['Width'] = $this->calculation_values['Width'] + 50;
                            $this->calculation_values['Height'] = $this->calculation_values['Height'] + 50;
                            // $this->calculation_values['ClearanceForTubeRemoval'] = 3710;

                            $this->calculation_values['DryWeight'] = $this->calculation_values['DryWeight'] + 0.1;
                            $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + 0.1;
                            $this->calculation_values['OperatingWeight'] = $this->calculation_values['OperatingWeight'] + 0.1;
                            $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + 0.1;

                        }
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

                    break;

                case 2130:
                    if ($this->calculation_values['TCHW12'] < 3.5)
                    {
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->model_values['model_name'] = "TZC S2 H2 N";
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TZC S2 H2";
                        }
                    }
                    else
                    {
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->model_values['model_name'] = "TAC S2 H2 N";
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TAC S2 H2";
                        }
                    }
                    if($this->calculation_values['region_type'] == 2 || $this->calculation_values['region_type'] == 3)
                    {
                        if ($this->calculation_values['TCHW12'] < 3.5)
                        {
                            $this->model_values['model_name'] = "TZC S2 H2";
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TAC S2 H2";
                        }

                        $DryWeight1 = $this->calculation_values['DryWeight'] * $this->calculation_values['EX_DryWeight'];

                        $ex_DryWeight =  $DryWeight1 - $this->calculation_values['DryWeight'] ;

                        $this->calculation_values['DryWeight'] = $DryWeight1; 
                        $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + $ex_DryWeight;
                        $this->calculation_values['OperatingWeight'] =$this->calculation_values['OperatingWeight'] + $ex_DryWeight;
                        $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + $ex_DryWeight;
                    }
                    else{
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->calculation_values['Length'] = $this->calculation_values['Length'] + 50;
                            $this->calculation_values['Width'] = $this->calculation_values['Width'] + 50;
                            $this->calculation_values['Height'] = $this->calculation_values['Height'] + 50;
                            // $this->calculation_values['ClearanceForTubeRemoval'] = 3710;

                            $this->calculation_values['DryWeight'] = $this->calculation_values['DryWeight'] + 0.1;
                            $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + 0.1;
                            $this->calculation_values['OperatingWeight'] = $this->calculation_values['OperatingWeight'] + 0.1;
                            $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + 0.1;

                        }
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
                    break;

                case 2270:
                    if ($this->calculation_values['TCHW12'] < 3.5)
                    {
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->model_values['model_name'] = "TZC S2 J1 N";
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TZC S2 J1";
                        }
                    }
                    else
                    {
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->model_values['model_name'] = "TAC S2 J1 N";
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TAC S2 J1";
                        }
                    }
                    if($this->calculation_values['region_type'] == 2 || $this->calculation_values['region_type'] == 3 )
                    {

                        if ($this->calculation_values['TCHW12'] < 3.5)
                        {
                            $this->model_values['model_name'] = "TZC S2 J1";
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TAC S2 J1";
                        }

                        $DryWeight1 = $this->calculation_values['DryWeight'] * $this->calculation_values['EX_DryWeight'];

                        $ex_DryWeight =  $DryWeight1 - $this->calculation_values['DryWeight'] ;

                        $this->calculation_values['DryWeight'] = $DryWeight1; 
                        $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + $ex_DryWeight;
                        $this->calculation_values['OperatingWeight'] =$this->calculation_values['OperatingWeight'] + $ex_DryWeight;
                        $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + $ex_DryWeight;
                    }
                    else{
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->calculation_values['Length'] = $this->calculation_values['Length'] + 50;
                            $this->calculation_values['Width'] = $this->calculation_values['Width'] + 50;
                            $this->calculation_values['Height'] = $this->calculation_values['Height'] + 50;
                            // $this->calculation_values['ClearanceForTubeRemoval'] = 3710;

                            $this->calculation_values['DryWeight'] = $this->calculation_values['DryWeight'] + 0.1;
                            $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + 0.1;
                            $this->calculation_values['OperatingWeight'] = $this->calculation_values['OperatingWeight'] + 0.1;
                            $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + 0.1;

                        }
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
                    break;

                case 2560:
                    if ($this->calculation_values['TCHW12'] < 3.5)
                    {
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->model_values['model_name'] = "TZC S2 J2 N";
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TZC S2 J2";
                        }
                    }
                    else
                    {
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->model_values['model_name'] = "TAC S2 J2 N";
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TAC S2 J2";
                        }
                    }
                    if($this->calculation_values['region_type'] == 2 || $this->calculation_values['region_type'] == 3 )
                    {

                        if ($this->calculation_values['TCHW12'] < 3.5)
                        {
                            $this->model_values['model_name'] = "TZC S2 J2";
                        }
                        else
                        {
                            $this->model_values['model_name'] = "TAC S2 J2";
                        }

                        $DryWeight1 = $this->calculation_values['DryWeight'] * $this->calculation_values['EX_DryWeight'];

                        $ex_DryWeight =  $DryWeight1 - $this->calculation_values['DryWeight'] ;

                        $this->calculation_values['DryWeight'] = $DryWeight1; 
                        $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + $ex_DryWeight;
                        $this->calculation_values['OperatingWeight'] =$this->calculation_values['OperatingWeight'] + $ex_DryWeight;
                        $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + $ex_DryWeight;
                    }
                    else{
                        if ($this->calculation_values['PST1'] < 6.01)
                        {
                            $this->calculation_values['Length'] = $this->calculation_values['Length'] + 50;
                            $this->calculation_values['Width'] = $this->calculation_values['Width'] + 50;
                            $this->calculation_values['Height'] = $this->calculation_values['Height'] + 50;
                            // $this->calculation_values['ClearanceForTubeRemoval'] = 3710;

                            $this->calculation_values['DryWeight'] = $this->calculation_values['DryWeight'] + 0.1;
                            $this->calculation_values['MaxShippingWeight'] = $this->calculation_values['MaxShippingWeight'] + 0.1;
                            $this->calculation_values['OperatingWeight'] = $this->calculation_values['OperatingWeight'] + 0.1;
                            $this->calculation_values['FloodedWeight'] = $this->calculation_values['FloodedWeight'] + 0.1;

                        }
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

                    break;

            default:
                # code...
                break;
        }
    }

    public function wordFormat($user_report_id){
        
        $user_report = UserReport::find($user_report_id);

        $unit_set = UnitSet::find($user_report->unit_set_id);


        $calculation_values = json_decode($user_report->calculation_values,true);
        $date = date('m/d/Y, h:i A', strtotime($user_report->created_at));

        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$this->model_code)
                                        ->where('min_model','<=',$calculation_values['MODEL'])->where('max_model','>',$calculation_values['MODEL'])->first();

        $chiller_options = $chiller_metallurgy_options->chillerOptions;
        
        $evaporator_option = $chiller_options->where('type', 'eva')->where('value',$calculation_values['TU2'])->first();
        $absorber_option = $chiller_options->where('type', 'abs')->where('value',$calculation_values['TU5'])->first();
        $condenser_option = $chiller_options->where('type', 'con')->where('value',$calculation_values['TV5'])->first();

        $evaporator_name = $evaporator_option->metallurgy->display_name;
        $absorber_name = $absorber_option->metallurgy->display_name;
        $condenser_name = $condenser_option->metallurgy->display_name;

        $vam_base = new VamBaseController();
        $language_datas = $vam_base->getLanguageDatas();
        $units_data = $vam_base->getUnitsData();

        $phpWord = new \PhpOffice\PhpWord\PhpWord();


        $section = $phpWord->addSection();

        
        
        $section->addImage(asset('assets/images/pic.png'),array('align' => 'center'));
        $section->addTextBreak(1);
        $description = "Technical Specification : Vapour Absorption Chiller";

        // $section->addImage(asset('assets/images/pic.png'),array('marginLeft' => 5));
        $title = array('size' => 12, 'bold' => true,'align' => 'center');

        
        $section->addTextRun($title)->addText($description,$title);
        //$section->addTextBreak(1);
        $cellRowSpan = array('bgColor' => 'e5e5e5');

        $table_style = new \PhpOffice\PhpWord\Style\Table;
        $table_style->setBorderColor('cccccc');
        $table_style->setBorderSize(10);
        $table_style->setUnit(\PhpOffice\PhpWord\Style\Table::WIDTH_PERCENT);
        $table_style->setWidth(100 * 50);

        $alignment = array('bold' => true, 'align' => 'center');


        $header = array('size' => 10, 'bold' => true);

        $header_table = $section->addTable($table_style);
        $header_table->addRow();
        $header_table->addCell(1050,$cellRowSpan)->addText(htmlspecialchars($language_datas['client']),$header);
        $header_table->addCell(2550,$cellRowSpan)->addText(htmlspecialchars($user_report->name),$header);
        $header_table->addCell(1550,$cellRowSpan)->addText(htmlspecialchars($language_datas['version']),$header);
        $header_table->addCell(2000,$cellRowSpan)->addText(htmlspecialchars("5.1.2.0"),$header);

        $header_table->addRow();
        $header_table->addCell(1050,$cellRowSpan)->addText(htmlspecialchars($language_datas['enquiry']),$header);
        $header_table->addCell(2550,$cellRowSpan)->addText(htmlspecialchars($user_report->phone),$header);
        $header_table->addCell(1550,$cellRowSpan)->addText(htmlspecialchars($language_datas['date']),$header);
        $header_table->addCell(2000,$cellRowSpan)->addText(htmlspecialchars($date),$header);

        $header_table->addRow();
        $header_table->addCell(1050,$cellRowSpan)->addText(htmlspecialchars($language_datas['project']),$header);
        $header_table->addCell(2550,$cellRowSpan)->addText(htmlspecialchars($user_report->project),$header);
        $header_table->addCell(1550,$cellRowSpan)->addText(htmlspecialchars($language_datas['model']),$header);
        $header_table->addCell(2000,$cellRowSpan)->addText(htmlspecialchars($calculation_values['model_name']),$header);

        $section->addTextBreak(1);

        $description_table = $section->addTable($table_style);
        $description_table->addRow();
        $description_table->addCell(700,$cellRowSpan)->addText(htmlspecialchars(""),$header);
        $description_table->addCell(2850,$cellRowSpan)->addText(htmlspecialchars($language_datas['description']),$header);
        $description_table->addCell(1750,$cellRowSpan)->addTextRun($alignment)->addText(htmlspecialchars($language_datas['unit']),$header);
        $description_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars(""),$header);

        $description_table->addRow();
        $description_table->addCell(700,$cellRowSpan)->addText(htmlspecialchars(""),$header);
        $description_table->addCell(2850,$cellRowSpan)->addText(htmlspecialchars($language_datas['capacity']."(+/-3%)"),$header);
        $description_table->addCell(1750,$cellRowSpan)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->CapacityUnit]),$header);
        $description_table->addCell(1750,$cellRowSpan)->addTextRun($alignment)->addText(htmlspecialchars($calculation_values['TON']),$header);

        $section->addTextBreak(1);

        $chilled_table = $section->addTable($table_style);
        $chilled_table->addRow();
        $chilled_table->addCell(700,$cellRowSpan)->addText(htmlspecialchars("A"),$header);
        $chilled_table->addCell(2850,$cellRowSpan)->addText(htmlspecialchars($language_datas['chilled_water_circuit']),$header);
        $chilled_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars(""),$header);
        $chilled_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars(""),$header);

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("1."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['chilled_water_flow']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->FlowRateUnit]));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['ChilledWaterFlow'],1)));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("2."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['chilled_inlet_temp']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->TemperatureUnit]));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['TCHW11'],1)));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("3."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['chilled_outlet_temp']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->TemperatureUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['TCHW12'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("4."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['evaporate_pass']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "No" ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $calculation_values['EvaporatorPasses'] ));


        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("5."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['chilled_pressure_loss']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->PressureDropUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['ChilledFrictionLoss'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("6."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['chilled_connection_diameter']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->NozzleDiameterUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['ChilledConnectionDiameter'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("7."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['glycol_type']));
        $chilled_table->addCell(1750)->addText(htmlspecialchars( "" ));
        if($calculation_values['GL'] == 1)
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "NA" ));
        else if($calculation_values['GL'] == 2)
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "Ethylene" ));
        else
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "Proplylene" ));


        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("8."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['chilled_gylcol']. "%"));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(" ( % )"));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['CHGLY'],1)));
       
        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("9."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['chilled_fouling_factor']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->FoulingFactorUnit] ));
        if($calculation_values['TUU'] == "standard")
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $calculation_values['TUU'] ));
        else
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $calculation_values['FFCHW1'] ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("10."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['max_working_pressure']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->WorkPressureUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( ceil($calculation_values['m_maxCHWWorkPressure']) ));

        $section->addTextBreak(1);

        $chilled_table = $section->addTable($table_style);
        $chilled_table->addRow();
        $chilled_table->addCell(700,$cellRowSpan)->addText(htmlspecialchars("B"),$header);
        $chilled_table->addCell(2850,$cellRowSpan)->addText(htmlspecialchars($language_datas['cooling_water_circuit']),$header);
        $chilled_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars(""),$header);
        $chilled_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars(""),$header);

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("1."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['cooling_water_flow']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->FlowRateUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['GCW'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("2."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['cooling_inlet_temp']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->TemperatureUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['TCW11'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("3."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['cooling_outlet_temp']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->TemperatureUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['CoolingWaterOutTemperature'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("4."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['absorber_condenser_pass']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "No" ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $calculation_values['AbsorberPasses']."/".$calculation_values['CondenserPasses'] ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("5."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['cooling_bypass_flow']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->FlowRateUnit] ));
        if(empty($calculation_values['BypassFlow']))
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "-" ));
        else
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['BypassFlow'],1) ));


        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("6."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['cooling_pressure_loss']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->PressureDropUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['CoolingFrictionLoss'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("7."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['cooling_connection_diameter']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->NozzleDiameterUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['CoolingConnectionDiameter'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("8."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['glycol_type']));
        $chilled_table->addCell(1750)->addText(htmlspecialchars( "" ));
        if($calculation_values['GL'] == 1)
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "NA" ));
        else if($calculation_values['GL'] == 2)
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "Ethylene" ));
        else
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "Proplylene" ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("9."));
        $chilled_table->addCell(2550)->addText(htmlspecialchars($language_datas['cooling_gylcol']." ( % )"));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "%" ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['COGLY'],1) ));


        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("10."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['cooling_fouling_factor']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->FoulingFactorUnit] ));
        if($calculation_values['TUU'] == "standard")
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $calculation_values['TUU'] ));
        else
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $calculation_values['FFCOW1'] ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("11."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['max_working_pressure']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->WorkPressureUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( ceil($calculation_values['m_maxCOWWorkPressure']) ));

        $section->addTextBreak(1);

        $chilled_table = $section->addTable($table_style);
        $chilled_table->addRow();
        $chilled_table->addCell(700,$cellRowSpan)->addText(htmlspecialchars("C"),$header);
        $chilled_table->addCell(2850,$cellRowSpan)->addText(htmlspecialchars($language_datas['steam_circuit']),$header);
        $chilled_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars(""),$header);
        $chilled_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars(""),$header);

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("1."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['steam_pressure']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->PressureUnit]));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['PST1'],1)));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("2."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['steam_consumption']." (+/-3%)"));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->SteamConsumptionUnit]));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['SteamConsumption'],1)));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("3."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['condensate_drain_temperature']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->TemperatureUnit]));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(ceil($calculation_values['m_dMinCondensateDrainTemperature']) ." - ".ceil($calculation_values['m_dMaxCondensateDrainTemperature']) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("4."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['condensate_drain_pressure']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->PressureUnit]));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['m_dCondensateDrainPressure'],1)));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("5."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['connection_inlet_dia']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->NozzleDiameterUnit]));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['SteamConnectionDiameter'],1)));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("6."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['connection_drain_dia']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->NozzleDiameterUnit]));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['SteamDrainDiameter'],1)));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("7."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['design_pressure']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->PressureUnit]));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['m_DesignPressure'],1)));


        $section->addTextBreak(1);

        $chilled_table = $section->addTable($table_style);
        $chilled_table->addRow();
        $chilled_table->addCell(700,$cellRowSpan)->addText(htmlspecialchars("D"),$header);
        $chilled_table->addCell(2850,$cellRowSpan)->addText(htmlspecialchars($language_datas['electrical_data']),$header);
        $chilled_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars(""),$header);
        $chilled_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars(""),$header);

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("1."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['power_supply']));
        $chilled_table->addCell(1750)->addText(htmlspecialchars( "" ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $calculation_values['PowerSupply'] ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("2."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['power_consumption']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "kVA" ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['TotalPowerConsumption'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("3."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['absorbent_pump_rating']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "kW (A)" ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['AbsorbentPumpMotorKW'],2) ."( ". round($calculation_values['AbsorbentPumpMotorAmp'],2)." )" ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("4."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['refrigerant_pump_rating']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "kW (A)" ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['RefrigerantPumpMotorKW'],2) ."( ". round($calculation_values['RefrigerantPumpMotorAmp'],2)." )" ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("5."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['vaccum_pump_rating']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "kW (A)" ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['PurgePumpMotorKW'],2) ."( ". round($calculation_values['PurgePumpMotorAmp'],2)." )" ));
        if($calculation_values['region_type'] ==2)
        {

            $chilled_table->addRow();
            $chilled_table->addCell(700)->addText(htmlspecialchars("6."));
            $chilled_table->addCell(2850)->addText(htmlspecialchars("MOP"));
            $chilled_table->addCell(1750)->addText(htmlspecialchars( "" ));
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['MOP'],2)));

            $chilled_table->addRow();
            $chilled_table->addCell(700)->addText(htmlspecialchars("7."));
            $chilled_table->addCell(2850)->addText(htmlspecialchars("MCA"));
            $chilled_table->addCell(1750)->addText(htmlspecialchars( "" ));
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['MCA'],2) ));
        }


        $section->addTextBreak(1);

        $chilled_table = $section->addTable($table_style);
        $chilled_table->addRow();
        $chilled_table->addCell(700,$cellRowSpan)->addText(htmlspecialchars("E"),$header);
        $chilled_table->addCell(2850,$cellRowSpan)->addText(htmlspecialchars($language_datas['physical_data']),$header);
        $chilled_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars(""),$header);
        $chilled_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars(""),$header);

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("1."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['length']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->LengthUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( ceil($calculation_values['Length']) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("2."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['width']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->LengthUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( ceil($calculation_values['Width']) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("3."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['height']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->LengthUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( ceil($calculation_values['Height'])));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("4."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['operating_weight']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->WeightUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['OperatingWeight'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("5."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['shipping_weight']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->WeightUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['MaxShippingWeight'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("6."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['flooded_weight']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->WeightUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['FloodedWeight'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("7."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['dry_weight']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->WeightUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['DryWeight'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("8."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['tube_clearing_space']." (".$language_datas['one_side_length_wise'].")"));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->LengthUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['ClearanceForTubeRemoval'],1) ));

        $section->addTextBreak(1);

        $chilled_table = $section->addTable($table_style);
        $chilled_table->addRow();
        $chilled_table->addCell(700,$cellRowSpan)->addText(htmlspecialchars("F"),$header);
        $chilled_table->addCell(2850,$cellRowSpan)->addText(htmlspecialchars($language_datas['tube_metallurgy']),$header);
        $chilled_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars(""),$header);
        $chilled_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars(""),$header);

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("1."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['evaporator_tube_material']));
        $chilled_table->addCell(1750)->addText(htmlspecialchars(""));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($evaporator_name));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("2."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['absorber_tube_material']));
        $chilled_table->addCell(1750)->addText(htmlspecialchars(""));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($absorber_name));


        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("3."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['condenser_tube_material']));
        $chilled_table->addCell(1750)->addText(htmlspecialchars(""));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($condenser_name));

        if(!$calculation_values['isStandard'] || $calculation_values['isStandard'] != 'true'){
            $chilled_table->addRow();
            $chilled_table->addCell(700)->addText(htmlspecialchars("4."));
            $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['evaporator_tube_thickness']));
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->LengthUnit]));
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['TU3'],1)));

            $chilled_table->addRow();
            $chilled_table->addCell(700)->addText(htmlspecialchars("5."));
            $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['absorber_tube_thickness']));
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->LengthUnit]));
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['TU6'],1)));

            $chilled_table->addRow();
            $chilled_table->addCell(700)->addText(htmlspecialchars("6."));
            $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['condenser_tube_thickness']));
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->LengthUnit]));
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['TV6'],1)));
        }

        $section->addTextBreak(1);

        $chilled_table = $section->addTable($table_style);
        $chilled_table->addRow();
        $chilled_table->addCell(700,$cellRowSpan)->addText(htmlspecialchars("G"),$header);
        $chilled_table->addCell(2850,$cellRowSpan)->addText(htmlspecialchars($language_datas['low_temp_heat_exchange']),$header);
        $chilled_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars(""),$header);
        $chilled_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars($calculation_values['HHType']),$header);
        
        $section->addTextBreak(1);
        $chilled_table = $section->addTable($table_style);
        $chilled_table->addRow();
        $chilled_table->addCell(700,$cellRowSpan)->addText(htmlspecialchars($language_datas['caption_notes']." : "),$header);

        foreach ($calculation_values['notes'] as $key => $note) {
            $section->addText(($key + 1).". ".$note);
        }

        $file_name = "S2-Steam-Fired-Serices-".Auth::user()->id.".docx";
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        try {
            $objWriter->save(storage_path($file_name));
        } catch (Exception $e) {
            Log::info($e);
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
            'USA_capacity',
            'USA_chilled_water_in',
            'USA_chilled_water_out',
            'USA_cooling_water_in',
            'USA_cooling_water_flow']);

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
            'SteamConnectionDiameter',
            'SteamDrainDiameter',
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
            'ODC'

        ]);

        return $calculation_values;
    }

    public function testingS2Calculation($datas){
        
        $this->model_values = $datas;

        $vam_base = new VamBaseController();
        $this->notes = $vam_base->getNotesError();

        $this->model_values['metallurgy_standard'] = $this->getBoolean($this->model_values['metallurgy_standard']);
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

    



    // public function PR_DROP_DATA()
    // {
    //     if ($this->model_values['model_number'] == 130 || $this->model_values['model_number'] == 160 || $this->model_values['model_number'] == 210 || $this->model_values['model_number'] == 250)
    //     {
    //         //CHILLED WATER
    //         $PNB1 = 125; $PODE1 = 141.3; $THPE1 = 6.55;
    //         $PNB2 = 100; $PODE2 = 114.3; $THPE2 = 6.02;

    //         $SL1 = 0.49; $SL8 = 0.49;       //ST LENGTH AT INLET & OUTLET
    //         $SL2 = 0.82; $SL7 = 0.82;       //ST LENGTH BETWEEN BRANCHING
    //         $SL3 = 0.348; $SL6 = 0.123;     //ST LENGTH OF BRANCH AT INLET & OUTLET
    //         $SL4 = 0.204; $SL5 = 0.204;     //ST LENGTH AT INLET & OUTLET OF HEADER
    //         $FT1 = 0.016; $FT2 = 0.017;     //FRIC FACTOR AT TURBULENCE

    //         $SHE = 1.525;

    //         //COOLING WATER
    //         $PNB = 150; $PODA = 168.3; $THPA = 7.11;   //LINE SIZE AT INLET & OUTLET

    //         $PSL1 = 0.660 + 0.568;              //STRAIGHT LENGTH OF PIPE @ Inlet, Outlet & btw Abs 
    //         $PSL2 = 0.481;                //STRAIGHT LENGTH OF PIPE @ Outlet Of Con      
    //         $FT = 0.015;

    //         $SHA = 1.946;
           
    //     }
    //     if ($this->model_values['model_number'] == 310 || $this->model_values['model_number'] == 350 || $this->model_values['model_number'] == 410)
    //     {
    //         //EVA 
    //         $PNB1 = 150; $PODE1 = 168.3; $THPE1 = 7.11;
    //         $PNB2 = 125; $PODE2 = 141.3; $THPE2 = 6.55;

    //         $SL1 = 0.41; $SL8 = 0.41;       //ST LENGTH AT INLET & OUTLET
    //         $SL2 = 0.98; $SL7 = 0.98;       //ST LENGTH BETWEEN BRANCHING
    //         $SL3 = 0.26; $SL6 = 0.16;       //ST LENGTH OF BRANCH AT INLET & OUTLET
    //         $SL4 = 0.216; $SL5 = 0.216;     //ST LENGTH AT INLET & OUTLET OF HAEDER
    //         $FT1 = 0.015; $FT2 = 0.016;     //FRIC FACTOR AT TURBULENCE

    //         $SHE = 1.53;

    //         //COW 
    //         $PNB = 200; $PODA = 219.1; $THPA = 8.18;

    //         $PSL1 = 0.582 + 0.6010; $PSL2 = 0.566;
    //         $FT = 0.014;

    //         $SHA = 2.073;
            
    //     }
    //     if ($this->model_values['model_number'] == 470 || $this->model_values['model_number'] == 530 || $this->model_values['model_number'] == 580)
    //     {
    //         //EVA 
    //         $PNB1 = 200; $PODE1 = 219.1; $THPE1 = 8.18;
    //         $PNB2 = 150; $PODE2 = 168.3; $THPE2 = 7.11;

    //         $SL1 = 0.42; $SL8 = 0.42;       //ST LENGTH AT INLET & OUTLET
    //         $SL2 = 1.06; $SL7 = 1.06;       //ST LENGTH BETWEEN BRANCHING
    //         $SL3 = 0.321; $SL6 = 0.171;     //ST LENGTH OF BRANCH AT INLET & OUTLET
    //         $SL4 = 0.277; $SL5 = 0.277;     //ST LENGTH AT INLET & OUTLET OF HAEDER
    //         $FT1 = 0.014; $FT2 = 0.015;     //FRIC FACTOR AT TURBULENCE

    //         $SHE = 1.807;

    //         //COW 
    //         $PNB = 250; $PODA = 273.0; $THPA = 9.27;

    //         $PSL1 = 0.555 + 0.616; $PSL2 = 0.5650;
    //         $FT = 0.014;

    //         $SHA = 2.356;                
    //     }
    //     if ($this->model_values['model_number'] == 630 || $this->model_values['model_number'] == 710)
    //     {
    //         //EVA 
    //         $PNB1 = 200; $PODE1 = 219.1; $THPE1 = 8.18;
    //         $PNB2 = 150; $PODE2 = 168.3; $THPE2 = 7.11;

    //         $SL1 = 0.43; $SL8 = 0.43;       //ST LENGTH AT INLET & OUTLET
    //         $SL2 = 1.14; $SL7 = 1.14;       //ST LENGTH BETWEEN BRANCHING
    //         $SL3 = 0.321; $SL6 = 0.171;     //ST LENGTH OF BRANCH AT INLET & OUTLET
    //         $SL4 = 0.277; $SL5 = 0.277;     //ST LENGTH AT INLET & OUTLET OF HAEDER
    //         $FT1 = 0.014; $FT2 = 0.015;     //FRIC FACTOR AT TURBULENCE

    //         $SHE = 1.911;

    //         //COW 
    //         $PNB = 300; $PODA = 323.6; $THPA = 10.31;

    //         $PSL1 = 0.529 + 0.6880; $PSL2 = 0.665;
    //         $FT = 0.013;

    //         $SHA = 2.582;

    //     }
    //     if ($this->model_values['model_number'] == 760 || $this->model_values['model_number'] == 810 || $this->model_values['model_number'] == 900)
    //     {
    //         //EVA PIPE DIA
    //         $PNB1 = 250; $PODE1 = 273.0; $THPE1 = 9.27;
    //         $PNB2 = 200; $PODE2 = 219.1; $THPE2 = 8.18;

    //         $SL1 = 0.53; $SL8 = 0.53;       //ST LENGTH AT INLET & OUTLET
    //         $SL2 = 1.14; $SL7 = 1.14;       //ST LENGTH BETWEEN BRANCHING
    //         $SL3 = 0.395; $SL6 = 0.195;     //ST LENGTH OF BRANCH AT INLET & OUTLET
    //         $SL4 = 0.247; $SL5 = 0.247;     //ST LENGTH AT INLET & OUTLET OF HAEDER
    //         $FT1 = 0.014; $FT2 = 0.014;     //FRIC FACTOR AT TURBULENCE

    //         $SHE = 2.106;

    //         //COW 
    //         $PNB = 350; $PODA = 355.6; $THPA = 11.13;

    //         $PSL1 = 0.684 + 0.7; $PSL2 = 0.694;
    //         $FT = 0.013;

    //         $SHA = 2.804;
    //     }
    //     if ($this->model_values['model_number'] == 1010 || $this->model_values['model_number'] == 1130)
    //     {
    //         //EVA 
    //         $PNB1 = 250; $PODE1 = 273.0; $THPE1 = 9.27;
    //         $PNB2 = 200; $PODE2 = 219.1; $THPE2 = 8.18;

    //         $SL1 = 0.53; $SL8 = 0.53;       //ST LENGTH AT INLET & OUTLET
    //         $SL2 = 1.14; $SL7 = 1.14;       //ST LENGTH BETWEEN BRANCHING
    //         $SL3 = 0.395; $SL6 = 0.395;     //ST LENGTH OF BRANCH AT INLET & OUTLET
    //         $SL4 = 0.247; $SL5 = 0.247;     //ST LENGTH AT INLET & OUTLET OF HAEDER
    //         $FT1 = 0.014; $FT2 = 0.014;     //FRIC FACTOR AT TURBULENCE

    //         $SHE = 2.106;

    //         //COW 
    //         $PNB = 350; $PODA = 355.6; $THPA = 11.13;

    //         $PSL1 = 0.552 + 0.705; $PSL2 = 0.694;
    //         $FT = 0.013;

    //         $SHA = 2.789;
    //     }
    //     if ($this->model_values['model_number'] == 1260 || $this->model_values['model_number'] == 1380) //F
    //     {
    //         //EVA 
    //         $PNB1 = 300; $PODE1 = 323.6; $THPE1 = 10.31;
    //         $PNB2 = 250; $PODE2 = 273; $THPE2 = 9.27;

    //         $SL1 = 0.55; $SL8 = 0.55;       //ST LENGTH AT INLET & OUTLET
    //         $SL2 = 1.32; $SL7 = 1.32;       //ST LENGTH BETWEEN BRANCHING
    //         $SL3 = 0.51; $SL6 = 0.334;      //ST LENGTH OF BRANCH AT INLET & OUTLET
    //         $SL4 = 0.336; $SL5 = 0.336;     //ST LENGTH AT INLET & OUTLET OF HAEDER
    //         $FT1 = 0.013; $FT2 = 0.014;     //FRIC FACTOR AT TURBULENCE

    //         $SHE = 2.487;

    //         //COW
    //         $PNB = 400; $PODA = 406.4; $THPA = 12.7;

    //         $PSL1 = 0.794 + 1.0320; $PSL2 = 0.879;
    //         $FT = 0.013;

    //         $SHA = 2.144;
    //     }

    //     if ($this->model_values['model_number'] == 1560 || $this->model_values['model_number'] == 1690 || $this->model_values['model_number'] == 1890 || $this->model_values['model_number'] == 2130)
    //     {
    //         //EVA        
    //         $PNB1 = 350; $PODE1 = 355.6; $THPE1 = 11.13;
    //         $PNB2 = 300; $PODE2 = 323.6; $THPE2 = 10.31;

    //         $SL1 = 0.624; $SL8 = 0.624;     //ST LENGTH AT INLET & OUTLET
    //         $SL2 = 1.576; $SL7 = 1.576;     //ST LENGTH BETWEEN BRANCHING
    //         $SL3 = 0.571; $SL6 = 0.265;     //ST LENGTH OF BRANCH AT INLET & OUTLET
    //         $SL4 = 0.445; $SL5 = 0.445;     //ST LENGTH AT INLET & OUTLET OF HAEDER
    //         $FT1 = 0.013; $FT2 = 0.013;     //FRIC FACTOR AT TURBULENCE

    //         $SHE = 2.65;

    //         //COW 
    //         $PNB = 450; $PODA = 457.2; $THPA = 14.27;

    //         $PSL1 = 0.868 + 1.167; $PSL2 = 0.859;
    //         $FT = 0.012;

    //         $SHA = 2.229;
    //     }
    //     if ($this->model_values['model_number'] == 2270 || $this->model_values['model_number'] == 2560)
    //     {
    //         //EVA        
    //         $PNB1 = 400; $PODE1 = 406.4; $THPE1 = 12.7;
    //         $PNB2 = 300; $PODE2 = 323.6; $THPE2 = 10.31;

    //         $SL1 = 0.724; $SL8 = 0.724;     //ST LENGTH AT INLET & OUTLET
    //         $SL2 = 1.576; $SL7 = 1.576;     //ST LENGTH BETWEEN BRANCHING
    //         $SL3 = 0.545; $SL6 = 0.339;     //ST LENGTH OF BRANCH AT INLET & OUTLET
    //         $SL4 = 0.468; $SL5 = 0.468;     //ST LENGTH AT INLET & OUTLET OF HAEDER
    //         $FT1 = 0.013; $FT2 = 0.013;     //FRIC FACTOR AT TURBULENCE

    //         $SHE = 2.75;

    //         //COW 
    //         $PNB = 500; $PODA = 508; $THPA = 15.08;

    //         $PSL1 = 0.980 + 1.325; $PSL2 = 0.8670;
    //         $FT = 0.012;

    //         $SHA = 3.176;
    //     }
    // }
    


	// public function processAttribChanged(){

	// }


	// public function chillerAttributesChanged($attribute){

	// 	switch (strtoupper($attribute))
	// 	{
		    
	// 	    case "EVAPORATORTUBETYPE":
	//             if ($this->model_values['evaporator_material_value'] == 0 || $this->model_values['evaporator_material_value'] == 2)
	//             {
	//                 if ($this->model_values['model_number'] < 750)
	//                 {
	                    
	//                     $this->model_values['evaporator_thickness_min_range'] = 0.57;
	//                 }
	//                 else
	//                 {
	                    
	//                     $this->model_values['evaporator_thickness_range'] = 0.65;
	//                 }
	//                	$this->model_values['evaporator_thickness_max_range'] = 1;

	//             }
	//             else if ($this->model_values['evaporator_material_value'] == 1)
	//             {
	//                 if ($this->model_values['model_number'] < 750)
	//                 {
	//                     $this->model_values['evaporator_thickness_min_range'] = 0.6;
	//                 }
	//                 else
	//                 {
	//                     $this->model_values['evaporator_thickness_min_range'] = 0.65;
	//                 }

	//                 $this->model_values['evaporator_thickness_max_range'] = 1.0;
	//             }
	//             else if ($this->model_values['evaporator_material_value'] == 4)
	//             {
	//                 $this->model_values['evaporator_thickness_min_range'] = 0.9;
	//                 $this->model_values['evaporator_thickness_max_range'] = 1.2;
	//             } 
	//             else
	//             {
	//                 $this->model_values['evaporator_thickness_min_range'] = 0.6;
	//                 $this->model_values['evaporator_thickness_max_range'] = 1.0;
	//             }
	// 	    	break;

	// 	}


	// }


	
}
