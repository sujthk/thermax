<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ChillerDefaultValue;
use App\ChillerMetallurgyOption;
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
    	
        if($tube_type == 'eva'){
    	   $metallurgy_values = json_decode($metallurgy_calculator->evaporator_options,true);
        }
        elseif ($tube_type == 'abs') {
            $metallurgy_values = json_decode($metallurgy_calculator->absorber_options,true);
        }
        else{
            $metallurgy_values = json_decode($metallurgy_calculator->condenser_options,true);
        }

        // return $metallurgy_values;
    	return view('metallurgy_calculator_edit')
    						->with('metallurgy_calculator',$metallurgy_calculator)
    						->with('metallurgy_values',$metallurgy_values)
                            ->with('tube_type',$tube_type);
    }

    public function updateMetallurgyCalculator(Request $request,$chiller_metallurgy_id,$tube_type){
        // return $request->all();
        $this->validate($request, [
            'labels' => 'required',
            'values' => 'required'
        ]);

        $values = $request->values;

        
        $options = array();
        foreach ($request->labels as $key => $label) {
            $options[] = array('name' => $label,'value' => $values[$key]);
        }

        $chiller_metallurgy_option = ChillerMetallurgyOption::find($chiller_metallurgy_id);
        if($tube_type == 'eva'){
           $chiller_metallurgy_option->evaporator_options = json_encode($options);
        }
        elseif ($tube_type == 'abs') {
            $chiller_metallurgy_option->absorber_options = json_encode($options);
        }
        else{
            $chiller_metallurgy_option->condenser_options = json_encode($options);
        }

        $chiller_metallurgy_option->save();

        return redirect('tube-metallurgy/calculators')->with('message','Metallurgy Options Updated')
                        ->with('status','success');
    }

}
