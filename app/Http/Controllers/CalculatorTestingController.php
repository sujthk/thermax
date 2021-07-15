<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use App\CalculationKey;
use App\AutoTesting;
use Log;
use Excel;
use DB;

use App\Http\Controllers\DoubleSteamController;
use App\Http\Controllers\DoubleH2SteamController;

class CalculatorTestingController extends Controller
{
    public function getAutoTesting(){

        $calculator_keys = CalculationKey::orderBy('created_at', 'desc')->get();

        return view('calculator_testing')->with('calculator_keys',$calculator_keys);
    }

    public function exportCalculatorForamt(Request $request){
        // return $request->all();

        $this->validate($request, [
            'code' => 'required',
        ]);


        $data = array('S.No', 
        				'model_name',
        				'model_number',
                        'capacity',
        				'chilled_water_in',
                        'chilled_water_out',
        				'cooling_water_in',
        				'cooling_water_flow',
        				'glycol_selected',
        				'glycol_chilled_water',
        				'glycol_cooling_water',
        				'metallurgy_standard',
        				'evaporator_material_value',
        				'evaporator_thickness',
        				'absorber_material_value',
        				'absorber_thickness',
        				'condenser_material_value',
        				'condenser_thickness',
        				'fouling_factor',
        				'fouling_chilled_water_value',
        				'fouling_cooling_water_value',
        				'region_type'
        			);

        if($request->code == "D_S2" || $request->code == "S1"){
            $s2_data = array('steam_pressure');
            $data = array_merge($data, $s2_data);
        }

        if($request->code == 'D_H2'){
            $h2_data = array('hot_water_in','hot_water_out','all_work_pr_hw');
            $data = array_merge($data, $h2_data);
        }

        if($request->code == 'L5' || $request->code == 'L1'){
            $h2_data = array('glycol_hot_water','fouling_hot_water_value','hot_water_in','hot_water_flow','generator_tube_value');
            $data = array_merge($data, $h2_data);
        }

        if($request->code == 'D_G2'){
            $g2_data = array('fuel_type','fuel_value_type','calorific_value');
            $data = array_merge($data, $g2_data);
        }

        if($request->code == 'D_E2'){
            $g2_data = array('exhaust_gas_in','exhaust_gas_out','gas_flow','gas_flow_load','design_load','pressure_drop','engine_type','economizer');
            $data = array_merge($data, $g2_data);
        }

        if($request->code == 'H1'){
            $h1_data = array('hot_water_in','hot_water_out');
            $data = array_merge($data, $h1_data);
        }

        if($request->code == 'CH_S2'){
            $ch_s2_data = array('steam_pressure','heat_duty','hot_water_in','hot_water_out');
            $data = array_merge($data, $ch_s2_data);
        }

        if($request->code == 'CH_G2'){
            $ch_g2_data = array('fuel_type','fuel_value_type','calorific_value','heat_duty','hot_water_in','hot_water_out');
            $data = array_merge($data, $ch_g2_data);
        }

        
        return Excel::create($request->code.'_chiller_input', function($excel) use ($data) {
            $excel->sheet('mySheet', function($sheet) use ($data)
            {
                $sheet->fromArray($data);
            });
        })->download('xlsx');

    }

    public function importCalculatorForamt(Request $request)
    {
        $this->validate($request, [
            'code' => 'required',
            'data_file' => 'required',
        ]);



        $path = Input::file('data_file')->getRealPath();
        $data = Excel::load($path, function($reader) {})->get();
        $datas = collect($data)->toArray();
        

        DB::table('auto_testing')->where('user_id',Auth::user()->id)->delete();

        return view('auto_testing')->with('datas',$datas)
                                        ->with('code',$request->code);
                                         
       
        
    }

    public function testingCalculator(Request $request){
        $code = $request->input('code');
        $calculator_input = $request->input('calculator_input');


        if(empty($code) || empty($calculator_input)){
            return response()->json(['status'=>false,'msg'=>'Input Error']);
        }

        $auto_testing = new AutoTesting;
        $auto_testing->user_id = Auth::user()->id;
        $auto_testing->input_values = json_encode($request->calculator_input);
        $auto_testing->save();

        if($code == 'D_S2'){
            $s2 = new DoubleSteamController();
            $result = $s2->testingS2Calculation($calculator_input); 
        }

        if($code == 'S1'){
            $s1 = new S1SeriesController();
            $result = $s1->testingS1Calculation($calculator_input); 
        }

        if($code == 'D_H2'){
            $h2 = new DoubleH2SteamController();
            $result = $h2->testingH2Calculation($calculator_input);
        }

        if($code == 'L5'){
            $l5 = new L5SeriesController();
            $result = $l5->testingL5Calculation($calculator_input);
        }

        if($code == 'L1'){
            $l1 = new L1SeriesController();
            $result = $l1->testingL1Calculation($calculator_input);
        }

        if($code == 'D_G2'){
            $g2 = new DoubleG2SteamController();
            $result = $g2->testingG2Calculation($calculator_input);
        }

        if($code == 'D_E2'){
            $e2 = new E2SeriesController();
            $result = $e2->testingE2Calculation($calculator_input);
        }

        if($code == 'H1'){
            $h1 = new H1SeriesController();
            $result = $h1->testingH1Calculation($calculator_input);
        }

        if($code == 'CH_S2'){
            $ch_s2 = new ChillerHeaterS2Controller();
            $result = $ch_s2->testingCHS2Calculation($calculator_input);
        }

        if($code == 'CH_G2'){
            $ch_g2 = new ChillerHeaterG2Controller();
            $result = $ch_g2->testingCHG2Calculation($calculator_input);
        }

        $result = array_where($result, function ($value, $key) {
            return is_float($value) ?  is_nan($value) ? "" : $value : $value;
        });

        
        $auto_testing->output_values = json_encode($result);
        $auto_testing->save();
        // Log::info($result);
        return response()->json(['status'=>true,'msg'=>'Calculated Values','result'=>$result]);

    }

    public function downloadTestedCalculator(Request $request){
        
        $tested_results = AutoTesting::where('user_id',Auth::user()->id)->get();


        $datas = array();
        $failure_data = array();

        foreach ($tested_results as $tested_result) {
            $input_values = json_decode($tested_result->input_values,true);
            $output_values = json_decode($tested_result->output_values,true);

            if(!empty($tested_result->output_values)){
                
                $datas[] = $this->outputExcelFormat($input_values,$output_values,$input_values['calculator_code']);
                
            }
            else{
                $failure_data[] = $input_values;
            }

                
        }


        return Excel::create('calculation_output', function($excel) use ($datas,$failure_data) {
            $excel->sheet('calculations', function($sheet) use ($datas)
            {
                $sheet->fromArray($datas);
            });
            $excel->sheet('error', function($sheet) use ($failure_data)
            {
                $sheet->fromArray($failure_data);
            });
        })->download('xlsx');

    }

    public function outputExcelFormat($input_values,$output_values,$calculator_code){
        
        if($calculator_code == 'D_S2'){
            $data = $this->outputS2ExcelFormat($input_values,$output_values);
        }

        if($calculator_code == 'D_H2'){
            $data = $this->outputH2ExcelFormat($input_values,$output_values);
        }

        if($calculator_code == 'L5'){
            $data = $this->outputL5ExcelFormat($input_values,$output_values);
        }

        if($calculator_code == 'L1'){
            $data = $this->outputL1ExcelFormat($input_values,$output_values);
        }

        if($calculator_code == 'S1'){
            $data = $this->outputS1ExcelFormat($input_values,$output_values);
        }

        if($calculator_code == 'D_G2'){
            $data = $this->outputG2ExcelFormat($input_values,$output_values);
        }

        if($calculator_code == 'D_E2'){
            $data = $this->outputE2ExcelFormat($input_values,$output_values);
        }

        if($calculator_code == 'H1'){
            $data = $this->outputH1ExcelFormat($input_values,$output_values);
        }

        if($calculator_code == 'CH_S2'){
            $data = $this->outputCHS2ExcelFormat($input_values,$output_values);
        }

        if($calculator_code == 'CH_G2'){
            $data = $this->outputCHG2ExcelFormat($input_values,$output_values);
        }
        

        return $data;

    }


    public function outputS2ExcelFormat($input_values,$output_values){
        $data = [];
        // $data['S.No'] = $input_values['S.No'];
        // $data['model_name'] = $input_values['model_name'];
        // $data['model_number'] = $input_values['model_number'];
        // $data['capacity'] = $input_values['capacity'];
        // $data['chilled_water_in'] = $input_values['chilled_water_in'];
        // $data['chilled_water_out'] = $input_values['chilled_water_out'];
        // $data['cooling_water_in'] = $input_values['cooling_water_in'];
        // $data['cooling_water_flow'] = $input_values['cooling_water_flow'];
        // $data['glycol_selected'] = $input_values['glycol_selected'];
        // $data['glycol_chilled_water'] = $input_values['glycol_chilled_water'];
        // $data['glycol_cooling_water'] = $input_values['glycol_cooling_water'];
        // $data['metallurgy_standard'] = $input_values['metallurgy_standard'];
        // $data['evaporator_material_value'] = $input_values['evaporator_material_value'];
        // $data['evaporator_thickness'] = $input_values['evaporator_thickness'];
        // $data['absorber_material_value'] = $input_values['absorber_material_value'];
        // $data['absorber_thickness'] = $input_values['absorber_thickness'];
        // $data['condenser_material_value'] = $input_values['condenser_material_value'];
        // $data['condenser_thickness'] = $input_values['condenser_thickness'];
        // $data['fouling_factor'] = $input_values['fouling_factor'];
        // $data['fouling_chilled_water_value'] = $input_values['fouling_chilled_water_value'];
        // $data['fouling_cooling_water_value'] = $input_values['fouling_cooling_water_value'];
        // $data['steam_pressure'] = $input_values['steam_pressure'];
        // $data['region_type'] = $input_values['region_type'];



        $data['Sr.No'] = $input_values['S.No'];
        $data['model'] = isset($output_values['model_name']) ?  $output_values['model_name'] : "";
        $data['Capacity'] = isset($output_values['TON']) ?  $output_values['TON'] : "";
        $data['chilled_inlet_temp'] = isset($output_values['TCHW11']) ?  $output_values['TCHW11'] : "";
        $data['chilled_outlet_temp'] = isset($output_values['TCHW12']) ?  $output_values['TCHW12'] : "";
        $data['chilled_water_flow'] = isset($output_values['ChilledWaterFlow']) ?  $output_values['ChilledWaterFlow'] : "";
        $data['cooling_water_flow'] = isset($output_values['GCW']) ?  $output_values['GCW'] : "";
        $data['cooling_inlet_temp'] = isset($output_values['TCW11']) ?  $output_values['TCW11'] : "";
        $data['cooling_outlet_temp'] = isset($output_values['CoolingWaterOutTemperature']) ?  $output_values['CoolingWaterOutTemperature'] : "";
        $data['steam_pressure'] = isset($output_values['PST1']) ?  $output_values['PST1'] : "";
        $data['steam_consumption'] = isset($output_values['SteamConsumption']) ?  $output_values['SteamConsumption'] : "";
        if(empty($output_values['BypassFlow'])){
            $data['cooling_bypass_flow'] = "-";
        }
        else{
            $data['cooling_bypass_flow'] = isset($output_values['BypassFlow']) ?  $output_values['BypassFlow'] : "";
        }    
        if($output_values['GL'] == 1){
            $data['chilled_glycol_type'] = "NA";
        }
        else if($output_values['GL'] == 2){
            $data['chilled_glycol_type'] = "Ethylene";
        }
        else{
            $data['chilled_glycol_type'] = "Proplylene";
        }
        $data['chilled_gylcol'] = isset($output_values['CHGLY']) ?  $output_values['CHGLY'] : ""; 
        $data['cooling_gylcol'] = isset($output_values['COGLY']) ?  $output_values['COGLY'] : "";
        if($output_values['TUU'] == "standard"){
            $data['chilled_fouling_factor'] = "standard"; 
        }
        else{
            $data['chilled_fouling_factor'] = isset($output_values['FFCHW1']) ?  $output_values['FFCHW1'] : ""; 
        }

        if($output_values['TUU'] == "standard"){
            $data['cooling_fouling_factor'] = "standard"; 
        }
        else{
            $data['cooling_fouling_factor'] = isset($output_values['FFCOW1']) ?  $output_values['FFCOW1'] : ""; 
        }

        $data['UEVAH'] = isset($output_values['UEVAH']) ?  $output_values['UEVAH'] : "";
        $data['UABSH'] = isset($output_values['UABSH']) ?  $output_values['UABSH'] : "";
        $data['UCON'] = isset($output_values['UCON']) ?  $output_values['UCON'] : "";
        $data['GDIL'] = isset($output_values['GDIL']) ?  $output_values['GDIL'] : "";
        $data['XDIL'] = isset($output_values['XDIL']) ?  $output_values['XDIL'] : "";
        $data['XCONC'] = isset($output_values['XCONC']) ?  $output_values['XCONC'] : "";
        $data['TNEV'] = isset($output_values['TNEV']) ?  $output_values['TNEV'] : "";
        $data['TNAA'] = isset($output_values['TNAA']) ?  $output_values['TNAA'] : "";
        $data['TNC'] = isset($output_values['TNC']) ?  $output_values['TNC'] : "";
        $data['VEA'] = isset($output_values['VEA']) ?  $output_values['VEA'] : "";
        $data['evaporate_pass'] = isset($output_values['EvaporatorPasses']) ?  $output_values['EvaporatorPasses'] : "";
        $data['chilled_pressure_loss'] = isset($output_values['ChilledFrictionLoss']) ?  $output_values['ChilledFrictionLoss'] : "";
        $data['evaporator_tube_material'] = isset($output_values['TU2']) ?  $output_values['TU2'] : "";
        $data['evaporator_tube_thickness'] = isset($output_values['TU3']) ?  $output_values['TU3'] : "";
        $data['VA'] = isset($output_values['VA']) ?  $output_values['VA'] : "";
        $condeser_pass = isset($output_values['CondenserPasses']) ?  $output_values['CondenserPasses'] : "";
        $data['absorber_condenser_pass'] = isset($output_values['AbsorberPasses']) ?  $output_values['AbsorberPasses'] : ""."/".$condeser_pass;
        $data['cooling_pressure_loss'] = isset($output_values['CoolingFrictionLoss']) ?  $output_values['CoolingFrictionLoss'] : "";
        $data['absorber_tube_material'] = isset($output_values['TU5']) ?  $output_values['TU5'] : "";
        $data['absorber_tube_thickness'] = isset($output_values['TU6']) ?  $output_values['TU6'] : "";
        $data['VC'] = isset($output_values['VC']) ?  $output_values['VC'] : "";
        $data['condenser_tube_material'] = isset($output_values['TV5']) ?  $output_values['TV5'] : "";
        $data['condenser_tube_thickness'] = isset($output_values['TV6']) ?  $output_values['TV6'] : "";
        $data['chilled_connection_diameter'] = isset($output_values['ChilledConnectionDiameter']) ?  $output_values['ChilledConnectionDiameter'] : "";
        $data['cooling_connection_diameter'] = isset($output_values['CoolingConnectionDiameter']) ?  $output_values['CoolingConnectionDiameter'] : "";
        $data['connection_inlet_dia'] = isset($output_values['SteamConnectionDiameter']) ?  $output_values['SteamConnectionDiameter'] : "";
        $data['connection_drain_dia'] = isset($output_values['SteamDrainDiameter']) ?  $output_values['SteamDrainDiameter'] : "";
        $data['power_supply'] = isset($output_values['PowerSupply']) ?  $output_values['PowerSupply'] : "";
        $data['power_consumption'] = isset($output_values['TotalPowerConsumption']) ?  $output_values['TotalPowerConsumption'] : "";

        $data['absorbent_pump_rating(KW)'] = isset($output_values['AbsorbentPumpMotorKW']) ?  $output_values['AbsorbentPumpMotorKW'] : "";
        $data['absorbent_pump_rating(AMP)'] = isset($output_values['AbsorbentPumpMotorAmp']) ?  $output_values['AbsorbentPumpMotorAmp'] : "";
        $data['refrigerant_pump_rating(KW)'] = isset($output_values['RefrigerantPumpMotorKW']) ?  $output_values['RefrigerantPumpMotorKW'] : "";
        $data['refrigerant_pump_rating(AMP)'] = isset($output_values['RefrigerantPumpMotorAmp']) ?  $output_values['RefrigerantPumpMotorAmp'] : "";
        $data['vaccum_pump_rating(KW)'] = isset($output_values['PurgePumpMotorKW']) ?  $output_values['PurgePumpMotorKW'] : "";
        $data['vaccum_pump_rating(AMP)'] = isset($output_values['PurgePumpMotorAmp']) ?  $output_values['PurgePumpMotorAmp'] : "";
        $data['MOP'] = "";
        $data['MCA'] = "";
        if($output_values['region_type'] == 2){
            $data['absorbent_pump_rating(KW)'] = isset($output_values['USA_AbsorbentPumpMotorKW']) ?  $output_values['USA_AbsorbentPumpMotorKW'] : "";
            $data['absorbent_pump_rating(AMP)'] = isset($output_values['USA_AbsorbentPumpMotorAmp']) ?  $output_values['USA_AbsorbentPumpMotorAmp'] : "";
            $data['refrigerant_pump_rating(KW)'] = isset($output_values['USA_RefrigerantPumpMotorKW']) ?  $output_values['USA_RefrigerantPumpMotorKW'] : "";
            $data['refrigerant_pump_rating(AMP)'] = isset($output_values['USA_RefrigerantPumpMotorAmp']) ?  $output_values['USA_RefrigerantPumpMotorAmp'] : "";
            $data['vaccum_pump_rating(KW)'] = isset($output_values['USA_PurgePumpMotorKW']) ?  $output_values['USA_PurgePumpMotorKW'] : "";
            $data['vaccum_pump_rating(AMP)'] = isset($output_values['USA_PurgePumpMotorAmp']) ?  $output_values['USA_PurgePumpMotorAmp'] : "";
            $data['MOP'] = isset($output_values['MOP']) ?  $output_values['MOP'] : "";
            $data['MCA'] = isset($output_values['MCA']) ?  $output_values['MCA'] : "";
        }  
        $data['Length'] = isset($output_values['Length']) ?  $output_values['Length'] : "";
        $data['Width'] = isset($output_values['Width']) ?  $output_values['Width'] : "";
        $data['Height'] = isset($output_values['Height']) ?  $output_values['Height'] : "";
        $data['OperatingWeight'] = isset($output_values['OperatingWeight']) ?  $output_values['OperatingWeight'] : "";
        $data['MaxShippingWeight'] = isset($output_values['MaxShippingWeight']) ?  $output_values['MaxShippingWeight'] : "";
        $data['FloodedWeight'] = isset($output_values['FloodedWeight']) ?  $output_values['FloodedWeight'] : "";
        $data['DryWeight'] = isset($output_values['DryWeight']) ?  $output_values['DryWeight'] : "";
        $data['ClearanceForTubeRemoval'] = isset($output_values['ClearanceForTubeRemoval']) ?  $output_values['ClearanceForTubeRemoval'] : "";
        $data['region_type'] = isset($output_values['region_type']) ?  $output_values['region_type'] : "";
        $data['Result'] = isset($output_values['Result']) ?  $output_values['Result'] : "";


        return $data;

    }

