<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Region;
use Log;
class RegionController extends Controller
{
    public function getRegion(){

        $regions = Region::orderBy('created_at', 'desc')->get();

        return view('region_list')->with('regions',$regions);
    }
    public function postRegion(Request $request){

        $this->validate($request, [
            'name' => 'required|unique:regions,name'
        ]);

        $regions = new Region;
        $regions->name = $request->name;
        $regions->save();

        return redirect('region')->with('message','Region Added')
                        ->with('status','success');
    }
    public function editRegion(Request $request,$id){

        $this->validate($request, [
            'name' =>  'required|unique:regions,name,'.$id,
        ]);

        $regions = Region::find($id);
        $regions->name = $request->name;
        $regions->save();

        return redirect('region')->with('message','Region Updated')
                        ->with('status','success');

    }
}
