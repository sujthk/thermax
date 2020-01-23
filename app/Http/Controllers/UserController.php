<?php

namespace App\Http\Controllers;
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
use Validator;	
use Hash;
use Mail;
use DB;
use Log;
use Exception;
use PDF;
class UserController extends Controller
{
    public function getUsers(){

    	$users = User::all();

    	return view('users')->with('users',$users);
    }

    public function addUser(){

        
        $unit_sets = UnitSet::select('name','id')->where('user_type','ADMIN')->get();
        $regions = Region::orderBy('created_at', 'desc')->get();
        $group_calculators = GroupCalculator::get();

    	return view('user_add')->with('unit_sets',$unit_sets)->with('regions',$regions)->with('group_calculators',$group_calculators);
    }

    public function postUser(Request $request){

        //return $request->all();
		$this->validate($request, [
		    'name' => 'required',
            'email' => 'required|unique:users,email',
		    'password' => 'required',
            'user_type' => 'required',
		    'unit_set_id' => 'required',
            'region_type' => 'required',
            'mobile' => 'required',
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
		$user->email = $request->email;
        $user->mobile = $request->mobile;
		$user->password = $hashed_password;
        $user->user_type = $request->user_type;
		$user->unit_set_id = $request->unit_set_id;
        $user->region_type = $request->region_type;
        $user->region_id = $request->region_id;
        $user->group_calculator_id = $request->group_calculator_id;

        $user->min_chilled_water_out = $request->min_chilled_water_out;
        $user->unitset_status = $request->unitset_type;
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
        $unit_sets = UnitSet::select('name','id')->where('user_type','ADMIN')->get();
        $regions = Region::orderBy('created_at', 'desc')->get();
    	return view('user_edit')->with('user',$user)->with('unit_sets',$unit_sets)->with('regions',$regions)->with('group_calculators',$group_calculators)->with('selected_calculators',$selected_calculators);
    }

    public function updateUser(Request $request,$user_id){
    	$this->validate($request, [
		    'user_type' => 'required',
		    'name' => 'required',
		    'email' => 'required|unique:users,email,'.$user_id,
            'unit_set_id' => 'required',
            'region_type' => 'required',
            'mobile' => 'required',
		]);

        //return $request->all();
        // Log::info($request->all());
        if($request->user_type =='THERMAX_USER' || $request->user_type == 'NON_THERMAX_USER')
        {   
            if(empty($request->group_calculator_id))
                return Redirect::back()->with('status','error')->with('message', 'Please select group Calculoter');

            if(empty($request->calculators))
                return Redirect::back()->with('status','error')->with('message', 'Please select  Calculoter');
        }

    	$user = User::find($user_id);
    	$user->name = $request->name;
		$user->email = $request->email;
        $user->mobile = $request->mobile;
		$user->user_type = $request->user_type;
        $user->unit_set_id = $request->unit_set_id;
        $user->region_type = $request->region_type;
        $user->region_id = $request->region_id;
        $user->min_chilled_water_out = $request->min_chilled_water_out;
        $user->unitset_status = $request->unitset_type;

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
        //$otp = 12345;

    	$user = User::find($user->id);
    	$user->otp = $otp;
    	$user->save();

    	Mail::to($user->email)->send(new SendUserOtp($user->name,$otp));

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
    public function updateUserProfile(Request $request,$user_id){
        $this->validate($request, [
            'mobile' => 'required',
            'name' => 'required',
            'email' => 'required|unique:users,email,'.$user_id,
            'unit_set_id' => 'required',
        ]);

        // Log::info($request->all());

        $user = User::find($user_id);
        $user->name = $request->name;
        $user->email = $request->email;
        $user->mobile = $request->mobile;
        $user->unit_set_id = $request->unit_set_id;
        $user->save();

        return redirect('profile')->with('message','Profile Updated')
                        ->with('status','success');

    }
    public function getGroupCalcluation(Request $request){

        $group_calculator_id = $request->input('group_calculator_id');

        $group_calculator = GroupCalculator::find($group_calculator_id);
        //log::info($group_calculator->groupCalculatorDetails);

        if($group_calculator)
        {      
            $content ='';
            foreach ($group_calculator->groupCalculatorDetails as $groupCalculatorDetail ){
              $content .='<input type="checkbox"  name="calculators[]" value="'.$groupCalculatorDetail->calculator_id.'" checked="">&nbsp;&nbsp;'. ucwords($groupCalculatorDetail->calculator->name).'<br>';
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
                                        ->where('min_model','<',$calculation_values['MODEL'])->where('max_model','>',$calculation_values['MODEL'])->first();

        $chiller_options = $chiller_metallurgy_options->chillerOptions;
        
        $evaporator_option = $chiller_options->where('type', 'eva')->where('value',$calculation_values['TU2'])->first();
        $absorber_option = $chiller_options->where('type', 'abs')->where('value',$calculation_values['TU5'])->first();
        $condenser_option = $chiller_options->where('type', 'con')->where('value',$calculation_values['TV5'])->first();

        $evaporator_name = $evaporator_option->metallurgy->display_name;
        $absorber_name = $absorber_option->metallurgy->display_name;
        $condenser_name = $condenser_option->metallurgy->display_name;

        $unit_set = UnitSet::find($user_report->unit_set_id);

        $units_data = $this->getUnitsData();

        if($user_report->calculator_code == 'D_S2')
        {
            $pdf = PDF::loadView('reports.report_pdf', ['name' => $name,'phone' => $phone,'project' => $project,'calculation_values' => $calculation_values,'evaporator_name' => $evaporator_name,'absorber_name' => $absorber_name,'condenser_name' => $condenser_name,'unit_set' => $unit_set,'units_data' => $units_data]);
            return $pdf->download('Steam-Fired-series.pdf');
        }
        elseif ($user_report->calculator_code == 'D_H2') {
           $pdf = PDF::loadView('reports.report_pdf_h2', ['name' => $name,'phone' => $phone,'project' => $project,'calculation_values' => $calculation_values,'evaporator_name' => $evaporator_name,'absorber_name' => $absorber_name,'condenser_name' => $condenser_name,'unit_set' => $unit_set,'units_data' => $units_data]);
            return $pdf->download('Hot-Water-Fired-series.pdf');
        }
        elseif ($user_report->calculator_code == 'D_G2') {

            $pdf = PDF::loadView('reports.report_pdf', ['name' => $name,'phone' => $phone,'project' => $project,'calculation_values' => $calculation_values,'evaporator_name' => $evaporator_name,'absorber_name' => $absorber_name,'condenser_name' => $condenser_name,'unit_set' => $unit_set,'units_data' => $units_data]);
            return $pdf->download('Direct-Fired-series.pdf');
         }
        

    }
    public function getUnitsData()
    {

        return array('Centigrade' => "°C",'Fahrenheit' => "°F",'Millimeter' => "mm",'Inch' => "in",'Kilogram' => "kg",'Ton' => "ton",'Pound' => "lbs",'KgPerCmSq' => "kg/cm²",'KgPerCmSqGauge' =>"kg/cm²(g)",'Bar' =>"bar",'BarGauge' =>"bar(g)",'mLC' =>"mLC",'mWC' => "mWC",'mmWC' => "mmWC",'ftLC' => "ftLC",'ftWC' => "ftWC",'psi' => "psi",'psig' => "psi(g)",'kiloPascal' => "kPa",'kiloPascalGauge' => "kPa(g)",'CubicMeter' =>"m³",'CubicFeet' =>"cu.ft.",'SquareMeter' =>"m²",'SquareFeet' =>"sq.ft.",'TR' => "TR",'kW' => "kW",'CubicMeterPerHr' => "m³/hr",'CubicFeetPerHour' => "cu.ft./hr",'GallonPerMin' => "gallon/min",'KilogramsPerHr' => "kg/hr",'PoundsPerHour' => "lb/hr",'NCubicMeterPerHr' => "Nm³/hr",'NCubicFeetPerHour' =>"Ncu.ft./hr",'SquareMeterKperkW' =>"m² K/kW",'SquareMeterHrCperKcal' =>"m² hr °C/kcal",'SquareFeetHrFperBTU' =>"ft² Hr °F/BTU",'kCPerHour' => "kcal/Hr",'KWatt' => "kW",'MBTUPerHour' => "MBH",'kCPerKilogram' => "kcal/kg",'BTUPerPound' => "BTU/lb",'kJPerKilogram' => "kJ/kg",'kCPerNcubicmetre' => "kcal/Nm³",'BTUPerNcubicfeet' => "BTU/Ncu.ft",'kJPerNcubicmetre' =>"kJ/Nm³",'KgPerCmSqGauge' =>"kg/cm²(g)",'psiGauge' =>"psi(g)",'kiloPascalGauge' =>"kPa(g)",'DN' =>"DN",'NB' =>"NPS",'kcalperkgdegC' =>"kcal/kg°C",'kJouleperkgdegC' =>"kJ/kg°C",'BTUperpounddegF' =>"BTU/lb°F");

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

        if($user->user_type=='ADMIN')
        {
            $unit_sets = UnitSet::select('name','id')->where('user_type','ADMIN')->get();
        }
        else
        {
            $unit_sets = UnitSet::select('name','id')->where(function ($query) use ($user) {
                                        $query->where('user_type','ADMIN');
            
                                    })->orWhere(function ($query) use ($user){
                                        $query->where('user_id',$user->id);
                                    })->get();
        }
        //return $unit_sets;

        return view('user_profile')->with('user',$user)->with('unit_sets',$unit_sets);
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
        Mail::to($user->email)->send(new SendUserPassword($name,$email_token)); 
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