    public function outputS1ExcelFormat($input_values,$output_values){
        $data = [];
        $data['Sr.No'] = $input_values['S.No'];
        $data['model'] = isset($output_values['model_name']) ?  $output_values['model_name'] : "";
        $data['Capacity'] = isset($output_values['TON']) ?  $output_values['TON'] : "";
        $data['chilled_inlet_temp'] = isset($output_values['TCHW11']) ?  $output_values['TCHW11'] : "";
        $data['chilled_outlet_temp'] = isset($output_values['TCHW12']) ?  $output_values['TCHW12'] : "";
        $data['chilled_water_flow'] = isset($output_values['ChilledWaterFlow']) ?  $output_values['ChilledWaterFlow'] : "";
        $data['cooling_water_flow'] = isset($output_values['GCW']) ?  $output_values['GCW'] : "";
        $data['cooling_inlet_temp'] = isset($output_values['TCW11']) ?  $output_values['TCW11'] : "";
        $data['cooling_outlet_temp'] = isset($output_values['CoolingWaterOutTemperature']) ?  $output_values['CoolingWaterOutTemperature'] : "";
        $data['steam_pressure'] = isset($output_values['PST1']) ?  $output_values['PST1'] : "";
        $data['steam_consumption'] = isset($output_values['SteamConsumption']) ?  $output_values['SteamConsumption'] : "";
        if(empty($output_values['BypassFlow'])){
            $data['cooling_bypass_flow'] = "-";
        }
        else{
            $data['cooling_bypass_flow'] = isset($output_values['BypassFlow']) ?  $output_values['BypassFlow'] : "";
        }    
        if($output_values['GL'] == 1){
            $data['chilled_glycol_type'] = "NA";
        }
        else if($output_values['GL'] == 2){
            $data['chilled_glycol_type'] = "Ethylene";
        }
        else{
            $data['chilled_glycol_type'] = "Proplylene";
        }
        $data['chilled_gylcol'] = isset($output_values['CHGLY']) ?  $output_values['CHGLY'] : ""; 
        $data['cooling_gylcol'] = isset($output_values['COGLY']) ?  $output_values['COGLY'] : "";
        if($output_values['TUU'] == "standard"){
            $data['chilled_fouling_factor'] = "standard"; 
        }
        else{
            $data['chilled_fouling_factor'] = isset($output_values['FFCHW1']) ?  $output_values['FFCHW1'] : ""; 
        }

        if($output_values['TUU'] == "standard"){
            $data['cooling_fouling_factor'] = "standard"; 
        }
        else{
            $data['cooling_fouling_factor'] = isset($output_values['FFCOW1']) ?  $output_values['FFCOW1'] : ""; 
        }

        $data['UEVAH'] = isset($output_values['UEVAH']) ?  $output_values['UEVAH'] : "";
        $data['UABSH'] = isset($output_values['UABSH']) ?  $output_values['UABSH'] : "";
        $data['UCON'] = isset($output_values['UCON']) ?  $output_values['UCON'] : "";
        $data['GDIL'] = isset($output_values['GDIL']) ?  $output_values['GDIL'] : "";
        $data['XDIL'] = isset($output_values['XDIL']) ?  $output_values['XDIL'] : "";
        $data['XCONC'] = isset($output_values['XCONC']) ?  $output_values['XCONC'] : "";
        $data['TNEV'] = isset($output_values['TNEV']) ?  $output_values['TNEV'] : "";
        $data['TNAA'] = isset($output_values['TNAA']) ?  $output_values['TNAA'] : "";
        $data['TNC'] = isset($output_values['TNC']) ?  $output_values['TNC'] : "";
        $data['VEA'] = isset($output_values['VEA']) ?  $output_values['VEA'] : "";
        $data['evaporate_pass'] = isset($output_values['EvaporatorPasses']) ?  $output_values['EvaporatorPasses'] : "";
        $data['chilled_pressure_loss'] = isset($output_values['ChilledFrictionLoss']) ?  $output_values['ChilledFrictionLoss'] : "";
        $data['evaporator_tube_material'] = isset($output_values['TU2']) ?  $output_values['TU2'] : "";
        $data['evaporator_tube_thickness'] = isset($output_values['TU3']) ?  $output_values['TU3'] : "";
        $data['VA'] = isset($output_values['VA']) ?  $output_values['VA'] : "";
        $condeser_pass = isset($output_values['CondenserPasses']) ?  $output_values['CondenserPasses'] : "";
        $data['absorber_condenser_pass'] = isset($output_values['AbsorberPasses']) ?  $output_values['AbsorberPasses'] : ""."/".$condeser_pass;
        $data['cooling_pressure_loss'] = isset($output_values['CoolingFrictionLoss']) ?  $output_values['CoolingFrictionLoss'] : "";
        $data['absorber_tube_material'] = isset($output_values['TU5']) ?  $output_values['TU5'] : "";
        $data['absorber_tube_thickness'] = isset($output_values['TU6']) ?  $output_values['TU6'] : "";
        $data['VC'] = isset($output_values['VC']) ?  $output_values['VC'] : "";
        $data['condenser_tube_material'] = isset($output_values['TV5']) ?  $output_values['TV5'] : "";
        $data['condenser_tube_thickness'] = isset($output_values['TV6']) ?  $output_values['TV6'] : "";
        $data['chilled_connection_diameter'] = isset($output_values['ChilledConnectionDiameter']) ?  $output_values['ChilledConnectionDiameter'] : "";
        $data['cooling_connection_diameter'] = isset($output_values['CoolingConnectionDiameter']) ?  $output_values['CoolingConnectionDiameter'] : "";
        $data['connection_inlet_dia'] = isset($output_values['SteamConnectionDiameter']) ?  $output_values['SteamConnectionDiameter'] : "";
        $data['connection_drain_dia'] = isset($output_values['SteamDrainDiameter']) ?  $output_values['SteamDrainDiameter'] : "";
        $data['power_supply'] = isset($output_values['PowerSupply']) ?  $output_values['PowerSupply'] : "";
        $data['power_consumption'] = isset($output_values['TotalPowerConsumption']) ?  $output_values['TotalPowerConsumption'] : "";

        $data['absorbent_pump_rating(KW)'] = isset($output_values['AbsorbentPumpMotorKW']) ?  $output_values['AbsorbentPumpMotorKW'] : "";
        $data['absorbent_pump_rating(AMP)'] = isset($output_values['AbsorbentPumpMotorAmp']) ?  $output_values['AbsorbentPumpMotorAmp'] : "";
        $data['refrigerant_pump_rating(KW)'] = isset($output_values['RefrigerantPumpMotorKW']) ?  $output_values['RefrigerantPumpMotorKW'] : "";
        $data['refrigerant_pump_rating(AMP)'] = isset($output_values['RefrigerantPumpMotorAmp']) ?  $output_values['RefrigerantPumpMotorAmp'] : "";
        $data['vaccum_pump_rating(KW)'] = isset($output_values['PurgePumpMotorKW']) ?  $output_values['PurgePumpMotorKW'] : "";
        $data['vaccum_pump_rating(AMP)'] = isset($output_values['PurgePumpMotorAmp']) ?  $output_values['PurgePumpMotorAmp'] : "";
        $data['MOP'] = "";
        $data['MCA'] = "";
        if($output_values['region_type'] == 2){
            $data['absorbent_pump_rating(KW)'] = isset($output_values['USA_AbsorbentPumpMotorKW']) ?  $output_values['USA_AbsorbentPumpMotorKW'] : "";
            $data['absorbent_pump_rating(AMP)'] = isset($output_values['USA_AbsorbentPumpMotorAmp']) ?  $output_values['USA_AbsorbentPumpMotorAmp'] : "";
            $data['refrigerant_pump_rating(KW)'] = isset($output_values['USA_RefrigerantPumpMotorKW']) ?  $output_values['USA_RefrigerantPumpMotorKW'] : "";
            $data['refrigerant_pump_rating(AMP)'] = isset($output_values['USA_RefrigerantPumpMotorAmp']) ?  $output_values['USA_RefrigerantPumpMotorAmp'] : "";
            $data['vaccum_pump_rating(KW)'] = isset($output_values['USA_PurgePumpMotorKW']) ?  $output_values['USA_PurgePumpMotorKW'] : "";
            $data['vaccum_pump_rating(AMP)'] = isset($output_values['USA_PurgePumpMotorAmp']) ?  $output_values['USA_PurgePumpMotorAmp'] : "";
            $data['MOP'] = isset($output_values['MOP']) ?  $output_values['MOP'] : "";
            $data['MCA'] = isset($output_values['MCA']) ?  $output_values['MCA'] : "";
        }  
        $data['Length'] = isset($output_values['Length']) ?  $output_values['Length'] : "";
        $data['Width'] = isset($output_values['Width']) ?  $output_values['Width'] : "";
        $data['Height'] = isset($output_values['Height']) ?  $output_values['Height'] : "";
        $data['OperatingWeight'] = isset($output_values['OperatingWeight']) ?  $output_values['OperatingWeight'] : "";
        $data['MaxShippingWeight'] = isset($output_values['MaxShippingWeight']) ?  $output_values['MaxShippingWeight'] : "";
        $data['FloodedWeight'] = isset($output_values['FloodedWeight']) ?  $output_values['FloodedWeight'] : "";
        $data['DryWeight'] = isset($output_values['DryWeight']) ?  $output_values['DryWeight'] : "";
        $data['ClearanceForTubeRemoval'] = isset($output_values['ClearanceForTubeRemoval']) ?  $output_values['ClearanceForTubeRemoval'] : "";
        $data['region_type'] = isset($output_values['region_type']) ?  $output_values['region_type'] : "";
        $data['Result'] = isset($output_values['Result']) ?  $output_values['Result'] : "";


        return $data;

    }

