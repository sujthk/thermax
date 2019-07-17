<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Log;
class DoubleSteamController extends Controller
{
    




    public function getDoubleEffectS2(){


    	
    	$default_values = $this->getModelDefaultData(130);
    	$evaporator_options = $this->getEvaporatorOptions($default_values['capacity']);
    	$absorber_options = $this->getAbsorberOptions();
    	$condenser_options = $this->getCondenserOptions();

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

		$range_calculation = $this->RANGECAL($model_values['model_number'],$model_values['chilled_water_out'],$model_values['capacity']);
		if($range_calculation['status']){
			$range_values = $range_calculation['range_values'];
			$model_values['cooling_water_ranges'] = $range_values;
		}
		else{
			return response()->json(['status'=>'fail','msg'=>$range_calculation['msg']]);
		}

		if ($model_values['chilled_water_out'] < 3.5)
		{
		    if ($model_values['steam_pressure'] < 6.01)
		    {
		        $model_values['model_name'] = "TZC S2 C3 N";
		    }
		    else
		    {
		        $model_values['model_name'] = "TZC S2 C3";
		    }
		}
		else
		{
		    if ($model_values['steam_pressure'] < 6.01)
		    {
		        $model_values['model_name'] = "TAC S2 C3 N";
		    }
		    else
		    {
		        $model_values['model_name'] = "TAC S2 C3";
		    }
		}

		if($model_values['chilled_water_out'] < 1){
			$model_values['glycol_none'] = true;
			$model_values['glycol_selected'] = 2;
		}
		else{
			$model_values['glycol_none'] = false;
			$model_values['glycol_selected'] = 2;

		}
		$glycol_change = $this->getGlycolChangeValue($model_values,$model_values['glycol_selected']);
		if ($model_values['chilled_water_out'] < 3.499 && $model_values['chilled_water_out'] > 0.99 && $model_values['glycol_chilled_water'] == 0)
		{
			$model_values['metallurgy_standard'] = false;
			$model_values['evaporator_material_value'] = 3;
			$model_values['evaporator_thickness'] = 0.8;

			$model_values = $this->chillerAttributesChanged($model_values,"EVAPORATORTUBETYPE");

		 }
		else
		{
		    $model_values['metallurgy_standard'] = true;
		}






		

		return response()->json(['status'=>'success','msg'=>'Ajax Datas','model_values'=>$model_values]);
	}

	public function updateModelDatas($post_values,$model_number){
		// Log::info("post_values = ".$post_values);

		$model_number = floatval($post_values['model_number']);
		$capacity = floatval($post_values['capacity']);
		$chilled_water_out = floatval($post_values['chilled_water_out']);
		$steam_pressure = floatval($post_values['steam_pressure']);
		$glycol_chilled_water = floatval(isset($post_values['glycol_chilled_water']) ? $post_values['glycol_chilled_water'] : 0);
		$glycol_type = floatval($post_values['glycol']);

		$model_values = $this->getModelDefaultData($model_number);

		$model_values['capacity'] = $capacity;
		$model_values['chilled_water_out'] = $chilled_water_out;
		$model_values['steam_pressure'] = $steam_pressure;
		$model_values['glycol_chilled_water'] = $glycol_chilled_water;
		$model_values['glycol_selected'] = $glycol_type;

		return $model_values;
	}

