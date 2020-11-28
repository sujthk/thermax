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


        $data = array('capacity',
        				'model_name',
        				'model_number',
        				'chilled_water_in',
        				'cooling_water_in',
        				'chilled_water_out',
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
        				'steam_pressure',
        				'region_type'
        			);
        
        return Excel::create('chiller_calculation', function($excel) use ($data) {
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
        Log::info($calculator_input);
        if(empty($code) || empty($calculator_input)){
            return response()->json(['status'=>false,'msg'=>'Input Error']);
        }

        $auto_testing = new AutoTesting;
        $auto_testing->user_id = Auth::user()->id;
        $auto_testing->input_values = json_encode($request->calculator_input);
        $auto_testing->save();

        $result = $this->testingS2($calculator_input);
        $auto_testing->output_values = json_encode($result);
        $auto_testing->save();
        Log::info($result);
        return response()->json(['status'=>true,'msg'=>'Calculated Values','result'=>$result]);

    }

    public function downloadTestedCalculator(Request $request){
        
        $tested_results = AutoTesting::where('user_id',Auth::user()->id)->get();


        $data = array();
        $failure_data = array();
        foreach ($tested_results as $tested_result) {
            $input_values = json_decode($tested_result->input_values,true);
            $output_values = json_decode($tested_result->output_values,true);
            if(!empty($tested_result->output_values)){
                $array_merge = array_merge($input_values,$output_values);
                $data[] = $array_merge;
            }
            else{
                $failure_data[] = $input_values;
            }
                
        }


        return Excel::create('chiller_calculation', function($excel) use ($data,$failure_data) {
            $excel->sheet('mySheet', function($sheet) use ($data)
            {
                $sheet->fromArray($data);
            });
            $excel->sheet('mySheet', function($sheet) use ($data)
            {
                $sheet->fromArray($data);
            });
        })->download('xlsx');

    }

    public function testingS2($data){
        $s2 = new DoubleSteamController();

        $result = $s2->testingS2Calculation($data);
       // $result = json_decode($result,true);
        $data = [];

        $data['model_name'] = isset($result['model_name']) ?  $result['model_name'] : "";
        $data['MODEL'] = isset($result['MODEL']) ?  $result['MODEL'] : "";
        $data['Result'] = isset($result['Result']) ?  $result['Result'] : "";
        $data['msg'] = isset($result['msg']) ?  $result['msg'] : "";
        $data['Notes'] = !empty($result['Notes']) ?  $result['Notes'] : (isset($result['notes']) ?  is_array($result['notes']) ? implode(',',$result['notes']) : $result['notes'] : "");

        if(empty($data['Notes'])){
            $data['Notes'] = $data['msg'];
        }



        return $data;
    }

    
}