    public function outputH2ExcelFormat($input_values,$output_values){
        $data = [];
        $data['S.No'] = $input_values['S.No'];
        $data['model'] = isset($output_values['model_name']) ?  $output_values['model_name'] : "";
        $data['Capacity'] = isset($output_values['TON']) ?  floatval($output_values['TON']) : "";
        $data['chilled_inlet_temp'] = isset($output_values['TCHW1H']) ?  floatval($output_values['TCHW1H']) : "";
        $data['chilled_outlet_temp'] = isset($output_values['TCHW2L']) ?  floatval($output_values['TCHW2L']) : "";
        $data['chilled_water_flow'] = isset($output_values['ChilledWaterFlow']) ?  floatval($output_values['ChilledWaterFlow']) : "";
        $data['cooling_water_flow'] = isset($output_values['TCHW2L']) ?  floatval($output_values['TCHW2L']) : "";
        $data['cooling_inlet_temp'] = isset($output_values['TCW11']) ?  floatval($output_values['TCW11']) : "";
        $data['cooling_outlet_temp'] = isset($output_values['CoolingWaterOutTemperature']) ?  floatval($output_values['CoolingWaterOutTemperature']) : "";
        $data['hot_water_in'] = isset($output_values['THW1']) ?  floatval($output_values['THW1']) : "";
        $data['hot_water_out'] = isset($output_values['THW2']) ?  floatval($output_values['THW2']) : "";
        $data['hot_water_flow'] = isset($output_values['GHOT']) ?  floatval($output_values['GHOT']) : "";
        if(empty($output_values['BypassFlow'])){
            $data['cooling_bypass_flow'] = "-";
        }
        else{
            $data['cooling_bypass_flow'] = isset($output_values['BypassFlow']) ?  $output_values['BypassFlow'] : "";
        }
        if($output_values['GL'] == 1){
            $data['chilled_glycol_type'] = "NA";
        }
        else if($output_values['GL'] == 2){
            $data['chilled_glycol_type'] = "Ethylene";
        }
        else{
            $data['chilled_glycol_type'] = "Proplylene";
        } 
        $data['chilled_gylcol'] = isset($output_values['CHGLY']) ?  floatval($output_values['CHGLY']) : ""; 
        $data['cooling_gylcol'] = isset($output_values['COGLY']) ?  floatval($output_values['COGLY']) : "";
        if($output_values['TUU'] == "standard"){
            $data['chilled_fouling_factor'] = "standard"; 
        }
        else{
            $data['chilled_fouling_factor'] = isset($output_values['FFCHW1']) ?  floatval($output_values['FFCHW1']) : ""; 
        }

        if($output_values['TUU'] == "standard"){
            $data['cooling_fouling_factor'] = "standard"; 
        }
        else{
            $data['cooling_fouling_factor'] = isset($output_values['FFCOW1']) ?  floatval($output_values['FFCOW1']) : ""; 
        }

        $data['UEVAH'] = isset($output_values['UEVAH']) ?  floatval($output_values['UEVAH']) : "";
        $data['UABSH'] = isset($output_values['UABSH']) ?  floatval($output_values['UABSH']) : "";
        $data['UCON'] = isset($output_values['UCON']) ?  floatval($output_values['UCON']) : "";
        $data['UHTG'] = isset($output_values['UHTG']) ?  floatval($output_values['UHTG']) : "";
        $data['GDIL'] = isset($output_values['GDIL']) ?  floatval($output_values['GDIL']) : "";
        $data['XDIL'] = isset($output_values['XDIL']) ?  floatval($output_values['XDIL']) : "";
        $data['XCONC'] = isset($output_values['XCONC']) ?  floatval($output_values['XCONC']) : "";
        $data['TNEV'] = isset($output_values['TNEV']) ?  floatval($output_values['TNEV']) : "";
        $data['TNAA'] = isset($output_values['TNAA']) ?  floatval($output_values['TNAA']) : "";
        $data['TNC'] = isset($output_values['TNC']) ?  floatval($output_values['TNC']) : "";
        $data['TNG'] = isset($output_values['TNG']) ?  floatval($output_values['TNG']) : "";
        $data['VEA'] = isset($output_values['VEA']) ?  floatval($output_values['VEA']) : "";
        $data['evaporate_pass'] = isset($output_values['EvaporatorPasses']) ?  $output_values['EvaporatorPasses'] : "";
        $data['chilled_pressure_loss'] = isset($output_values['ChilledFrictionLoss']) ?  floatval($output_values['ChilledFrictionLoss']) : "";
        $data['evaporator_tube_material'] = isset($output_values['TU2']) ?  floatval($output_values['TU2']) : "";
        $data['evaporator_tube_thickness'] = isset($output_values['TU3']) ?  floatval($output_values['TU3']) : "";
        $data['VA'] = isset($output_values['VA']) ?  floatval($output_values['VA']) : "";
        $data['condenser_pass'] = isset($output_values['CondenserPasses']) ?  $output_values['CondenserPasses'] : "";
        $data['absorber_pass'] = isset($output_values['AbsorberPasses']) ?  $output_values['AbsorberPasses'] : "";
        $data['cooling_pressure_loss'] = isset($output_values['CoolingFrictionLoss']) ?  floatval($output_values['CoolingFrictionLoss']) : "";
        $data['absorber_tube_material'] = isset($output_values['TU5']) ?  floatval($output_values['TU5']) : "";
        $data['absorber_tube_thickness'] = isset($output_values['TU6']) ?  floatval($output_values['TU6']) : "";
        $data['VC'] = isset($output_values['VC']) ?  floatval($output_values['VC']) : "";
        $data['condenser_tube_material'] = isset($output_values['TV5']) ?  floatval($output_values['TV5']) : "";
        $data['condenser_tube_thickness'] = isset($output_values['TV6']) ?  floatval($output_values['TV6']) : "";
        $data['chilled_connection_diameter'] = isset($output_values['ChilledConnectionDiameter']) ?  floatval($output_values['ChilledConnectionDiameter']) : "";
        $data['cooling_connection_diameter'] = isset($output_values['CoolingConnectionDiameter']) ?  floatval($output_values['CoolingConnectionDiameter']) : "";
        $data['VG'] = isset($output_values['VG']) ?  floatval($output_values['VG']) : "";
        $data['hot_water_passes'] = isset($output_values['TGP']) ?  floatval($output_values['TGP']) : "";
        $data['hot_water_pressure_loss'] = isset($output_values['HotWaterFrictionLoss']) ?  floatval($output_values['HotWaterFrictionLoss']) : "";
        $data['power_supply'] = isset($output_values['PowerSupply']) ?  $output_values['PowerSupply'] : "";
        $data['power_consumption'] = isset($output_values['TotalPowerConsumption']) ?  floatval($output_values['TotalPowerConsumption']) : "";
        $data['absorbent_pump_rating(KW)'] = isset($output_values['AbsorbentPumpMotorKW']) ?  floatval($output_values['AbsorbentPumpMotorKW']) : "";
        $data['absorbent_pump_rating(AMP)'] = isset($output_values['AbsorbentPumpMotorAmp']) ?  floatval($output_values['AbsorbentPumpMotorAmp']) : "";
        $data['refrigerant_pump_rating(KW)'] = isset($output_values['RefrigerantPumpMotorKW']) ?  floatval($output_values['RefrigerantPumpMotorKW']) : "";
        $data['refrigerant_pump_rating(AMP)'] = isset($output_values['RefrigerantPumpMotorAmp']) ?  floatval($output_values['RefrigerantPumpMotorAmp']) : "";
        $data['vaccum_pump_rating(KW)'] = isset($output_values['PurgePumpMotorKW']) ?  floatval($output_values['PurgePumpMotorKW']) : "";
        $data['vaccum_pump_rating(AMP)'] = isset($output_values['PurgePumpMotorAmp']) ?  floatval($output_values['PurgePumpMotorAmp']) : "";
        $data['MOP'] = "";
        $data['MCA'] = "";
        if($output_values['region_type'] == 2){
            $data['absorbent_pump_rating(KW)'] = isset($output_values['USA_AbsorbentPumpMotorKW']) ?  floatval($output_values['USA_AbsorbentPumpMotorKW']) : "";
            $data['absorbent_pump_rating(AMP)'] = isset($output_values['USA_AbsorbentPumpMotorAmp']) ?  floatval($output_values['USA_AbsorbentPumpMotorAmp']) : "";
            $data['refrigerant_pump_rating(KW)'] = isset($output_values['USA_RefrigerantPumpMotorKW']) ?  floatval($output_values['USA_RefrigerantPumpMotorKW']) : "";
            $data['refrigerant_pump_rating(AMP)'] = isset($output_values['USA_RefrigerantPumpMotorAmp']) ?  floatval($output_values['USA_RefrigerantPumpMotorAmp']) : "";
            $data['vaccum_pump_rating(KW)'] = isset($output_values['USA_PurgePumpMotorKW']) ?  floatval($output_values['USA_PurgePumpMotorKW']) : "";
            $data['vaccum_pump_rating(AMP)'] = isset($output_values['USA_PurgePumpMotorAmp']) ?  floatval($output_values['USA_PurgePumpMotorAmp']) : "";
            $data['MOP'] = isset($output_values['MOP']) ?  floatval($output_values['MOP']) : "";
            $data['MCA'] = isset($output_values['MCA']) ?  floatval($output_values['MCA']) : "";
        }


        $data['Length'] = isset($output_values['Length']) ?  floatval($output_values['Length']) : "";
        $data['Width'] = isset($output_values['Width']) ?  floatval($output_values['Width']) : "";
        $data['Height'] = isset($output_values['Height']) ?  floatval($output_values['Height']) : "";
        $data['OperatingWeight'] = isset($output_values['OperatingWeight']) ?  floatval($output_values['OperatingWeight']) : "";
        $data['MaxShippingWeight'] = isset($output_values['MaxShippingWeight']) ?  floatval($output_values['MaxShippingWeight']) : "";
        $data['FloodedWeight'] = isset($output_values['FloodedWeight']) ?  floatval($output_values['FloodedWeight']) : "";
        $data['DryWeight'] = isset($output_values['DryWeight']) ?  floatval($output_values['DryWeight']) : "";
        $data['ClearanceForTubeRemoval'] = isset($output_values['ClearanceForTubeRemoval']) ?  floatval($output_values['ClearanceForTubeRemoval']) : "";
        $data['region_type'] = isset($output_values['region_type']) ?  floatval($output_values['region_type']) : "";
        $data['Result'] = isset($output_values['Result']) ?  $output_values['Result'] : "";



        return $data;

    }

