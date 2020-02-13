<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\TimeLine;
use Validator;	
use DB;
use Log;
use Exception;

class TimeLineController extends Controller
{
    public function getTimeLine(){

    	$time_lines = TimeLine::orderBy('created_at', 'desc')->get();
    	return view('time_line')->with('time_lines',$time_lines);
    }

    public function postTimeLine(Request $request){
    	$this->validate($request, [
		    'name' => 'required',
            'description' => 'required',
		]);

		$time_line = new TimeLine;
		$time_line->name = $request->name;
		$time_line->description = $request->description;
		$time_line->save();

    	return redirect('/time-line')->with('status','success')->with('message','Time Line Insert SuccessFully');
    }
    public function editTimeLine(Request $request,$id){
    	$this->validate($request, [
		    'name' => 'required',
            'description' => 'required',
		]);

		$time_line =TimeLine::find($id);
		$time_line->name = $request->name;
		$time_line->description = $request->description;
		$time_line->save();

    	return redirect('/time-line')->with('status','success')->with('message','Time Line Update SuccessFully');
    }
    public function destroy($id){
    	$time_line = TimeLine::find($id);
    	$time_line->delete();
    	return redirect('/time-line')->with('status','success')->with('message','SuccessFully Deleted');
    }
}
