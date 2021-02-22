<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Metallurgy;
use App\ChillerOption;
class MetallurgyController extends Controller
{
    public function getMetallurgies(){

    	$metallurgies = Metallurgy::all();


    	return view('metallurgies')->with('metallurgies',$metallurgies);
    }

    public function addMetallurgy(){

    	return view('metallurgy_add');
    }

    public function postMetallurgy(Request $request){
		$this->validate($request, [
            'name' => 'required',
		    'display_name' => 'required',
            'default_thickness' => 'required|numeric',
            'min_thickness' => 'required|numeric',
            'max_thickness' => 'required|numeric',
            'eva_min_velocity' => 'required|numeric',
            'eva_max_velocity' => 'required|numeric',
            'con_min_velocity' => 'required|numeric',
            'con_max_velocity' => 'required|numeric',
            'abs_min_velocity' => 'required|numeric',
            'abs_max_velocity' => 'required|numeric',
            'ode' => 'required|numeric',
		]);



		$metallurgy = new Metallurgy;
        $metallurgy->name = $request->name;
		$metallurgy->display_name = $request->display_name;
		$metallurgy->default_thickness = $request->default_thickness;
		$metallurgy->min_thickness = $request->min_thickness;
		$metallurgy->max_thickness = $request->max_thickness;
		$metallurgy->eva_min_velocity = $request->eva_min_velocity;
		$metallurgy->eva_max_velocity = $request->eva_max_velocity;
        $metallurgy->abs_min_velocity = $request->abs_min_velocity;
        $metallurgy->abs_max_velocity = $request->abs_max_velocity;
        $metallurgy->con_min_velocity = $request->con_min_velocity;
        $metallurgy->con_max_velocity = $request->con_max_velocity;
        $metallurgy->ode = $request->ode;
		$metallurgy->save();

		return redirect('metallurgies')->with('message','Metallurgy Added')
                        ->with('status','success');
    }

    public function editMetallurgy($metallurgy_id){
    	$metallurgy = Metallurgy::find($metallurgy_id);

    	return view('metallurgy_edit')->with('metallurgy',$metallurgy);
    }

    public function updateMetallurgy(Request $request,$metallurgy_id){
    	$this->validate($request, [
            'name' => 'required',
		    'display_name' => 'required',
            'default_thickness' => 'required|numeric',
            'min_thickness' => 'required|numeric',
            'max_thickness' => 'required|numeric',
            'eva_min_velocity' => 'required|numeric',
            'eva_max_velocity' => 'required|numeric',
            'con_min_velocity' => 'required|numeric',
            'con_max_velocity' => 'required|numeric',
            'abs_min_velocity' => 'required|numeric',
            'abs_max_velocity' => 'required|numeric',
            'ode' => 'required|numeric',
		]);


    	$metallurgy = Metallurgy::find($metallurgy_id);
		$metallurgy->name = $request->name;
        $metallurgy->display_name = $request->display_name;
		$metallurgy->default_thickness = $request->default_thickness;
		$metallurgy->min_thickness = $request->min_thickness;
		$metallurgy->max_thickness = $request->max_thickness;
		$metallurgy->eva_min_velocity = $request->eva_min_velocity;
        $metallurgy->eva_max_velocity = $request->eva_max_velocity;
        $metallurgy->abs_min_velocity = $request->abs_min_velocity;
        $metallurgy->abs_max_velocity = $request->abs_max_velocity;
        $metallurgy->con_min_velocity = $request->con_min_velocity;
        $metallurgy->con_max_velocity = $request->con_max_velocity;
        $metallurgy->ode = $request->ode;
		$metallurgy->save();

		return redirect('metallurgies')->with('message','Metallurgy Updated')
                        ->with('status','success');
    }

    public function deleteMetallurgy($metallurgy_id){
        $deletedRows = ChillerOption::where('metallurgy_id', $metallurgy_id)->delete();

        $metallurgy = Metallurgy::destroy($metallurgy_id);

        return redirect('metallurgies')->with('message','Metallurgy Deleted')
                        ->with('status','success');
    }
}