    public function outputL5ExcelFormat($input_values,$output_values){
        $data = [];
        $data['S.No'] = $input_values['S.No'];
        $data['model'] = isset($output_values['model_name']) ?  $output_values['model_name'] : "";
        $data['Capacity'] = isset($output_values['TON']) ?  floatval($output_values['TON']) : "";
        $data['chilled_inlet_temp'] = isset($output_values['TCHW1H']) ?  floatval($output_values['TCHW1H']) : "";
        $data['chilled_outlet_temp'] = isset($output_values['TCHW2L']) ?  floatval($output_values['TCHW2L']) : "";
        $data['chilled_water_flow'] = isset($output_values['ChilledWaterFlow']) ?  floatval($output_values['ChilledWaterFlow']) : "";
        $data['cooling_water_flow'] = isset($output_values['TCHW2L']) ?  floatval($output_values['TCHW2L']) : "";
        $data['cooling_inlet_temp'] = isset($output_values['TCW11']) ?  floatval($output_values['TCW11']) : "";
        $data['cooling_outlet_temp'] = isset($output_values['CoolingWaterOutTemperature']) ?  floatval($output_values['CoolingWaterOutTemperature']) : "";
        $data['hot_water_in'] = isset($output_values['THW1']) ?  floatval($output_values['THW1']) : "";
        $data['hot_water_out'] = isset($output_values['THW4']) ?  floatval($output_values['THW4']) : "";
        $data['hot_water_flow'] = isset($output_values['GHOT']) ?  floatval($output_values['GHOT']) : "";
        if(empty($output_values['BypassFlow'])){
            $data['cooling_bypass_flow'] = "-";
        }
        else{
            $data['cooling_bypass_flow'] = isset($output_values['BypassFlow']) ?  $output_values['BypassFlow'] : "";
        }
        if($output_values['GL'] == 1){
            $data['chilled_glycol_type'] = "NA";
        }
        else if($output_values['GL'] == 2){
            $data['chilled_glycol_type'] = "Ethylene";
        }
        else{
            $data['chilled_glycol_type'] = "Proplylene";
        } 
        $data['chilled_gylcol'] = isset($output_values['CHGLY']) ?  floatval($output_values['CHGLY']) : ""; 
        $data['cooling_gylcol'] = isset($output_values['COGLY']) ?  floatval($output_values['COGLY']) : "";
        if($output_values['TUU'] == "standard"){
            $data['chilled_fouling_factor'] = "standard"; 
        }
        else{
            $data['chilled_fouling_factor'] = isset($output_values['FFCHW1']) ?  floatval($output_values['FFCHW1']) : ""; 
        }

        if($output_values['TUU'] == "standard"){
            $data['cooling_fouling_factor'] = "standard"; 
        }
        else{
            $data['cooling_fouling_factor'] = isset($output_values['FFCOW1']) ?  floatval($output_values['FFCOW1']) : ""; 
        }

        $data['UEVAH'] = isset($output_values['UEVAH']) ?  floatval($output_values['UEVAH']) : "";
        $data['UABSH'] = isset($output_values['UABSH']) ?  floatval($output_values['UABSH']) : "";
        $data['UCONH'] = isset($output_values['UCONH']) ?  floatval($output_values['UCONH']) : "";
        $data['UCONL'] = isset($output_values['UCONL']) ?  floatval($output_values['UCONL']) : "";
        $data['UGENH'] = isset($output_values['UGENH']) ?  floatval($output_values['UGENH']) : "";
        $data['GDIL'] = isset($output_values['GDIL']) ?  floatval($output_values['GDIL']) : "";
        $data['XDIL'] = isset($output_values['XDIL']) ?  floatval($output_values['XDIL']) : "";
        $data['XCONC'] = isset($output_values['XCONC']) ?  floatval($output_values['XCONC']) : "";
        $data['TNEV'] = isset($output_values['TNEV']) ?  floatval($output_values['TNEV']) : "";
        $data['TNAA'] = isset($output_values['TNAA']) ?  floatval($output_values['TNAA']) : "";
        $data['TNC'] = isset($output_values['TNC']) ?  floatval($output_values['TNC']) : "";
        $data['TNG'] = isset($output_values['TNG']) ?  floatval($output_values['TNG']) : "";
        $data['VEA'] = isset($output_values['VEA']) ?  floatval($output_values['VEA']) : "";
        $data['evaporate_pass'] = isset($output_values['EvaporatorPasses']) ?  $output_values['EvaporatorPasses'] : "";
        $data['chilled_pressure_loss'] = isset($output_values['ChilledFrictionLoss']) ?  floatval($output_values['ChilledFrictionLoss']) : "";
        $data['evaporator_tube_material'] = isset($output_values['TU2']) ?  floatval($output_values['TU2']) : "";
        $data['evaporator_tube_thickness'] = isset($output_values['TU3']) ?  floatval($output_values['TU3']) : "";
        $data['VA'] = isset($output_values['VA']) ?  floatval($output_values['VA']) : "";
        $data['condenser_pass'] = isset($output_values['CondenserPasses']) ?  $output_values['CondenserPasses'] : "";
        $data['absorber_pass'] = isset($output_values['AbsorberPasses']) ?  $output_values['AbsorberPasses'] : "";
        $data['cooling_pressure_loss'] = isset($output_values['CoolingFrictionLoss']) ?  floatval($output_values['CoolingFrictionLoss']) : "";
        $data['absorber_tube_material'] = isset($output_values['TU5']) ?  floatval($output_values['TU5']) : "";
        $data['absorber_tube_thickness'] = isset($output_values['TU6']) ?  floatval($output_values['TU6']) : "";
        $data['VC'] = isset($output_values['VC']) ?  floatval($output_values['VC']) : "";
        $data['condenser_tube_material'] = isset($output_values['TV5']) ?  floatval($output_values['TV5']) : "";
        $data['condenser_tube_thickness'] = isset($output_values['TV6']) ?  floatval($output_values['TV6']) : "";
        $data['chilled_connection_diameter'] = isset($output_values['ChilledConnectionDiameter']) ?  floatval($output_values['ChilledConnectionDiameter']) : "";
        $data['cooling_connection_diameter'] = isset($output_values['CoolingConnectionDiameter']) ?  floatval($output_values['CoolingConnectionDiameter']) : "";
        $data['VG'] = isset($output_values['VG']) ?  floatval($output_values['VG']) : "";
        $data['hot_water_passes'] = isset($output_values['TGP']) ?  floatval($output_values['TGP']) : "";
        $data['hot_water_pressure_loss'] = isset($output_values['HotWaterFrictionLoss']) ?  floatval($output_values['HotWaterFrictionLoss']) : "";
        $data['power_supply'] = isset($output_values['PowerSupply']) ?  $output_values['PowerSupply'] : "";
        $data['power_consumption'] = isset($output_values['TotalPowerConsumption']) ?  floatval($output_values['TotalPowerConsumption']) : "";
        $data['hp_absorbent_pump_rating(KW)'] = isset($output_values['HPAbsorbentPumpMotorKW']) ?  floatval($output_values['HPAbsorbentPumpMotorKW']) : "";
        $data['hp_absorbent_pump_rating(AMP)'] = isset($output_values['HPAbsorbentPumpMotorAmp']) ?  floatval($output_values['HPAbsorbentPumpMotorAmp']) : "";
        $data['lp_absorbent_pump_rating(KW)'] = isset($output_values['LPAbsorbentPumpMotorKW']) ?  floatval($output_values['LPAbsorbentPumpMotorKW']) : "";
        $data['lp_absorbent_pump_rating(AMP)'] = isset($output_values['LPAbsorbentPumpMotorAmp']) ?  floatval($output_values['LPAbsorbentPumpMotorAmp']) : "";
        $data['refrigerant_pump_rating(KW)'] = isset($output_values['RefrigerantPumpMotorKW']) ?  floatval($output_values['RefrigerantPumpMotorKW']) : "";
        $data['refrigerant_pump_rating(AMP)'] = isset($output_values['RefrigerantPumpMotorAmp']) ?  floatval($output_values['RefrigerantPumpMotorAmp']) : "";
        $data['vaccum_pump_rating(KW)'] = isset($output_values['PurgePumpMotorKW']) ?  floatval($output_values['PurgePumpMotorKW']) : "";
        $data['vaccum_pump_rating(AMP)'] = isset($output_values['PurgePumpMotorAmp']) ?  floatval($output_values['PurgePumpMotorAmp']) : "";
        $data['MOP'] = "";
        $data['MCA'] = "";
        if($output_values['region_type'] == 2){
            $data['hp_absorbent_pump_rating(KW)'] = isset($output_values['USA_HPAbsorbentPumpMotorKW']) ?  floatval($output_values['USA_HPAbsorbentPumpMotorKW']) : "";
            $data['hp_absorbent_pump_rating(AMP)'] = isset($output_values['USA_HPAbsorbentPumpMotorAmp']) ?  floatval($output_values['USA_HPAbsorbentPumpMotorAmp']) : "";
            $data['lp_absorbent_pump_rating(KW)'] = isset($output_values['USA_LPAbsorbentPumpMotorKW']) ?  floatval($output_values['USA_LPAbsorbentPumpMotorKW']) : "";
            $data['lp_absorbent_pump_rating(AMP)'] = isset($output_values['USA_LPAbsorbentPumpMotorAmp']) ?  floatval($output_values['USA_LPAbsorbentPumpMotorAmp']) : "";
            $data['refrigerant_pump_rating(KW)'] = isset($output_values['USA_RefrigerantPumpMotorKW']) ?  floatval($output_values['USA_RefrigerantPumpMotorKW']) : "";
            $data['refrigerant_pump_rating(AMP)'] = isset($output_values['USA_RefrigerantPumpMotorAmp']) ?  floatval($output_values['USA_RefrigerantPumpMotorAmp']) : "";
            $data['vaccum_pump_rating(KW)'] = isset($output_values['USA_PurgePumpMotorKW']) ?  floatval($output_values['USA_PurgePumpMotorKW']) : "";
            $data['vaccum_pump_rating(AMP)'] = isset($output_values['USA_PurgePumpMotorAmp']) ?  floatval($output_values['USA_PurgePumpMotorAmp']) : "";
            $data['MOP'] = isset($output_values['MOP']) ?  floatval($output_values['MOP']) : "";
            $data['MCA'] = isset($output_values['MCA']) ?  floatval($output_values['MCA']) : "";
        }


        $data['Length'] = isset($output_values['Length']) ?  floatval($output_values['Length']) : "";
        $data['Width'] = isset($output_values['Width']) ?  floatval($output_values['Width']) : "";
        $data['Height'] = isset($output_values['Height']) ?  floatval($output_values['Height']) : "";
        $data['OperatingWeight'] = isset($output_values['OperatingWeight']) ?  floatval($output_values['OperatingWeight']) : "";
        $data['MaxShippingWeight'] = isset($output_values['MaxShippingWeight']) ?  floatval($output_values['MaxShippingWeight']) : "";
        $data['FloodedWeight'] = isset($output_values['FloodedWeight']) ?  floatval($output_values['FloodedWeight']) : "";
        $data['DryWeight'] = isset($output_values['DryWeight']) ?  floatval($output_values['DryWeight']) : "";
        $data['ClearanceForTubeRemoval'] = isset($output_values['ClearanceForTubeRemoval']) ?  floatval($output_values['ClearanceForTubeRemoval']) : "";
        $data['region_type'] = isset($output_values['region_type']) ?  floatval($output_values['region_type']) : "";
        $data['Result'] = isset($output_values['Result']) ?  $output_values['Result'] : "";



        return $data;

    }
    
    public function outputL1ExcelFormat($input_values,$output_values){
        $data = [];
        Log::info($output_values);
        $data['S.No'] = $input_values['S.No'];
        $data['model'] = isset($output_values['model_name']) ?  $output_values['model_name'] : "";
        $data['Capacity'] = isset($output_values['TON']) ?  floatval($output_values['TON']) : "";
        $data['chilled_inlet_temp'] = isset($output_values['TCHW1H']) ?  floatval($output_values['TCHW1H']) : "";
        $data['chilled_outlet_temp'] = isset($output_values['TCHW2L']) ?  floatval($output_values['TCHW2L']) : "";
        $data['chilled_water_flow'] = isset($output_values['ChilledWaterFlow']) ?  floatval($output_values['ChilledWaterFlow']) : "";
        $data['cooling_water_flow'] = isset($output_values['GCW']) ?  floatval($output_values['GCW']) : "";
        $data['cooling_inlet_temp'] = isset($output_values['TCW11']) ?  floatval($output_values['TCW11']) : "";
        $data['cooling_outlet_temp'] = isset($output_values['CoolingWaterOutTemperature']) ?  floatval($output_values['CoolingWaterOutTemperature']) : "";
        $data['hot_water_in'] = isset($output_values['THW1']) ?  floatval($output_values['THW1']) : "";
        $data['hot_water_out'] = isset($output_values['THW2']) ?  floatval($output_values['THW2']) : "";
        $data['hot_water_flow'] = isset($output_values['GHOT']) ?  floatval($output_values['GHOT']) : "";
        if(empty($output_values['BypassFlow'])){
            $data['cooling_bypass_flow'] = "-";
        }
        else{
            $data['cooling_bypass_flow'] = isset($output_values['BypassFlow']) ?  $output_values['BypassFlow'] : "";
        }
        if($output_values['GL'] == 1){
            $data['chilled_glycol_type'] = "NA";
        }
        else if($output_values['GL'] == 2){
            $data['chilled_glycol_type'] = "Ethylene";
        }
        else{
            $data['chilled_glycol_type'] = "Proplylene";
        } 
        $data['chilled_gylcol'] = isset($output_values['CHGLY']) ?  floatval($output_values['CHGLY']) : ""; 
        $data['cooling_gylcol'] = isset($output_values['COGLY']) ?  floatval($output_values['COGLY']) : "";
        $data['hot_water_gylcol'] = isset($output_values['HWGLY']) ?  floatval($output_values['HWGLY']) : "";
        if($output_values['TUU'] == "standard"){
            $data['chilled_fouling_factor'] = "standard"; 
        }
        else{
            $data['chilled_fouling_factor'] = isset($output_values['FFCHW1']) ?  floatval($output_values['FFCHW1']) : ""; 
        }

        if($output_values['TUU'] == "standard"){
            $data['cooling_fouling_factor'] = "standard"; 
        }
        else{
            $data['cooling_fouling_factor'] = isset($output_values['FFCOW1']) ?  floatval($output_values['FFCOW1']) : ""; 
        }

        if($output_values['TUU'] == "standard"){
            $data['hot_fouling_factor'] = "standard"; 
        }
        else{
            $data['hot_fouling_factor'] = isset($output_values['FFHOW1']) ?  floatval($output_values['FFHOW1']) : ""; 
        }
        

        $data['UEVAH'] = isset($output_values['UEVAH']) ?  floatval($output_values['UEVAH']) : "";
        $data['UABSH'] = isset($output_values['UABSH']) ?  floatval($output_values['UABSH']) : "";
        $data['UCON'] = isset($output_values['UCON']) ?  floatval($output_values['UCON']) : "";
        $data['UGEN'] = isset($output_values['UGEN']) ?  floatval($output_values['UGEN']) : "";
        $data['GDIL'] = isset($output_values['GDIL']) ?  floatval($output_values['GDIL']) : "";
        $data['XDIL'] = isset($output_values['XDIL']) ?  floatval($output_values['XDIL']) : "";
        $data['XCONC'] = isset($output_values['XCONC']) ?  floatval($output_values['XCONC']) : "";
        $data['TNEV'] = isset($output_values['TNEV']) ?  floatval($output_values['TNEV']) : "";
        $data['TNAA'] = isset($output_values['TNAA']) ?  floatval($output_values['TNAA']) : "";
        $data['TNC'] = isset($output_values['TNC']) ?  floatval($output_values['TNC']) : "";
        $data['TNG'] = isset($output_values['TNG']) ?  floatval($output_values['TNG']) : "";
        $data['VEA'] = isset($output_values['VEA']) ?  floatval($output_values['VEA']) : "";
        $data['evaporate_pass'] = isset($output_values['EvaporatorPasses']) ?  $output_values['EvaporatorPasses'] : "";
        $data['chilled_pressure_loss'] = isset($output_values['ChilledFrictionLoss']) ?  floatval($output_values['ChilledFrictionLoss']) : "";
        $data['evaporator_tube_material'] = isset($output_values['TU2']) ?  floatval($output_values['TU2']) : "";
        $data['evaporator_tube_thickness'] = isset($output_values['TU3']) ?  floatval($output_values['TU3']) : "";
        $data['VA'] = isset($output_values['VA']) ?  floatval($output_values['VA']) : "";
        $data['condenser_pass'] = isset($output_values['CondenserPasses']) ?  $output_values['CondenserPasses'] : "";
        $data['absorber_pass'] = isset($output_values['AbsorberPasses']) ?  $output_values['AbsorberPasses'] : "";
        $data['cooling_pressure_loss'] = isset($output_values['CoolingFrictionLoss']) ?  floatval($output_values['CoolingFrictionLoss']) : "";
        $data['absorber_tube_material'] = isset($output_values['TU5']) ?  floatval($output_values['TU5']) : "";
        $data['absorber_tube_thickness'] = isset($output_values['TU6']) ?  floatval($output_values['TU6']) : "";
        $data['VC'] = isset($output_values['VC']) ?  floatval($output_values['VC']) : "";
        $data['condenser_tube_material'] = isset($output_values['TV5']) ?  floatval($output_values['TV5']) : "";
        $data['condenser_tube_thickness'] = isset($output_values['TV6']) ?  floatval($output_values['TV6']) : "";
        $data['chilled_connection_diameter'] = isset($output_values['ChilledConnectionDiameter']) ?  floatval($output_values['ChilledConnectionDiameter']) : "";
        $data['cooling_connection_diameter'] = isset($output_values['CoolingConnectionDiameter']) ?  floatval($output_values['CoolingConnectionDiameter']) : "";
        $data['VG'] = isset($output_values['VG']) ?  floatval($output_values['VG']) : "";
        $data['hot_water_passes'] = isset($output_values['TGP']) ?  floatval($output_values['TGP']) : "";
        $data['hot_water_pressure_loss'] = isset($output_values['HotWaterFrictionLoss']) ?  floatval($output_values['HotWaterFrictionLoss']) : "";
        $data['power_supply'] = isset($output_values['PowerSupply']) ?  $output_values['PowerSupply'] : "";
        $data['power_consumption'] = isset($output_values['TotalPowerConsumption']) ?  floatval($output_values['TotalPowerConsumption']) : "";
        $data['absorbent_pump_rating(KW)'] = isset($output_values['AbsorbentPumpMotorKW']) ?  floatval($output_values['AbsorbentPumpMotorKW']) : "";
        $data['absorbent_pump_rating(AMP)'] = isset($output_values['AbsorbentPumpMotorAmp']) ?  floatval($output_values['AbsorbentPumpMotorAmp']) : "";
        $data['refrigerant_pump_rating(KW)'] = isset($output_values['RefrigerantPumpMotorKW']) ?  floatval($output_values['RefrigerantPumpMotorKW']) : "";
        $data['refrigerant_pump_rating(AMP)'] = isset($output_values['RefrigerantPumpMotorAmp']) ?  floatval($output_values['RefrigerantPumpMotorAmp']) : "";
        $data['vaccum_pump_rating(KW)'] = isset($output_values['PurgePumpMotorKW']) ?  floatval($output_values['PurgePumpMotorKW']) : "";
        $data['vaccum_pump_rating(AMP)'] = isset($output_values['PurgePumpMotorAmp']) ?  floatval($output_values['PurgePumpMotorAmp']) : "";
        $data['MOP'] = "";
        $data['MCA'] = "";
        if($output_values['region_type'] == 2){
            $data['absorbent_pump_rating(KW)'] = isset($output_values['USA_AbsorbentPumpMotorKW']) ?  floatval($output_values['USA_AbsorbentPumpMotorKW']) : "";
            $data['absorbent_pump_rating(AMP)'] = isset($output_values['USA_AbsorbentPumpMotorAmp']) ?  floatval($output_values['USA_AbsorbentPumpMotorAmp']) : "";
            $data['refrigerant_pump_rating(KW)'] = isset($output_values['USA_RefrigerantPumpMotorKW']) ?  floatval($output_values['USA_RefrigerantPumpMotorKW']) : "";
            $data['refrigerant_pump_rating(AMP)'] = isset($output_values['USA_RefrigerantPumpMotorAmp']) ?  floatval($output_values['USA_RefrigerantPumpMotorAmp']) : "";
            $data['vaccum_pump_rating(KW)'] = isset($output_values['USA_PurgePumpMotorKW']) ?  floatval($output_values['USA_PurgePumpMotorKW']) : "";
            $data['vaccum_pump_rating(AMP)'] = isset($output_values['USA_PurgePumpMotorAmp']) ?  floatval($output_values['USA_PurgePumpMotorAmp']) : "";
            $data['MOP'] = isset($output_values['MOP']) ?  floatval($output_values['MOP']) : "";
            $data['MCA'] = isset($output_values['MCA']) ?  floatval($output_values['MCA']) : "";
        }


        $data['Length'] = isset($output_values['Length']) ?  floatval($output_values['Length']) : "";
        $data['Width'] = isset($output_values['Width']) ?  floatval($output_values['Width']) : "";
        $data['Height'] = isset($output_values['Height']) ?  floatval($output_values['Height']) : "";
        $data['OperatingWeight'] = isset($output_values['OperatingWeight']) ?  floatval($output_values['OperatingWeight']) : "";
        $data['MaxShippingWeight'] = isset($output_values['MaxShippingWeight']) ?  floatval($output_values['MaxShippingWeight']) : "";
        $data['FloodedWeight'] = isset($output_values['FloodedWeight']) ?  floatval($output_values['FloodedWeight']) : "";
        $data['DryWeight'] = isset($output_values['DryWeight']) ?  floatval($output_values['DryWeight']) : "";
        $data['ClearanceForTubeRemoval'] = isset($output_values['ClearanceForTubeRemoval']) ?  floatval($output_values['ClearanceForTubeRemoval']) : "";
        $data['region_type'] = isset($output_values['region_type']) ?  floatval($output_values['region_type']) : "";
        $data['Result'] = isset($output_values['Result']) ?  $output_values['Result'] : "";



        return $data;

    }
    