	public function getGlycolChangeValue($model_values,$glycol_type){
		switch ($glycol_type)
		{
		    
		    case 1:
	            $model_values['glycol_selected'] = 1;
	            $model_values['glycol_chilled_water'] = 0;
	            $model_values['glycol_cooling_water'] = 0;
		        $evaporator_validator = $this->validateChillerAttributesChanged($model_values,'EVAPORATORTUBETYPE');
		        if(!$evaporator_validator['status'])
		        	return $evaporator_validator;
		    break;



		}


		return  array('status' => true,'msg' => "process run successfully",'model_values' => $model_values);

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
	
	public function getModelDefaultData($model_number){



		$cooling_water_ranges = array(87.5,217.4,218.2,234.9);
		return array('model_number' => 130,'model_name' => "TAC S2 C3",'capacity' => 114,'chilled_water_in' => 12,'chilled_water_out' => 7,'min_chilled_water_out' => 0,'cooling_water_in' => 32,'cooling_water_flow' => 114,'cooling_water_in_min_range' =>25.0,'cooling_water_in_max_range' => 36.0,'cooling_water_ranges' => $cooling_water_ranges,'evaporator_material_value' => 2,'evaporator_thickness' => 0.5700,'evaporator_thickness_min_range' => 0.57,'evaporator_thickness_max_range' => 1.0,'absorber_material_value' => 2,'absorber_thickness' => 0.6500,'absorber_thickness_min_range' => 0.65,'absorber_thickness_max_range' => 1,'condenser_material_value' => 2,'condenser_thickness' => 0.6500,'condenser_thickness_min_range' => 0.65,'condenser_thickness_max_range' => 1,'glycol_selected' => 1,'glycol_none' => false,'metallurgy_standard' => true,'glycol_chilled_water' => 0.0,'glycol_cooling_water' => 0.0,'steam_pressure_min_range' => 3.5,'steam_pressure_max_range' => 10.0,'steam_pressure' => 8.0,'fouling_factor' => "standard",'fouling_non_chilled' => 0.00001,'fouling_non_cooling' => 0.00001,'fouling_ari_chilled' => 0.00002,'fouling_ari_cooling' => 0.00005,'calculate_option' => true);
	}


	public function validateChillerAttributesChanged($model_values,$attribute){

		switch (strtoupper($attribute))
		{
		    
		    case "EVAPORATORTUBETYPE":
                    if ($model_values['chilled_water_out'] < 3.5 && $model_values['glycol_chilled_water'] == 0)
                    {
                        if ($model_values['evaporator_material_value'] != 3)
                        {
                            return array('status' => false,'msg' => "cooling water flow crossing limit");
                        }
                    }
                    break;
		}


		return array('status' => true,'msg' => "process run successfully",'model_values' => $model_values);

	}


	public function chillerAttributesChanged($model_values,$attribute){

		switch (strtoupper($attribute))
		{
		    
		    case "EVAPORATORTUBETYPE":
	            if ($model_values['evaporator_material_value'] == 0 || $model_values['evaporator_material_value'] == 2)
	            {
	                if ($model_values['model_number'] < 750)
	                {
	                    
	                    $model_values['evaporator_thickness_min_range'] = 0.57;
	                }
	                else
	                {
	                    
	                    $model_values['evaporator_thickness_range'] = 0.65;
	                }
	               	$model_values['evaporator_thickness_max_range'] = 1;

	            }
	            else if ($model_values['evaporator_material_value'] == 1)
	            {
	                if ($model_values['model_number'] < 750)
	                {
	                    $model_values['evaporator_thickness_min_range'] = 0.6;
	                }
	                else
	                {
	                    $model_values['evaporator_thickness_min_range'] = 0.65;
	                }

	                $model_values['evaporator_thickness_max_range'] = 1.0;
	            }
	            else if ($model_values['evaporator_material_value'] == 4)
	            {
	                $model_values['evaporator_thickness_min_range'] = 0.9;
	                $model_values['evaporator_thickness_max_range'] = 1.2;
	            } 
	            else
	            {
	                $model_values['evaporator_thickness_min_range'] = 0.6;
	                $model_values['evaporator_thickness_max_range'] = 1.0;
	            }
		        
		    break;

		}


		return $model_values;

	}

	public function RANGECAL($model_number,$chilled_water_out,$capacity)
	{
	    $FMIN1 = 0; 
	    $FMAX1 = 0;
	    $TAPMAX = 0;
	    $FMAX = array();
	    $FMIN = array();

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


	    return array('status' => true,'msg' => "process run successfully",'f_min' => $FMIN,'f_max' => $FMAX,'flowmin' => $FLOWMN,'flow_max' => $FLOWMX,'count' => $INIT,'range_values' => $range_values);
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


    public function getChiller(){
    	$chiller = array('m_bStandanrd' => true,
    						'm_nModelNo' => '',
    						'm_CNO' => '',
    						'm_modelName' => '',
    						'm_maxCHWWorkPressure' => 8,
    						'm_maxCOWWorkPressure' => 8,
    						'm_maxHWWorkPressure' => 8,
    						'm_maxSteamWorkPressure' => 10.5,
    						'm_maxSteamDesignPressure' => 10,
    						'm_DesignPressure' => 8,
    						'm_maxHWDesignPressure' => 8,
    						'm_eFoulingFactor' => '',
    						'm_eSideArm' => '',
    						'm_eGlycolType' => '',
    						'm_eFFtype1' => '',
    						'm_eFFtype2' => '',
    						'm_eFFtype3' => '',
    						'm_eHHType' => '',
    						'm_eEconomizer' => '',
    						'm_Customer' => '',
    						'm_Project' => '',
    						'm_Enquiry' => '',
    						'm_dSteamPressure' => '',
    						'm_dMinSteamPressure' => '',
    						'm_dMaxSteamPressure' => '',
    						'm_dSteamConsumption' => '',
    						'm_dSteamConnectionDiameter' => '',
    						'm_dSteamDrainDiameter' => '',
    						'm_dHeatDuty' => '',
    						'm_dMaxHeatDuty' => '',
    						'm_dMinHeatDuty' => '',
    						'm_eCalorificValueType' => '',
    						'm_eFuelType' => '',
    						'm_dFuelConsumption' => '',
    						'm_dCalorificValue' => '',
    						'm_dFuelCVstd' => '',
    						'm_dMinCalorificValue' => '',
    						'm_dMaxCalorificValue' => '',
    						'm_dExhaustGasDuctSize' => '',
    						'm_eEngineType' => '',
    						'm_dExhaustGasFlowRate' => '',
    						'm_dExhaustGasTempIn' => '',
    						'm_dExhaustGasTempOut' => '',
    						'm_dActExhaustGasTempOut' => '',
    						'm_dMinExhaustGasTempOut' => '',
    						'm_dExhaustConnectionDiameter' => '',
    						'm_dExhaustGasFlowFullLoad' => '',
    						'm_nModelNo' => '',
    						'm_nModelNo' => '',
    						'm_nModelNo' => '',
    						'm_nModelNo' => '',
    						'm_nModelNo' => '',
    						'm_nModelNo' => '',
    						'selection' => false
    					);
    }

	
}
