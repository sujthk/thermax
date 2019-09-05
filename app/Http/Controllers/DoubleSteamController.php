<?php

namespace App\Http\Controllers;

use App\Http\Controllers\VamBaseController;
use Illuminate\Http\Request;
use App\ChillerDefaultValue;
use App\ChillerMetallurgyOption;
use Log;
class DoubleSteamController extends Controller
{
    
	private $model_values;
	private $default_model_values;
	private $model_code = "D_S2";
	private $calculation_values;

    public function getDoubleEffectS2(){


    	$chiller_default_datas = ChillerDefaultValue::where('code',$this->model_code)
    											->where('min_model','<',130)->where('max_model','>',130)->first();
    	
    	$default_values = $chiller_default_datas->default_values;
    	$default_values = json_decode($default_values,true);

    	$chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$this->model_code)
    									->where('min_model','<',130)->where('max_model','>',130)->first();

    	$chiller_options = $chiller_metallurgy_options->chillerOptions;
    	
    	$evaporator_options = $chiller_options->where('type', 'eva');
    	$absorber_options = $chiller_options->where('type', 'abs');
    	$condenser_options = $chiller_options->where('type', 'con');

    	// return $evaporator_options;
		return view('double_steam_s2')->with('default_values',$default_values)
										->with('evaporator_options',$evaporator_options)
										->with('absorber_options',$absorber_options)
										->with('condenser_options',$condenser_options)
										->with('chiller_metallurgy_options',$chiller_metallurgy_options);
	}

	public function calculateDoubleEffectS2(Request $request){
		return $request->all();
	}

	public function postAjaxDoubleEffectS2(Request $request){

		$model_values = $request->input('values');
		$changed_value = $request->input('changed_value');
		Log::info($changed_value);
		// update user values with model values

		$this->model_values = $model_values;
        $this->castToBoolean();
        // Log::info($this->model_values);


		$attribute_validator = $this->validateChillerAttribute($changed_value);

		if(!$attribute_validator['status'])
			return response()->json(['status'=>false,'msg'=>$attribute_validator['msg']]);

		// Log::info("metallurgy updated = ".print_r($this->model_values,true));
		return response()->json(['status'=>true,'msg'=>'Ajax Datas','model_values'=>$this->model_values]);
	}

	public function postDoubleEffectS2(Request $request){

		$model_values = $request->input('values');

		$this->model_values = $model_values;

		// Log::info($this->model_values);
		$validate_attributes = array('CAPACITY','CHILLED_WATER_IN','CHILLED_WATER_OUT','EVAPORATOR_TUBE_TYPE','GLYCOL_TYPE_CHANGED','GLYCOL_CHILLED_WATER','GLYCOL_COOLING_WATER','COOLING_WATER_IN','COOLING_WATER_FLOW','EVAPORATOR_THICKNESS','ABSORBER_THICKNESS','CONDENSER_THICKNESS','FOULING_CHILLED_VALUE','FOULING_COOLING_VALUE','STEAM_PRESSURE');	
		
		foreach ($validate_attributes as $key => $validate_attribute) {
			$attribute_validator = $this->validateChillerAttribute($validate_attribute);

			if(!$attribute_validator['status'])
				return response()->json(['status'=>false,'msg'=>$attribute_validator['msg'],'input_target'=>strtolower($validate_attribute)]);
		}									

		$this->model_values = $model_values;
		$this->castToBoolean();

		$this->updateInputs();
        $this->WATERPROP();
        $velocity_status = $this->VELOCITY();

        $this->CALCULATIONS();

        // Log::info(print_r($this->calculation_values,true));

        if(!$velocity_status['status'])
            return response()->json(['status'=>false,'msg'=>$velocity_status['msg'],'calculation_values'=>$this->calculation_values]);

		// $vam_base = new VamBaseController();
		// $CHGLY_VIS12 = $vam_base->EG_VISCOSITY($this->calculation_values['TCHW12'], $this->calculation_values['CHGLY']) / 1000;
  //       $CHGLY_TCON12 = $vam_base->EG_THERMAL_CONDUCTIVITY($this->calculation_values['TCHW12'], $this->calculation_values['CHGLY']);


  //       Log::info($CHGLY_VIS12);
  //       Log::info($CHGLY_TCON12);
		// Log::info("metallurgy updated = ".print_r($this->model_values,true));
		return response()->json(['status'=>true,'msg'=>'Ajax Datas','calculation_values'=>$this->calculation_values]);
	}

	public function postResetDoubleEffectS2(Request $request){
		$model_number = $request->input('model_number');

		$chiller_default_datas = ChillerDefaultValue::where('code',$this->model_code)
												->where('min_model','<',$model_number)->where('max_model','>',$model_number)->first();
		
		$default_values = $chiller_default_datas->default_values;
		$default_values = json_decode($default_values,true);

		$chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$this->model_code)
										->where('min_model','<',$model_number)->where('max_model','>',$model_number)->first();

		$chiller_options = $chiller_metallurgy_options->chillerOptions;
		
		$evaporator_options = $chiller_options->where('type', 'eva');
		$absorber_options = $chiller_options->where('type', 'abs');
		$condenser_options = $chiller_options->where('type', 'con');


		return response()->json(['status'=>true,'msg'=>'Ajax Datas','model_values'=>$default_values,'evaporator_options'=>$evaporator_options,'absorber_options'=>$absorber_options,'condenser_options'=>$condenser_options,'chiller_metallurgy_options'=>$chiller_metallurgy_options]);

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
		
	   	if($value == "false"){
	   		return false;
	   	}
	   	else if($value == "true"){
	   		return true;
	   	}
	   	else{
	   		return $value;
	   	}
	}



	public function updateInputs(){

		$chiller_data = $this->getChillerData();
		$this->calculation_values = $chiller_data;

        $constant_data = $this->getConstantData();
        $this->calculation_values = array_merge($this->calculation_values,$constant_data);


		$this->calculation_values['MODEL'] = $this->model_values['model_number'];
		$this->calculation_values['TON'] = $this->model_values['capacity'];
		$this->calculation_values['TUU'] = $this->model_values['fouling_factor'];
		$this->calculation_values['FFCHW1'] = $this->model_values['fouling_chilled_water_value'];
		$this->calculation_values['FFCOW1'] = $this->model_values['fouling_cooling_water_value'];
		
		if($this->model_values['metallurgy_standard']){

            $chiller_metallurgy_option = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$this->model_code)
                                        ->where('min_model','<',$this->model_values['model_number'])->where('max_model','>',$this->model_values['model_number'])->first();

            $chiller_options = $chiller_metallurgy_option->chillerOptions; 
                        

            $evaporator_option = $chiller_options->where('type', 'eva')->where('value',$chiller_metallurgy_option->eva_default_value)->first();
            $absorber_option = $chiller_options->where('type', 'abs')->where('value',$chiller_metallurgy_option->abs_default_value)->first();
            $condenser_option = $chiller_options->where('type', 'con')->where('value',$chiller_metallurgy_option->con_default_value)->first();
                         

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


		$this->calculation_values['TCW11'] = $this->model_values['cooling_water_in']; 
		// Glycol Selected = (1 = none, 2 = 'ethylene', 3 = 'Propylene' 
		$this->calculation_values['GL'] = $this->model_values['glycol_selected']; 
		$this->calculation_values['CHGLY'] = $this->model_values['glycol_chilled_water']; 
		$this->calculation_values['COGLY'] = $this->model_values['glycol_cooling_water']; 
		$this->calculation_values['TCHW11'] = $this->model_values['chilled_water_in']; 
		$this->calculation_values['TCHW12'] = $this->model_values['chilled_water_out']; 
		$this->calculation_values['GCW'] = $this->model_values['cooling_water_flow']; 
		$this->calculation_values['PST1'] = $this->model_values['steam_pressure']; 


		$this->DATA();

		$this->THICKNESS();
	}

	private function DATA()
    {


        if ($this->calculation_values['TCW11'] <= 32.0)
            $this->calculation_values['TCWA'] = $this->calculation_values['TCW11'];
        else
            $this->calculation_values['TCWA'] = 32.0;

        /************** MAX GEN TEMPERATURE *********/

        $this->calculation_values['AT13'] = 101;
        if ($this->calculation_values['TCW11'] < 29.4)

            $this->calculation_values['AT13'] = 99.99;
        else
            $this->calculation_values['AT13'] = (0.385 * $this->calculation_values['TCWA']) + 88.68;


        $this->calculation_values['ALTHE'] = $this->calculation_values['ALTHE'] * 1.33;
        $this->calculation_values['ALTHE'] = $this->calculation_values['ALTHE'] * 1.35;
        $this->calculation_values['AHR'] = $this->calculation_values['AHR'] * 1.1;
        $this->calculation_values['KCON'] = 3000 * 1.4;

        $this->calculation_values['ULTHE'] = 450; 
        $this->calculation_values['UHTHE'] = 1400; 
        $this->calculation_values['UDHE'] = 400; 
        $this->calculation_values['UHR'] = 700;      //UHTG = 1750;

        if ($this->calculation_values['MODEL'] < 1200)
        {
            $this->calculation_values['ULTG'] = 1850;
            $this->calculation_values['UHTG'] = 1750;
        }
        else
        {
            $this->calculation_values['ULTG'] = 1790; 
            $this->calculation_values['UHTG'] = 1625;
        }

        if ($this->calculation_values['MODEL'] < 1200)
        {
            $this->calculation_values['ODE'] = 0.016;
            $this->calculation_values['ODA'] = 0.016;

            if ($this->calculation_values['MODEL'] > 950)
            {
                $this->calculation_values['ODC'] = 0.019;
            }
            else
            {
                $this->calculation_values['ODC'] = 0.016;
            }
        }
        else
        {
            $this->calculation_values['ODE'] = 0.019;
            $this->calculation_values['ODA'] = 0.019;
            $this->calculation_values['ODC'] = 0.019;
        }
        /******** DETERMINATION OF KEVA FOR NON STD.SELECTION*****/
        if ($this->calculation_values['MODEL'] < 750)
        {
            $this->calculation_values['KEVA1'] = 1 / ((1 / $this->calculation_values['KEVA']) - (0.57 / 340000.0));
        }
        else
        {
            $this->calculation_values['KEVA1'] = 1 / ((1 / $this->calculation_values['KEVA']) - (0.65 / 340000.0));
        }
        if ($this->calculation_values['TU2'] == 2)
            $this->calculation_values['KEVA'] = 1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 340000.0));
        if ($this->calculation_values['TU2'] == 1)
            $this->calculation_values['KEVA'] = 1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 37000.0));
        if ($this->calculation_values['TU2'] == 4)
            $this->calculation_values['KEVA'] = (1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 21000.0))) * 0.93;
        if ($this->calculation_values['TU2'] == 3)
            $this->calculation_values['KEVA'] = 1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 21000.0)) * 0.93;              //Changed to KEVA1 from 1600 on 06/11/2017 as tube metallurgy is changed
        if ($this->calculation_values['TU2'] == 5)
            $this->calculation_values['KEVA'] = 1 / ((1 / 1600.0) + ($this->calculation_values['TU3'] / 15000.0));
        /********* VARIATION OF KABS WITH CON METALLURGY ****/
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
                $this->calculation_values['KABS'] = (1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 21000))) * 0.93;
            else
            {
                $this->calculation_values['KABS1'] = 1240;
                if ($this->calculation_values['TU5'] == 3)
                    $this->calculation_values['KABS'] = 1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 37000));
                if ($this->calculation_values['TU5'] == 4)
                    $this->calculation_values['KABS'] = 1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 340000));
                if ($this->calculation_values['TU5'] == 5)
                    $this->calculation_values['KABS'] = 1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 21000));
                if ($this->calculation_values['TU5'] == 7)
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

			case "CAPACITY":
				$capacity = floatval($this->model_values['capacity']);
				if($capacity <= 0){
					return array('status' => false,'msg' => "Invalid Capacity");
				}
				$this->model_values['capacity'] = $capacity;
				$range_calculation = $this->RANGECAL();
				if(!$range_calculation['status']){
					return array('status'=>false,'msg'=>$range_calculation['msg']);
				}
				break;

			case "CHILLED_WATER_IN":
				if(floatval($this->model_values['chilled_water_out']) >= floatval($this->model_values['chilled_water_in'])){
					return array('status' => false,'msg' => "Chilled water inlet temperature should be greater than Chilled Water outlet temperature");
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
				    return array('status' => false,'msg' => "Chilled water outlet temperature should be greater than Chilled Water minimum temperature");
				}
				if (floatval($this->model_values['chilled_water_out']) >= floatval($this->model_values['chilled_water_in']))
				{
				    return array('status' => false,'msg' => "Chilled water outlet temperature should be less than Chilled Water inlet temperature");
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
                    if (floatval($this->model_values['evaporator_material_value']) != 3)
                    {

                        return array('status' => false,'msg' => "cooling water flow crossing limit");
                    }
                    else{
                    	$this->model_values['evaporator_thickness_change'] = false;
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
            	}
                
                break;  
            case "GLYCOL_CHILLED_WATER":
            	if (($this->model_values['glycol_chilled_water'] > $this->model_values['glycol_max_chilled_water'] || $this->model_values['glycol_chilled_water'] < $this->model_values['glycol_min_chilled_water']))
            	{
            	    if ($this->model_values['glycol_min_chilled_water'] == 10)
            	    {
            	        return array('status' => false,'msg' => "Min glycol chilled water is 10");
            	    }
            	    else if ($this->model_values['glycol_min_chilled_water'] == 7.5)
            	    {
            	        return array('status' => false,'msg' => "Min glycol chilled water is 7.5");
            	    }
            	    else
            	    {
            	        return array('status' => false,'msg' => "Min glycol chilled water is 0");
            	    }
            	}
            break;
           	case "GLYCOL_COOLING_WATER":
           		if (($this->model_values['glycol_cooling_water'] > $this->model_values['glycol_max_cooling_water']))
           		{
           		    return array('status' => false,'msg' => "Glycol Cooling water temperature is high");
           		}
           	break;
           	case "COOLING_WATER_IN":
           		if (!(($this->model_values['cooling_water_in'] >= $this->model_values['cooling_water_in_min_range']) && ($this->model_values['cooling_water_in'] <= $this->model_values['cooling_water_in_max_range'])))
           		{
           		    return array('status' => false,'msg' => "Cooling Water is not in range");
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
           			return array('status' => false,'msg' => "Cooling Water flow is not in range");
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
       				return array('status' => false,'msg' => "Evaporator Thickness is out of range");
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
       				return array('status' => false,'msg' => "Absorber Thickness is out of range");
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
       				return array('status' => false,'msg' => "Condenser Thickness is out of range");
       			}
           	break;
           	case "FOULING_CHILLED_VALUE":
           		// Log::info(print_r($this->model_values,true));
           		if($this->model_values['fouling_factor'] == 'non_standard'){
           			if($this->model_values['fouling_chilled_water_value'] <= $this->model_values['fouling_non_chilled']){
           				return array('status' => false,'msg' => "Fouling Chilled Water is less than min value");
           			}
           		}
           		if($this->model_values['fouling_factor'] == 'ari'){
           			if($this->model_values['fouling_chilled_water_value'] <= $this->model_values['fouling_ari_chilled']){
           				return array('status' => false,'msg' => "Fouling Chilled Water is less than min value");
           			}
           		}
           		
           	break;
           	case "FOULING_COOLING_VALUE":
           		// Log::info(print_r($this->model_values,true));
           		if($this->model_values['fouling_factor'] == 'non_standard'){
           			if($this->model_values['fouling_cooling_water_value'] <= $this->model_values['fouling_non_cooling']){
           				return array('status' => false,'msg' => "Fouling Cooling Water is less than min value");
           			}
           		}
           		if($this->model_values['fouling_factor'] == 'ari'){
           			if($this->model_values['fouling_cooling_water_value'] <= $this->model_values['fouling_ari_cooling']){
           				return array('status' => false,'msg' => "Fouling Cooling Water is less than min value");
           			}
           		}
           		
           	break;
           	case "STEAM_PRESSURE":
           		if (!(($this->model_values['steam_pressure'] >= $this->model_values['steam_pressure_min_range']) && ($this->model_values['steam_pressure'] <= $this->model_values['steam_pressure_max_range'])))
           		{
           		    return array('status' => false,'msg' => "Steam Pressure is not in range");
           		}
           	break;

		}


		return array('status' => true,'msg' => "process run successfully");

	}

	public function onChangeMetallurgyOption(){
		if($this->model_values['metallurgy_standard']){
			$chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$this->model_code)
    									->where('min_model','<',$this->model_values['model_number'])->where('max_model','>',$this->model_values['model_number'])->first();

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

		$glycol_validator = $this->validateChillerAttribute('GLYCOLTYPECHANGED');
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
			$this->model_values['metallurgy_standard'] = false;
			$this->model_values['evaporator_material_value'] = 3;
			$this->model_values['evaporator_thickness'] = 0.8;
			$this->model_values['evaporator_thickness_change'] = false;
			// $this->chillerAttributesChanged("EVAPORATORTUBETYPE");

		}
		else
		{
		    $this->model_values['metallurgy_standard'] = true;
		    $this->model_values['evaporator_thickness_change'] = true;
		}

		$evaporator_validator = $this->validateChillerAttribute('EVAPORATORTUBETYPE');
		if(!$evaporator_validator['status'])
			return array('status'=>false,'msg'=>$evaporator_validator['msg']);

		// Log::info("metallurgy updated = ".print_r($this->model_values,true));
		$this->onChangeMetallurgyOption();

		return  array('status' => true,'msg' => "process run successfully");
	}

    public function VELOCITY(){
        // Log::info(print_r($this->calculation_values,true));    


        $IDA = floatval($this->calculation_values['IDA']);
        $TNAA = floatval($this->calculation_values['TNAA']);


        $GCW = floatval($this->calculation_values['GCW']);
        $model_number = $this->calculation_values['MODEL'];

        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$this->model_code)
                                        ->where('min_model','<',$model_number)->where('max_model','>',$model_number)->first();

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

        $this->calculation_values['VC'] = ($this->calculation_values['GCWC'] * 4) / (3.141593 * $this->calculation_values['IDC'] * $this->calculation_values['IDC'] * $this->calculation_values['TNC'] * 3600 / $this->calculation_values['TCP']);

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
                    return  array('status' => false,'msg' => "chilled water velocity low");
                }
            }
            if ($this->calculation_values['VEA'] > $this->calculation_values['VEMAX'])                        // 06/11/2017
            {
                if ($this->calculation_values['TP'] == 1)
                {
                    return  array('status' => false,'msg' => "chilled water velocity high");
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
                    return  array('status' => false,'msg' => "chilled water velocity low");
                }
            }


            if ($this->calculation_values['VEA'] > $this->calculation_values['VEMAX'])                        // 14 FEB 2012
            {
                if ($this->calculation_values['TP'] == 1)
                {
                    return  array('status' => false,'msg' => "chilled water velocity high");
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

        if ($this->calculation_values['FLE'] > 11)
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
        $this->calculation_values['VPE2'] = (0.5 * $this->calculation_values['GCHW'] * 4) / (3.141593 * $this->calculation_values['PIDE2'] * $this->calculation_values['PIDE2'] * 3600);
        $this->calculation_values['VPBR'] = (0.5 * $this->calculation_values['GCHW'] * 4) / (3.141593 * $this->calculation_values['PIDE1'] * $this->calculation_values['PIDE1'] * 3600);

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
       $this->calculation_values['REPE2'] = ($this->calculation_values['PIDE2'] * $this->calculation_values['VPE2'] * $this->calculation_values['CHGLY_ROW22']) / $this->calculation_values['CHGLY_VIS22'];
       $this->calculation_values['REBR'] = ($this->calculation_values['PIDE1'] * $this->calculation_values['VPBR'] * $this->calculation_values['CHGLY_ROW22']) / $this->calculation_values['CHGLY_VIS22'];           //REYNOLDS NO IN PIPE1
       
       $this->calculation_values['FF1'] = 1.325 / pow(log((0.0457 / (3.7 * $this->calculation_values['PIDE1'] * 1000)) + (5.74 / pow($this->calculation_values['REPE1'], 0.9))), 2);       //FRICTION FACTOR CAL
       $this->calculation_values['FF2'] = 1.325 / pow(log((0.0457 / (3.7 * $this->calculation_values['PIDE2'] * 1000)) + (5.74 / pow($this->calculation_values['REPE2'], 0.9))), 2);
       $this->calculation_values['FF3'] = 1.325 / pow(log((0.0457 / (3.7 * $this->calculation_values['PIDE1'] * 1000)) + (5.74 / pow($this->calculation_values['REBR'], 0.9))), 2);


       $this->calculation_values['FL1'] = ($this->calculation_values['FF1'] * ($this->calculation_values['SL1'] + $this->calculation_values['SL8']) / $this->calculation_values['PIDE1']) * ($this->calculation_values['VPE1'] * $this->calculation_values['VPE1'] / (2 * 9.81));
       $this->calculation_values['FL2'] = ($this->calculation_values['FF2'] * ($this->calculation_values['SL3'] + $this->calculation_values['SL4'] + $this->calculation_values['SL5'] + $this->calculation_values['SL6']) / $this->calculation_values['PIDE2']) * ($this->calculation_values['VPE2'] * $this->calculation_values['VPE2'] / (2 * 9.81));
       $this->calculation_values['FL3'] = ($this->calculation_values['FF3'] * ($this->calculation_values['SL2'] + $this->calculation_values['SL7']) / $this->calculation_values['PIDE1']) * ($this->calculation_values['VPBR'] * $this->calculation_values['VPBR'] / (2 * 9.81));
       $this->calculation_values['FL4'] = (2 * $this->calculation_values['FT1'] * 20 * $this->calculation_values['VPBR'] * $this->calculation_values['VPBR'] / (2 * 9.81)) + (2 * $this->calculation_values['FT2'] * 60 * $this->calculation_values['VPE2'] * $this->calculation_values['VPE2'] / (2 * 9.81)) + (2 * $this->calculation_values['FT2'] * 14 * $this->calculation_values['VPE2'] * $this->calculation_values['VPE2'] / (2 * 9.81));
       $this->calculation_values['FL5'] = ($this->calculation_values['VPE2'] * $this->calculation_values['VPE2'] / (2 * 9.81)) + (0.5 * $this->calculation_values['VPE2'] * $this->calculation_values['VPE2'] / (2 * 9.81));

       $this->calculation_values['FLP'] = $this->calculation_values['FL1'] + $this->calculation_values['FL2'] + $this->calculation_values['FL3'] + $this->calculation_values['FL4'] + $this->calculation_values['FL5'];      //EVAPORATOR PIPE LOSS

       $this->calculation_values['RE'] = ($this->calculation_values['VEA'] * $this->calculation_values['IDE'] * $this->calculation_values['CHGLY_ROW22']) / $this->calculation_values['CHGLY_VIS22']; 


       if (($this->calculation_values['MODEL'] < 750 && ($this->calculation_values['TU2'] == 1 || $this->calculation_values['TU2'] == 2 )||($this->calculation_values['MODEL'] < 1200 && $this->calculation_values['TU2'] == 3)))
       {
           $this->calculation_values['F'] = 1.325 / pow(log((1.53 / (3.7 * $this->calculation_values['IDE'] * 1000)) + (5.74 / pow($this->calculation_values['RE'], 0.9))), 2);
           $this->calculation_values['FE1'] = $this->calculation_values['F'] * $this->calculation_values['LE'] * $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (2 * 9.81 * $this->calculation_values['IDE']);
       }
       else if ($this->calculation_values['MODEL'] > 1200 && $this->calculation_values['TU2'] == 3)                                         //06/11/2017   Changed for SS FInned
       {
           $this->calculation_values['F'] = (1.325 / pow(log((1.53 / (3.7 * $this->calculation_values['IDE'] * 1000)) + (5.74 / pow($this->calculation_values['RE'], 0.9))), 2)) * ((-0.0315 * $this->calculation_values['VEA']) + 0.85);
           $this->calculation_values['FE1'] = $this->calculation_values['F'] * $this->calculation_values['LE'] * $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (2 * 9.81 * $this->calculation_values['IDE']);

       }
       else if (($this->calculation_values['MODEL'] < 1200 && $this->calculation_values['MODEL'] > 750 && ($this->calculation_values['TU2'] == 1 || $this->calculation_values['TU2'] == 2) ||($this->calculation_values['MODEL'] < 1200 && $this->calculation_values['TU2'] == 4)))                  // 12% AS PER EXPERIMENTATION      
       {
           $this->calculation_values['F'] = (0.0014 + (0.137 / pow($this->calculation_values['RE'], 0.32))) * 1.12;
           $this->calculation_values['FE1'] = 2 * $this->calculation_values['F'] * $this->calculation_values['LE'] * $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (9.81 * $this->calculation_values['IDE']);
       }
       else if ($this->calculation_values['MODEL'] > 1200 && ($this->calculation_values['TU2'] == 1 || $this->calculation_values['TU2'] == 2 || $this->calculation_values['TU2'] == 4))
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


    public function CALCULATIONS(){
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

        if ($this->calculation_values['MODEL'] < 750 && $this->calculation_values['TU2'] == 0)                            //12.13.2011
        {
            $VEVA = 0.7;
        }
        else if (($this->calculation_values['MODEL'] < 750 && ($this->calculation_values['TU2'] == 1 || $this->calculation_values['TU2'] == 2)) || ($this->calculation_values['MODEL'] < 1200 && $this->calculation_values['TU2'] == 3))
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
        //R = log($this->calculation_values['ODC'] / $this->calculation_values['IDC']) * $this->calculation_values['ODC'] / (2 * 340);
        //HO = 1 / (1 / KCON - ($this->calculation_values['ODC'] / (HI1 * $this->calculation_values['IDC'])) - R);

        if ($this->calculation_values['TV5'] == 2.0 || $this->calculation_values['TV5'] == 0 || $this->calculation_values['TV5'] == 4 )
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

	
	
	public function RANGECAL()
	{
	    $FMIN1 = 0; 
	    $FMAX1 = 0;
	    $TAPMAX = 0;
	    $FMAX = array();
	    $FMIN = array();
	    $model_number = $this->model_values['model_number'];
	    $chilled_water_out = $this->model_values['chilled_water_out'];
	    $capacity = $this->model_values['capacity'];

	    $GCWMIN1 = $this->RANGECAL1($model_number,$chilled_water_out,$capacity);
	    // Log::info("Range");

        $this->updateInputs();
        // Log::info("calculation = ".print_r($this->calculation_values,true));

	    // $chiller_data = $this->getChillerData();

	    $IDC = floatval($this->calculation_values['IDC']);
	    $IDA = floatval($this->calculation_values['IDA']);
	    $TNC = floatval($this->calculation_values['TNC']);
	    $TNAA = floatval($this->calculation_values['TNAA']);
	    $PODA = floatval($this->calculation_values['PODA']);
	    $THPA = floatval($this->calculation_values['THPA']);

	    // Log::info($IDC);
     //    Log::info("IDC=".$IDC);
     //    Log::info("IDA=".$IDA);
	    // Log::info("TNC=".$TNC);
     //    Log::info("TNAA=".$TNAA);
     //    Log::info("PODA=".$PODA);
     //    Log::info("THPA=".$THPA);
    

        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$this->model_code)
                                        ->where('min_model','<',$model_number)->where('max_model','>',$model_number)->first();

        $chiller_options = $chiller_metallurgy_options->chillerOptions;
        
        // $evaporator_option = $chiller_options->where('type', 'eva')->where('value',$this->model_values['evaporator_material_value'])->first();
        $absorber_option = $chiller_options->where('type', 'abs')->where('value',$this->model_values['absorber_material_value'])->first();
        $condenser_option = $chiller_options->where('type', 'con')->where('value',$this->model_values['condenser_material_value'])->first();

	    $TCP = 1;
        $VAMIN = $absorber_option->metallurgy->abs_min_velocity;          
        $VAMAX = $absorber_option->metallurgy->abs_max_velocity;
        $VCMIN = $condenser_option->metallurgy->con_min_velocity;
        $VCMAX = $condenser_option->metallurgy->con_max_velocity;
        // Log::info($absorber_option->id);
        // Log::info($VAMIN);


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

        // Log::info("vamin=".$VAMIN);
        // Log::info("vamax=".$VAMAX);
        // Log::info("vcmin=".$VCMIN);
        // Log::info("vcamx=".$VCMAX);

	    $GCWMIN = 3.141593 / 4 * $IDC * $IDC * $VCMIN * $TNC * 3600 / $TCP;		//min required flow in condenser
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
	        
	        return array('status' => false,'msg' => "cooling water flow crossing limit");
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
	        
	        return array('status' => false,'msg' => "cooling water flow crossing limit");
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

	   	// $range_values = array_sort($range_values);

	   	// Log::info($range_values);
	   	// for ($i=0; $i < $INIT; $i++) { 
	   	// 	$range_values .= "(".$FMIN[$i]." - ".$FMAX[$i].")<br>";
	   	// }

	   	$this->model_values['cooling_water_ranges'] = $range_values;


	    return array('status' => true,'msg' => "process run successfully");
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
                                        ->where('min_model','<',$this->model_values['model_number'])->where('max_model','>',$this->model_values['model_number'])->first();

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

	    return array('TCWA' => '32','AT13' => '101','LE' => '2.072','TNEV' => '304','TNAA' => '276','TNC' => '140','AEVA' => '31.0','AABS' => '28.2','ACON' => '14.3','ALTG' => '12.7','AHTG' => '10.9','ALTHE' => '13.806','AHTHE' => '10.6384','ADHE' => '2.57','AHR' => '3.73','MODEL1' => '100','KEVA' => '2790.72','KABS' => '1525.39387','SFACTOR' => '0.891','KCON' => '4200','ULTHE' => '450','ULTG' => '1850','ODE' => '0.016','ODA' => '0.016','ODC' => '0.016','AEVAH' => '15.5','AEVAL' => '15.5','AABSH' => '14.1','AABSL' => '14.1','UHTHE' => '1400','UDHE' => '400','UHR' => '700','UHTG' => '1750','IDE' => '0.01486','IDA' => '0.0145','IDC' => '0.0145','PNB' => '150','PODA' => '168.3','THPA' => '7.11','PNB1' => '125','PNB2' => '100','SL1' => '0.49','SL2' => '0.82','SL3' => '0.348','SL4' => '0.204','SL5' => '0.204','SL6' => '0.123','SL7' => '0.82','SL8' => '0.49','SHE' => '1.525','PNB' => '150','PLSI' => '0.660','PLSO' => '0.568','PLS2' => '0.481','SHA' => '1.946');

	}

    public function getConstantData()
    {

        return array('VEMIN1' => '0.9','TEPMAX' => '4');

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



	// public function loadSpecSheetData(){
	// 	$model_number = floatval($this->model_values['model_number']);
	// 	switch ($model_number) {
	// 		case 130:
	// 			if ($this->model_values['chilled_water_out'] < 3.5)
	// 			{
	// 			    if ($this->model_values['steam_pressure'] < 6.01)
	// 			    {
	// 			        $this->model_values['model_name'] = "TZC S2 C3 N";
	// 			    }
	// 			    else
	// 			    {
	// 			        $this->model_values['model_name'] = "TZC S2 C3";
	// 			    }
	// 			}
	// 			else
	// 			{
	// 			    if ($this->model_values['steam_pressure'] < 6.01)
	// 			    {
	// 			        $this->model_values['model_name'] = "TAC S2 C3 N";
	// 			    }
	// 			    else
	// 			    {
	// 			        $this->model_values['model_name'] = "TAC S2 C3";
	// 			    }
	// 			}
	// 			break;
			
	// 		default:
	// 			# code...
	// 			break;
	// 	}

	// }

	
}