    public function outputG2ExcelFormat($input_values,$output_values){
        $data = [];
        // $data['S.No'] = $input_values['S.No'];
        // $data['model_name'] = $input_values['model_name'];
        // $data['model_number'] = $input_values['model_number'];
        // $data['capacity'] = $input_values['capacity'];
        // $data['chilled_water_in'] = $input_values['chilled_water_in'];
        // $data['chilled_water_out'] = $input_values['chilled_water_out'];
        // $data['cooling_water_in'] = $input_values['cooling_water_in'];
        // $data['cooling_water_flow'] = $input_values['cooling_water_flow'];
        // $data['glycol_selected'] = $input_values['glycol_selected'];
        // $data['glycol_chilled_water'] = $input_values['glycol_chilled_water'];
        // $data['glycol_cooling_water'] = $input_values['glycol_cooling_water'];
        // $data['metallurgy_standard'] = $input_values['metallurgy_standard'];
        // $data['evaporator_material_value'] = $input_values['evaporator_material_value'];
        // $data['evaporator_thickness'] = $input_values['evaporator_thickness'];
        // $data['absorber_material_value'] = $input_values['absorber_material_value'];
        // $data['absorber_thickness'] = $input_values['absorber_thickness'];
        // $data['condenser_material_value'] = $input_values['condenser_material_value'];
        // $data['condenser_thickness'] = $input_values['condenser_thickness'];
        // $data['fouling_factor'] = $input_values['fouling_factor'];
        // $data['fouling_chilled_water_value'] = $input_values['fouling_chilled_water_value'];
        // $data['fouling_cooling_water_value'] = $input_values['fouling_cooling_water_value'];
        // $data['steam_pressure'] = $input_values['steam_pressure'];
        // $data['region_type'] = $input_values['region_type'];



        $data['Sr.No'] = $input_values['S.No'];
        $data['model'] = isset($output_values['model_name']) ?  $output_values['model_name'] : "";
        $data['Capacity'] = isset($output_values['TON']) ?  $output_values['TON'] : "";
        $data['chilled_inlet_temp'] = isset($output_values['TCHW11']) ?  $output_values['TCHW11'] : "";
        $data['chilled_outlet_temp'] = isset($output_values['TCHW12']) ?  $output_values['TCHW12'] : "";
        $data['chilled_water_flow'] = isset($output_values['ChilledWaterFlow']) ?  $output_values['ChilledWaterFlow'] : "";
        $data['cooling_water_flow'] = isset($output_values['GCW']) ?  $output_values['GCW'] : "";
        $data['cooling_inlet_temp'] = isset($output_values['TCW11']) ?  $output_values['TCW11'] : "";
        $data['cooling_outlet_temp'] = isset($output_values['CoolingWaterOutTemperature']) ?  $output_values['CoolingWaterOutTemperature'] : "";

        $data['fuel_type'] = isset($output_values['GCV']) ?  $output_values['GCV'] : "";
        $data['fuel_consumption'] = isset($output_values['FuelConsumption']) ?  $output_values['FuelConsumption'] : "";
        if(empty($output_values['BypassFlow'])){
            $data['cooling_bypass_flow'] = "-";
        }
        else{
            $data['cooling_bypass_flow'] = isset($output_values['BypassFlow']) ?  $output_values['BypassFlow'] : "";
        }    
        if($output_values['GLL'] == 1){
            $data['chilled_glycol_type'] = "NA";
        }
        else if($output_values['GLL'] == 2){
            $data['chilled_glycol_type'] = "Ethylene";
        }
        else{
            $data['chilled_glycol_type'] = "Proplylene";
        }
        $data['chilled_gylcol'] = isset($output_values['CHGLY']) ?  $output_values['CHGLY'] : ""; 
        $data['cooling_gylcol'] = isset($output_values['COGLY']) ?  $output_values['COGLY'] : "";
        if($output_values['TUU'] == "standard"){
            $data['chilled_fouling_factor'] = "standard"; 
        }
        else{
            $data['chilled_fouling_factor'] = isset($output_values['FFCHW1']) ?  $output_values['FFCHW1'] : ""; 
        }

        if($output_values['TUU'] == "standard"){
            $data['cooling_fouling_factor'] = "standard"; 
        }
        else{
            $data['cooling_fouling_factor'] = isset($output_values['FFCOW1']) ?  $output_values['FFCOW1'] : ""; 
        }

        $data['UEVAH'] = isset($output_values['UEVAH']) ?  $output_values['UEVAH'] : "";
        $data['UABSH'] = isset($output_values['UABSH']) ?  $output_values['UABSH'] : "";
        $data['UCON'] = isset($output_values['UCON']) ?  $output_values['UCON'] : "";
        $data['GDIL'] = isset($output_values['GDIL']) ?  $output_values['GDIL'] : "";
        $data['XDIL'] = isset($output_values['XDIL']) ?  $output_values['XDIL'] : "";
        $data['XCONC'] = isset($output_values['XCONC']) ?  $output_values['XCONC'] : "";
        $data['TNEV'] = isset($output_values['TNEV']) ?  $output_values['TNEV'] : "";
        $data['TNAA'] = isset($output_values['TNAA']) ?  $output_values['TNAA'] : "";
        $data['TNC'] = isset($output_values['TNC']) ?  $output_values['TNC'] : "";
        $data['VEA'] = isset($output_values['VEA']) ?  $output_values['VEA'] : "";
        $data['evaporate_pass'] = isset($output_values['EvaporatorPasses']) ?  $output_values['EvaporatorPasses'] : "";
        $data['chilled_pressure_loss'] = isset($output_values['ChilledFrictionLoss']) ?  $output_values['ChilledFrictionLoss'] : "";
        $data['evaporator_tube_material'] = isset($output_values['TU2']) ?  $output_values['TU2'] : "";
        $data['evaporator_tube_thickness'] = isset($output_values['TU3']) ?  $output_values['TU3'] : "";
        $data['VA'] = isset($output_values['VA']) ?  $output_values['VA'] : "";
        $condeser_pass = isset($output_values['CondenserPasses']) ?  $output_values['CondenserPasses'] : "";
        $data['absorber_condenser_pass'] = isset($output_values['AbsorberPasses']) ?  $output_values['AbsorberPasses'] : ""."/".$condeser_pass;
        $data['cooling_pressure_loss'] = isset($output_values['CoolingFrictionLoss']) ?  $output_values['CoolingFrictionLoss'] : "";
        $data['absorber_tube_material'] = isset($output_values['TU5']) ?  $output_values['TU5'] : "";
        $data['absorber_tube_thickness'] = isset($output_values['TU6']) ?  $output_values['TU6'] : "";
        $data['VC'] = isset($output_values['VC']) ?  $output_values['VC'] : "";
        $data['condenser_tube_material'] = isset($output_values['TV5']) ?  $output_values['TV5'] : "";
        $data['condenser_tube_thickness'] = isset($output_values['TV6']) ?  $output_values['TV6'] : "";
        $data['chilled_connection_diameter'] = isset($output_values['ChilledConnectionDiameter']) ?  $output_values['ChilledConnectionDiameter'] : "";
        $data['cooling_connection_diameter'] = isset($output_values['CoolingConnectionDiameter']) ?  $output_values['CoolingConnectionDiameter'] : "";
        $data['connection_inlet_dia'] = isset($output_values['SteamConnectionDiameter']) ?  $output_values['SteamConnectionDiameter'] : "";
        $data['connection_drain_dia'] = isset($output_values['SteamDrainDiameter']) ?  $output_values['SteamDrainDiameter'] : "";
        $data['power_supply'] = isset($output_values['PowerSupply']) ?  $output_values['PowerSupply'] : "";
        $data['power_consumption'] = isset($output_values['TotalPowerConsumption']) ?  $output_values['TotalPowerConsumption'] : "";

        $data['absorbent_pump_rating(KW)'] = isset($output_values['AbsorbentPumpMotorKW']) ?  $output_values['AbsorbentPumpMotorKW'] : "";
        $data['absorbent_pump_rating(AMP)'] = isset($output_values['AbsorbentPumpMotorAmp']) ?  $output_values['AbsorbentPumpMotorAmp'] : "";
        $data['refrigerant_pump_rating(KW)'] = isset($output_values['RefrigerantPumpMotorKW']) ?  $output_values['RefrigerantPumpMotorKW'] : "";
        $data['refrigerant_pump_rating(AMP)'] = isset($output_values['RefrigerantPumpMotorAmp']) ?  $output_values['RefrigerantPumpMotorAmp'] : "";
        $data['vaccum_pump_rating(KW)'] = isset($output_values['PurgePumpMotorKW']) ?  $output_values['PurgePumpMotorKW'] : "";
        $data['vaccum_pump_rating(AMP)'] = isset($output_values['PurgePumpMotorAmp']) ?  $output_values['PurgePumpMotorAmp'] : "";
        $data['MOP'] = "";
        $data['MCA'] = "";
        if($output_values['region_type'] == 2){
            $data['absorbent_pump_rating(KW)'] = isset($output_values['USA_AbsorbentPumpMotorKW']) ?  $output_values['USA_AbsorbentPumpMotorKW'] : "";
            $data['absorbent_pump_rating(AMP)'] = isset($output_values['USA_AbsorbentPumpMotorAmp']) ?  $output_values['USA_AbsorbentPumpMotorAmp'] : "";
            $data['refrigerant_pump_rating(KW)'] = isset($output_values['USA_RefrigerantPumpMotorKW']) ?  $output_values['USA_RefrigerantPumpMotorKW'] : "";
            $data['refrigerant_pump_rating(AMP)'] = isset($output_values['USA_RefrigerantPumpMotorAmp']) ?  $output_values['USA_RefrigerantPumpMotorAmp'] : "";
            $data['vaccum_pump_rating(KW)'] = isset($output_values['USA_PurgePumpMotorKW']) ?  $output_values['USA_PurgePumpMotorKW'] : "";
            $data['vaccum_pump_rating(AMP)'] = isset($output_values['USA_PurgePumpMotorAmp']) ?  $output_values['USA_PurgePumpMotorAmp'] : "";
            $data['MOP'] = isset($output_values['MOP']) ?  $output_values['MOP'] : "";
            $data['MCA'] = isset($output_values['MCA']) ?  $output_values['MCA'] : "";
        }  
        $data['Length'] = isset($output_values['Length']) ?  $output_values['Length'] : "";
        $data['Width'] = isset($output_values['Width']) ?  $output_values['Width'] : "";
        $data['Height'] = isset($output_values['Height']) ?  $output_values['Height'] : "";
        $data['OperatingWeight'] = isset($output_values['OperatingWeight']) ?  $output_values['OperatingWeight'] : "";
        $data['MaxShippingWeight'] = isset($output_values['MaxShippingWeight']) ?  $output_values['MaxShippingWeight'] : "";
        $data['FloodedWeight'] = isset($output_values['FloodedWeight']) ?  $output_values['FloodedWeight'] : "";
        $data['DryWeight'] = isset($output_values['DryWeight']) ?  $output_values['DryWeight'] : "";
        $data['ClearanceForTubeRemoval'] = isset($output_values['ClearanceForTubeRemoval']) ?  $output_values['ClearanceForTubeRemoval'] : "";
        $data['region_type'] = isset($output_values['region_type']) ?  $output_values['region_type'] : "";
        $data['Result'] = isset($output_values['Result']) ?  $output_values['Result'] : "";


        return $data;

    }

