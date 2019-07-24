<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Mail\SendUserOtp;
use Illuminate\Http\Request;
use App\User;
use App\UserTracking;
use Validator;	
use Hash;
use Mail;
use DB;
class UserController extends Controller
{
    public function getUsers(){

    	$users = User::all();


    	return view('users')->with('users',$users);
    }

    public function addUser(){

    	return view('user_add');
    }

    public function postUser(Request $request){
		$this->validate($request, [
		    'name' => 'required',
            'email' => 'required|unique:users,email',
		    'password' => 'required',
		    'user_type' => 'required',
		]);



		$hashed_password = Hash::make($request->password);

		$user = new User;
		$user->name = $request->name;
		$user->email = $request->email;
		$user->password = $hashed_password;
		$user->user_type = $request->user_type;
		$user->status = 1;
		$user->save();

		return redirect('users')->with('message','User Added')
                        ->with('status','success');
    }

    public function editUser($user_id){
    	$user = User::find($user_id);

    	return view('user_edit')->with('user',$user);
    }

    public function updateUser(Request $request,$user_id){
    	$this->validate($request, [
		    'user_type' => 'required',
		    'name' => 'required',
		    'email' => 'required|unique:users,email,'.$user_id,
		]);


    	$user = User::find($user_id);
    	$user->name = $request->name;
		$user->email = $request->email;
		$user->user_type = $request->user_type;

		if ($request->has('password')) {
		    $hashed_password = Hash::make($request->password);
		    $user->password = $hashed_password;
		}

		$user->save();

		return redirect('users')->with('message','User Updated')
                        ->with('status','success');

    }


    public function changeUserStatus($user_id,$status){

    	$user = User::find($user_id);
    	$user->status = $status;
    	$user->save();

    	return redirect('users')->with('message','User Status Changed')
                        ->with('status','success');
    }

    public function sendUserOtp(Request $request){
    	$email = $request->email;
    	$password = $request->password;

    	$user = DB::table('users')->where("email",$email)->first();
    	if(!$user)
    		return response()->json(['status'=>false,'msg'=>'User Not Found']);

    	if (!Hash::check($password, $user->password)) {
    	    return response()->json(['status'=>false,'msg'=>'Invalid Password']);
    	}

    	if(!$user->status)
    		return response()->json(['status'=>false,'msg'=>'Account Deactivated Contact Admin']);

    	$otp = rand(100000,999999);
        $otp = 12345;

    	$user = User::find($user->id);
    	$user->otp = $otp;
    	$user->save();

    	// Mail::to($user->email)->send(new SendUserOtp($user->name,$otp));

    	return response()->json(['status'=>true,'msg'=>'Mail Send']);

    }

    public function loginUser(Request $request){
		$this->validate($request, [
		    'email' => 'required',
		    'password' => 'required',
		    'otp' => 'required',
		]);

		$user = DB::table('users')->where("email",$request->email)->first();
    	if(!$user)
    		return response()->json(['status'=>false,'msg'=>'User Not Found']);

    	if (!Hash::check($request->password, $user->password)) {
    	    return response()->json(['status'=>false,'msg'=>'Invalid Password']);
    	}

    	if(!$user->status)
    		return response()->json(['status'=>false,'msg'=>'Account Deactivated Contact Admin']);

    	if($request->otp != $user->otp)
    		return response()->json(['status'=>false,'msg'=>'Invalid Otp']);


		if (Auth::attempt(['email' => $request->email, 'password' => $request->password, 'otp' => $request->otp, 'status' => 1])) {
            // Authentication passed...

			$user = Auth::user();

			$user->otp = NULL;
			$user->last_logged_in = date("Y-m-d H:i:s");
			$user->save();

			$user_tacking = new UserTracking;
			$user_tacking->user_id = $user->id;
			$user_tacking->ip_address = $request->ip();
			$user_tacking->logged_in = date("Y-m-d H:i:s");
			$user_tacking->save();


            return response()->json(['status'=>true,'msg'=>'Login Success']);
        }


		return response()->json(['status'=>false,'msg'=>'Invalid Credentials']);
    }

    public function logoutUser(){
    	$user = Auth::user();

    	$user_tacking = UserTracking::where('user_id',$user->id)->latest()->first();
    	if($user_tacking){
    		$user_tacking->logged_out = date("Y-m-d H:i:s");
    		$user_tacking->save();
    	}

    	Auth::logout();
    	return redirect('/');
    }

}
