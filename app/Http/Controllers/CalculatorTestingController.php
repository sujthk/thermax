<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use App\CalculationKey;
use Log;
use Excel;


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

        $s2 = new DoubleSteamController();
        $result = $s2->testingS2Calculation($datas[0]);

        return $result;
        
    }
}
