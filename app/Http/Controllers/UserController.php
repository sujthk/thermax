<?php

namespace App\Http\Controllers;
use App\Http\Controllers\VamBaseController;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use App\Mail\SendUserOtp;
use App\Mail\SendUserPassword;
use Illuminate\Http\Request;
use App\ChillerMetallurgyOption;
use App\User;
use App\UserTracking;
use App\GroupCalculator;
use App\UnitSet;
use App\UserReport;
use App\Region;
use App\TimeLine;
use App\Language;
use App\UserCalculator;
use Validator;	
use Hash;
use Mail;
use DB;
use Log;
use Exception;
use PDF;
use Adldap\AdldapInterface;

class UserController extends Controller
{
    
    public function ldapUsers(AdldapInterface $ldap)
    {

        return $ldap->search()->users()->get();
        
    }

    public function getUsers(){

    	$users = User::all();

    	return view('users')->with('users',$users);
    }

    public function addUser(){

        
        $unit_sets = UnitSet::select('name','id')->where('user_type','ADMIN')->where('status',1)->get();
        $regions = Region::orderBy('created_at', 'desc')->get();
        $group_calculators = GroupCalculator::get();
        $languages = Language::where('status',1)->get();

    	return view('user_add')->with('unit_sets',$unit_sets)->with('regions',$regions)->with('languages',$languages)->with('group_calculators',$group_calculators);
    }

    public function postUser(Request $request){

        //return $request->all();
		$this->validate($request, [
		    'name' => 'required',
            'username' => 'required|unique:users,username',
		    'password' => 'required',
            'user_type' => 'required',
		    'unit_set_id' => 'required',
            'region_type' => 'required',
            'language_id' => 'required',
            'region_id' => 'required',
		]);

        if($request->user_type =='THERMAX_USER' || $request->user_type == 'NON_THERMAX_USER')
        {   
            if(empty($request->group_calculator_id))
                return Redirect::back()->with('status','error')->with('message', 'Please select group Calculoter');

            if(empty($request->calculators))
                return Redirect::back()->with('status','error')->with('message', 'Please select  Calculoter');

        }

		$hashed_password = Hash::make($request->password);

		$user = new User;
		$user->name = $request->name;
		$user->username = $request->username;
        $user->mobile = $request->mobile;
		$user->password = $hashed_password;
        $user->user_type = $request->user_type;
		$user->unit_set_id = $request->unit_set_id;
        $user->region_type = $request->region_type;
        $user->region_id = $request->region_id;
        $user->group_calculator_id = $request->group_calculator_id;

        $user->min_chilled_water_out = $request->min_chilled_water_out;
        $user->unitset_status = $request->unitset_type;
        $user->language_id = $request->language_id;
		$user->status = 1;
		$user->save();

        if($request->user_type =='THERMAX_USER' || $request->user_type == 'NON_THERMAX_USER')
        {
            $user->calculators()->sync($request->calculators);
        }

		return redirect('users')->with('message','User Added')
                        ->with('status','success');
    }

    public function editUser($user_id){
    	$user = User::find($user_id);

        $selected_calculators = $user->calculators->pluck('id')->toArray();
        $group_calculators = GroupCalculator::get();
        $unit_sets = UnitSet::select('name','id')->where('user_type','ADMIN')->where('status',1)->get();
        $regions = Region::orderBy('created_at', 'desc')->get();
        $languages = Language::where('status',1)->get();

    	return view('user_edit')->with('user',$user)->with('unit_sets',$unit_sets)->with('regions',$regions)->with('group_calculators',$group_calculators)->with('selected_calculators',$selected_calculators)->with('languages',$languages);
    }

