<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ChillerDefaultValue;
use App\ChillerOption;
use App\ChillerMetallurgyOption;
use App\Metallurgy;
use Log;
class DefaultCalculatorController extends Controller
{
    public function getCalculators(){

    	$default_calculators = ChillerDefaultValue::select('id','name','model')->get();


    	return view('default_calculators')->with('default_calculators',$default_calculators);
    }

    public function editCalculator($chiller_default_id){
    	$default_calculator = ChillerDefaultValue::find($chiller_default_id);

    	// return $default_calculator;

    	$default_values = json_decode($default_calculator->default_values,true);
    	$default_value_keys = array_keys($default_values);
    	// return $default_values;

    	return view('default_calculator_edit')
    						->with('default_value_keys',$default_value_keys)
    						->with('default_calculator',$default_calculator)
    						->with('default_values',$default_values);
    }

    public function updateCalculator(Request $request,$chiller_default_id){
    	// return $request->all();


    	$this->validate($request, [
		    'default_values' => 'required',
		    'name' => 'required',
		    'model' => 'required'
		]);


    	$chiller_default_value = ChillerDefaultValue::find($chiller_default_id);
        $chiller_default_value->name = $request->name;
        $chiller_default_value->model = $request->model;
        $chiller_default_value->default_values = json_encode($request->default_values);
        $chiller_default_value->save();

		return redirect('default/calculators')->with('message','Chiller Default Value Updated')
                        ->with('status','success');

    }

    public function getMetallurgyCalculators(){

    	$metallurgy_calculators = ChillerMetallurgyOption::select('id','name','model')->get();


    	return view('metallurgy_calculators')->with('metallurgy_calculators',$metallurgy_calculators);
    }

    public function editMetallurgyCalculator($chiller_metallurgy_id,$tube_type){
    	$metallurgy_calculator = ChillerMetallurgyOption::find($chiller_metallurgy_id);

        $metallurgies = Metallurgy::all();
    	$metallurgy_values = ChillerOption::where('chiller_metallurgy_option_id',$chiller_metallurgy_id)->where('type',$tube_type)->get();

        // return $metallurgy_values;
    	return view('metallurgy_calculator_edit')
    						->with('metallurgy_calculator',$metallurgy_calculator)
                            ->with('metallurgy_values',$metallurgy_values)
    						->with('metallurgies',$metallurgies)
                            ->with('tube_type',$tube_type);
    }

    public function addMetallurgyCalculator(){

        return view('metallurgy_calculator_add');
    }

    public function postMetallurgyCalculator(Request $request){
        $this->validate($request, [
            'name' => 'required',
            'code' => 'required',
            'model' => 'required|numeric',

        ]);

        $chiller_metallurgy_option = new ChillerMetallurgyOption;
        $chiller_metallurgy_option->name = $request->name;
        $chiller_metallurgy_option->code = $request->code;
        $chiller_metallurgy_option->model = $request->model;
        $chiller_metallurgy_option->save();
        

        return redirect('tube-metallurgy/calculators')->with('message','Metallurgy Calculator Added')
                        ->with('status','success');
    }

    public function updateMetallurgyCalculator(Request $request,$chiller_metallurgy_id,$tube_type){
        // return $request->all();
        $this->validate($request, [
            'metallurgy' => 'required'
        ]);

        $deletedRows = ChillerOption::where('chiller_metallurgy_option_id', $chiller_metallurgy_id)->where('type',$tube_type)->delete();

        if ($request->has('metallurgy')){

            foreach ($request->metallurgy as $metallurgy) {

                $metallurgy_option = new ChillerOption;
                $metallurgy_option->chiller_metallurgy_option_id = $chiller_metallurgy_id;
                $metallurgy_option->metallurgy_id = $metallurgy['metallurgy_id'];
                $metallurgy_option->value = $metallurgy['value'];
                $metallurgy_option->type = $tube_type;
                $metallurgy_option->save();

            }
        }

        
        
        return redirect('tube-metallurgy/calculators')->with('message','Metallurgy Options Updated')
                        ->with('status','success');
    }

}