    public function outputE2ExcelFormat($input_values,$output_values){
        $data = [];
        $data['Sr.No'] = $input_values['S.No'];
        $data['model'] = isset($output_values['model_name']) ?  $output_values['model_name'] : "";
        $data['Capacity'] = isset($output_values['TON']) ?  $output_values['TON'] : "";
        $data['chilled_inlet_temp'] = isset($output_values['TCHW11']) ?  $output_values['TCHW11'] : "";
        $data['chilled_outlet_temp'] = isset($output_values['TCHW12']) ?  $output_values['TCHW12'] : "";
        $data['chilled_water_flow'] = isset($output_values['ChilledWaterFlow']) ?  $output_values['ChilledWaterFlow'] : "";
        $data['cooling_water_flow'] = isset($output_values['GCW']) ?  $output_values['GCW'] : "";
        $data['cooling_inlet_temp'] = isset($output_values['TCW11']) ?  $output_values['TCW11'] : "";
        $data['cooling_outlet_temp'] = isset($output_values['CoolingWaterOutTemperature']) ?  $output_values['CoolingWaterOutTemperature'] : "";
        $data['engine_type'] = isset($output_values['engine_type']) ?  $output_values['engine_type'] : "";
        $data['exhaust_gas_flow'] = isset($output_values['GEXHAUST']) ?  $output_values['GEXHAUST'] : "";
        $data['exhaust_gas_in_temp'] = isset($output_values['TEXH1']) ?  $output_values['TEXH1'] : "";
        $data['exhaust_gas_out_temp'] = isset($output_values['ActExhaustGasTempOut']) ?  $output_values['ActExhaustGasTempOut'] : "";
        $data['exhaust_gas_flow'] = isset($output_values['GEXHAUST']) ?  $output_values['GEXHAUST'] : "";
        $data['percentage_engine_load_considered'] = isset($output_values['LOAD']) ?  $output_values['LOAD'] : "";
        $data['vam_eg_pressure_drop'] = isset($output_values['FURNPRDROP']) ?  $output_values['FURNPRDROP'] : "";
        if(empty($output_values['BypassFlow'])){
            $data['cooling_bypass_flow'] = "-";
        }
        else{
            $data['cooling_bypass_flow'] = isset($output_values['BypassFlow']) ?  $output_values['BypassFlow'] : "";
        }    
        if($output_values['GLL'] == 1){
            $data['chilled_glycol_type'] = "NA";
        }
        else if($output_values['GLL'] == 2){
            $data['chilled_glycol_type'] = "Ethylene";
        }
        else{
            $data['chilled_glycol_type'] = "Proplylene";
        }
        $data['chilled_gylcol'] = isset($output_values['CHGLY']) ?  $output_values['CHGLY'] : ""; 
        $data['cooling_gylcol'] = isset($output_values['COGLY']) ?  $output_values['COGLY'] : "";
        if($output_values['TUU'] == "standard"){
            $data['chilled_fouling_factor'] = "standard"; 
        }
        else{
            $data['chilled_fouling_factor'] = isset($output_values['FFCHW1']) ?  $output_values['FFCHW1'] : ""; 
        }

        if($output_values['TUU'] == "standard"){
            $data['cooling_fouling_factor'] = "standard"; 
        }
        else{
            $data['cooling_fouling_factor'] = isset($output_values['FFCOW1']) ?  $output_values['FFCOW1'] : ""; 
        }

        $data['UEVAH'] = isset($output_values['UEVAH']) ?  $output_values['UEVAH'] : "";
        $data['UABSH'] = isset($output_values['UABSH']) ?  $output_values['UABSH'] : "";
        $data['UCON'] = isset($output_values['UCON']) ?  $output_values['UCON'] : "";
        $data['GDIL'] = isset($output_values['GDIL']) ?  $output_values['GDIL'] : "";
        $data['XDIL'] = isset($output_values['XDIL']) ?  $output_values['XDIL'] : "";
        $data['XCONC'] = isset($output_values['XCONC']) ?  $output_values['XCONC'] : "";
        $data['TNEV'] = isset($output_values['TNEV']) ?  $output_values['TNEV'] : "";
        $data['TNAA'] = isset($output_values['TNAA']) ?  $output_values['TNAA'] : "";
        $data['TNC'] = isset($output_values['TNC']) ?  $output_values['TNC'] : "";
        $data['VEA'] = isset($output_values['VEA']) ?  $output_values['VEA'] : "";
        $data['evaporate_pass'] = isset($output_values['EvaporatorPasses']) ?  $output_values['EvaporatorPasses'] : "";
        $data['chilled_pressure_loss'] = isset($output_values['ChilledFrictionLoss']) ?  $output_values['ChilledFrictionLoss'] : "";
        $data['evaporator_tube_material'] = isset($output_values['TU2']) ?  $output_values['TU2'] : "";
        $data['evaporator_tube_thickness'] = isset($output_values['TU3']) ?  $output_values['TU3'] : "";
        $data['VA'] = isset($output_values['VA']) ?  $output_values['VA'] : "";
        $condeser_pass = isset($output_values['CondenserPasses']) ?  $output_values['CondenserPasses'] : "";
        $data['absorber_condenser_pass'] = isset($output_values['AbsorberPasses']) ?  $output_values['AbsorberPasses'] : ""."/".$condeser_pass;
        $data['cooling_pressure_loss'] = isset($output_values['CoolingFrictionLoss']) ?  $output_values['CoolingFrictionLoss'] : "";
        $data['absorber_tube_material'] = isset($output_values['TU5']) ?  $output_values['TU5'] : "";
        $data['absorber_tube_thickness'] = isset($output_values['TU6']) ?  $output_values['TU6'] : "";
        $data['VC'] = isset($output_values['VC']) ?  $output_values['VC'] : "";
        $data['condenser_tube_material'] = isset($output_values['TV5']) ?  $output_values['TV5'] : "";
        $data['condenser_tube_thickness'] = isset($output_values['TV6']) ?  $output_values['TV6'] : "";
        $data['chilled_connection_diameter'] = isset($output_values['ChilledConnectionDiameter']) ?  $output_values['ChilledConnectionDiameter'] : "";
        $data['cooling_connection_diameter'] = isset($output_values['CoolingConnectionDiameter']) ?  $output_values['CoolingConnectionDiameter'] : "";
        $data['exhaust_connection_diameter'] = isset($output_values['ExhaustConnectionDiameter']) ?  $output_values['ExhaustConnectionDiameter'] : "";
        $data['exhaust_gas_sp_heat_capacity'] = isset($output_values['AvgExhGasCp']) ?  $output_values['AvgExhGasCp'] : "";
        $data['power_supply'] = isset($output_values['PowerSupply']) ?  $output_values['PowerSupply'] : "";
        $data['power_consumption'] = isset($output_values['TotalPowerConsumption']) ?  $output_values['TotalPowerConsumption'] : "";

        $data['absorbent_pump_rating(KW)'] = isset($output_values['AbsorbentPumpMotorKW']) ?  $output_values['AbsorbentPumpMotorKW'] : "";
        $data['absorbent_pump_rating(AMP)'] = isset($output_values['AbsorbentPumpMotorAmp']) ?  $output_values['AbsorbentPumpMotorAmp'] : "";
        $data['refrigerant_pump_rating(KW)'] = isset($output_values['RefrigerantPumpMotorKW']) ?  $output_values['RefrigerantPumpMotorKW'] : "";
        $data['refrigerant_pump_rating(AMP)'] = isset($output_values['RefrigerantPumpMotorAmp']) ?  $output_values['RefrigerantPumpMotorAmp'] : "";
        $data['vaccum_pump_rating(KW)'] = isset($output_values['PurgePumpMotorKW']) ?  $output_values['PurgePumpMotorKW'] : "";
        $data['vaccum_pump_rating(AMP)'] = isset($output_values['PurgePumpMotorAmp']) ?  $output_values['PurgePumpMotorAmp'] : "";
        $data['MOP'] = "";
        $data['MCA'] = "";
        if($output_values['region_type'] == 2){
            $data['absorbent_pump_rating(KW)'] = isset($output_values['USA_AbsorbentPumpMotorKW']) ?  $output_values['USA_AbsorbentPumpMotorKW'] : "";
            $data['absorbent_pump_rating(AMP)'] = isset($output_values['USA_AbsorbentPumpMotorAmp']) ?  $output_values['USA_AbsorbentPumpMotorAmp'] : "";
            $data['refrigerant_pump_rating(KW)'] = isset($output_values['USA_RefrigerantPumpMotorKW']) ?  $output_values['USA_RefrigerantPumpMotorKW'] : "";
            $data['refrigerant_pump_rating(AMP)'] = isset($output_values['USA_RefrigerantPumpMotorAmp']) ?  $output_values['USA_RefrigerantPumpMotorAmp'] : "";
            $data['vaccum_pump_rating(KW)'] = isset($output_values['USA_PurgePumpMotorKW']) ?  $output_values['USA_PurgePumpMotorKW'] : "";
            $data['vaccum_pump_rating(AMP)'] = isset($output_values['USA_PurgePumpMotorAmp']) ?  $output_values['USA_PurgePumpMotorAmp'] : "";
            $data['MOP'] = isset($output_values['MOP']) ?  $output_values['MOP'] : "";
            $data['MCA'] = isset($output_values['MCA']) ?  $output_values['MCA'] : "";
        }  
        $data['Length'] = isset($output_values['Length']) ?  $output_values['Length'] : "";
        $data['Width'] = isset($output_values['Width']) ?  $output_values['Width'] : "";
        $data['Height'] = isset($output_values['Height']) ?  $output_values['Height'] : "";
        $data['OperatingWeight'] = isset($output_values['OperatingWeight']) ?  $output_values['OperatingWeight'] : "";
        $data['MaxShippingWeight'] = isset($output_values['MaxShippingWeight']) ?  $output_values['MaxShippingWeight'] : "";
        $data['FloodedWeight'] = isset($output_values['FloodedWeight']) ?  $output_values['FloodedWeight'] : "";
        $data['DryWeight'] = isset($output_values['DryWeight']) ?  $output_values['DryWeight'] : "";
        $data['ClearanceForTubeRemoval'] = isset($output_values['ClearanceForTubeRemoval']) ?  $output_values['ClearanceForTubeRemoval'] : "";
        $data['region_type'] = isset($output_values['region_type']) ?  $output_values['region_type'] : "";
        $data['Result'] = isset($output_values['Result']) ?  $output_values['Result'] : "";


        return $data;

    }
    
