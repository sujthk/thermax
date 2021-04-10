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
use App\UnitSet;
use App\Region;
use Exception;
use Log;
use PDF;
use DB;

class DoubleG2SteamController extends Controller
{
	private $model_values;
	private $model_code = "D_G2";
	private $calculation_values;
    private $notes;
    private $changed_value;



    public function getDoubleEffectG2(){

        $chiller_form_values = $this->getFormValues(130);


    	$chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')
                                        ->where('code',$this->model_code)
    									->where('min_model','<=',130)->where('max_model','>=',130)->first();

    	$chiller_options = $chiller_metallurgy_options->chillerOptions;
    	
    	$evaporator_options = $chiller_options->where('type', 'eva');
    	$absorber_options = $chiller_options->where('type', 'abs');
    	$condenser_options = $chiller_options->where('type', 'con');

        // Log::info($default_values);
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

		return view('double_effect_g2_series')->with(['default_values'=>$converted_values,'unit_set'=>$unit_set,'units_data'=>$units_data,'evaporator_options'=>$evaporator_options,'absorber_options'=>$absorber_options,'condenser_options'=>$condenser_options,'chiller_metallurgy_options'=>$chiller_metallurgy_options,'regions'=>$regions,'language_datas'=>$language_datas]);
                           
	}

	public function postAjaxDoubleEffectG2(Request $request){

		$model_values = $request->input('values');
		$changed_value = $request->input('changed_value');

        $unit_conversions = new UnitConversionController;
        if(!empty($changed_value)){

            $model_values = $unit_conversions->calculationUnitConversion($model_values,$this->model_code);
        }
       
		$this->model_values = $model_values;
        $this->castToBoolean();

		$attribute_validator = $this->validateChillerAttribute($changed_value);
        
		if(!$attribute_validator['status'])
			return response()->json(['status'=>false,'msg'=>$attribute_validator['msg']]);

        $this->updateInputs();
        $this->loadSpecSheetData();

        $converted_values = $unit_conversions->formUnitConversion($this->model_values,$this->model_code);

		return response()->json(['status'=>true,'msg'=>'Ajax Datas','model_values'=>$converted_values]);
	}
    
	public function postDoubleEffectG2(Request $request){

		$model_values = $request->input('values');

        $unit_conversions = new UnitConversionController;
        $converted_values = $unit_conversions->calculationUnitConversion($model_values,$this->model_code);

		$this->model_values = $converted_values;
        $this->castToBoolean();

		  
		$validate_attributes = array('CAPACITY','CHILLED_WATER_IN','CHILLED_WATER_OUT','EVAPORATOR_TUBE_TYPE','GLYCOL_TYPE_CHANGED','GLYCOL_CHILLED_WATER','GLYCOL_COOLING_WATER','COOLING_WATER_IN','COOLING_WATER_FLOW','EVAPORATOR_THICKNESS','ABSORBER_THICKNESS','CONDENSER_THICKNESS','FOULING_CHILLED_VALUE','FOULING_COOLING_VALUE','STEAM_PRESSURE');	
		
		foreach ($validate_attributes as $key => $validate_attribute) {
			$attribute_validator = $this->validateChillerAttribute($validate_attribute);

			if(!$attribute_validator['status'])
				return response()->json(['status'=>false,'msg'=>$attribute_validator['msg'],'input_target'=>strtolower($validate_attribute)]);
		}									

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
             Log::info($e);

            return response()->json(['status'=>false,'msg'=>$this->notes['NOTES_ERROR']]);
        }

	

