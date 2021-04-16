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
       
        $this->changed_value =$changed_value;       
		$this->model_values = $model_values;
        $this->castToBoolean();

        $vam_base = new VamBaseController();
        $this->notes = $vam_base->getNotesError();


		$attribute_validator = $this->validateChillerAttribute($changed_value);
        
		if(!$attribute_validator['status'])
			return response()->json(['status'=>false,'msg'=>$attribute_validator['msg'],'changed_value'=>$this->changed_value]);

        $this->updateInputs();
        // $this->loadSpecSheetData();

        $converted_values = $unit_conversions->formUnitConversion($this->model_values,$this->model_code);

		return response()->json(['status'=>true,'msg'=>'Ajax Datas','model_values'=>$converted_values,'changed_value'=>$this->changed_value]);
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

    public function postDoubleEffectG2(Request $request){

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

        

        // try {
            $this->WATERPROP();
            $velocity_status = $this->VELOCITY();
        // } 
        // catch (\Exception $e) {

        //     return response()->json(['status'=>false,'msg'=>$this->notes['NOTES_ERROR']]);
        // }
    

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
        /********************* ABSORBER VELOCITY ********************/
        /*if ($this->calculation_values['MODEL'] > 800)
        {
            $this->calculation_values['TAP'] = 1;
            do
            {
                $this->calculation_values['VA'] = $this->calculation_values['GCW'] / (((3600 * 3.141593 * $this->calculation_values['IDA'] * $this->calculation_values['IDA']) / 4.0) * ($this->calculation_values['TNAA'] / $this->calculation_values['TAP']));

                if ($this->calculation_values['VA'] < VAMIN)
                {
                    $this->calculation_values['TAP'] = $this->calculation_values['TAP'] + 1;
                }
            } while ($this->calculation_values['VA'] < VAMIN);
        }
        else
        {*/
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

                /*if ($this->calculation_values['VEA'] > 1.6)        //ENDCROSSED TUBES WILL BE USED
                {
                    $this->calculation_values['VELEVA'] = 1;
                    $this->calculation_values['IDE'] = ODE - ((2 * 0.75) / 1000);

                    $this->calculation_values['TP'] = 1;
                    do
                    {
                        $this->calculation_values['VEA'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * (($this->calculation_values['TNEV'] / 2) / $this->calculation_values['TP']));
                        if ($this->calculation_values['VEA'] < 1.5)
                            $this->calculation_values['TP'] = $this->calculation_values['TP'] + 1;
                    } while ($this->calculation_values['VEA'] < 1.5 && $this->calculation_values['TP'] <= 4);
                }

                if ($this->calculation_values['TP'] > 4)
                {
                    $this->calculation_values['TP'] = 4;
                    $this->calculation_values['VEA'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * (($this->calculation_values['TNEV'] / 2) / $this->calculation_values['TP']));
                }
                */
                //earlier maximum velocity for ec tubes 2.5 m/sec

                if ($this->calculation_values['VEA'] > 1.8)                                       // 14 FEB 2012
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

                    if ($this->calculation_values['VEA'] > 2.78)                    // 14 FEB 2012
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
        
        // PR_DROP_DATA();
        $this->PR_DROP_CHILL();

        if ($this->calculation_values['FLE'] > 12)
        {
            //if ($this->calculation_values['MODEL'] < 750 && $this->calculation_values['TU2'] < 2.1)
            //{
            //    $this->calculation_values['VEMIN'] = 0.45;
            //}
            //else
            // {
            $this->calculation_values['VEMIN'] = 1;
            // }
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

        $this->calculation_values['CHGLY_ROW22'] = 0;
        $this->calculation_values['CHGLY_VIS22'] = 0;
        $this->calculation_values['FE1'] = 0;
        $this->calculation_values['F'] = 0;

        // $this->calculation_values['PIDE1'] = ($this->calculation_values['PODE1'] - (2 * $this->calculation_values['THPE1'])) / 1000;
        // $this->calculation_values['PIDE2'] = ($this->calculation_values['PODE2'] - (2 * $this->calculation_values['THPE2'])) / 1000;

        $this->calculation_values['VPE1'] = ($this->calculation_values['GCHW'] * 4) / (3.141593 * $this->calculation_values['PIDE1'] * $this->calculation_values['PIDE1'] * 3600);

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

        // double $this->calculation_values['VPE1'] = ($this->calculation_values['GCHW'] * 4) / (3.141593 * $this->calculation_values['PIDE1'] * $this->calculation_values['PIDE1'] * 3600);            //VELOCITY IN PIPE1
        $this->calculation_values['TME'] = ($this->calculation_values['TCHW11'] + $this->calculation_values['TCHW12']) / 2.0;

        if ($this->calculation_values['GLL'] == 3)
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

        if ($this->calculation_values['MODEL'] > 300)
        {
            $this->calculation_values['REPE2'] = ($this->calculation_values['PIDE2'] * $this->calculation_values['VPE2'] * $this->calculation_values['CHGLY_ROW22']) / $this->calculation_values['CHGLY_VIS22'];
            $this->calculation_values['REBR'] = ($this->calculation_values['PIDE1'] * $this->calculation_values['VPBR'] * $this->calculation_values['CHGLY_ROW22']) / $this->calculation_values['CHGLY_VIS22'];          //REYNOLDS NO IN PIPE1
        }
        else
        {
            $this->calculation_values['REPE2'] = $this->calculation_values['REBR'] = 0;
        }

        $this->calculation_values['FF1'] = 1.325 / pow(log((0.0457 / (3.7 * $this->calculation_values['PIDE1'] * 1000)) + (5.74 / pow($this->calculation_values['REPE1'], 0.9))), 2);       //FRICTION FACTOR CAL

        if ($this->calculation_values['MODEL'] > 300)
        {
            $this->calculation_values['FF2'] = 1.325 / pow(log((0.0457 / (3.7 * $this->calculation_values['PIDE2'] * 1000)) + (5.74 / pow($this->calculation_values['REPE2'], 0.9))), 2);
            $this->calculation_values['FF3'] = 1.325 / pow(log((0.0457 / (3.7 * $this->calculation_values['PIDE1'] * 1000)) + (5.74 / pow($this->calculation_values['REBR'], 0.9))), 2);
        }
        else
        {
            $this->calculation_values['FF2'] = $this->calculation_values['FF3'] = 0;
        }


        $this->calculation_values['FL1'] = ($this->calculation_values['FF1'] * ($this->calculation_values['SL1'] + $this->calculation_values['SL8']) / $this->calculation_values['PIDE1']) * ($this->calculation_values['VPE1'] * $this->calculation_values['VPE1'] / (2 * 9.81));

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
            $this->calculation_values['FL5'] = ($this->calculation_values['VPE1'] * $this->calculation_values['VPE1'] / (2 * 9.81)) + (0.5 * $this->calculation_values['VPE1'] * $this->calculation_values['VPE1'] / (2 * 9.81));
        }

        $this->calculation_values['FLP'] = $this->calculation_values['FL1'] + $this->calculation_values['FL2'] + $this->calculation_values['FL3'] + $this->calculation_values['FL4'] + $this->calculation_values['FL5'];      //EVAPORATOR PIPE LOSS

        $this->calculation_values['RE'] = ($this->calculation_values['VEA'] * $this->calculation_values['IDE'] * $this->calculation_values['CHGLY_ROW22']) / $this->calculation_values['CHGLY_VIS22'];            //REYNOLDS NO IN TUBES

        //if ($this->calculation_values['MODEL'] < 750 && $this->calculation_values['TU2'] == 0)
        //{
        //    $this->calculation_values['F'] = 1.325 / pow(log((1.53 / (3.7 * $this->calculation_values['IDE'] * 1000)) + (5.74 / pow($this->calculation_values['RE'], 0.9))), 2);
        //    $this->calculation_values['FE1'] = $this->calculation_values['F'] * $this->calculation_values['LE'] * $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (2 * 9.81 * $this->calculation_values['IDE']);
        //}
        //else 
        if (($this->calculation_values['MODEL'] < 1200 && $this->calculation_values['TU2'] == 3))
        {
            $this->calculation_values['F'] = 1.325 / pow(log((1.53 / (3.7 * $this->calculation_values['IDE'] * 1000)) + (5.74 / pow($this->calculation_values['RE'], 0.9))), 2);
            $this->calculation_values['FE1'] = $this->calculation_values['F'] * $this->calculation_values['LE'] * $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (2 * 9.81 * $this->calculation_values['IDE']);
        }
        else if ($this->calculation_values['MODEL'] > 1200 && $this->calculation_values['TU2'] == 3)                                         //06/11/2017   Changed for SS FInned
        {
            $this->calculation_values['F'] = (1.325 / pow(log((1.53 / (3.7 * $this->calculation_values['IDE'] * 1000)) + (5.74 / pow($this->calculation_values['RE'], 0.9))), 2)) * ((-0.0315 * $this->calculation_values['VEA']) + 0.85);
            $this->calculation_values['FE1'] = $this->calculation_values['F'] * $this->calculation_values['LE'] * $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (2 * 9.81 * $this->calculation_values['IDE']);

        }
        else if (($this->calculation_values['MODEL'] < 1200 && ($this->calculation_values['TU2'] == 1 || $this->calculation_values['TU2'] == 2 || $this->calculation_values['TU2'] == 0) || ($this->calculation_values['MODEL'] < 1200 && $this->calculation_values['TU2'] == 4)))                    // 12% AS PER EXPERIMENTATION      
        {
            $this->calculation_values['F'] = (0.0014 + (0.137 / pow($this->calculation_values['RE'], 0.32))) * 1.12;
            $this->calculation_values['FE1'] = 2 * $this->calculation_values['F'] * $this->calculation_values['LE'] * $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (9.81 * $this->calculation_values['IDE']);
        }
        else if ($this->calculation_values['MODEL'] > 1200 && ($this->calculation_values['TU2'] == 1 || $this->calculation_values['TU2'] == 2 || $this->calculation_values['TU2'] == 4 || $this->calculation_values['TU2'] == 0))
        {
            $this->calculation_values['F'] = 0.0014 + (0.137 / pow($this->calculation_values['RE'], 0.32));
            $this->calculation_values['FE1'] = 2 * $this->calculation_values['F'] * $this->calculation_values['LE'] * $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (9.81 * $this->calculation_values['IDE']);
        }
        else
        {
            $this->calculation_values['F'] = 0.0014 + (0.125 / pow($this->calculation_values['RE'], 0.32));
            $this->calculation_values['FE1'] = 2 * $this->calculation_values['F'] * $this->calculation_values['LE'] * $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (9.81 * $this->calculation_values['IDE']);
        }

        $FE2 = $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (4 * 9.81);
        $FE3 = $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (2 * 9.81);
        $FE4 = (($this->calculation_values['FE1'] + $FE2 + $FE3) * $this->calculation_values['TP']) * 2;      //EVAPORATOR TUBE LOSS FOR DOUBLE ABS
        $this->calculation_values['FLE'] = $this->calculation_values['FLP'] + $FE4;                //TOTAL FRICTION LOSS IN CHILLED WATER CKT
        $this->calculation_values['PDE'] = $this->calculation_values['FLE'] + $this->calculation_values['SHE'];                    //PRESSURE DROP IN CHILLED WATER CKT
    }


    public function CALCULATIONS()
    {
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
        /**/

        //$this->calculation_values['UEVAH'] = $this->calculation_values['UEVAH'] * 0.96;
        //$this->calculation_values['UEVAL'] = $this->calculation_values['UEVAL'] * 0.96;
        //$this->calculation_values['UABSH'] = $this->calculation_values['UABSH'] * 0.96;
        //$this->calculation_values['UABSL'] = $this->calculation_values['UABSL'] * 0.96;
        /*************************/       

        if ($this->calculation_values['TUU'] != 'ari')
        {
            $this->EVAPORATOR();                
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

            $this->calculation_values['TCHW1H'] = $this->calculation_values['TCHW11'];
            $this->calculation_values['TCHW2L'] = $this->calculation_values['TCHW12'];
            $this->calculation_values['TCW1H'] = $this->calculation_values['TCW11'];
            $this->calculation_values['TCW14'] = $this->calculation_values['TCW4'];
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

                $ARISSPC = ($this->calculation_values['T3'] - $this->calculation_values['TCW14']) * 1.8;
                $ARIRC = ($this->calculation_values['TCW14'] - $this->calculation_values['TCW11']) * 1.8;
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

        //if ($this->calculation_values['MODEL'] < 750 && $this->calculation_values['TU2'] == 0)                            //12.13.2011
        //{
        //    $VEVA = 0.7;
        //}
        //else
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
        //if ($this->calculation_values['TU2'] < 2.1 && $this->calculation_values['MODEL'] <750 && VELEVA == 0)
        //{
        //    $HI1 = $HI1 * 2;
        //}
        //$R = log($this->calculation_values['ODE'] / $this->calculation_values['IDE']) * $this->calculation_values['ODE'] / (2 * 340);
        //$HO = 1 / (1 / $this->calculation_values['KEVA'] - ($this->calculation_values['ODE'] / ($HI1 * $this->calculation_values['IDE'])) - $R);

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

        //if ($this->calculation_values['TU2'] < 2.1 && $this->calculation_values['MODEL'] < 750 && VELEVA == 0)
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

        if ($this->calculation_values['MODEL'] < 1200)
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

        if (TV5 == 2.0 || TV5 == 0 || TV5 == 4.0)
            $R1 = log($this->calculation_values['ODC'] / $this->calculation_values['IDC']) * $this->calculation_values['ODC'] / (2 * 340);
        if (TV5 == 1.0)
            $R1 = log($this->calculation_values['ODC'] / $this->calculation_values['IDC']) * $this->calculation_values['ODC'] / (2 * 37);
        if (TV5 == 3.0 )
            $R1 = log($this->calculation_values['ODC'] / $this->calculation_values['IDC']) * $this->calculation_values['ODC'] / (2 * 21);
        if (TV5 == 5.0)
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

    public function validateChillerAttribute($attribute){
        switch (strtoupper($attribute))
        {
            case "MODEL_NUMBER":
                // $this->modulNumberDoubleEffectG2();

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
            case "CALORIFIC_VALUE":
                if (!(($this->model_values['calorific_value'] >= 8000) && ($this->model_values['calorific_value'] <= 12000)))
                {
                    return array('status' => false,'msg' => $this->notes['NOTES_CV_OR']);
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

        //"CALORIFIC_VALUE":
        if (!(($this->model_values['calorific_value'] >= 8000) && ($this->model_values['calorific_value'] <= 12000)))
        {
            return array('status' => false,'msg' => $this->notes['NOTES_CV_OR']);
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

        $this->calculation_values['GLL'] = $this->model_values['glycol_selected']; 
        $this->calculation_values['CHGLY'] = $this->model_values['glycol_chilled_water']; 
        $this->calculation_values['COGLY'] = $this->model_values['glycol_cooling_water']; 
        $this->calculation_values['TCHW11'] = $this->model_values['chilled_water_in']; 
        $this->calculation_values['TCHW12'] = $this->model_values['chilled_water_out']; 
        $this->calculation_values['GCW'] = $this->model_values['cooling_water_flow']; 
        $this->calculation_values['RCV1'] = $this->model_values['calorific_value']; 
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


        $this->calculation_values['KEVA1'] = 1 / ((1 / $this->calculation_values['KEVA']) - (0.65 / 340000.0));

        if ($this->calculation_values['TU2'] == 2 || $this->calculation_values['TU2'] == 7)
            $this->calculation_values['KEVA'] = 1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 340000.0));
        if ($this->calculation_values['TU2'] == 1 || $this->calculation_values['TU2'] == 6)
            $this->calculation_values['KEVA'] = 1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 37000.0));
        if ($this->calculation_values['TU2'] == 4)
            $this->calculation_values['KEVA'] = (1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 21000.0))) * 0.93;
        if ($this->calculation_values['TU2'] == 3)
            $this->calculation_values['KEVA'] = 1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 21000.0)) * 0.93;             
        if ($this->calculation_values['TU2'] == 8 || $this->calculation_values['TU2'] == 9)
            $this->calculation_values['KEVA'] = (1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 15000.0))) * 0.93;
        if ($this->calculation_values['TU2'] == 5)
            $this->calculation_values['KEVA'] = 1 / ((1 / 1600.0) + ($this->calculation_values['TU3'] / 15000.0));


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
            if ($this->calculation_values['TU5'] == 6)
                $this->calculation_values['KABS'] = (1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 15000))) * 0.93;
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



        if ($this->calculation_values['TU2'] < 2.1 || $this->calculation_values['TU2'] == 4)
            $this->calculation_values['IDE'] = $this->calculation_values['ODE'] - ((2.0 * ($this->calculation_values['THE'] + 0.1)) / 1000.0);
        else
            $this->calculation_values['IDE'] = $this->calculation_values['ODE'] - ((2.0 * $this->calculation_values['THE']) / 1000.0);

        if ($this->calculation_values['TU5'] < 2.1 || $this->calculation_values['TU5'] == 6)
            $this->calculation_values['IDA'] = $this->calculation_values['ODA'] - ((2.0 * ($this->calculation_values['THA'] + 0.1)) / 1000.0);
        else
            $this->calculation_values['IDA'] = $this->calculation_values['ODA'] - ((2.0 * $this->calculation_values['THA']) / 1000.0);

        if ($this->calculation_values['TV5'] == 1 || $this->calculation_values['TV5'] == 2 || $this->calculation_values['TV5'] == 0 || $this->calculation_values['TV5'] == 4)
            $this->calculation_values['IDC'] = $this->calculation_values['ODC'] - ((2.0 * ($this->calculation_values['THC'] + 0.1)) / 1000.0);
        else
            $this->calculation_values['IDC'] = $this->calculation_values['ODC'] - ((2.0 * $this->calculation_values['THC']) / 1000.0);    


      
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
            'normal_ng_calorific_value',
            'gross_ng_calorific_value',
            'normal_hsd_calorific_value',
            'gross_hsd_calorific_value',
            'normal_sko_calorific_value',
            'gross_sko_calorific_value',
            'fuel_type',
            'fuel_value_type'
        ]);

        $region_type = Auth::user()->region_type;

        if($region_type == 2)
            $region_name = Auth::user()->region->name;
        else
            $region_name = '';


        $standard_values = array('evaporator_thickness' => 0,'absorber_thickness' => 0,'condenser_thickness' => 0,'evaporator_thickness_min_range' => 0,'evaporator_thickness_max_range' => 0,'absorber_thickness_min_range' => 0,'absorber_thickness_max_range' => 0,'condenser_thickness_min_range' => 0,'condenser_thickness_max_range' => 0,'fouling_chilled_water_value' => 0,'fouling_cooling_water_value' => 0,'evaporator_thickness_change' => 1,'absorber_thickness_change' => 1,'condenser_thickness_change' => 1,'fouling_chilled_water_checked' => 0,'fouling_cooling_water_checked' => 0,'fouling_chilled_water_disabled' => 1,'fouling_cooling_water_disabled' => 1,'fouling_chilled_water_value_disabled' => 1,'fouling_cooling_water_value_disabled' => 1,'region_name'=>$region_name,'region_type'=>$region_type,'calorific_value' => 0,'std_calorific_value' => 0);


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
