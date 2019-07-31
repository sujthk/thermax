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

    public function getDoubleEffectS2(){


    	$chiller_default_datas = ChillerDefaultValue::where('code',$this->model_code)->where('model',130)->first();
    	// return $chiller_default_datas;
    	$default_values = $chiller_default_datas->default_values;
    	$default_values = json_decode($default_values,true);

    	$chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$this->model_code)->where('model',130)->first();

    	$chiller_options = $chiller_metallurgy_options->chillerOptions;
    	
    	$evaporator_options = $chiller_options->where('type', 'eva');
    	$absorber_options = $chiller_options->where('type', 'abs');
    	$condenser_options = $chiller_options->where('type', 'con');

    	// return $evaporator_options;
		return view('double_steam_s2')->with('default_values',$default_values)
										->with('evaporator_options',$evaporator_options)
										->with('absorber_options',$absorber_options)
										->with('condenser_options',$condenser_options);
	}

	public function calculateDoubleEffectS2(Request $request){
		return $request->all();
	}

	public function postAjaxDoubleEffectS2(Request $request){
		$post_values = $request->all();
		$model_values = $request->input('values');
		$changed_value = $request->input('changed_value');
		Log::info($changed_value);
		// update user values with model values
		// $model_values = $this->updateModelDatas($post_values,$model_number);
		$this->model_values = $model_values;

		$chiller_default_datas = ChillerDefaultValue::where('code',$this->model_code)->where('model',$this->model_values['model_number'])->first();
		// return $chiller_default_datas;
		$default_values = $chiller_default_datas->default_values;
		$this->default_model_values = json_decode($default_values,true);


		// Log::info($this->model_values);

		$attribute_validator = $this->validateChillerAttribute($changed_value);

		if(!$attribute_validator['status'])
			return response()->json(['status'=>false,'msg'=>$attribute_validator['msg']]);


		return response()->json(['status'=>true,'msg'=>'Ajax Datas','model_values'=>$this->model_values]);
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

			case "CHILLEDWATERIN":
				if(floatval($this->model_values['chilled_water_out']) >= floatval($this->model_values['chilled_water_in'])){
					return array('status' => false,'msg' => "Chilled water inlet temperature should be greater than Chilled Water outlet temperature");
				}
				break;

			case "CHILLEDWATEROUT":
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
		    
		    case "EVAPORATORTUBETYPE":
                if (floatval($this->model_values['chilled_water_out']) < 3.5 && floatval($this->model_values['glycol_chilled_water']) == 0)
                {
                    if (floatval($this->model_values['evaporator_material_value']) != 3)
                    {
                        return array('status' => false,'msg' => "cooling water flow crossing limit");
                    }
                }
                break;

            case "GLYCOLTYPECHANGED":
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
            case "GLYCOLCHILLEDWATER":
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
           	case "GLYCOLCOOLINGWATER":
           		if (($this->model_values['glycol_cooling_water'] > $this->model_values['glycol_max_cooling_water']))
           		{
           		    return array('status' => false,'msg' => "Glycol Cooling water temperature is high");
           		}
           	break;
           	case "COOLINGWATERIN":
           		if (!(($this->model_values['cooling_water_in'] >= $this->model_values['cooling_water_in_min_range']) && ($this->model_values['cooling_water_in'] <= $this->model_values['cooling_water_in_max_range'])))
           		{
           		    return array('status' => false,'msg' => "Cooling Water is not in range");
           		}
           	break;
           	case "COOLINGWATERFLOW":
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
		}


		return array('status' => true,'msg' => "process run successfully");

	}

	public function onChangeMetallurgyOption(){
		if($this->model_values['metallurgy_standard']){
			$this->model_values['evaporator_material_value'] = $this->default_model_values['evaporator_material_value'];
			$this->model_values['evaporator_thickness'] = $this->default_model_values['evaporator_thickness'];
			$this->model_values['absorber_material_value'] = $this->default_model_values['absorber_material_value'];
			$this->model_values['absorber_thickness'] = $this->default_model_values['absorber_thickness'];
			$this->model_values['condenser_material_value'] = $this->default_model_values['condenser_material_value'];
			$this->model_values['condenser_thickness'] = $this->default_model_values['condenser_thickness'];
		}

	}


	public function processAttribChanged(){

	}


	public function chillerAttributesChanged($attribute){

		switch (strtoupper($attribute))
		{
		    
		    case "EVAPORATORTUBETYPE":
	            if ($this->model_values['evaporator_material_value'] == 0 || $this->model_values['evaporator_material_value'] == 2)
	            {
	                if ($this->model_values['model_number'] < 750)
	                {
	                    
	                    $this->model_values['evaporator_thickness_min_range'] = 0.57;
	                }
	                else
	                {
	                    
	                    $this->model_values['evaporator_thickness_range'] = 0.65;
	                }
	               	$this->model_values['evaporator_thickness_max_range'] = 1;

	            }
	            else if ($this->model_values['evaporator_material_value'] == 1)
	            {
	                if ($this->model_values['model_number'] < 750)
	                {
	                    $this->model_values['evaporator_thickness_min_range'] = 0.6;
	                }
	                else
	                {
	                    $this->model_values['evaporator_thickness_min_range'] = 0.65;
	                }

	                $this->model_values['evaporator_thickness_max_range'] = 1.0;
	            }
	            else if ($this->model_values['evaporator_material_value'] == 4)
	            {
	                $this->model_values['evaporator_thickness_min_range'] = 0.9;
	                $this->model_values['evaporator_thickness_max_range'] = 1.2;
	            } 
	            else
	            {
	                $this->model_values['evaporator_thickness_min_range'] = 0.6;
	                $this->model_values['evaporator_thickness_max_range'] = 1.0;
	            }
		    	break;

		}


	}

	public function loadSpecSheetData(){
		$model_number = floatval($this->model_values['model_number']);
		switch ($model_number) {
			case 130:
				if ($this->model_values['chilled_water_out'] < 3.5)
				{
				    if ($this->model_values['steam_pressure'] < 6.01)
				    {
				        $this->model_values['model_name'] = "TZC S2 C3 N";
				    }
				    else
				    {
				        $this->model_values['model_name'] = "TZC S2 C3";
				    }
				}
				else
				{
				    if ($this->model_values['steam_pressure'] < 6.01)
				    {
				        $this->model_values['model_name'] = "TAC S2 C3 N";
				    }
				    else
				    {
				        $this->model_values['model_name'] = "TAC S2 C3";
				    }
				}
				break;
			
			default:
				# code...
				break;
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
			$this->chillerAttributesChanged("EVAPORATORTUBETYPE");

		}
		else
		{
		    $this->model_values['metallurgy_standard'] = true;
		}

		$evaporator_validator = $this->validateChillerAttribute('EVAPORATORTUBETYPE');
		if(!$evaporator_validator['status'])
			return array('status'=>false,'msg'=>$evaporator_validator['msg']);

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


    
	public function getEvaporatorOptions($model_number){
		$eva_options = array();
		$model_number = floatval($model_number);

		if($model_number < 750){
			$eva_options[] = array('name' => 'CuNi (90:10,95:5) Finned','value' => '1');
			$eva_options[] = array('name' => 'Cu Finned','value' => '2');
		}
		else{
			$eva_options[] = array('name' => 'CuNi (90:10,95:5) Mini Finned','value' => '1');
			$eva_options[] = array('name' => 'Cu Mini Finned','value' => '2');
		}

		$eva_options[] = array('name' => 'SS Finned','value' => '3');
		$eva_options[] = array('name' => 'SS Mini Finned','value' => '4');
		$eva_options[] = array('name' => 'Titanium Plain','value' => '5');

		return $eva_options;

	}

	public function getAbsorberOptions(){
		$abs_options = array();

		$abs_options[] = array('name' => 'CuNi (90:10,95:5) Mini Finned','value' => '1');
		$abs_options[] = array('name' => 'Cu Mini Finned','value' => '2');
		$abs_options[] = array('name' => 'SS Plain ERW','value' => '5');
		$abs_options[] = array('name' => 'SS Mini finned','value' => '6');
		$abs_options[] = array('name' => 'Titanium Plain','value' => '7');

		return $abs_options;

	}

	public function getCondenserOptions(){
		$con_options = array();

		$con_options[] = array('name' => 'CuNi (90:10,95:5) Mini Finned','value' => '1');
		$con_options[] = array('name' => 'Cu Mini Finned','value' => '2');
		$con_options[] = array('name' => 'SS Plain ERW','value' => '3');
		$con_options[] = array('name' => 'SS Mini finned','value' => '4');
		$con_options[] = array('name' => 'Titanium Plain','value' => '5');

		return $con_options;

	}


	public function getChillerData()
	{

	    return array('TCWA' => '32','AT13' => '101','LE' => '2.072','TNEV' => '304','TNAA' => '276','TNC' => '140','AEVA' => '31.0','AABS' => '28.2','ACON' => '14.3','ALTG' => '12.7','AHTG' => '10.9','ALTHE' => '13.806','AHTHE' => '10.6384','ADHE' => '2.57','AHR' => '3.73','MODEL1' => '100','KEVA' => '2790.72','KABS' => '1525.39387','SFACTOR' => '0.891','KCON' => '4200','ULTHE' => '450','ULTG' => '1850','ODE' => '0.016','ODA' => '0.016','ODC' => '0.016','AEVAH' => '15.5','AEVAL' => '15.5','AABSH' => '14.1','AABSL' => '14.1','UHTHE' => '1400','UDHE' => '400','UHR' => '700','UHTG' => '1750','IDE' => '0.01486','IDA' => '0.0145','IDC' => '0.0145','PNB' => '150','PODA' => '168.3','THPA' => '7.11');

	}

	
}