    public function outputH1ExcelFormat($input_values,$output_values){
        $data = [];
        $data['S.No'] = $input_values['S.No'];
        $data['model'] = isset($output_values['model_name']) ?  $output_values['model_name'] : "";
        $data['Capacity'] = isset($output_values['TON']) ?  floatval($output_values['TON']) : "";
        $data['chilled_inlet_temp'] = isset($output_values['TCHW1H']) ?  floatval($output_values['TCHW1H']) : "";
        $data['chilled_outlet_temp'] = isset($output_values['TCHW2L']) ?  floatval($output_values['TCHW2L']) : "";
        $data['chilled_water_flow'] = isset($output_values['ChilledWaterFlow']) ?  floatval($output_values['ChilledWaterFlow']) : "";
        $data['cooling_water_flow'] = isset($output_values['TCHW2L']) ?  floatval($output_values['TCHW2L']) : "";
        $data['cooling_inlet_temp'] = isset($output_values['TCW11']) ?  floatval($output_values['TCW11']) : "";
        $data['cooling_outlet_temp'] = isset($output_values['CoolingWaterOutTemperature']) ?  floatval($output_values['CoolingWaterOutTemperature']) : "";
        $data['hot_water_in'] = isset($output_values['THW1']) ?  floatval($output_values['THW1']) : "";
        $data['hot_water_out'] = isset($output_values['THW2']) ?  floatval($output_values['THW2']) : "";
        $data['hot_water_flow'] = isset($output_values['GHOT']) ?  floatval($output_values['GHOT']) : "";
        if(empty($output_values['BypassFlow'])){
            $data['cooling_bypass_flow'] = "-";
        }
        else{
            $data['cooling_bypass_flow'] = isset($output_values['BypassFlow']) ?  $output_values['BypassFlow'] : "";
        }
        if($output_values['GLL'] == 1){
            $data['chilled_glycol_type'] = "NA";
        }
        else if($output_values['GLL'] == 2){
            $data['chilled_glycol_type'] = "Ethylene";
        }
        else{
            $data['chilled_glycol_type'] = "Proplylene";
        } 
        $data['chilled_gylcol'] = isset($output_values['CHGLY']) ?  floatval($output_values['CHGLY']) : ""; 
        $data['cooling_gylcol'] = isset($output_values['COGLY']) ?  floatval($output_values['COGLY']) : "";
        if($output_values['TUU'] == "standard"){
            $data['chilled_fouling_factor'] = "standard"; 
        }
        else{
            $data['chilled_fouling_factor'] = isset($output_values['FFCHW1']) ?  floatval($output_values['FFCHW1']) : ""; 
        }

        if($output_values['TUU'] == "standard"){
            $data['cooling_fouling_factor'] = "standard"; 
        }
        else{
            $data['cooling_fouling_factor'] = isset($output_values['FFCOW1']) ?  floatval($output_values['FFCOW1']) : ""; 
        }

        $data['UEVAH'] = isset($output_values['UEVAH']) ?  floatval($output_values['UEVAH']) : "";
        $data['UABSH'] = isset($output_values['UABSH']) ?  floatval($output_values['UABSH']) : "";
        $data['UCON'] = isset($output_values['UCON']) ?  floatval($output_values['UCON']) : "";
        $data['UHTG'] = isset($output_values['UHTG']) ?  floatval($output_values['UHTG']) : "";
        $data['GDIL'] = isset($output_values['GDIL']) ?  floatval($output_values['GDIL']) : "";
        $data['XDIL'] = isset($output_values['XDIL']) ?  floatval($output_values['XDIL']) : "";
        $data['XCONC'] = isset($output_values['XCONC']) ?  floatval($output_values['XCONC']) : "";
        $data['TNEV'] = isset($output_values['TNEV']) ?  floatval($output_values['TNEV']) : "";
        $data['TNAA'] = isset($output_values['TNAA']) ?  floatval($output_values['TNAA']) : "";
        $data['TNC'] = isset($output_values['TNC']) ?  floatval($output_values['TNC']) : "";
        $data['TNG'] = isset($output_values['TNG']) ?  floatval($output_values['TNG']) : "";
        $data['VEA'] = isset($output_values['VEA']) ?  floatval($output_values['VEA']) : "";
        $data['evaporate_pass'] = isset($output_values['EvaporatorPasses']) ?  $output_values['EvaporatorPasses'] : "";
        $data['chilled_pressure_loss'] = isset($output_values['ChilledFrictionLoss']) ?  floatval($output_values['ChilledFrictionLoss']) : "";
        $data['evaporator_tube_material'] = isset($output_values['TU2']) ?  floatval($output_values['TU2']) : "";
        $data['evaporator_tube_thickness'] = isset($output_values['TU3']) ?  floatval($output_values['TU3']) : "";
        $data['VA'] = isset($output_values['VA']) ?  floatval($output_values['VA']) : "";
        $data['condenser_pass'] = isset($output_values['CondenserPasses']) ?  $output_values['CondenserPasses'] : "";
        $data['absorber_pass'] = isset($output_values['AbsorberPasses']) ?  $output_values['AbsorberPasses'] : "";
        $data['cooling_pressure_loss'] = isset($output_values['CoolingFrictionLoss']) ?  floatval($output_values['CoolingFrictionLoss']) : "";
        $data['absorber_tube_material'] = isset($output_values['TU5']) ?  floatval($output_values['TU5']) : "";
        $data['absorber_tube_thickness'] = isset($output_values['TU6']) ?  floatval($output_values['TU6']) : "";
        $data['VC'] = isset($output_values['VC']) ?  floatval($output_values['VC']) : "";
        $data['condenser_tube_material'] = isset($output_values['TV5']) ?  floatval($output_values['TV5']) : "";
        $data['condenser_tube_thickness'] = isset($output_values['TV6']) ?  floatval($output_values['TV6']) : "";
        $data['chilled_connection_diameter'] = isset($output_values['ChilledConnectionDiameter']) ?  floatval($output_values['ChilledConnectionDiameter']) : "";
        $data['cooling_connection_diameter'] = isset($output_values['CoolingConnectionDiameter']) ?  floatval($output_values['CoolingConnectionDiameter']) : "";
        $data['VG'] = isset($output_values['VG']) ?  floatval($output_values['VG']) : "";
        $data['hot_water_passes'] = isset($output_values['TGP']) ?  floatval($output_values['TGP']) : "";
        $data['hot_water_pressure_loss'] = isset($output_values['HotWaterFrictionLoss']) ?  floatval($output_values['HotWaterFrictionLoss']) : "";
        $data['power_supply'] = isset($output_values['PowerSupply']) ?  $output_values['PowerSupply'] : "";
        $data['power_consumption'] = isset($output_values['TotalPowerConsumption']) ?  floatval($output_values['TotalPowerConsumption']) : "";
        $data['absorbent_pump_rating(KW)'] = isset($output_values['AbsorbentPumpMotorKW']) ?  floatval($output_values['AbsorbentPumpMotorKW']) : "";
        $data['absorbent_pump_rating(AMP)'] = isset($output_values['AbsorbentPumpMotorAmp']) ?  floatval($output_values['AbsorbentPumpMotorAmp']) : "";
        $data['refrigerant_pump_rating(KW)'] = isset($output_values['RefrigerantPumpMotorKW']) ?  floatval($output_values['RefrigerantPumpMotorKW']) : "";
        $data['refrigerant_pump_rating(AMP)'] = isset($output_values['RefrigerantPumpMotorAmp']) ?  floatval($output_values['RefrigerantPumpMotorAmp']) : "";
        $data['vaccum_pump_rating(KW)'] = isset($output_values['PurgePumpMotorKW']) ?  floatval($output_values['PurgePumpMotorKW']) : "";
        $data['vaccum_pump_rating(AMP)'] = isset($output_values['PurgePumpMotorAmp']) ?  floatval($output_values['PurgePumpMotorAmp']) : "";
        $data['MOP'] = "";
        $data['MCA'] = "";
        if($output_values['region_type'] == 2){
            $data['absorbent_pump_rating(KW)'] = isset($output_values['USA_AbsorbentPumpMotorKW']) ?  floatval($output_values['USA_AbsorbentPumpMotorKW']) : "";
            $data['absorbent_pump_rating(AMP)'] = isset($output_values['USA_AbsorbentPumpMotorAmp']) ?  floatval($output_values['USA_AbsorbentPumpMotorAmp']) : "";
            $data['refrigerant_pump_rating(KW)'] = isset($output_values['USA_RefrigerantPumpMotorKW']) ?  floatval($output_values['USA_RefrigerantPumpMotorKW']) : "";
            $data['refrigerant_pump_rating(AMP)'] = isset($output_values['USA_RefrigerantPumpMotorAmp']) ?  floatval($output_values['USA_RefrigerantPumpMotorAmp']) : "";
            $data['vaccum_pump_rating(KW)'] = isset($output_values['USA_PurgePumpMotorKW']) ?  floatval($output_values['USA_PurgePumpMotorKW']) : "";
            $data['vaccum_pump_rating(AMP)'] = isset($output_values['USA_PurgePumpMotorAmp']) ?  floatval($output_values['USA_PurgePumpMotorAmp']) : "";
            $data['MOP'] = isset($output_values['MOP']) ?  floatval($output_values['MOP']) : "";
            $data['MCA'] = isset($output_values['MCA']) ?  floatval($output_values['MCA']) : "";
        }


        $data['Length'] = isset($output_values['Length']) ?  floatval($output_values['Length']) : "";
        $data['Width'] = isset($output_values['Width']) ?  floatval($output_values['Width']) : "";
        $data['Height'] = isset($output_values['Height']) ?  floatval($output_values['Height']) : "";
        $data['OperatingWeight'] = isset($output_values['OperatingWeight']) ?  floatval($output_values['OperatingWeight']) : "";
        $data['MaxShippingWeight'] = isset($output_values['MaxShippingWeight']) ?  floatval($output_values['MaxShippingWeight']) : "";
        $data['FloodedWeight'] = isset($output_values['FloodedWeight']) ?  floatval($output_values['FloodedWeight']) : "";
        $data['DryWeight'] = isset($output_values['DryWeight']) ?  floatval($output_values['DryWeight']) : "";
        $data['ClearanceForTubeRemoval'] = isset($output_values['ClearanceForTubeRemoval']) ?  floatval($output_values['ClearanceForTubeRemoval']) : "";
        $data['region_type'] = isset($output_values['region_type']) ?  floatval($output_values['region_type']) : "";
        $data['Result'] = isset($output_values['Result']) ?  $output_values['Result'] : "";



        return $data;

    }

    public function outputCHS2ExcelFormat($input_values,$output_values){
        $data = [];

        $data['Sr.No'] = $input_values['S.No'];
        $data['model'] = isset($output_values['model_name']) ?  $output_values['model_name'] : "";
        $data['Capacity'] = isset($output_values['TON']) ?  $output_values['TON'] : "";
        $data['chilled_inlet_temp'] = isset($output_values['TCHW11']) ?  $output_values['TCHW11'] : "";
        $data['chilled_outlet_temp'] = isset($output_values['TCHW12']) ?  $output_values['TCHW12'] : "";
        $data['chilled_water_flow'] = isset($output_values['ChilledWaterFlow']) ?  $output_values['ChilledWaterFlow'] : "";
        $data['cooling_water_flow'] = isset($output_values['GCW']) ?  $output_values['GCW'] : "";
        $data['cooling_inlet_temp'] = isset($output_values['TCW11']) ?  $output_values['TCW11'] : "";
        $data['cooling_outlet_temp'] = isset($output_values['CoolingWaterOutTemperature']) ?  $output_values['CoolingWaterOutTemperature'] : "";
        $data['steam_pressure'] = isset($output_values['PST1']) ?  $output_values['PST1'] : "";
        $data['steam_consumption'] = isset($output_values['SteamConsumption']) ?  $output_values['SteamConsumption'] : "";
        if(empty($output_values['BypassFlow'])){
            $data['cooling_bypass_flow'] = "-";
        }
        else{
            $data['cooling_bypass_flow'] = isset($output_values['BypassFlow']) ?  $output_values['BypassFlow'] : "";
        }    
        if($output_values['GL'] == 1){
            $data['chilled_glycol_type'] = "NA";
        }
        else if($output_values['GL'] == 2){
            $data['chilled_glycol_type'] = "Ethylene";
        }
        else{
            $data['chilled_glycol_type'] = "Proplylene";
        }
        $data['chilled_gylcol'] = isset($output_values['CHGLY']) ?  $output_values['CHGLY'] : ""; 
        $data['cooling_gylcol'] = isset($output_values['COGLY']) ?  $output_values['COGLY'] : "";
        if($output_values['TUU'] == "standard"){
            $data['chilled_fouling_factor'] = "standard"; 
        }
        else{
            $data['chilled_fouling_factor'] = isset($output_values['FFCHW1']) ?  $output_values['FFCHW1'] : ""; 
        }

        if($output_values['TUU'] == "standard"){
            $data['cooling_fouling_factor'] = "standard"; 
        }
        else{
            $data['cooling_fouling_factor'] = isset($output_values['FFCOW1']) ?  $output_values['FFCOW1'] : ""; 
        }

        $data['UEVAH'] = isset($output_values['UEVAH']) ?  $output_values['UEVAH'] : "";
        $data['UABSH'] = isset($output_values['UABSH']) ?  $output_values['UABSH'] : "";
        $data['UCON'] = isset($output_values['UCON']) ?  $output_values['UCON'] : "";
        $data['GDIL'] = isset($output_values['GDIL']) ?  $output_values['GDIL'] : "";
        $data['XDIL'] = isset($output_values['XDIL']) ?  $output_values['XDIL'] : "";
        $data['XCONC'] = isset($output_values['XCONC']) ?  $output_values['XCONC'] : "";
        $data['TNEV'] = isset($output_values['TNEV']) ?  $output_values['TNEV'] : "";
        $data['TNAA'] = isset($output_values['TNAA']) ?  $output_values['TNAA'] : "";
        $data['TNC'] = isset($output_values['TNC']) ?  $output_values['TNC'] : "";
        $data['VEA'] = isset($output_values['VEA']) ?  $output_values['VEA'] : "";
        $data['evaporate_pass'] = isset($output_values['EvaporatorPasses']) ?  $output_values['EvaporatorPasses'] : "";
        $data['chilled_pressure_loss'] = isset($output_values['ChilledFrictionLoss']) ?  $output_values['ChilledFrictionLoss'] : "";
        $data['evaporator_tube_material'] = isset($output_values['TU2']) ?  $output_values['TU2'] : "";
        $data['evaporator_tube_thickness'] = isset($output_values['TU3']) ?  $output_values['TU3'] : "";
        $data['VA'] = isset($output_values['VA']) ?  $output_values['VA'] : "";
        $condeser_pass = isset($output_values['CondenserPasses']) ?  $output_values['CondenserPasses'] : "";
        $data['absorber_condenser_pass'] = isset($output_values['AbsorberPasses']) ?  $output_values['AbsorberPasses'] : ""."/".$condeser_pass;
        $data['cooling_pressure_loss'] = isset($output_values['CoolingFrictionLoss']) ?  $output_values['CoolingFrictionLoss'] : "";
        $data['absorber_tube_material'] = isset($output_values['TU5']) ?  $output_values['TU5'] : "";
        $data['absorber_tube_thickness'] = isset($output_values['TU6']) ?  $output_values['TU6'] : "";
        $data['VC'] = isset($output_values['VC']) ?  $output_values['VC'] : "";
        $data['condenser_tube_material'] = isset($output_values['TV5']) ?  $output_values['TV5'] : "";
        $data['condenser_tube_thickness'] = isset($output_values['TV6']) ?  $output_values['TV6'] : "";
        $data['chilled_connection_diameter'] = isset($output_values['ChilledConnectionDiameter']) ?  $output_values['ChilledConnectionDiameter'] : "";
        $data['cooling_connection_diameter'] = isset($output_values['CoolingConnectionDiameter']) ?  $output_values['CoolingConnectionDiameter'] : "";
        $data['connection_inlet_dia'] = isset($output_values['SteamConnectionDiameter']) ?  $output_values['SteamConnectionDiameter'] : "";
        $data['connection_drain_dia'] = isset($output_values['SteamDrainDiameter']) ?  $output_values['SteamDrainDiameter'] : "";

        $data['ASA'] = isset($output_values['ASA']) ?  $output_values['ASA'] : "";
        $data['side_arm_passes'] = isset($output_values['TGP']) ?  $output_values['TGP'] : "";
        $data['hot_water_pressure_loss'] = isset($output_values['HotWaterFrictionLoss']) ?  $output_values['HotWaterFrictionLoss'] : "";
        $data['hot_water_flow'] = isset($output_values['HotWaterFlow']) ?  $output_values['HotWaterFlow'] : "";

        $data['power_supply'] = isset($output_values['PowerSupply']) ?  $output_values['PowerSupply'] : "";
        $data['power_consumption'] = isset($output_values['TotalPowerConsumption']) ?  $output_values['TotalPowerConsumption'] : "";
        $data['absorbent_pump_rating(KW)'] = isset($output_values['AbsorbentPumpMotorKW']) ?  $output_values['AbsorbentPumpMotorKW'] : "";
        $data['absorbent_pump_rating(AMP)'] = isset($output_values['AbsorbentPumpMotorAmp']) ?  $output_values['AbsorbentPumpMotorAmp'] : "";
        $data['refrigerant_pump_rating(KW)'] = isset($output_values['RefrigerantPumpMotorKW']) ?  $output_values['RefrigerantPumpMotorKW'] : "";
        $data['refrigerant_pump_rating(AMP)'] = isset($output_values['RefrigerantPumpMotorAmp']) ?  $output_values['RefrigerantPumpMotorAmp'] : "";
        $data['vaccum_pump_rating(KW)'] = isset($output_values['PurgePumpMotorKW']) ?  $output_values['PurgePumpMotorKW'] : "";
        $data['vaccum_pump_rating(AMP)'] = isset($output_values['PurgePumpMotorAmp']) ?  $output_values['PurgePumpMotorAmp'] : "";
        $data['MOP'] = "";
        $data['MCA'] = "";
        if($output_values['region_type'] == 2){
            $data['absorbent_pump_rating(KW)'] = isset($output_values['USA_AbsorbentPumpMotorKW']) ?  $output_values['USA_AbsorbentPumpMotorKW'] : "";
            $data['absorbent_pump_rating(AMP)'] = isset($output_values['USA_AbsorbentPumpMotorAmp']) ?  $output_values['USA_AbsorbentPumpMotorAmp'] : "";
            $data['refrigerant_pump_rating(KW)'] = isset($output_values['USA_RefrigerantPumpMotorKW']) ?  $output_values['USA_RefrigerantPumpMotorKW'] : "";
            $data['refrigerant_pump_rating(AMP)'] = isset($output_values['USA_RefrigerantPumpMotorAmp']) ?  $output_values['USA_RefrigerantPumpMotorAmp'] : "";
            $data['vaccum_pump_rating(KW)'] = isset($output_values['USA_PurgePumpMotorKW']) ?  $output_values['USA_PurgePumpMotorKW'] : "";
            $data['vaccum_pump_rating(AMP)'] = isset($output_values['USA_PurgePumpMotorAmp']) ?  $output_values['USA_PurgePumpMotorAmp'] : "";
            $data['MOP'] = isset($output_values['MOP']) ?  $output_values['MOP'] : "";
            $data['MCA'] = isset($output_values['MCA']) ?  $output_values['MCA'] : "";
        }  
        $data['Length'] = isset($output_values['Length']) ?  $output_values['Length'] : "";
        $data['Width'] = isset($output_values['Width']) ?  $output_values['Width'] : "";
        $data['Height'] = isset($output_values['Height']) ?  $output_values['Height'] : "";
        $data['OperatingWeight'] = isset($output_values['OperatingWeight']) ?  $output_values['OperatingWeight'] : "";
        $data['MaxShippingWeight'] = isset($output_values['MaxShippingWeight']) ?  $output_values['MaxShippingWeight'] : "";
        $data['FloodedWeight'] = isset($output_values['FloodedWeight']) ?  $output_values['FloodedWeight'] : "";
        $data['DryWeight'] = isset($output_values['DryWeight']) ?  $output_values['DryWeight'] : "";
        $data['ClearanceForTubeRemoval'] = isset($output_values['ClearanceForTubeRemoval']) ?  $output_values['ClearanceForTubeRemoval'] : "";
        $data['region_type'] = isset($output_values['region_type']) ?  $output_values['region_type'] : "";
        $data['Result'] = isset($output_values['Result']) ?  $output_values['Result'] : "";


        return $data;

    }