    public function updateUser(Request $request,$user_id){
    	$this->validate($request, [
		    'user_type' => 'required',
		    'name' => 'required',
		    'username' => 'required|unique:users,username,'.$user_id,
            'unit_set_id' => 'required',
            'region_type' => 'required',
            'language_id' => 'required',
            'region_id' => 'required',
		]);

        //return $request->all();
        // Log::info($request->all());
        if($request->user_type =='THERMAX_USER' || $request->user_type == 'NON_THERMAX_USER')
        {   
            if(empty($request->group_calculator_id))
                return Redirect::back()->with('status','error')->with('message', 'Please select group Calculater');

            if(empty($request->calculators))
                return Redirect::back()->with('status','error')->with('message', 'Please select  Calculater');
        }

    	$user = User::find($user_id);
    	$user->name = $request->name;
		$user->username = $request->username;
        $user->mobile = $request->mobile;
		$user->user_type = $request->user_type;
        $user->unit_set_id = $request->unit_set_id;
        $user->region_type = $request->region_type;
        $user->region_id = $request->region_id;
        $user->min_chilled_water_out = $request->min_chilled_water_out;
        $user->unitset_status = $request->unitset_type;
        $user->language_id = $request->language_id;

		if ($request->has('password') && !empty($request->password)) {
		    $hashed_password = Hash::make($request->password);
		    $user->password = $hashed_password;
		}
        
        if($request->user_type =='THERMAX_USER' || $request->user_type == 'NON_THERMAX_USER')
        {
            $user->group_calculator_id = $request->group_calculator_id;
            $user->calculators()->sync($request->calculators);
        }
        else
        {
            $user->group_calculator_id =NULL;
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

    	$username = $request->username;
    	$password = $request->password;

    	$user = DB::table('users')->where("username",$username)->first();
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
		    'username' => 'required',
		    'password' => 'required',
		]);

		$user = DB::table('users')->where("username",$request->username)->first();
    	if(!$user)
    		return response()->json(['status'=>false,'msg'=>'User Not Found']);

    	if (!Hash::check($request->password, $user->password)) {
    	    return response()->json(['status'=>false,'msg'=>'Invalid Password']);
    	}

    	if(!$user->status)
    		return response()->json(['status'=>false,'msg'=>'Account Deactivated Contact Admin']);

    	// if($request->otp != $user->otp)
    	// 	return response()->json(['status'=>false,'msg'=>'Invalid Otp']);


