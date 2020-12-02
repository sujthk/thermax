<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App\User;

class DeleteController extends Controller
{
    public function deleteUser($user_id){

        $user = User::find($user_id);

        if($user && $user->user_type != 'ADMIN'){
            $user->unit_set_id = NULL;
            $user->save();
            DB::table('auto_testing')->where('user_id',$user_id)->delete();
            DB::table('user_reports')->where('user_id',$user_id)->delete();
            DB::table('unit_sets')->where('user_id',$user_id)->delete();
            DB::table('user_calculators')->where('user_id',$user_id)->delete();
            DB::table('user_trackings')->where('user_id',$user_id)->delete();

            $user->delete();

            return "User Deleted Success";
        }

        return "Invalid User or Id belongs to admin";    
    }
}