    public function outputCHG2ExcelFormat($input_values,$output_values){
        $data = [];


        $data['Sr.No'] = $input_values['S.No'];
        $data['model'] = isset($output_values['model_name']) ?  $output_values['model_name'] : "";
        $data['Capacity'] = isset($output_values['TON']) ?  $output_values['TON'] : "";
        $data['chilled_inlet_temp'] = isset($output_values['TCHW11']) ?  $output_values['TCHW11'] : "";
        $data['chilled_outlet_temp'] = isset($output_values['TCHW12']) ?  $output_values['TCHW12'] : "";
        $data['chilled_water_flow'] = isset($output_values['ChilledWaterFlow']) ?  $output_values['ChilledWaterFlow'] : "";
        $data['cooling_water_flow'] = isset($output_values['GCW']) ?  $output_values['GCW'] : "";
        $data['cooling_inlet_temp'] = isset($output_values['TCW11']) ?  $output_values['TCW11'] : "";
        $data['cooling_outlet_temp'] = isset($output_values['CoolingWaterOutTemperature']) ?  $output_values['CoolingWaterOutTemperature'] : "";

        $data['fuel_type'] = isset($output_values['GCV']) ?  $output_values['GCV'] : "";
        $data['fuel_consumption'] = isset($output_values['FuelConsumption']) ?  $output_values['FuelConsumption'] : "";
        if(empty($output_values['BypassFlow'])){
            $data['cooling_bypass_flow'] = "-";
        }
        else{
            $data['cooling_bypass_flow'] = isset($output_values['BypassFlow']) ?  $output_values['BypassFlow'] : "";
        }    
        if($output_values['GLL'] == 1){
            $data['chilled_glycol_type'] = "NA";
        }
        else if($output_values['GLL'] == 2){
            $data['chilled_glycol_type'] = "Ethylene";
        }
        else{
            $data['chilled_glycol_type'] = "Proplylene";
        }
        $data['chilled_gylcol'] = isset($output_values['CHGLY']) ?  $output_values['CHGLY'] : ""; 
        $data['cooling_gylcol'] = isset($output_values['COGLY']) ?  $output_values['COGLY'] : "";
        if($output_values['TUU'] == "standard"){
            $data['chilled_fouling_factor'] = "standard"; 
        }
        else{
            $data['chilled_fouling_factor'] = isset($output_values['FFCHW1']) ?  $output_values['FFCHW1'] : ""; 
        }

        if($output_values['TUU'] == "standard"){
            $data['cooling_fouling_factor'] = "standard"; 
        }
        else{
            $data['cooling_fouling_factor'] = isset($output_values['FFCOW1']) ?  $output_values['FFCOW1'] : ""; 
        }

        $data['UEVAH'] = isset($output_values['UEVAH']) ?  $output_values['UEVAH'] : "";
        $data['UABSH'] = isset($output_values['UABSH']) ?  $output_values['UABSH'] : "";
        $data['UCON'] = isset($output_values['UCON']) ?  $output_values['UCON'] : "";
        $data['GDIL'] = isset($output_values['GDIL']) ?  $output_values['GDIL'] : "";
        $data['XDIL'] = isset($output_values['XDIL']) ?  $output_values['XDIL'] : "";
        $data['XCONC'] = isset($output_values['XCONC']) ?  $output_values['XCONC'] : "";
        $data['TNEV'] = isset($output_values['TNEV']) ?  $output_values['TNEV'] : "";
        $data['TNAA'] = isset($output_values['TNAA']) ?  $output_values['TNAA'] : "";
        $data['TNC'] = isset($output_values['TNC']) ?  $output_values['TNC'] : "";
        $data['VEA'] = isset($output_values['VEA']) ?  $output_values['VEA'] : "";
        $data['evaporate_pass'] = isset($output_values['EvaporatorPasses']) ?  $output_values['EvaporatorPasses'] : "";
        $data['chilled_pressure_loss'] = isset($output_values['ChilledFrictionLoss']) ?  $output_values['ChilledFrictionLoss'] : "";
        $data['evaporator_tube_material'] = isset($output_values['TU2']) ?  $output_values['TU2'] : "";
        $data['evaporator_tube_thickness'] = isset($output_values['TU3']) ?  $output_values['TU3'] : "";
        $data['VA'] = isset($output_values['VA']) ?  $output_values['VA'] : "";
        $condeser_pass = isset($output_values['CondenserPasses']) ?  $output_values['CondenserPasses'] : "";
        $data['absorber_condenser_pass'] = isset($output_values['AbsorberPasses']) ?  $output_values['AbsorberPasses'] : ""."/".$condeser_pass;
        $data['cooling_pressure_loss'] = isset($output_values['CoolingFrictionLoss']) ?  $output_values['CoolingFrictionLoss'] : "";
        $data['absorber_tube_material'] = isset($output_values['TU5']) ?  $output_values['TU5'] : "";
        $data['absorber_tube_thickness'] = isset($output_values['TU6']) ?  $output_values['TU6'] : "";
        $data['VC'] = isset($output_values['VC']) ?  $output_values['VC'] : "";
        $data['condenser_tube_material'] = isset($output_values['TV5']) ?  $output_values['TV5'] : "";
        $data['condenser_tube_thickness'] = isset($output_values['TV6']) ?  $output_values['TV6'] : "";
        $data['chilled_connection_diameter'] = isset($output_values['ChilledConnectionDiameter']) ?  $output_values['ChilledConnectionDiameter'] : "";
        $data['cooling_connection_diameter'] = isset($output_values['CoolingConnectionDiameter']) ?  $output_values['CoolingConnectionDiameter'] : "";
        $data['connection_inlet_dia'] = isset($output_values['SteamConnectionDiameter']) ?  $output_values['SteamConnectionDiameter'] : "";
        $data['connection_drain_dia'] = isset($output_values['SteamDrainDiameter']) ?  $output_values['SteamDrainDiameter'] : "";

        $data['ASA'] = isset($output_values['ASA']) ?  $output_values['ASA'] : "";
        $data['side_arm_passes'] = isset($output_values['TGP']) ?  $output_values['TGP'] : "";
        $data['hot_water_pressure_loss'] = isset($output_values['HotWaterFrictionLoss']) ?  $output_values['HotWaterFrictionLoss'] : "";
        $data['hot_water_flow'] = isset($output_values['HotWaterFlow']) ?  $output_values['HotWaterFlow'] : "";

        $data['power_supply'] = isset($output_values['PowerSupply']) ?  $output_values['PowerSupply'] : "";
        $data['power_consumption'] = isset($output_values['TotalPowerConsumption']) ?  $output_values['TotalPowerConsumption'] : "";
        $data['absorbent_pump_rating(KW)'] = isset($output_values['AbsorbentPumpMotorKW']) ?  $output_values['AbsorbentPumpMotorKW'] : "";
        $data['absorbent_pump_rating(AMP)'] = isset($output_values['AbsorbentPumpMotorAmp']) ?  $output_values['AbsorbentPumpMotorAmp'] : "";
        $data['refrigerant_pump_rating(KW)'] = isset($output_values['RefrigerantPumpMotorKW']) ?  $output_values['RefrigerantPumpMotorKW'] : "";
        $data['refrigerant_pump_rating(AMP)'] = isset($output_values['RefrigerantPumpMotorAmp']) ?  $output_values['RefrigerantPumpMotorAmp'] : "";
        $data['vaccum_pump_rating(KW)'] = isset($output_values['PurgePumpMotorKW']) ?  $output_values['PurgePumpMotorKW'] : "";
        $data['vaccum_pump_rating(AMP)'] = isset($output_values['PurgePumpMotorAmp']) ?  $output_values['PurgePumpMotorAmp'] : "";
        $data['MOP'] = "";
        $data['MCA'] = "";
        if($output_values['region_type'] == 2){
            $data['absorbent_pump_rating(KW)'] = isset($output_values['USA_AbsorbentPumpMotorKW']) ?  $output_values['USA_AbsorbentPumpMotorKW'] : "";
            $data['absorbent_pump_rating(AMP)'] = isset($output_values['USA_AbsorbentPumpMotorAmp']) ?  $output_values['USA_AbsorbentPumpMotorAmp'] : "";
            $data['refrigerant_pump_rating(KW)'] = isset($output_values['USA_RefrigerantPumpMotorKW']) ?  $output_values['USA_RefrigerantPumpMotorKW'] : "";
            $data['refrigerant_pump_rating(AMP)'] = isset($output_values['USA_RefrigerantPumpMotorAmp']) ?  $output_values['USA_RefrigerantPumpMotorAmp'] : "";
            $data['vaccum_pump_rating(KW)'] = isset($output_values['USA_PurgePumpMotorKW']) ?  $output_values['USA_PurgePumpMotorKW'] : "";
            $data['vaccum_pump_rating(AMP)'] = isset($output_values['USA_PurgePumpMotorAmp']) ?  $output_values['USA_PurgePumpMotorAmp'] : "";
            $data['MOP'] = isset($output_values['MOP']) ?  $output_values['MOP'] : "";
            $data['MCA'] = isset($output_values['MCA']) ?  $output_values['MCA'] : "";
        }  
        $data['Length'] = isset($output_values['Length']) ?  $output_values['Length'] : "";
        $data['Width'] = isset($output_values['Width']) ?  $output_values['Width'] : "";
        $data['Height'] = isset($output_values['Height']) ?  $output_values['Height'] : "";
        $data['OperatingWeight'] = isset($output_values['OperatingWeight']) ?  $output_values['OperatingWeight'] : "";
        $data['MaxShippingWeight'] = isset($output_values['MaxShippingWeight']) ?  $output_values['MaxShippingWeight'] : "";
        $data['FloodedWeight'] = isset($output_values['FloodedWeight']) ?  $output_values['FloodedWeight'] : "";
        $data['DryWeight'] = isset($output_values['DryWeight']) ?  $output_values['DryWeight'] : "";
        $data['ClearanceForTubeRemoval'] = isset($output_values['ClearanceForTubeRemoval']) ?  $output_values['ClearanceForTubeRemoval'] : "";
        $data['region_type'] = isset($output_values['region_type']) ?  $output_values['region_type'] : "";
        $data['Result'] = isset($output_values['Result']) ?  $output_values['Result'] : "";


        return $data;

    }
}