		if (Auth::attempt(['username' => $request->username, 'password' => $request->password, 'status' => 1])) {
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
    public function updateUserProfile(Request $request,$user_id){
        $this->validate($request, [
            'mobile' => 'required',
            'name' => 'required',
            'unit_set_id' => 'required',
            'language_id' => 'required',
        ]);

        // Log::info($request->all());

        $user = User::find($user_id);
        $user->name = $request->name;
        $user->mobile = $request->mobile;
        $user->unit_set_id = $request->unit_set_id;
        $user->language_id = $request->language_id;

        if($request->hasFile('image')){
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            $image_name = 'user-'.date("Ymdgis").'.'.$request->image->extension();  
               
            $request->image->move(public_path('user-images'), $image_name);

            $user->image=$image_name;
        }

        $user->save();

        return redirect('profile')->with('message','Profile Updated')
                        ->with('status','success');

    }
    public function getGroupCalcluation(Request $request){

        $group_calculator_id = $request->input('group_calculator_id');
        $user_id = $request->input('user_id');

        $group_calculator = GroupCalculator::find($group_calculator_id);
        //log::info($group_calculator->groupCalculatorDetails);

        $user_calculator_ids = UserCalculator::where('user_id',$user_id)->pluck('calculator_id')->toArray();

        if($group_calculator)
        {      
            $content ='';
            foreach ($group_calculator->groupCalculatorDetails as $groupCalculatorDetail ){
                if(in_array($groupCalculatorDetail->calculator_id, $user_calculator_ids)){
                    $content .='<input type="checkbox"  name="calculators[]" value="'.$groupCalculatorDetail->calculator_id.'" checked="checked">&nbsp;&nbsp;'. ucwords($groupCalculatorDetail->calculator->name).'<br>';
                }
                else{
                    $content .='<input type="checkbox"  name="calculators[]" value="'.$groupCalculatorDetail->calculator_id.'" >&nbsp;&nbsp;'. ucwords($groupCalculatorDetail->calculator->name).'<br>';
                }
              
            }
             return response()->json(['status'=>true,'content'=>$content]);
        }
        else
            return response()->json(['status'=>false,'msg'=>"Empty value"]);
    }

    public function getuserlist($user_id)
    {
        $user =User::where('id',$user_id)->first();
        $user_trackings = UserTracking::where('user_id',$user_id)->orderBy('created_at','desc')->get();
        $user_reports = UserReport::where('user_id',$user_id)->orderBy('created_at','desc')->get();
        $unit_sets = UnitSet::where('user_id',$user->id)->get();

        return view('user_profile_view')->with('user',$user)->with('user_trackings',$user_trackings)->with('user_reports',$user_reports)->with('unit_sets',$unit_sets);
    }

    public function getUserReport($user_report_id){

        $user_report = UserReport::find($user_report_id);
      

        $calculation_values = json_decode($user_report->calculation_values,true);
        // Log::info($calculation_values);
       
        $name = $user_report->name;
        $project = $user_report->project;
        $phone = $user_report->phone;

        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$user_report->calculator_code)
                                        ->where('min_model','<=',$calculation_values['MODEL'])->where('max_model','>',$calculation_values['MODEL'])->first();
                                
        $chiller_options = $chiller_metallurgy_options->chillerOptions;
        
        $evaporator_option = $chiller_options->where('type', 'eva')->where('value',$calculation_values['TU2'])->first();
        $absorber_option = $chiller_options->where('type', 'abs')->where('value',$calculation_values['TU5'])->first();
        $condenser_option = $chiller_options->where('type', 'con')->where('value',$calculation_values['TV5'])->first();

        $evaporator_name = $evaporator_option->metallurgy->display_name;
        $absorber_name = $absorber_option->metallurgy->display_name;
        $condenser_name = $condenser_option->metallurgy->display_name;

        $unit_set = UnitSet::find($user_report->unit_set_id);


        $language = $user_report->language;
        $vam_base = new VamBaseController();
        $language_datas = $vam_base->getLanguageDatas($language);
        $units_data = $vam_base->getUnitsData();
        

        if($user_report->calculator_code == 'D_S2')
        {
        
            $pdf = PDF::loadView('reports.report_s2_pdf', ['name' => $name,'phone' => $phone,'project' => $project,'calculation_values' => $calculation_values,'evaporator_name' => $evaporator_name,'absorber_name' => $absorber_name,'condenser_name' => $condenser_name,'unit_set' => $unit_set,'units_data' => $units_data,'language_datas' => $language_datas,'language' => $language]);
            return $pdf->download('Steam-Fired-series.pdf');
        }
        elseif ($user_report->calculator_code == 'D_H2') {
               $pdf = PDF::loadView('report.report_s2_pdf', ['name' => $name,'phone' => $phone,'project' => $project,'calculation_values' => $calculation_values,'evaporator_name' => $evaporator_name,'absorber_name' => $absorber_name,'condenser_name' => $condenser_name,'unit_set' => $unit_set,'units_data' => $units_data,'language_datas' => $language_datas,'language' => $language]);
            return $pdf->download('Hot-Water-Fired-series.pdf');
        }
        elseif ($user_report->calculator_code == 'D_G2') {

            $pdf = PDF::loadView('reports.report_pdf', ['name' => $name,'phone' => $phone,'project' => $project,'calculation_values' => $calculation_values,'evaporator_name' => $evaporator_name,'absorber_name' => $absorber_name,'condenser_name' => $condenser_name,'unit_set' => $unit_set,'units_data' => $units_data,'language_datas' => $language_datas,'language' => $language]);
            return $pdf->download('Direct-Fired-series.pdf');
         }
         elseif ($user_report->calculator_code == 'L5') {

            $pdf = PDF::loadView('reports.report_l5_pdf', ['name' => $name,'phone' => $phone,'project' => $project,'calculation_values' => $calculation_values,'evaporator_name' => $evaporator_name,'absorber_name' => $absorber_name,'condenser_name' => $condenser_name,'unit_set' => $unit_set,'units_data' => $units_data,'language_datas' => $language_datas,'language' => $language]);
            return $pdf->download('l5-series.pdf');
         }
        

    }
 
    public function getDashboard()
    {
        $time_lines = TimeLine::all();
        return view('dashboard')->with('time_lines',$time_lines);
    }

