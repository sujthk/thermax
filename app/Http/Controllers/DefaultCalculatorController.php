<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use App\ChillerDefaultValue;
use App\ChillerOption;
use App\ChillerMetallurgyOption;
use App\ChillerCalculationValue;
use App\NotesError;
use App\Metallurgy;
use App\CalculationKey;
use App\Language;
use App\LanguageValue;
use App\LanguageKey;
use App\Calculator;
use Log;
use Excel;
class DefaultCalculatorController extends Controller
{
    public function getCalculators(){

    	$default_calculators = ChillerDefaultValue::get();


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
		    'min_model' => 'required',
            'max_model' => 'required'
		]);


    	$chiller_default_value = ChillerDefaultValue::find($chiller_default_id);
        $chiller_default_value->name = $request->name;
        $chiller_default_value->max_model = $request->max_model;
        $chiller_default_value->min_model = $request->min_model;
        $chiller_default_value->default_values = json_encode($request->default_values);
        $chiller_default_value->save();

		return redirect('default/calculators')->with('message','Chiller Default Value Updated')
                        ->with('status','success');

    }

    

    public function getMetallurgyCalculators(){

    	$metallurgy_calculators = ChillerMetallurgyOption::orderBy('created_at', 'desc')->get();


    	return view('metallurgy_calculators')->with('metallurgy_calculators',$metallurgy_calculators);
    }

    public function editMetallurgyCalculator($chiller_metallurgy_id,$tube_type){
    	$metallurgy_calculator = ChillerMetallurgyOption::find($chiller_metallurgy_id);

        $metallurgies = Metallurgy::all();
    	$metallurgy_values = ChillerOption::where('chiller_metallurgy_option_id',$chiller_metallurgy_id)->where('type',$tube_type)->get();

        // return $metallurgy_values;
    	return view('metallurgy_calculator_edit')
    						->with('metallurgy_calculator',$metallurgy_calculator)
                            ->with('metallurgy_values',$metallurgy_values)
    						->with('metallurgies',$metallurgies)
                            ->with('tube_type',$tube_type);
    }

    public function addMetallurgyCalculator(){

        $calculators = Calculator::all();

        return view('metallurgy_calculator_add')->with('calculators',$calculators);
    }

    public function postMetallurgyCalculator(Request $request){
        $this->validate($request, [
            'name' => 'required',
            'code' => 'required',
            'min_model' => 'required|numeric',
            'max_model' => 'required|numeric',

        ]);

        $chiller_metallurgy_option = new ChillerMetallurgyOption;
        $chiller_metallurgy_option->name = $request->name;
        $chiller_metallurgy_option->code = $request->code;
        $chiller_metallurgy_option->min_model = $request->min_model;
        $chiller_metallurgy_option->max_model = $request->max_model;
        $chiller_metallurgy_option->save();
        

        return redirect('tube-metallurgy/calculators')->with('message','Metallurgy Calculator Added')
                        ->with('status','success');
    }

    public function updateMetallurgyCalculator(Request $request,$chiller_metallurgy_id,$tube_type){
        // return $request->all();
        $this->validate($request, [
            'metallurgy' => 'required',
            'default_value' => 'required|numeric',
        ]);

        $deletedRows = ChillerOption::where('chiller_metallurgy_option_id', $chiller_metallurgy_id)->where('type',$tube_type)->delete();
        $metallurgy_calculator = ChillerMetallurgyOption::find($chiller_metallurgy_id);

        if($tube_type == 'eva'){
            $metallurgy_calculator->eva_default_value = $request->default_value;
        }
        elseif($tube_type == 'abs'){
            $metallurgy_calculator->abs_default_value = $request->default_value;
        }
        else{
            $metallurgy_calculator->con_default_value = $request->default_value;
        }

        $metallurgy_calculator->save();

        if ($request->has('metallurgy')){

            foreach ($request->metallurgy as $metallurgy) {

                $metallurgy_option = new ChillerOption;
                $metallurgy_option->chiller_metallurgy_option_id = $chiller_metallurgy_id;
                $metallurgy_option->metallurgy_id = $metallurgy['metallurgy_id'];
                $metallurgy_option->value = $metallurgy['value'];
                $metallurgy_option->type = $tube_type;
                $metallurgy_option->save();

            }
        }

        
        
        return redirect('tube-metallurgy/calculators')->with('message','Metallurgy Options Updated')
                        ->with('status','success');
    }

    public function deleteMetallurgyCalculator($chiller_metallurgy_id){

        $deletedRows = ChillerOption::where('chiller_metallurgy_option_id', $chiller_metallurgy_id)->delete();

        $metallurgy_calculator = ChillerMetallurgyOption::destroy($chiller_metallurgy_id);

        
        return redirect('tube-metallurgy/calculators')->with('message','Metallurgy Options Deleted')
                        ->with('status','success');
    }


    public function getChillerCalculations(){

        $chiller_calculation_values = ChillerCalculationValue::get();
        $calculator_keys = CalculationKey::orderBy('created_at', 'desc')->get();

        return view('chiller_calculation_values')->with('chiller_calculation_values',$chiller_calculation_values)->with('calculator_keys',$calculator_keys);
    }

    public function editCalculatorValue($chiller_calculation_value_id){
        $chiller_calculation_value = ChillerCalculationValue::find($chiller_calculation_value_id);

        // return $default_calculator;

        $calculation_values = json_decode($chiller_calculation_value->calculation_values,true);
        $calculation_value_keys = array_keys($calculation_values);
        // return $default_values;

        return view('calculator_values_edit')
                            ->with('calculation_value_keys',$calculation_value_keys)
                            ->with('calculation_values',$calculation_values)
                            ->with('chiller_calculation_value',$chiller_calculation_value);
    }

    public function updateCalculatorValue(Request $request,$chiller_calculation_value_id){
        // return $request->all();


        $this->validate($request, [
            'calculation_values' => 'required',
            'name' => 'required',
            'min_model' => 'required'
        ]);


        $chiller_calculation_value = ChillerCalculationValue::find($chiller_calculation_value_id);
        $chiller_calculation_value->name = $request->name;
        $chiller_calculation_value->min_model = $request->min_model;
        $chiller_calculation_value->calculation_values = json_encode($request->calculation_values);
        $chiller_calculation_value->save();

        return redirect('chiller/calculation-values')->with('message','Chiller Calculation Value Updated')
                        ->with('status','success');

    }

    public function deleteCalculatorValue($chiller_default_id){
        $default_calculator = ChillerCalculationValue::destroy($chiller_default_id);


        return redirect('chiller/calculation-values')->with('message','Chiller Value Deleted')
                        ->with('status','success');
    }

    public function getErrorNotes(){

        $notes_errors = NotesError::get();
        $languages = Language::where('status',1)->get();

        return view('notes_errors')->with('notes_errors',$notes_errors)->with('languages',$languages);
    }

    public function postErrorNote(Request $request){
        // return $request->all();


        $this->validate($request, [
            'note_name' => 'required',
            'language_id' => 'required',
            'note_value' => 'required'
        ]);


        $language_key = new LanguageKey;
        $language_key->name = $request->note_name;
        $language_key->type = "NOTES_ERRORS";
        $language_key->save();

        $notes_error = new NotesError;
        $notes_error->language_id = $request->language_id;
        $notes_error->language_key_id = $language_key->id;
        $notes_error->value = $request->note_value;
        $notes_error->save();

        return redirect('error-notes')->with('message','Notes Added')
                        ->with('status','success');

    }

    public function updateErrorNote(Request $request,$error_notes_id){
        // return $request->all();

        $this->validate($request, [
            'key_value' => 'required'
        ]);


        $notes_error = NotesError::find($error_notes_id);
        $notes_error->value = $request->key_value;
        $notes_error->save();

        return redirect('error-notes')->with('message','Notes Updated')
                        ->with('status','success');

    }

    public function DeleteErrorNote($error_notes_id){


        $notes_error = NotesError::destroy($error_notes_id);

        return redirect('error-notes')->with('message','Notes Deleted')
                        ->with('status','success');

    }


    public function exportErrorExcel(Request $request)
    {

        $languages = Language::get();
        $error_datas = array();

        foreach ($languages as $language) {
            $error_values = NotesError::with(['language', 'language_key'])->where('language_id',$language->id)->get();

            $error_key_values = array();
            $error_key_values['language'] = $language->name;
            foreach ($error_values as $error_value) {
                $error_key_values[$error_value->language_key->name] = $error_value->value;
            }

            $error_datas[] = $error_key_values;
        }

        
        // return $language_datas;
        return Excel::create('error_datas', function($excel) use ($error_datas) {
            $excel->sheet('mySheet', function($sheet) use ($error_datas)
            {
                $sheet->fromArray($error_datas);
            });
        })->download('xlsx');
       
        
    }

    public function importErrorExcel(Request $request)
    {
        //return $request->all();
        if(Input::hasFile('file')){
            $path = Input::file('file')->getRealPath();
            $datas = Excel::load($path, function($reader) {
               

            })->get();
            $datas = collect($datas)->toArray();
            
            if(!empty($datas) && count($datas)){
                foreach ($datas as $data) {
                    $language = Language::where('name',$data['language'])->first();
                    if($language){
                        $language_keys = LanguageKey::where('type','NOTES_ERRORS')->get();
                        foreach ($language_keys as $language_key) {
                            $error_value = NotesError::where('language_id',$language->id)->where('language_key_id',$language_key->id)->first();
                            if(!$error_value){
                                $error_value = new NotesError;
                                $error_value->language_id = $language->id;
                                $error_value->language_key_id = $language_key->id;
                            }
                            $error_value->value = empty($data[$language_key->name]) ?  "" : $data[$language_key->name];
                            $error_value->save();

                        }
                    }                      
                }
                return redirect('error-notes')->with('message','Error Notes updated')
                        ->with('status','success');

            }
        }
        return back()->with('message','File Missing')
                        ->with('status','error');
    }



    public function importExport(Request $request)
    {
        //return $request->all();
        //$data = Item::get()->toArray();
        $chiller_calculation_values = ChillerCalculationValue::where('code',$request->code)->get();
        //$data=[];
        //return $chiller_calculation_values;
        
        // Define the Excel spreadsheet headers
        $key_datas1 = CalculationKey::where('code',$request->code)->first();
        $key_datas =explode(",",$key_datas1->keys);
        //$key_datas = array_pluck($key_datas1, 'name');
       //return $key_datas1;
        //$data2 = ['id','name','code','min_model']; 
       //$data[] = array_merge($data2,$key_datas);
        if(count($chiller_calculation_values))
        {
                       
            foreach ($chiller_calculation_values as $chiller_calculation_value) {
                $data1 =[];
                $data1['id'] = $chiller_calculation_value->id;
                $data1['name'] = $chiller_calculation_value->name;
                $data1['code'] = $chiller_calculation_value->code;
                $data1['min_model'] = $chiller_calculation_value->min_model;

                $calculation_values = json_decode($chiller_calculation_value->calculation_values,true);

                foreach ($key_datas as $key => $key_data) {
                    if(isset($calculation_values[$key_data]))
                        $data1[$key_data] = $calculation_values[$key_data];
                    else
                        $data1[$key_data] = 0;
                }
               $data[] = $data1;
            }

            return Excel::create('chiller_calculation', function($excel) use ($data) {
                $excel->sheet('mySheet', function($sheet) use ($data)
                {
                    $sheet->fromArray($data);
                });
            })->download('xlsx');
        }   
        else
        {
             
            $data1 =[];
            $data1['id'] = "";
            $data1['name'] = "";
            $data1['code'] = "";
            $data1['min_model'] = "";
             foreach ($key_datas as $key => $key_data) {
                $data1[$key_data] = "";
             }
            $data[] = $data1;


            return Excel::create('chiller_calculation', function($excel) use ($data) {
                $excel->sheet('mySheet', function($sheet) use ($data)
                {
                    $sheet->fromArray($data);
                });
            })->download('xlsx');

            // return redirect('chiller/calculation-values')->with('message','Chiller Calculation Value Empty')
            //             ->with('status','error');
 
        }
       
           

    }
    public function importExcel(Request $request)
    {
        //return $request->all();
        if(Input::hasFile('file')){
            $path = Input::file('file')->getRealPath();
            $data = Excel::load($path, function($reader) {
               

            })->get();
            $data = collect($data)->toArray();
            $titles = "";

            if(!empty($data) && count($data)){
            $key_datas1 = CalculationKey::where('code',$request->code)->first();
            $key_datas =explode(",",$key_datas1->keys);
            }
            //return  $key_datas;
            if(!empty($data) && count($data)){
                foreach ($data as $value) {
                    $data1=[];
                    foreach ($key_datas as $key_data) {
                        //if($value[strtolower($key_data)])
                            $data1[$key_data] = $value[$key_data];
                    }
                    
                    $chiller_calculation_value = ChillerCalculationValue::where('code',$value['code'])->where('min_model',$value['min_model'])->first();
                    if($chiller_calculation_value)
                    {
                        $chiller_calculation_value->name = $value['name'];
                        $chiller_calculation_value->code = $request->code;
                        $chiller_calculation_value->min_model = $value['min_model'];
                        $chiller_calculation_value->calculation_values = json_encode($data1);
                        //return $chiller_calculation_value;
                        $chiller_calculation_value->save();
                    }
                    else
                    {
                        $chiller_calculation_value =new ChillerCalculationValue;
                        $chiller_calculation_value->name = $value['name'];
                        $chiller_calculation_value->code = $request->code;
                        $chiller_calculation_value->min_model = $value['min_model'];
                        $chiller_calculation_value->calculation_values = json_encode($data1);
                        //return $chiller_calculation_value;
                        $chiller_calculation_value->save();
                    }
                    
                }
                return redirect('chiller/calculation-values')->with('message','Chiller Calculation Value Updated')
                        ->with('status','success');
                //$calculation_values = json_encode($insert_values);
              
                // if(!empty($insert)){
                //     //DB::table('items')->insert($insert);
                //     dd('Insert Record successfully.');
                // }
            }
        }
        return back();
    }
    public function getCalculationKeys(){

        $calculator_keys = CalculationKey::orderBy('created_at', 'desc')->get();

        $calculators = Calculator::all();

        return view('calculator_key_list')->with('calculator_keys',$calculator_keys)->with('calculators',$calculators);
    }
    public function postCalculationKey(Request $request){

        $this->validate($request, [
            'name' => 'required',
            'keys'=> 'required',
            'code'=>'required'
        ]);


        $calculator_key = new CalculationKey;
        $calculator_key->name = $request->name;
        $calculator_key->code = $request->code;
        $calculator_key->keys = $request->keys;
        $calculator_key->save();

        return redirect('calculation-keys')->with('message','Calculator Key Added')
                        ->with('status','success');

    }
     public function editCalculationKey(Request $request,$id){

        $this->validate($request, [
            'name' => 'required',
            'keys'=> 'required',
            'code'=>'required'
        ]);

        $calculator_key = CalculationKey::find($id);
        $calculator_key->name = $request->name;
        $calculator_key->code = $request->code;
        $calculator_key->keys = $request->keys;
        $calculator_key->save();

        return redirect('calculation-keys')->with('message','Calculator Key Updated')
                        ->with('status','success');

    }

    public function getLanguageNotes(){

        $language_values = LanguageValue::get();
        $languages = Language::where('status',1)->get();

        return view('language_notes')->with('languages',$languages)->with('language_values',$language_values);
    }

    public function postLanguageNote(Request $request){
        // return $request->all();


        $this->validate($request, [
            'note_name' => 'required',
            'language_id' => 'required',
            'key_value' => 'required'
        ]);


        $language_key = new LanguageKey;
        $language_key->name = $request->note_name;
        $language_key->type = "FORM_VALUES";
        $language_key->save();

        $language_value = new LanguageValue;
        $language_value->language_id = $request->language_id;
        $language_value->language_key_id = $language_key->id;
        $language_value->value = $request->key_value;
        $language_value->save();

        return redirect('languages-notes')->with('message','Language Notes Added')
                        ->with('status','success');

    }

    public function updateLanguageNote(Request $request,$language_note_id){
        // return $request->all();

        $this->validate($request, [
            'key_value' => 'required'

        ]);


        $language_value = LanguageValue::find($language_note_id);
        $language_value->value = $request->key_value;
        $language_value->save();

        return redirect('languages-notes')->with('message','Notes Updated')
                        ->with('status','success');

    }

    public function DeleteLanguageNote($language_note_id){


        $language_value = LanguageValue::destroy($language_note_id);

        return redirect('languages-notes')->with('message','Language Notes Deleted')
                        ->with('status','success');

    }


    public function exportLanguageExcel(Request $request)
    {

        $languages = Language::get();
        $language_datas = array();

        foreach ($languages as $language) {
            $language_values = LanguageValue::with(['language', 'language_key'])->where('language_id',$language->id)->get();

            $language_key_values = array();
            $language_key_values['language'] = $language->name;
            foreach ($language_values as $language_value) {
                $language_key_values[$language_value->language_key->name] = $language_value->value;
            }

            $language_datas[] = $language_key_values;
        }

        
        // return $language_datas;
        return Excel::create('language_datas', function($excel) use ($language_datas) {
            $excel->sheet('mySheet', function($sheet) use ($language_datas)
            {
                $sheet->fromArray($language_datas);
            });
        })->download('xlsx');
       
        
    }

    public function importLanguageExcel(Request $request)
    {
        //return $request->all();
        if(Input::hasFile('file')){
            $path = Input::file('file')->getRealPath();
            $datas = Excel::load($path, function($reader) {
               

            })->get();
            $datas = collect($datas)->toArray();
            // Log::info($datas);
            if(!empty($datas) && count($datas)){
                foreach ($datas as $data) {
                    $language = Language::where('name',$data['language'])->first();
                    if($language){
                        $language_keys = LanguageKey::where('type','FORM_VALUES')->get();
                        foreach ($language_keys as $language_key) {
                            $language_value = LanguageValue::where('language_id',$language->id)->where('language_key_id',$language_key->id)->first();
                            if(!$language_value){
                                $language_value = new LanguageValue;
                                $language_value->language_id = $language->id;
                                $language_value->language_key_id = $language_key->id;
                            }
                            $language_value->value = empty($data[$language_key->name]) ?  "" : $data[$language_key->name];
                            $language_value->save();
                        }
                    }                      
                }
                return redirect('languages-notes')->with('message','Language Notes updated')
                        ->with('status','success');

            }
        }
        return back()->with('message','File Missing')
                        ->with('status','error');
    }

    public function getLanguages(){

        $languages = Language::get();

        return view('languages')->with('languages',$languages);
    }

    public function postLanguage(Request $request){
        // return $request->all();


        $this->validate($request, [
            'language_name' => 'required'
        ]);


        $language = new Language;
        $language->name = $request->language_name;
        $language->status = 1;
        $language->save();


        return redirect('languages')->with('message','Language Added')
                        ->with('status','success');

    }

    public function updateLanguage(Request $request,$language_id){
        // return $request->all();

        $this->validate($request, [
            'language_name' => 'required'

        ]);


        $language = Language::find($language_id);
        $language->name = $request->language_name;
        $language->save();

        return redirect('languages')->with('message','Language Updated')
                        ->with('status','success');

    }

    public function changeLanguageStatus($language_id,$status){

        $language = Language::find($language_id);
        $language->status = $status;
        $language->save();

        return redirect('languages')->with('message','Langauge Status Changed')
                        ->with('status','success');
    }

}