        $calculated_values = $unit_conversions->reportUnitConversion($this->calculation_values,$this->model_code);
		return response()->json(['status'=>true,'msg'=>'Ajax Datas','calculation_values'=>$calculated_values]);
	}

	public function postResetDoubleEffectG2(Request $request){
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
        $standard_values = array('evaporator_thickness' => 0,'absorber_thickness' => 0,'condenser_thickness' => 0,'evaporator_thickness_min_range' => 0,'evaporator_thickness_max_range' => 0,'absorber_thickness_min_range' => 0,'absorber_thickness_max_range' => 0,'condenser_thickness_min_range' => 0,'condenser_thickness_max_range' => 0,'fouling_chilled_water_value' => 0,'fouling_cooling_water_value' => 0,'evaporator_thickness_change' => 1,'absorber_thickness_change' => 1,'condenser_thickness_change' => 1,'fouling_chilled_water_checked' => 0,'fouling_cooling_water_checked' => 0,'fouling_chilled_water_disabled' => 1,'fouling_cooling_water_disabled' => 1,'fouling_chilled_water_value_disabled' => 1,'fouling_cooling_water_value_disabled' => 1);

       

       $chiller_form_values = collect($chiller_form_values)->union($standard_values);

            
        $this->model_values = $chiller_form_values;

        // log::info($this->model_values);
        $this->castToBoolean();
        $range_calculation = $this->RANGECAL();
        //log::info($this->model_values);
        $unit_conversions = new UnitConversionController;
        $converted_values = $unit_conversions->formUnitConversion($this->model_values,$this->model_code);
         //log::info($converted_values);

		return response()->json(['status'=>true,'msg'=>'Ajax Datas','model_values'=>$converted_values,'evaporator_options'=>$evaporator_options,'absorber_options'=>$absorber_options,'condenser_options'=>$condenser_options,'chiller_metallurgy_options'=>$chiller_metallurgy_options]);

	}
    public function modulNumberDoubleEffectG2(){
        // $model_values = $this->model_values;
        $model_number =(int)$this->model_values['model_number'];

        if(empty($this->model_values['metallurgy_standard'])){
            // $this->model_values['evaporator_material_value']=$model_values['evaporator_material_value'];
            // $this->model_values['absorber_material_value']=$model_values['absorber_material_value'];
            // $this->model_values['condenser_material_value']=$model_values['condenser_material_value'];

            $this->model_values['metallurgy_standard'] = false;
            //Log::info("Metallurgy Standard false");
        }
        else{
            
            $chiller_metallurgy_options = ChillerMetallurgyOption::where('code',$this->model_code)->where('min_model','<=',$model_number)->where('max_model','>=',$model_number)->first();

            $this->model_values['evaporator_material_value']=$chiller_metallurgy_options->eva_default_value;
            $this->model_values['absorber_material_value']=$chiller_metallurgy_options->abs_default_value;
            $this->model_values['condenser_material_value']=$chiller_metallurgy_options->con_default_value;


            $this->model_values['evaporator_thickness_change'] = true;
            $this->model_values['absorber_thickness_change'] = true;
            $this->model_values['condenser_thickness_change'] = true;

            //Log::info("Metallurgy Standard true");
        }
    }


    public function postShowReport(Request $request){

        $calculation_values = $request->input('calculation_values');
        
        $name = $request->input('name',"");
        $project = $request->input('project',"");
        $phone = $request->input('phone',"");

        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$this->model_code)
                                        ->where('min_model','<=',(int)$calculation_values['MODEL'])->where('max_model','>=',(int)$calculation_values['MODEL'])->first();

        $chiller_options = $chiller_metallurgy_options->chillerOptions;
        
        $evaporator_option = $chiller_options->where('type', 'eva')->where('value',$calculation_values['TU2'])->first();
        $absorber_option = $chiller_options->where('type', 'abs')->where('value',$calculation_values['TU5'])->first();
        $condenser_option = $chiller_options->where('type', 'con')->where('value',$calculation_values['TV5'])->first();

        $evaporator_name = $evaporator_option->metallurgy->display_name;
        $absorber_name = $absorber_option->metallurgy->display_name;
        $condenser_name = $condenser_option->metallurgy->display_name;

        $unit_set_id = Auth::user()->unit_set_id;
        $unit_set = UnitSet::find($unit_set_id);

        $units_data = $this->getUnitsData();

        //Log::info($calculation_values);
        $view = view("report", ['name' => $name,'phone' => $phone,'project' => $project,'calculation_values' => $calculation_values,'evaporator_name' => $evaporator_name,'absorber_name' => $absorber_name,'condenser_name' => $condenser_name,'unit_set' => $unit_set,'units_data' => $units_data])->render();

        return response()->json(['report'=>$view]);
    
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
        $user_report->unit_set_id = $user->unit_set_id;
        $user_report->calculation_values = json_encode($calculation_values);
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

            $file_name = "s2-".Auth::user()->id.".docx";
            return response()->download(storage_path($file_name));
        }

        $calculation_values = json_decode($user_report->calculation_values,true);
        // Log::info($calculation_values);
        $name = $user_report->name;
        $project = $user_report->name;
        $phone = $user_report->name;

        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$this->model_code)
                                        ->where('min_model','<=',$calculation_values['MODEL'])->where('max_model','>=',$calculation_values['MODEL'])->first();

        $chiller_options = $chiller_metallurgy_options->chillerOptions;
        
        $evaporator_option = $chiller_options->where('type', 'eva')->where('value',$calculation_values['TU2'])->first();
        $absorber_option = $chiller_options->where('type', 'abs')->where('value',$calculation_values['TU5'])->first();
        $condenser_option = $chiller_options->where('type', 'con')->where('value',$calculation_values['TV5'])->first();

        $evaporator_name = $evaporator_option->metallurgy->display_name;
        $absorber_name = $absorber_option->metallurgy->display_name;
        $condenser_name = $condenser_option->metallurgy->display_name;

        $unit_set_id = Auth::user()->unit_set_id;
        $unit_set = UnitSet::find($unit_set_id);

        $units_data = $this->getUnitsData();

        $pdf = PDF::loadView('report_pdf', ['name' => $name,'phone' => $phone,'project' => $project,'calculation_values' => $calculation_values,'evaporator_name' => $evaporator_name,'absorber_name' => $absorber_name,'condenser_name' => $condenser_name,'unit_set' => $unit_set,'units_data' => $units_data]);
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
            'USA_cooling_water_flow',
            'calorific_value',
            'fuel_type',
            'fuel_value_type'
        ]);

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
	
}
