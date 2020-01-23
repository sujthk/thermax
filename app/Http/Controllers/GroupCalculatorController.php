<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\GroupCalculator;
use App\Calculator;
use Log;
class GroupCalculatorController extends Controller
{
    public function getGroupCalcluation(){

       $group_calculations = GroupCalculator::orderBy('created_at', 'desc')->get();

       return view('group_calculation_list')->with('group_calculations',$group_calculations);
    }
    public function addGroupCalcluation(){
    	$calculators = Calculator::get();
    	return view('group_calculation_add')->with('calculators',$calculators);
    }
    public function postGroupCalcluation(Request $request){
        $this->validate($request, [
            'name' => 'required|unique:group_calculators,name'
        ]);

        $group_calculation = new GroupCalculator;
        $group_calculation->name = $request->name;
        $group_calculation->save();

        $group_calculation->calculators()->sync($request->calculators);


        return redirect('group-calcluation')->with('message','Group Calculator Added')->with('status','success');
    }
    public function editGroupCalcluation($id)
	{
		
		$group_calculation = GroupCalculator::find($id);
		$selected_calculators = $group_calculation->calculators->pluck('id')->toArray();
		$calculators = Calculator::get();
		return view('group_calculation_edit')->with(['group_calculation'=>$group_calculation,'selected_calculators'=>$selected_calculators,'calculators'=>$calculators]);
	}
    public function GroupCalcluationUpdate(Request $request,$id){

        $this->validate($request, [
            'name' =>  'required|unique:group_calculators,name,'.$id,
        ]);

        $group_calculation = GroupCalculator::find($id);
        $group_calculation->name = $request->name;
        $group_calculation->save();
         $group_calculation->calculators()->sync($request->calculators);

        return redirect('group-calcluation')->with('message','Group Calculator Updated')
                        ->with('status','success');

    }
}
