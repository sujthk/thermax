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

        if($request->code == "D_S2"){
            $s2_data = array('steam_pressure');
            $data = array_merge($data, $s2_data);
        }

        if($request->code == 'D_H2'){
            $h2_data = array('hot_water_in','hot_water_out','all_work_pr_hw');
            $data = array_merge($data, $h2_data);
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
        // Log::info($calculator_input);
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

        if($code == 'D_H2'){
            $h2 = new DoubleH2SteamController();
            $result = $h2->testingH2Calculation($calculator_input);
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
        $i = 1;
        foreach ($tested_results as $tested_result) {
            $input_values = json_decode($tested_result->input_values,true);
            $output_values = json_decode($tested_result->output_values,true);

            if(!empty($tested_result->output_values)){
                
                $datas[] = $this->outputExcelFormat($output_values,$input_values['calculator_code'],$i);
                $i++;
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

    public function outputExcelFormat($output_values,$calculator_code,$i){
        $data = [];
        $data['Sr.No'] = $i;
        $data['Capacity (kW)'] = (isset($output_values['TON']) ?  $output_values['TON'] : "")*3.516;
        $data['Chilled Water Flow'] = isset($output_values['GCHW']) ?  $output_values['GCHW'] : "";
        $data['Chilled water Velocity'] = isset($output_values['VEA']) ?  $output_values['VEA'] : "";
        $data['Evap passes'] = isset($output_values['EvaporatorPasses']) ?  $output_values['EvaporatorPasses'] : "";
        $data['CHW friction loss'] = isset($output_values['ChilledFrictionLoss']) ?  $output_values['ChilledFrictionLoss'] : "";
        $data['CHW Pressure drop'] = isset($output_values['ChilledPressureDrop']) ?  $output_values['ChilledPressureDrop'] : "";
        $data['Heat Rejected (kW)'] = isset($output_values['QCW']) ?  $output_values['QCW'] : "";
        $data['COW Flow'] = isset($output_values['GCW']) ?  $output_values['GCW'] : "";
        $data['Abs Velocity'] = isset($output_values['VA']) ?  $output_values['VA'] : "";
        $data['Cond Velocity'] = isset($output_values['VC']) ?  $output_values['VC'] : "";
        $data['COW In Temp'] = isset($output_values['TCW11']) ?  $output_values['TCW11'] : "";
        $data['COW Out temp'] = isset($output_values['CoolingWaterOutTemperature']) ?  $output_values['CoolingWaterOutTemperature'] : "";
        $absorber_passes = isset($output_values['AbsorberPasses']) ?  $output_values['AbsorberPasses'] : "";
        $condenser_passes = isset($output_values['CondenserPasses']) ?  $output_values['CondenserPasses'] : "";
        $data['COW passes'] = $absorber_passes."/".$condenser_passes;
        $data['COW Friction loss'] = isset($output_values['CoolingFrictionLoss']) ?  $output_values['CoolingFrictionLoss'] : "";
        $data['COW Pressure drop'] = isset($output_values['CoolingPressureDrop']) ?  $output_values['CoolingPressureDrop'] : "";
        
        
        $data['Eva Tube'] = isset($output_values['TU2']) ?  $output_values['TU2'] : "";
        $data['Abs Tube'] = isset($output_values['TU5']) ?  $output_values['TU5'] : "";
        $data['Con Tube'] = isset($output_values['TV5']) ?  $output_values['TV5'] : "";
        $data['Pow Supply'] = isset($output_values['PowerSupply']) ?  $output_values['PowerSupply'] : "";
        $data['Power Cons'] = isset($output_values['TotalPowerConsumption']) ?  $output_values['TotalPowerConsumption'] : "";

        if(intval($output_values['region_type']) == 2){
            $data['Abs Pump'] = isset($output_values['USA_AbsorbentPumpMotorAmp']) ?  $output_values['USA_AbsorbentPumpMotorAmp'] : "";
            $data['Ref Pump'] = isset($output_values['USA_RefrigerantPumpMotorAmp']) ?  $output_values['USA_RefrigerantPumpMotorAmp'] : "";
            $data['Vacuum Pump'] = isset($output_values['USA_PurgePumpMotorAmp']) ?  $output_values['USA_PurgePumpMotorAmp'] : "";
        }
        else{
            $data['Abs Pump'] = isset($output_values['AbsorbentPumpMotorAmp']) ?  $output_values['AbsorbentPumpMotorAmp'] : "";
            $data['Ref Pump'] = isset($output_values['RefrigerantPumpMotorAmp']) ?  $output_values['RefrigerantPumpMotorAmp'] : "";
            $data['Vacuum Pump'] = isset($output_values['PurgePumpMotorAmp']) ?  $output_values['PurgePumpMotorAmp'] : "";
        }

        

        $data['MCA'] = isset($output_values['MCA']) ?  $output_values['MCA'] : "";
        $data['MOP'] = isset($output_values['MOP']) ?  $output_values['MOP'] : "";
        $data['Length'] = isset($output_values['Length']) ?  $output_values['Length'] : "";
        $data['Width'] = isset($output_values['Width']) ?  $output_values['Width'] : "";
        $data['Height'] = isset($output_values['Height']) ?  $output_values['Height'] : "";
        $data['Dry Wt'] = isset($output_values['DryWeight']) ?  $output_values['DryWeight'] : "";
        $data['Oper WT'] = isset($output_values['OperatingWeight']) ?  $output_values['OperatingWeight'] : "";
        $data['Shipp Wt'] = isset($output_values['MaxShippingWeight']) ?  $output_values['MaxShippingWeight'] : "";
        $data['Flooded Wt'] = isset($output_values['FloodedWeight']) ?  $output_values['FloodedWeight'] : "";
        $data['Tube cleaning space'] = isset($output_values['ClearanceForTubeRemoval']) ?  $output_values['ClearanceForTubeRemoval'] : "";
        $data['Heat Input(kW)'] = isset($output_values['QINPUT']) ?  $output_values['QINPUT'] : "";


        if($calculator_code == 'D_S2'){
            $data['Steam Consumption'] = isset($output_values['GSTEAM']) ?  $output_values['GSTEAM'] : "";
            $data['Steam Pressure'] = isset($output_values['PST1']) ?  $output_values['PST1'] : "";
        }

        if($calculator_code == 'D_H2'){
            $data['HotWaterFlow'] = isset($output_values['HotWaterFlow']) ?  $output_values['HotWaterFlow'] : "";
            $data['hot_water_in'] = isset($output_values['hot_water_in']) ?  $output_values['hot_water_in'] : "";
            $data['hot_water_out'] = isset($output_values['hot_water_out']) ?  $output_values['hot_water_out'] : "";
            $data['GeneratorPasses'] = isset($output_values['GeneratorPasses']) ?  $output_values['GeneratorPasses'] : "";
            $data['HotWaterFrictionLoss'] = isset($output_values['HotWaterFrictionLoss']) ?  $output_values['HotWaterFrictionLoss'] : "";
            $data['HotWaterConnectionDiameter'] = isset($output_values['HotWaterConnectionDiameter']) ?  $output_values['HotWaterConnectionDiameter'] : "";
            $data['all_work_pr_hw'] = isset($output_values['all_work_pr_hw']) ?  $output_values['all_work_pr_hw'] : "";
        }
        

        return $data;

    }

    

    
}
