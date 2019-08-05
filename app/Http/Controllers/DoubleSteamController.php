<?php

namespace App\Http\Controllers;

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

		$this->updateInputs();


		// Log::info("metallurgy updated = ".print_r($this->model_values,true));
		return response()->json(['status'=>true,'msg'=>'Ajax Datas','model_values'=>$this->model_values]);
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

	public function updateInputs(){
		$this->calculation_values['MODEL'] = $this->model_values['model_number'];
		$this->calculation_values['TON'] = $this->model_values['capacity'];
		$this->calculation_values['TUU'] = $this->model_values['fouling_factor'];
		$this->calculation_values['FFCHW1'] = $this->model_values['fouling_chilled_water_value'];
		$this->calculation_values['FFCOW1'] = $this->model_values['fouling_cooling_water_value'];
		
		if($this->model_values['metallurgy_standard']){
			$this->calculation_values['TU2'] = 0.0; 
			$this->calculation_values['TU3'] = 0.0; 
			$this->calculation_values['TU5'] = 0.0; 
			$this->calculation_values['TU6'] = 0.0; 
			$this->calculation_values['TV5'] = 0.0; 
			$this->calculation_values['TV6'] = 0.0; 
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

        $this->calculation_values['KCON']ULTHE = 450; 
        $this->calculation_values['KCON']UHTHE = 1400; $this->calculation_values['KCON']UDHE = 400; $this->calculation_values['KCON']UHR = 700;      //UHTG = 1750;

        if (MODEL < 1200)
        {
            ULTG = 1850; UHTG = 1750;
        }
        else
        {
            ULTG = 1790; UHTG = 1625;
        }

        if (MODEL < 1200)
        {
            ODE = 0.016;
            ODA = 0.016;

            if (MODEL > 950)
            {
                ODC = 0.019;
            }
            else
            {
                ODC = 0.016;
            }
        }
        else
        {
            ODE = 0.019;
            ODA = 0.019;
            ODC = 0.019;
        }
        /******** DETERMINATION OF KEVA FOR NON STD.SELECTION*****/
        if (MODEL < 750)
        {
            KEVA1 = 1 / ((1 / KEVA) - (0.57 / 340000.0));
        }
        else
        {
            KEVA1 = 1 / ((1 / KEVA) - (0.65 / 340000.0));
        }
        if (TU2 == 2)
            KEVA = 1 / ((1 / KEVA1) + (TU3 / 340000.0));
        if (TU2 == 1)
            KEVA = 1 / ((1 / KEVA1) + (TU3 / 37000.0));
        if (TU2 == 4)
            KEVA = (1 / ((1 / KEVA1) + (TU3 / 21000.0))) * 0.93;
        if (TU2 == 3)
            KEVA = 1 / ((1 / KEVA1) + (TU3 / 21000.0)) * 0.93;              //Changed to KEVA1 from 1600 on 06/11/2017 as tube metallurgy is changed
        if (TU2 == 5)
            KEVA = 1 / ((1 / 1600.0) + (TU3 / 15000.0));
        /********* VARIATION OF KABS WITH CON METALLURGY ****/
        if (TU2 == 6 || TU2 == 7 || TU2 == 8)
        {
            if (TV5 == 1)
                KM5 = 1;
            else if (TV5 == 2)
                KM5 = 1;
            else if (TV5 == 3)
                KM5 = 1;
            else if (TV5 == 4)
                KM5 = 1;
            else if (TV5 == 5)
                KM5 = 1;
            else
                KM5 = 1;
        }
        else
            KM5 = 1;
        /********* DETERMINATION OF KABS FOR NONSTD. SELECTION****/
        KABS1 = 1 / ((1 / KABS) - (0.65 / 340000));
        if (TU5 == 1)
        {
            KABS = 1 / ((1 / KABS1) + (TU6 / 37000));
        }
        else
        {
            if (TU5 == 2)
                KABS = 1 / ((1 / KABS1) + (TU6 / 340000));
            if (TU5 == 6)
                KABS = (1 / ((1 / KABS1) + (TU6 / 21000))) * 0.93;
            else
            {
                KABS1 = 1240;
                if (TU5 == 3)
                    KABS = 1 / ((1 / KABS1) + (TU6 / 37000));
                if (TU5 == 4)
                    KABS = 1 / ((1 / KABS1) + (TU6 / 340000));
                if (TU5 == 5)
                    KABS = 1 / ((1 / KABS1) + (TU6 / 21000));
                if (TU5 == 7)
                    KABS = 1 / ((1 / KABS1) + (TU6 / 15000));
            }
        }
        KABS = KABS * KM5;


        /********** DETERMINATION OF KCON IN NONSTD. SELECTION*******/
        KCON1 = 1 / ((1 / KCON) - (0.65 / 340000));         //Changed from 0.57 to 0.65 on 06/11/2017

        if (TV5 == 1)
        {
            //KCON1 = 4000;
            KCON = 1 / ((1 / KCON1) + (TV6 / 37000));
        }
        else if (TV5 == 2 )
            KCON = 1 / ((1 / KCON1) + (TV6 / 340000));
        else if (TV5 == 4)
            KCON = 1 / ((1 / KCON1) + (TV6 / 21000)) * 0.95;        
        else
        {
            KCON1 = 3000;
            if (TV5 == 3)
                KCON = 1 / ((1 / KCON1) + (TV6 / 21000));                
            if (TV5 == 5)
                KCON = 1 / ((1 / KCON1) + (TV6 / 15000));
        }           


        AEVAH = AEVA / 2;
        AEVAL = AEVA / 2;
        AABSH = AABS / 2;
        AABSL = AABS / 2;
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
       				break;
       			}
       			else{
       				return array('status' => false,'msg' => "Evaporator Thicknes is out of range");
       			}
           	break;
           	case "ABSORBER_THICKNESS":
           		$this->model_values['absorber_thickness_change'] = false;
           		if(($this->model_values['absorber_thickness'] >= $this->model_values['absorber_thickness_min_range']) && ($this->model_values['absorber_thickness'] <= $this->model_values['absorber_thickness_max_range'])){
       				break;
       			}
       			else{
       				return array('status' => false,'msg' => "Absorber Thicknes is out of range");
       			}
           	break;
           	case "CONDENSER_THICKNESS":
           		$this->model_values['condenser_thickness_change'] = false;
           		if(($this->model_values['condenser_thickness'] >= $this->model_values['condenser_thickness_min_range']) && ($this->model_values['condenser_thickness'] <= $this->model_values['condenser_thickness_max_range'])){
       				break;
       			}
       			else{
       				return array('status' => false,'msg' => "Condenser Thicknes is out of range");
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
	    // Log::info($GCWMIN1);
	    $chiller_data = $this->getChillerData();

	    $IDC = floatval($chiller_data['IDC']);
	    $IDA = floatval($chiller_data['IDA']);
	    $TNC = floatval($chiller_data['TNC']);
	    $TNAA = floatval($chiller_data['TNAA']);
	    $PODA = floatval($chiller_data['PODA']);
	    $THPA = floatval($chiller_data['THPA']);

	    // Log::info($IDC);
	    // Log::info("TNAA".$TNAA);

	    $TCP = 1;

	    if ($model_number < 1200)
	    {
	        $VAMIN = 1.33;			//Velocity limit reduced to accomodate more range of cow flow
	        $VAMAX = 2.65;
	        if ($model_number > 950)
	        {
	            $VCMIN = 1.0;
	            $VCMAX = 2.78;
	        }
	        else
	        {
	            $VCMIN = 1.0;			
	            $VCMAX = 2.65;
	        }                
	    }
	    else
	    {
	        $VAMIN = 1.39;
	        $VAMAX = 2.78;
	        $VCMIN = 1.00;
	        $VCMAX = 2.78;
	    }

	    $GCWMIN = 3.141593 / 4 * $IDC * $IDC * $VCMIN * $TNC * 3600 / $TCP;		//min required flow in condenser
	    $GCWCMAX = 3.141593 / 4 * $IDC * $IDC * $VCMAX * $TNC * 3600 / $TCP;

	    // Log::info($GCWMIN);
	    // Log::info($GCWCMAX);

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

	    return array('TCWA' => '32','AT13' => '101','LE' => '2.072','TNEV' => '304','TNAA' => '276','TNC' => '140','AEVA' => '31.0','AABS' => '28.2','ACON' => '14.3','ALTG' => '12.7','AHTG' => '10.9','ALTHE' => '13.806','AHTHE' => '10.6384','ADHE' => '2.57','AHR' => '3.73','MODEL1' => '100','KEVA' => '2790.72','KABS' => '1525.39387','SFACTOR' => '0.891','KCON' => '4200','ULTHE' => '450','ULTG' => '1850','ODE' => '0.016','ODA' => '0.016','ODC' => '0.016','AEVAH' => '15.5','AEVAL' => '15.5','AABSH' => '14.1','AABSL' => '14.1','UHTHE' => '1400','UDHE' => '400','UHR' => '700','UHTG' => '1750','IDE' => '0.01486','IDA' => '0.0145','IDC' => '0.0145','PNB' => '150','PODA' => '168.3','THPA' => '7.11');

	}


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
