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
            'url_link' => 'required',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
		]);

        $image_name = 'banner-'.date("Ymdgis").'.'.$request->image->extension();  
        $request->image->move(public_path('banner-images'), $image_name);
        

		$time_line = new TimeLine;
		$time_line->name = $request->name;
        $time_line->image=$image_name;
        $time_line->url_link=$request->url_link;
		$time_line->description = $request->description;
		$time_line->save();

    	return redirect('/time-line')->with('status','success')->with('message','Time Line Insert SuccessFully');
    }
    public function editTimeLine(Request $request,$id){
    	$this->validate($request, [
		    'name' => 'required',
            'description' => 'required',
            'url_link' => 'required',
		]);

        if($request->hasFile('image')){
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            $image_name = 'banner-'.date("Ymdgis").'.'.$request->image->extension();     
            $request->image->move(public_path('banner-images'), $image_name);
            
        }

		$time_line =TimeLine::find($id);
		$time_line->name = $request->name;
        if($request->hasFile('image')){
            $time_line->image=$image_name;
        }
        $time_line->url_link=$request->url_link;
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