    public function getProfile()
    {
        $user_id=Auth::user()->id;
        $user =User::where('id',$user_id)->first();

        $languages = Language::where('status',1)->get();

        if($user->user_type=='ADMIN')
        {
            $unit_sets = UnitSet::select('name','id')->where('status',1)->where('user_type','ADMIN')->get();
        }
        else
        {
            $unit_sets = UnitSet::select('name','id')->where('status',1)->where(function ($query) use ($user) {
                                        $query->where('user_type','ADMIN');
            
                                    })->orWhere(function ($query) use ($user){
                                        $query->where('user_id',$user->id);
                                    })->get();
        }
        //return $unit_sets;

        return view('user_profile')->with('user',$user)->with('unit_sets',$unit_sets)->with('languages',$languages);
    }
    public function postPasswordChange(Request $request)
    {
        //dd($request->input());
        $this->validate($request, [
            'old_password' => 'required',
        ]); 

        $user_id=Auth::user()->id;
        $user =User::where('id',$user_id)->first();

        if (!Hash::check($request->old_password, $user->password))
            {
                $errors = ['message' => 'Your Old Password Not Matching.'];
                return redirect()->back()->with('message','Your Old Password Not Matching.')
                        ->with('status','error');
            }
            else
            {
                //return $request->password;
                $password = Hash::make($request->password);
                $user->password =  $password;
                $user->save();
                return Redirect::back()->with('message','Updated')
                ->with('status','success');
            }
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
    public function forgotPassword(Request $request){
        $this->validate($request,[
            'email' => 'required',
        ]);

        $user = User::where('email',$request->email)->first();
        if(!$user)
        {
            $errors = ['email' => 'Sorry, the Provided Email doesn\'t match.'];
            return redirect()->back()->withInput()->withErrors($errors); 
        }
            
           
        $email_token = str_random(30);
        $name = $user->name; 
        // Mail::to($user->email)->send(new SendUserPassword($name,$email_token)); 
        $user->password = bcrypt("admin123");
        $user->remember_token = $email_token;
        $user->save();
        $errors = ['email' => 'Password Reset Link Sent Email Successfully'];
        return redirect('/login')->with('status','success')->withErrors($errors);
    }
    public function verifyCustomerToken($token){

        $user = User::where('remember_token',$token)->first();
        if($user){
            $user->remember_token = NULL;
            $user->save();

            return view('password_reset_success', ['name' => $user->name,'customer_id' => $user->id]);
        }

        return view('errors.verification_error');
    }
    public function resetAdminPassword(Request $request){
        $this->validate($request, [
           'password' => 'required|confirmed',
           'customer_id' => 'required',

       ]);

        $admin = User::find($request->customer_id);
        $admin->password = bcrypt($request->password);
        $admin->save();

        $errors = ['msg' => 'Password Updated Successfully'];
        return redirect('/login')->with('status','success')->withErrors($errors);

    }

    public function getword(){

        $phpWord = new \PhpOffice\PhpWord\PhpWord();

        $section = $phpWord->addSection();

        $description = "Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod
        tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,
        quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo
        consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse
        cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non
        proident, sunt in culpa qui officia deserunt mollit anim id est laborum.";


        $section->addImage("http://itsolutionstuff.com/frontTheme/images/logo.png");
        $section->addText($description);


        $table_style = new \PhpOffice\PhpWord\Style\Table;
        $table_style->setBorderColor('cccccc');
        $table_style->setBorderSize(1);
        $table_style->setUnit(\PhpOffice\PhpWord\Style\Table::WIDTH_PERCENT);
        $table_style->setWidth(100 * 50);

        $header = array('size' => 16, 'bold' => true);
        $table = $section->addTable($table_style);
        $table->addRow();
        $table->addCell(1750)->addText(htmlspecialchars("Client"),$header);
        $table->addCell(1750)->addText(htmlspecialchars("Name"),$header);
        $table->addCell(1750)->addText(htmlspecialchars("Version"),$header);
        $table->addCell(1750)->addText(htmlspecialchars("123"),$header);

        $table->addRow();
        $table->addCell(1750)->addText(htmlspecialchars("Enquiry"),$header);
        $table->addCell(1750)->addText(htmlspecialchars("12345"),$header);
        $table->addCell(1750)->addText(htmlspecialchars("Date"),$header);
        $table->addCell(1750)->addText(htmlspecialchars("123"),$header);


        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        try {
            $objWriter->save(storage_path('helloWorld.docx'));
        } catch (Exception $e) {
        }

        return response()->download(storage_path('helloWorld.docx'));
    }

}
