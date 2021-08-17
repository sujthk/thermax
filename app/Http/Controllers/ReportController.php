<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\UserReport;
use App\UnitSet;
use App\ChillerMetallurgyOption;
use App\CalculatorReport;
use App\Http\Controllers\VamBaseController;
use Log;
use PhpOffice\PhpWord\Element\Table;

class ReportController extends Controller
{


    public function templateCheck(){
        $my_template = new \PhpOffice\PhpWord\TemplateProcessor(storage_path('report_template/l1.docx'));

        $my_template->setValue('capacity', 50);

        $my_template = $this->commonTemplate($my_template);

        try{
                $my_template->saveAs(storage_path('l1.docx'));
            }catch (Exception $e){
                //handle exception
            }

        return response()->download(storage_path('l1.docx'));
    }

    public function commonTemplate($my_template, $language_datas, $calculation_values,$units_data, $unit_set){
        $my_template->setValue('client', $language_datas['client']);
        $my_template->setValue('client_name', $calculation_values['client_name']);

        $my_template->setValue('version', $language_datas['version']);
        $my_template->setValue('current_version', $calculation_values['version']." Dt : ".$calculation_values['version_date']);

        $my_template->setValue('enquiry', $language_datas['enquiry']);
        $my_template->setValue('enquiry_name', $calculation_values['enquiry_name']);

        $my_template->setValue('date', $language_datas['date']);
        $my_template->setValue('date_time', $calculation_values['date_time']);

        $my_template->setValue('project', $language_datas['project']);
        $my_template->setValue('project_name', $calculation_values['project_name']);

        $my_template->setValue('model', $language_datas['model']);
        $my_template->setValue('model_name', $calculation_values['model_name']);

        $my_template->setValue('description', $language_datas['description']);
        $my_template->setValue('unit', $language_datas['unit']);

        $my_template->setValue('capacity', $language_datas['capacity']);
        $my_template->setValue('capacity_unit', $units_data[$unit_set->CapacityUnit]);
        $my_template->setValue('capacity_value', round($calculation_values['TON'],1));


        // Chilled Water Circuit
        $my_template->setValue('ch_water_circuit', $language_datas['chilled_water_circuit']);

        $my_template->setValue('ch_water_flow', $language_datas['chilled_water_flow']);
        $my_template->setValue('ch_water_flow_unit', $units_data[$unit_set->FlowRateUnit]);
        $my_template->setValue('ch_water_flow_value', round($calculation_values['ChilledWaterFlow'],1));

        $my_template->setValue('ch_in_temp', $language_datas['chilled_inlet_temp']);
        $my_template->setValue('ch_in_unit', $units_data[$unit_set->TemperatureUnit]);
        $my_template->setValue('ch_in_value', round($calculation_values['TCHW11'],1));

        $my_template->setValue('ch_out_temp', $language_datas['chilled_outlet_temp']);
        $my_template->setValue('ch_out_unit', $units_data[$unit_set->TemperatureUnit]);
        $my_template->setValue('ch_out_value', round($calculation_values['TCHW12'],1));

        $my_template->setValue('evaporator_passes', $language_datas['evaporate_pass']);
        $my_template->setValue('evaporator_pass_value', $calculation_values['EvaporatorPasses']);

        $my_template->setValue('ch_pressure_loss', $language_datas['chilled_pressure_loss']);
        $my_template->setValue('ch_pressure_loss_unit', $units_data[$unit_set->PressureDropUnit]);
        $my_template->setValue('ch_pressure_loss_value', round($calculation_values['ChilledFrictionLoss'],1));    

        $my_template->setValue('ch_conn_dia', $language_datas['chilled_connection_diameter']);
        $my_template->setValue('ch_conn_dia_unit', $units_data[$unit_set->NozzleDiameterUnit]);
        $my_template->setValue('ch_conn_dia_value', round($calculation_values['ChilledConnectionDiameter'],1));  

        $my_template->setValue('glycol', $language_datas['glycol_type']);
        if(empty($calculation_values['CHGLY']) || $calculation_values['GL'] == 1)
            $my_template->setValue('glycol_value', "NA");
        else if($calculation_values['GL'] == 2)
            $my_template->setValue('glycol_value', "Ethylene");
        else
            $my_template->setValue('glycol_value', "Proplylene"); 

        $my_template->setValue('ch_glycol', $language_datas['chilled_gylcol']);
        $my_template->setValue('ch_glycol_value', round($calculation_values['CHGLY'],1)); 

        $my_template->setValue('ch_fouling_factor', $language_datas['chilled_fouling_factor']);
        $my_template->setValue('ch_fouling_factor_unit', $units_data[$unit_set->FoulingFactorUnit]);
        if($calculation_values['TUU'] == "standard")
            $my_template->setValue('ch_fouling_factor_value', $calculation_values['TUU']);
        else
            $my_template->setValue('ch_fouling_factor_value', $calculation_values['FFCHW1']);


        $my_template->setValue('ch_max_working_pressure', $language_datas['max_working_pressure']);
        $my_template->setValue('ch_max_working_pressure_unit', $units_data[$unit_set->WorkPressureUnit]);
        $my_template->setValue('ch_max_working_pressure_value', round($calculation_values['m_maxCHWWorkPressure'],1));  



        // Cooling water
        $my_template->setValue('co_water_circuit', $language_datas['cooling_water_circuit']);

        $my_template->setValue('heat_rejected', $language_datas['heat_rejected']);
        $my_template->setValue('heat_rejected_unit', $units_data[$unit_set->HeatUnit]);
        $my_template->setValue('heat_rejected_value', round($calculation_values['HeatRejected'],1));

        $my_template->setValue('co_water_flow', $language_datas['cooling_water_flow']);
        $my_template->setValue('co_water_flow_unit', $units_data[$unit_set->FlowRateUnit]);
        $my_template->setValue('co_water_flow_value', round($calculation_values['GCW'],1));

        $my_template->setValue('co_in_temp', $language_datas['cooling_inlet_temp']);
        $my_template->setValue('co_in_unit', $units_data[$unit_set->TemperatureUnit]);
        $my_template->setValue('co_in_value', round($calculation_values['TCW11'],1));

        $my_template->setValue('co_out_temp', $language_datas['cooling_outlet_temp']);
        $my_template->setValue('co_out_unit', $units_data[$unit_set->TemperatureUnit]);
        $my_template->setValue('co_out_value', round($calculation_values['CoolingWaterOutTemperature'],1));

        $my_template->setValue('abs_con_pass', $language_datas['absorber_condenser_pass']);
        $my_template->setValue('abs_pass_value', $calculation_values['AbsorberPasses']);
        $my_template->setValue('con_pass_value', $calculation_values['CondenserPasses']);

        $my_template->setValue('co_bypass_flow', $language_datas['cooling_bypass_flow']);
        $my_template->setValue('co_bypass_flow_unit', $units_data[$unit_set->FlowRateUnit]);
        if(empty($calculation_values['BypassFlow']))
            $my_template->setValue('co_bypass_flow_value', "-");
        else        
            $my_template->setValue('co_bypass_flow_value', round($calculation_values['BypassFlow'],1));    

        $my_template->setValue('co_pressure_loss', $language_datas['cooling_pressure_loss']);
        $my_template->setValue('co_pressure_loss_unit', $units_data[$unit_set->PressureDropUnit]);
        $my_template->setValue('co_pressure_loss_value', round($calculation_values['CoolingFrictionLoss'],1));    

        $my_template->setValue('co_conn_dia', $language_datas['cooling_connection_diameter']);
        $my_template->setValue('co_conn_dia_unit', $units_data[$unit_set->NozzleDiameterUnit]);
        $my_template->setValue('co_conn_dia_value', round($calculation_values['CoolingConnectionDiameter'],1));  

        $my_template->setValue('co_glycol', $language_datas['cooling_gylcol']);
        $my_template->setValue('co_glycol_value', round($calculation_values['COGLY'],1)); 

        $my_template->setValue('co_fouling_factor', $language_datas['cooling_fouling_factor']);
        $my_template->setValue('co_fouling_factor_unit', $units_data[$unit_set->FoulingFactorUnit]);
        if($calculation_values['TUU'] == "standard")
            $my_template->setValue('co_fouling_factor_value', $calculation_values['TUU']);
        else
            $my_template->setValue('co_fouling_factor_value', $calculation_values['FFCOW1']);


        $my_template->setValue('co_max_working_pressure', $language_datas['max_working_pressure']);
        $my_template->setValue('co_max_working_pressure_unit', $units_data[$unit_set->WorkPressureUnit]);
        $my_template->setValue('co_max_working_pressure_value', ceil($calculation_values['m_maxCOWWorkPressure']));  


        // Electrical Data
        $my_template->setValue('electrical_data', $language_datas['electrical_data']);

        $my_template->setValue('power_supply', $language_datas['power_supply']);
        $my_template->setValue('power_supply_value', $calculation_values['PowerSupply']);

        $my_template->setValue('power_consumption', $language_datas['power_consumption']);
        $my_template->setValue('power_consumption_value', round($calculation_values['TotalPowerConsumption'],1));

        $contains = str_contains($calculation_values['model_name'], 'L5');
        if($contains)
            $my_template->setValue('absorbent_pump_rating', $language_datas['hp_absorbent_pump_rating']);
        else
            $my_template->setValue('absorbent_pump_rating', $language_datas['absorbent_pump_rating']);
        
        if($calculation_values['region_type'] ==2){
            $my_template->setValue('absorbent_pump_rating_kw_value', round($calculation_values['USA_AbsorbentPumpMotorKW'],2));
            $my_template->setValue('absorbent_pump_rating_amp_value', round($calculation_values['USA_AbsorbentPumpMotorAmp'],2));
        }
        else{
            $my_template->setValue('absorbent_pump_rating_kw_value', round($calculation_values['AbsorbentPumpMotorKW'],2));
            $my_template->setValue('absorbent_pump_rating_amp_value', round($calculation_values['AbsorbentPumpMotorAmp'],2));
        }


        $my_template->setValue('refrigerant_pump_rating', $language_datas['refrigerant_pump_rating']);
        if($calculation_values['region_type'] ==2){
            $my_template->setValue('refrigerant_pump_rating_kw_value', round($calculation_values['USA_RefrigerantPumpMotorKW'],2));
            $my_template->setValue('refrigerant_pump_rating_amp_value', round($calculation_values['USA_RefrigerantPumpMotorAmp'],2));
        }
        else{
            $my_template->setValue('refrigerant_pump_rating_kw_value', round($calculation_values['RefrigerantPumpMotorKW'],2));
            $my_template->setValue('refrigerant_pump_rating_amp_value', round($calculation_values['RefrigerantPumpMotorAmp'],2));
        }

        $my_template->setValue('vacuum_pump_rating', $language_datas['vaccum_pump_rating']);
        if($calculation_values['region_type'] ==2){
            $my_template->setValue('vacuum_pump_rating_kw_value', round($calculation_values['USA_PurgePumpMotorKW'],2));
            $my_template->setValue('vacuum_pump_rating_amp_value', round($calculation_values['USA_PurgePumpMotorAmp'],2));
        }
        else{
            $my_template->setValue('vacuum_pump_rating_kw_value', round($calculation_values['PurgePumpMotorKW'],2));
            $my_template->setValue('vacuum_pump_rating_amp_value', round($calculation_values['PurgePumpMotorAmp'],2));
        }

        if($calculation_values['region_type'] ==2){
            $my_template->setValue('mop_value', round($calculation_values['MOP'],2));
            $my_template->setValue('mca_value', round($calculation_values['MCA'],2));
        }

        // Physical Data
        $my_template->setValue('physical_data', $language_datas['physical_data']);

        $my_template->setValue('length', $language_datas['length']);
        $my_template->setValue('length_unit', $units_data[$unit_set->LengthUnit]);
        $my_template->setValue('length_value', ceil($calculation_values['Length']));

        $my_template->setValue('width', $language_datas['width']);
        $my_template->setValue('width_unit', $units_data[$unit_set->LengthUnit]);
        $my_template->setValue('width_value', ceil($calculation_values['Width']));

        $my_template->setValue('height', $language_datas['height']);
        $my_template->setValue('height_unit', $units_data[$unit_set->LengthUnit]);
        $my_template->setValue('height_value', ceil($calculation_values['Height']));

        $my_template->setValue('operating_weight', $language_datas['operating_weight']);
        $my_template->setValue('operating_weight_unit', $units_data[$unit_set->WeightUnit]);
        $my_template->setValue('operating_weight_value',round($calculation_values['OperatingWeight'],1));

        $my_template->setValue('shipping_weight', $language_datas['shipping_weight']);
        $my_template->setValue('shipping_weight_unit', $units_data[$unit_set->WeightUnit]);
        $my_template->setValue('shipping_weight_value', round($calculation_values['MaxShippingWeight'],1));

        $my_template->setValue('flooded_weight', $language_datas['flooded_weight']);
        $my_template->setValue('flooded_weight_unit', $units_data[$unit_set->WeightUnit]);
        $my_template->setValue('flooded_weight_value', round($calculation_values['FloodedWeight'],1));

        $my_template->setValue('dry_weight', $language_datas['dry_weight']);
        $my_template->setValue('dry_weight_unit', $units_data[$unit_set->WeightUnit]);
        $my_template->setValue('dry_weight_value', round($calculation_values['DryWeight'],1));



        $my_template->setValue('tube_cleaning_space', $language_datas['tube_clearing_space']." (".$language_datas['one_side_length_wise'].")");
        $my_template->setValue('tube_cleaning_space_unit', $units_data[$unit_set->LengthUnit]);
        $my_template->setValue('tube_cleaning_space_value', round($calculation_values['ClearanceForTubeRemoval'],1));

        // Tune Metallurgy
        $my_template->setValue('tube_metallurgy', $language_datas['tube_metallurgy']);

        $my_template->setValue('evaporator_tube', $language_datas['evaporator_tube_material']);
        $my_template->setValue('evaporator_tube_value', $calculation_values['evaporator_name']);

        $my_template->setValue('absorber_tube', $language_datas['absorber_tube_material']);
        $my_template->setValue('absorber_tube_value', $calculation_values['absorber_name']);

        $my_template->setValue('condenser_tube', $language_datas['condenser_tube_material']);
        $my_template->setValue('condenser_tube_value', $calculation_values['condenser_name']);

        

        $my_template->setValue('notes', $language_datas['caption_notes']);

        $my_template->cloneBlock('block_name', count($calculation_values['notes']), true, true);
       
        foreach ($calculation_values['notes'] as $key => $note) {
            $my_template->setValue('caption_notes#'.($key+1), ($key + 1).". ".$note);
        }


        return $my_template;
    }

    public function wordFormatCHG2($user_report_id,$model_code){
        $user_report = UserReport::find($user_report_id);

        $unit_set = UnitSet::find($user_report->unit_set_id);


        $calculation_values = json_decode($user_report->calculation_values,true);
        $date = date('d-M-Y, H:i ', strtotime($user_report->created_at));

        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$model_code)
                                        ->where('min_model','<=',(int)$calculation_values['MODEL'])->where('max_model','>=',(int)$calculation_values['MODEL'])->first();

        $chiller_options = $chiller_metallurgy_options->chillerOptions;
        
        $evaporator_option = $chiller_options->where('type', 'eva')->where('value',$calculation_values['TU2'])->first();
        $absorber_option = $chiller_options->where('type', 'abs')->where('value',$calculation_values['TU5'])->first();
        $condenser_option = $chiller_options->where('type', 'con')->where('value',$calculation_values['TV5'])->first();

        $evaporator_name = $evaporator_option->metallurgy->report_name;
        $absorber_name = $absorber_option->metallurgy->report_name;
        $condenser_name = $condenser_option->metallurgy->report_name;

        $vam_base = new VamBaseController();
        $language_datas = $vam_base->getLanguageDatas();
        $units_data = $vam_base->getUnitsData();

        $calculation_values['client_name'] = $user_report->name;
        $calculation_values['enquiry_name'] = $user_report->phone;
        $calculation_values['project_name'] = $user_report->project;
        $calculation_values['date_time'] = date('d-M-Y, H:i ', strtotime($user_report->created_at));
        $calculation_values['evaporator_name'] = $evaporator_name;
        $calculation_values['absorber_name'] = $absorber_name;
        $calculation_values['condenser_name'] = $condenser_name;
        $calculation_values['GL'] = $calculation_values['GLL'];

        if($calculation_values['region_type'] ==2){
            $my_template = new \PhpOffice\PhpWord\TemplateProcessor(storage_path('report_template/CHG2_USA.docx'));
        }
        else{
            $my_template = new \PhpOffice\PhpWord\TemplateProcessor(storage_path('report_template/CHG2.docx'));
        }
        


        $my_template = $this->commonTemplate($my_template, $language_datas, $calculation_values, $units_data, $unit_set);


        // Hot water circuit
        $my_template->setValue('hot_water_circuit', $language_datas['hot_water_circuit']);

        $my_template->setValue('heat_duty', $language_datas['heat_input']);
        $my_template->setValue('heat_duty_unit', $units_data[$unit_set->HeatUnit]);
        $my_template->setValue('heat_duty_value', round($calculation_values['HEATCAP'],1));

        $my_template->setValue('hot_water_flow', $language_datas['hot_water_flow']);
        $my_template->setValue('hot_water_flow_unit', $units_data[$unit_set->FlowRateUnit]);
        $my_template->setValue('hot_water_flow_value', round($calculation_values['HotWaterFlow'],1));

        $my_template->setValue('hot_in_temp', $language_datas['hot_water_in_temp']);
        $my_template->setValue('hot_in_temp_unit', $units_data[$unit_set->TemperatureUnit]);
        $my_template->setValue('hot_in_temp_value', round($calculation_values['THW1'],1));

        $my_template->setValue('hot_out_temp', $language_datas['hot_water_out_temp']);
        $my_template->setValue('hot_out_temp_unit', $units_data[$unit_set->TemperatureUnit]);
        $my_template->setValue('hot_out_temp_value', round($calculation_values['THW2'],1));

        $my_template->setValue('side_arm_passes', $language_datas['side_arm_passes']);
        $my_template->setValue('side_arm_passes_unit', "No");
        $my_template->setValue('side_arm_passes_value', round($calculation_values['TGP'],1));

        $my_template->setValue('hot_pressure_loss', $language_datas['hot_water_pressure_loss']);
        $my_template->setValue('hot_pressure_loss_unit', $units_data[$unit_set->PressureDropUnit]);
        $my_template->setValue('hot_pressure_loss_value', round($calculation_values['HotWaterFrictionLoss'],1));

        $my_template->setValue('hot_conn_dia', $language_datas['hot_water_connection_dia']);
        $my_template->setValue('hot_conn_dia_unit', $units_data[$unit_set->NozzleDiameterUnit]);
        $my_template->setValue('hot_conn_dia_value', round($calculation_values['HotWaterConnectionDiameter'],1));

        $my_template->setValue('hot_max_work_pressure', $language_datas['max_working_pressure']);
        $my_template->setValue('hot_max_work_pressure_unit', $units_data[$unit_set->WorkPressureUnit]);
        $my_template->setValue('hot_max_work_pressure_value', round($calculation_values['m_maxHWWorkPressure'],1));


        // Direct Circuit
        $my_template->setValue('direct_circuit', $language_datas['direct_fired_circuit']);

        $my_template->setValue('heat_input', $language_datas['heat_input']);
        $my_template->setValue('heat_input_unit', $units_data[$unit_set->HeatUnit]);
        $my_template->setValue('heat_input_value', round($calculation_values['HeatInput'],1));

        $my_template->setValue('fuel_type', $language_datas['fuel_type']);
        $my_template->setValue('fuel_type_value', $calculation_values['GCV']);

        $my_template->setValue('calorific_type', $language_datas['calorific_fuel_type']);
        $my_template->setValue('calorific_type_unit', "GCV");
        $my_template->setValue('calorific_type_value', $calculation_values['CV']);

        $my_template->setValue('calorific_value', $language_datas['calorific_value']);
        if($calculation_values['GCV'] == 'NaturalGas')
            $my_template->setValue('calorific_value_unit', $units_data[$unit_set->CalorificValueGasUnit]);
        else
            $my_template->setValue('calorific_value_unit', $units_data[$unit_set->CalorificValueOilUnit]);

        $my_template->setValue('calorific_value_value', ceil($calculation_values['RCV1']));

        $my_template->setValue('fuel_consumption', $language_datas['fuel_consumption']);
        $my_template->setValue('fuel_consumption_unit', "GCV");
        $my_template->setValue('fuel_consumption_value', round($calculation_values['FuelConsumption'],1));

        $my_template->setValue('gas_duct_size', $language_datas['exhaust_gas_duct_size']);
        $my_template->setValue('gas_duct_size_unit', $units_data[$unit_set->NozzleDiameterUnit]);
        $my_template->setValue('gas_duct_size_value', round($calculation_values['ExhaustDuctSize'],1));

        if($calculation_values['GCV'] == 'NaturalGas'){
            $my_template->setValue('gas_pressure', $language_datas['gas_pressure']);
            $my_template->setValue('gas_pressure_unit', "mbar");
            $my_template->setValue('gas_pressure_value', "100");
        }
        else{
            $my_template->setValue('gas_pressure', "");
            $my_template->setValue('gas_pressure_unit', "");
            $my_template->setValue('gas_pressure_value', "");
        }
        
        // Burner
        $my_template->setValue('burner_rating', $language_datas['burner_rating']);
        $my_template->setValue('burner_rating_kw_value', round($calculation_values['Burner_Rating_kW'],2));
        $my_template->setValue('burner_rating_amp_value', round($calculation_values['Burner_Rating_Amp'],2));
        
        
        $file_name = Auth::user()->id."_CHG2.docx";

        try{
            $my_template->saveAs(storage_path($file_name));
        }catch (Exception $e){
            //handle exception
        }


        return $file_name; 
        

    }


    public function wordFormatG2($user_report_id,$model_code){
        $user_report = UserReport::find($user_report_id);

        $unit_set = UnitSet::find($user_report->unit_set_id);


        $calculation_values = json_decode($user_report->calculation_values,true);
        $date = date('d-M-Y, H:i ', strtotime($user_report->created_at));

        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$model_code)
                                        ->where('min_model','<=',(int)$calculation_values['MODEL'])->where('max_model','>=',(int)$calculation_values['MODEL'])->first();

        $chiller_options = $chiller_metallurgy_options->chillerOptions;
        
        $evaporator_option = $chiller_options->where('type', 'eva')->where('value',$calculation_values['TU2'])->first();
        $absorber_option = $chiller_options->where('type', 'abs')->where('value',$calculation_values['TU5'])->first();
        $condenser_option = $chiller_options->where('type', 'con')->where('value',$calculation_values['TV5'])->first();

        $evaporator_name = $evaporator_option->metallurgy->report_name;
        $absorber_name = $absorber_option->metallurgy->report_name;
        $condenser_name = $condenser_option->metallurgy->report_name;

        $vam_base = new VamBaseController();
        $language_datas = $vam_base->getLanguageDatas();
        $units_data = $vam_base->getUnitsData();

        $calculation_values['client_name'] = $user_report->name;
        $calculation_values['enquiry_name'] = $user_report->phone;
        $calculation_values['project_name'] = $user_report->project;
        $calculation_values['date_time'] = date('d-M-Y, H:i ', strtotime($user_report->created_at));
        $calculation_values['evaporator_name'] = $evaporator_name;
        $calculation_values['absorber_name'] = $absorber_name;
        $calculation_values['condenser_name'] = $condenser_name;
        $calculation_values['GL'] = $calculation_values['GLL'];

        if($calculation_values['region_type'] ==2){
            $my_template = new \PhpOffice\PhpWord\TemplateProcessor(storage_path('report_template/G2_USA.docx'));
        }
        else{
            $my_template = new \PhpOffice\PhpWord\TemplateProcessor(storage_path('report_template/G2.docx'));
        }
        


        $my_template = $this->commonTemplate($my_template, $language_datas, $calculation_values, $units_data, $unit_set);


        // Direct Circuit
        $my_template->setValue('direct_circuit', $language_datas['direct_fired_circuit']);

        $my_template->setValue('heat_input', $language_datas['heat_input']);
        $my_template->setValue('heat_input_unit', $units_data[$unit_set->HeatUnit]);
        $my_template->setValue('heat_input_value', round($calculation_values['HeatInput'],1));

        $my_template->setValue('fuel_type', $language_datas['fuel_type']);
        $my_template->setValue('fuel_type_value', $calculation_values['GCV']);

        $my_template->setValue('calorific_type', $language_datas['calorific_fuel_type']);
        $my_template->setValue('calorific_type_unit', "GCV");
        $my_template->setValue('calorific_type_value', $calculation_values['CV']);

        $my_template->setValue('calorific_value', $language_datas['calorific_value']);
        if($calculation_values['GCV'] == 'NaturalGas')
            $my_template->setValue('calorific_value_unit', $units_data[$unit_set->CalorificValueGasUnit]);
        else
            $my_template->setValue('calorific_value_unit', $units_data[$unit_set->CalorificValueOilUnit]);

        $my_template->setValue('calorific_value_value', ceil($calculation_values['RCV1']));

        $my_template->setValue('fuel_consumption', $language_datas['fuel_consumption']);
        $my_template->setValue('fuel_consumption_unit', "GCV");
        $my_template->setValue('fuel_consumption_value', round($calculation_values['FuelConsumption'],1));

        $my_template->setValue('gas_duct_size', $language_datas['exhaust_gas_duct_size']);
        $my_template->setValue('gas_duct_size_unit', $units_data[$unit_set->NozzleDiameterUnit]);
        $my_template->setValue('gas_duct_size_value', round($calculation_values['ExhaustDuctSize'],1));

        if($calculation_values['GCV'] == 'NaturalGas'){
            $my_template->setValue('gas_pressure', $language_datas['gas_pressure']);
            $my_template->setValue('gas_pressure_unit', "mbar");
            $my_template->setValue('gas_pressure_value', "100");
        }
        else{
            $my_template->setValue('gas_pressure', "");
            $my_template->setValue('gas_pressure_unit', "");
            $my_template->setValue('gas_pressure_value', "");
        }
        
        // Burner
        $my_template->setValue('burner_rating', $language_datas['burner_rating']);
        $my_template->setValue('burner_rating_kw_value', round($calculation_values['Burner_Rating_kW'],2));
        $my_template->setValue('burner_rating_amp_value', round($calculation_values['Burner_Rating_Amp'],2));
        
        
        $file_name = Auth::user()->id."_G2.docx";

        try{
            $my_template->saveAs(storage_path($file_name));
        }catch (Exception $e){
            //handle exception
        }


        return $file_name; 
        

    }

     public function wordFormatH2($user_report_id,$model_code){
        $user_report = UserReport::find($user_report_id);

        $unit_set = UnitSet::find($user_report->unit_set_id);


        $calculation_values = json_decode($user_report->calculation_values,true);
        $date = date('d-M-Y, H:i ', strtotime($user_report->created_at));

        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$model_code)
                                        ->where('min_model','<=',(int)$calculation_values['MODEL'])->where('max_model','>=',(int)$calculation_values['MODEL'])->first();

        $chiller_options = $chiller_metallurgy_options->chillerOptions;
        
        $evaporator_option = $chiller_options->where('type', 'eva')->where('value',$calculation_values['TU2'])->first();
        $absorber_option = $chiller_options->where('type', 'abs')->where('value',$calculation_values['TU5'])->first();
        $condenser_option = $chiller_options->where('type', 'con')->where('value',$calculation_values['TV5'])->first();

        $evaporator_name = $evaporator_option->metallurgy->report_name;
        $absorber_name = $absorber_option->metallurgy->report_name;
        $condenser_name = $condenser_option->metallurgy->report_name;

        $vam_base = new VamBaseController();
        $language_datas = $vam_base->getLanguageDatas();
        $units_data = $vam_base->getUnitsData();

        $calculation_values['client_name'] = $user_report->name;
        $calculation_values['enquiry_name'] = $user_report->phone;
        $calculation_values['project_name'] = $user_report->project;
        $calculation_values['date_time'] = date('d-M-Y, H:i ', strtotime($user_report->created_at));
        $calculation_values['evaporator_name'] = $evaporator_name;
        $calculation_values['absorber_name'] = $absorber_name;
        $calculation_values['condenser_name'] = $condenser_name;

        if($calculation_values['region_type'] ==2){
            $my_template = new \PhpOffice\PhpWord\TemplateProcessor(storage_path('report_template/H2_USA.docx'));
        }
        else{
            $my_template = new \PhpOffice\PhpWord\TemplateProcessor(storage_path('report_template/H2.docx'));
        }
        


        $my_template = $this->commonTemplate($my_template, $language_datas, $calculation_values, $units_data, $unit_set);


        // Hot Water Circuit
        $my_template->setValue('hot_water_circuit', $language_datas['hot_water_circuit']);

        $my_template->setValue('heat_input', $language_datas['heat_input']);
        $my_template->setValue('heat_input_unit', $units_data[$unit_set->HeatUnit]);
        $my_template->setValue('heat_input_value', round($calculation_values['HeatInput'],1));

        $my_template->setValue('hot_water_flow', $language_datas['hot_water_flow']);
        $my_template->setValue('hot_water_flow_unit', $units_data[$unit_set->FlowRateUnit]);
        $my_template->setValue('hot_water_flow_value', round($calculation_values['HotWaterFlow'],1));

        $my_template->setValue('hot_water_in_temp', $language_datas['hot_water_in_temp']);
        $my_template->setValue('hot_water_in_temp_unit', $units_data[$unit_set->TemperatureUnit]);
        $my_template->setValue('hot_water_in_temp_value', round($calculation_values['hot_water_in'],1));

        $my_template->setValue('hot_water_out_temp', $language_datas['hot_water_out_temp']);
        $my_template->setValue('hot_water_out_temp_unit', $units_data[$unit_set->TemperatureUnit]);
        $my_template->setValue('hot_water_out_temp_value', ceil($calculation_values['hot_water_out']));

        $my_template->setValue('generator_passes', $language_datas['generator_passes']);
        $my_template->setValue('generator_passes_value', round($calculation_values['GeneratorPasses'],1));

        $my_template->setValue('hot_water_pressure_loss', $language_datas['hot_water_pressure_loss']);
        $my_template->setValue('hot_water_pressure_loss_unit', $units_data[$unit_set->PressureDropUnit]);
        $my_template->setValue('hot_water_pressure_loss_value', round($calculation_values['HotWaterFrictionLoss'],1));

        $my_template->setValue('hot_water_conn_dia', $language_datas['hot_water_connection_dia']);
        $my_template->setValue('hot_water_conn_dia_unit', $units_data[$unit_set->NozzleDiameterUnit]);
        $my_template->setValue('hot_water_conn_dia_value', round($calculation_values['HotWaterConnectionDiameter'],1));

        $my_template->setValue('hot_water_max_pressure', $language_datas['max_working_pressure']);
        $my_template->setValue('hot_water_max_pressure_unit', $units_data[$unit_set->AllWorkPrHWUnit]);
        $my_template->setValue('hot_water_max_pressure_value', round($calculation_values['all_work_pr_hw'],1));
        
        
        $file_name = Auth::user()->id."_H2.docx";

        try{
            $my_template->saveAs(storage_path($file_name));
        }catch (Exception $e){
            //handle exception
        }


        return $file_name; 
        
    }

    public function wordFormatH1($user_report_id,$model_code){
    
        $user_report = UserReport::find($user_report_id);

        $unit_set = UnitSet::find($user_report->unit_set_id);


        $calculation_values = json_decode($user_report->calculation_values,true);
        $date = date('d-M-Y, H:i ', strtotime($user_report->created_at));

        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$model_code)
                                        ->where('min_model','<=',(int)$calculation_values['MODEL'])->where('max_model','>=',(int)$calculation_values['MODEL'])->first();

        $chiller_options = $chiller_metallurgy_options->chillerOptions;
        
        $evaporator_option = $chiller_options->where('type', 'eva')->where('value',$calculation_values['TU2'])->first();
        $absorber_option = $chiller_options->where('type', 'abs')->where('value',$calculation_values['TU5'])->first();
        $condenser_option = $chiller_options->where('type', 'con')->where('value',$calculation_values['TV5'])->first();

        $evaporator_name = $evaporator_option->metallurgy->report_name;
        $absorber_name = $absorber_option->metallurgy->report_name;
        $condenser_name = $condenser_option->metallurgy->report_name;

        $vam_base = new VamBaseController();
        $language_datas = $vam_base->getLanguageDatas();
        $units_data = $vam_base->getUnitsData();

        $calculation_values['client_name'] = $user_report->name;
        $calculation_values['enquiry_name'] = $user_report->phone;
        $calculation_values['project_name'] = $user_report->project;
        $calculation_values['date_time'] = date('d-M-Y, H:i ', strtotime($user_report->created_at));
        $calculation_values['evaporator_name'] = $evaporator_name;
        $calculation_values['absorber_name'] = $absorber_name;
        $calculation_values['condenser_name'] = $condenser_name;
        $calculation_values['GL'] = $calculation_values['GLL'];


        if($calculation_values['region_type'] ==2){
            $my_template = new \PhpOffice\PhpWord\TemplateProcessor(storage_path('report_template/H1_USA.docx'));
        }
        else{
            $my_template = new \PhpOffice\PhpWord\TemplateProcessor(storage_path('report_template/H1.docx'));
        }
        


        $my_template = $this->commonTemplate($my_template, $language_datas, $calculation_values, $units_data, $unit_set);


        // Hot Water Circuit
        $my_template->setValue('hot_water_circuit', $language_datas['hot_water_circuit']);

        $my_template->setValue('heat_input', $language_datas['heat_input']);
        $my_template->setValue('heat_input_unit', $units_data[$unit_set->HeatUnit]);
        $my_template->setValue('heat_input_value', round($calculation_values['HeatInput'],1));

        $my_template->setValue('hot_water_flow', $language_datas['hot_water_flow']);
        $my_template->setValue('hot_water_flow_unit', $units_data[$unit_set->FlowRateUnit]);
        $my_template->setValue('hot_water_flow_value', round($calculation_values['HotWaterFlow'],1));

        $my_template->setValue('hot_water_in_temp', $language_datas['hot_water_in_temp']);
        $my_template->setValue('hot_water_in_temp_unit', $units_data[$unit_set->TemperatureUnit]);
        $my_template->setValue('hot_water_in_temp_value', round($calculation_values['THW1'],1));

        $my_template->setValue('hot_water_out_temp', $language_datas['hot_water_out_temp']);
        $my_template->setValue('hot_water_out_temp_unit', $units_data[$unit_set->TemperatureUnit]);
        $my_template->setValue('hot_water_out_temp_value', ceil($calculation_values['THW2']));

        $my_template->setValue('generator_passes', $language_datas['generator_passes']);
        $my_template->setValue('generator_passes_value', round($calculation_values['GeneratorPasses'],1));

        $my_template->setValue('hot_water_pressure_loss', $language_datas['hot_water_pressure_loss']);
        $my_template->setValue('hot_water_pressure_loss_unit', $units_data[$unit_set->PressureDropUnit]);
        $my_template->setValue('hot_water_pressure_loss_value', round($calculation_values['HotWaterFrictionLoss'],1));

        $my_template->setValue('hot_water_conn_dia', $language_datas['hot_water_connection_dia']);
        $my_template->setValue('hot_water_conn_dia_unit', $units_data[$unit_set->NozzleDiameterUnit]);
        $my_template->setValue('hot_water_conn_dia_value', round($calculation_values['HotWaterConnectionDiameter'],1));

        $my_template->setValue('hot_water_max_pressure', $language_datas['max_working_pressure']);
        $my_template->setValue('hot_water_max_pressure_unit', $units_data[$unit_set->AllWorkPrHWUnit]);
        $my_template->setValue('hot_water_max_pressure_value', round($calculation_values['all_work_pr_hw'],1));
        
        
        $file_name = Auth::user()->id."_H1.docx";

        try{
            $my_template->saveAs(storage_path($file_name));
        }catch (Exception $e){
            //handle exception
        }


        return $file_name;        
        
    }


    public function wordFormatL5($user_report_id,$model_code){
        $user_report = UserReport::find($user_report_id);

        $unit_set = UnitSet::find($user_report->unit_set_id);


        $calculation_values = json_decode($user_report->calculation_values,true);
        $date = date('d-M-Y, H:i ', strtotime($user_report->created_at));

        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$model_code)
                                        ->where('min_model','<=',(int)$calculation_values['MODEL'])->where('max_model','>=',(int)$calculation_values['MODEL'])->first();

        $chiller_options = $chiller_metallurgy_options->chillerOptions;
        
        $evaporator_option = $chiller_options->where('type', 'eva')->where('value',$calculation_values['TU2'])->first();
        $absorber_option = $chiller_options->where('type', 'abs')->where('value',$calculation_values['TU5'])->first();
        $condenser_option = $chiller_options->where('type', 'con')->where('value',$calculation_values['TV5'])->first();

        $evaporator_name = $evaporator_option->metallurgy->report_name;
        $absorber_name = $absorber_option->metallurgy->report_name;
        $condenser_name = $condenser_option->metallurgy->report_name;

        $vam_base = new VamBaseController();
        $language_datas = $vam_base->getLanguageDatas();
        $units_data = $vam_base->getUnitsData();

        $calculation_values['client_name'] = $user_report->name;
        $calculation_values['enquiry_name'] = $user_report->phone;
        $calculation_values['project_name'] = $user_report->project;
        $calculation_values['date_time'] = date('d-M-Y, H:i ', strtotime($user_report->created_at));
        $calculation_values['evaporator_name'] = $evaporator_name;
        $calculation_values['absorber_name'] = $absorber_name;
        $calculation_values['condenser_name'] = $condenser_name;
        $calculation_values['TCHW11'] = $calculation_values['TCHW1H'];
        $calculation_values['TCHW12'] = $calculation_values['TCHW2L'];
        $calculation_values['USA_AbsorbentPumpMotorKW'] = $calculation_values['USA_HPAbsorbentPumpMotorKW'];
        $calculation_values['USA_AbsorbentPumpMotorAmp'] = $calculation_values['USA_HPAbsorbentPumpMotorAmp'];
        $calculation_values['AbsorbentPumpMotorKW'] = $calculation_values['HPAbsorbentPumpMotorKW'];
        $calculation_values['AbsorbentPumpMotorAmp'] = $calculation_values['HPAbsorbentPumpMotorAmp'];


        if($calculation_values['region_type'] ==2){
            $my_template = new \PhpOffice\PhpWord\TemplateProcessor(storage_path('report_template/L5_USA.docx'));
        }
        else{
            $my_template = new \PhpOffice\PhpWord\TemplateProcessor(storage_path('report_template/L5.docx'));
        }
        


        $my_template = $this->commonTemplate($my_template, $language_datas, $calculation_values, $units_data, $unit_set);


        // Hot Water Circuit
        $my_template->setValue('hot_water_circuit', $language_datas['hot_water_circuit']);

        $my_template->setValue('heat_input', $language_datas['heat_input']);
        $my_template->setValue('heat_input_unit', $units_data[$unit_set->HeatUnit]);
        $my_template->setValue('heat_input_value', round($calculation_values['HeatInput'],1));

        $my_template->setValue('hot_water_flow', $language_datas['hot_water_flow']);
        $my_template->setValue('hot_water_flow_unit', $units_data[$unit_set->FlowRateUnit]);
        $my_template->setValue('hot_water_flow_value', round($calculation_values['GHOT'],1));

        $my_template->setValue('hot_water_in_temp', $language_datas['hot_water_in_temp']);
        $my_template->setValue('hot_water_in_temp_unit', $units_data[$unit_set->TemperatureUnit]);
        $my_template->setValue('hot_water_in_temp_value', round($calculation_values['THW1'],1));

        $my_template->setValue('hot_water_out_temp', $language_datas['hot_water_out_temp']);
        $my_template->setValue('hot_water_out_temp_unit', $units_data[$unit_set->TemperatureUnit]);
        $my_template->setValue('hot_water_out_temp_value', ceil($calculation_values['THW4']));

        $my_template->setValue('generator_passes', $language_datas['generator_passes']);
        $my_template->setValue('generator_passes_value', ($calculation_values['TGP']." + ".$calculation_values['TGP']));

        $my_template->setValue('hot_water_pressure_loss', $language_datas['hot_water_pressure_loss']);
        $my_template->setValue('hot_water_pressure_loss_unit', $units_data[$unit_set->PressureDropUnit]);
        $my_template->setValue('hot_water_pressure_loss_value', round($calculation_values['HotWaterFrictionLoss'],1));

        $my_template->setValue('hot_water_conn_dia', $language_datas['hot_water_connection_diameter']);
        $my_template->setValue('hot_water_conn_dia_unit', $units_data[$unit_set->NozzleDiameterUnit]);
        $my_template->setValue('hot_water_conn_dia_value', round($calculation_values['GENNB'],1));

        $my_template->setValue('hot_water_glycol', $language_datas['hot_water_glycol']);
        $my_template->setValue('hot_water_glycol_value', round($calculation_values['HWGLY'],1));

        $my_template->setValue('hot_water_max_pressure', $language_datas['max_working_pressure']);
        $my_template->setValue('hot_water_max_pressure_unit', $units_data[$unit_set->WorkPressureUnit]);
        $my_template->setValue('hot_water_max_pressure_value', round($calculation_values['m_maxHWWorkPressure'],1));

        $my_template->setValue('hot_water_fouling_factor', $language_datas['hot_water_fouling_factor']);
        $my_template->setValue('hot_water_fouling_factor_unit', $units_data[$unit_set->FoulingFactorUnit]);
        if($calculation_values['TUU'] == "standard")
            $my_template->setValue('hot_water_fouling_factor_value', $calculation_values['TUU']);
        else if($calculation_values['TUU'] == "ari")
            $my_template->setValue('hot_water_fouling_factor_value', "standard");
        else
            $my_template->setValue('hot_water_fouling_factor_value', $calculation_values['FFHOW1']);

        // Generator Tube
        $my_template->setValue('generator_tube', $language_datas['generator_tube_material']);
        if($calculation_values['TG2'] == 1){
            $my_template->setValue('generator_tube_value', "Copper");
        }
        else{
            $my_template->setValue('generator_tube_value', "SS316L ERW");
        }
        
        // LP Absorbent Tube
        $my_template->setValue('lp_absorbent_pump_rating', $language_datas['lp_absorbent_pump_rating']);
        if($calculation_values['region_type'] ==2){
            $my_template->setValue('lp_absorbent_pump_rating_kw_value', round($calculation_values['USA_LPAbsorbentPumpMotorKW'],2));
            $my_template->setValue('lp_absorbent_pump_rating_amp_value', round($calculation_values['USA_LPAbsorbentPumpMotorAmp'],2));
        }
        else{
            $my_template->setValue('lp_absorbent_pump_rating_kw_value', round($calculation_values['LPAbsorbentPumpMotorKW'],2));
            $my_template->setValue('lp_absorbent_pump_rating_amp_value', round($calculation_values['LPAbsorbentPumpMotorAmp'],2));
        }

        
        $file_name = Auth::user()->id."_L5.docx";

        try{
            $my_template->saveAs(storage_path($file_name));
        }catch (Exception $e){
            //handle exception
        }


        return $file_name;
        

    }

    public function wordFormatL1($user_report_id,$model_code){
        
        $user_report = UserReport::find($user_report_id);

        $unit_set = UnitSet::find($user_report->unit_set_id);


        $calculation_values = json_decode($user_report->calculation_values,true);
        $date = date('d-M-Y, H:i ', strtotime($user_report->created_at));

        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$model_code)
                                        ->where('min_model','<=',(int)$calculation_values['MODEL'])->where('max_model','>=',(int)$calculation_values['MODEL'])->first();

        $chiller_options = $chiller_metallurgy_options->chillerOptions;
        
        $evaporator_option = $chiller_options->where('type', 'eva')->where('value',$calculation_values['TU2'])->first();
        $absorber_option = $chiller_options->where('type', 'abs')->where('value',$calculation_values['TU5'])->first();
        $condenser_option = $chiller_options->where('type', 'con')->where('value',$calculation_values['TV5'])->first();

        $evaporator_name = $evaporator_option->metallurgy->report_name;
        $absorber_name = $absorber_option->metallurgy->report_name;
        $condenser_name = $condenser_option->metallurgy->report_name;

        $vam_base = new VamBaseController();
        $language_datas = $vam_base->getLanguageDatas();
        $units_data = $vam_base->getUnitsData();

        $calculation_values['client_name'] = $user_report->name;
        $calculation_values['enquiry_name'] = $user_report->phone;
        $calculation_values['project_name'] = $user_report->project;
        $calculation_values['date_time'] = date('d-M-Y, H:i ', strtotime($user_report->created_at));
        $calculation_values['evaporator_name'] = $evaporator_name;
        $calculation_values['absorber_name'] = $absorber_name;
        $calculation_values['condenser_name'] = $condenser_name;


        if($calculation_values['region_type'] ==2){
            $my_template = new \PhpOffice\PhpWord\TemplateProcessor(storage_path('report_template/L1_USA.docx'));
        }
        else{
            $my_template = new \PhpOffice\PhpWord\TemplateProcessor(storage_path('report_template/L1.docx'));
        }
        


        $my_template = $this->commonTemplate($my_template, $language_datas, $calculation_values, $units_data, $unit_set);


        // Hot Water Circuit
        $my_template->setValue('hot_water_circuit', $language_datas['hot_water_circuit']);

        $my_template->setValue('heat_input', $language_datas['heat_input']);
        $my_template->setValue('heat_input_unit', $units_data[$unit_set->HeatUnit]);
        $my_template->setValue('heat_input_value', round($calculation_values['HeatInput'],1));

        $my_template->setValue('hot_water_flow', $language_datas['hot_water_flow']);
        $my_template->setValue('hot_water_flow_unit', $units_data[$unit_set->FlowRateUnit]);
        $my_template->setValue('hot_water_flow_value', round($calculation_values['GHOT'],1));

        $my_template->setValue('hot_water_in_temp', $language_datas['hot_water_in_temp']);
        $my_template->setValue('hot_water_in_temp_unit', $units_data[$unit_set->TemperatureUnit]);
        $my_template->setValue('hot_water_in_temp_value', round($calculation_values['THW1'],1));

        $my_template->setValue('hot_water_out_temp', $language_datas['hot_water_out_temp']);
        $my_template->setValue('hot_water_out_temp_unit', $units_data[$unit_set->TemperatureUnit]);
        $my_template->setValue('hot_water_out_temp_value', ceil($calculation_values['THW2']));

        $my_template->setValue('generator_passes', $language_datas['generator_passes']);
        $my_template->setValue('generator_passes_value', round($calculation_values['TGP'],1));

        $my_template->setValue('hot_water_pressure_loss', $language_datas['hot_water_pressure_loss']);
        $my_template->setValue('hot_water_pressure_loss_unit', $units_data[$unit_set->PressureDropUnit]);
        $my_template->setValue('hot_water_pressure_loss_value', round($calculation_values['HotWaterFrictionLoss'],1));

        $my_template->setValue('hot_water_conn_dia', $language_datas['hot_water_connection_diameter']);
        $my_template->setValue('hot_water_conn_dia_unit', $units_data[$unit_set->NozzleDiameterUnit]);
        $my_template->setValue('hot_water_conn_dia_value', round($calculation_values['GENNB'],1));

        $my_template->setValue('hot_water_glycol', $language_datas['hot_water_glycol']);
        $my_template->setValue('hot_water_glycol_value', round($calculation_values['HWGLY'],1));

        $my_template->setValue('hot_water_max_pressure', $language_datas['max_working_pressure']);
        $my_template->setValue('hot_water_max_pressure_unit', $units_data[$unit_set->WorkPressureUnit]);
        $my_template->setValue('hot_water_max_pressure_value', round($calculation_values['m_maxHWWorkPressure'],1));

        $my_template->setValue('hot_water_fouling_factor', $language_datas['hot_water_fouling_factor']);
        $my_template->setValue('hot_water_fouling_factor_unit', $units_data[$unit_set->FoulingFactorUnit]);
        if($calculation_values['TUU'] == "standard")
            $my_template->setValue('hot_water_fouling_factor_value', $calculation_values['TUU']);
        else if($calculation_values['TUU'] == "ari")
            $my_template->setValue('hot_water_fouling_factor_value', "standard");
        else
            $my_template->setValue('hot_water_fouling_factor_value', $calculation_values['FFHOW1']);

        // Generator Tube
        $my_template->setValue('generator_tube', $language_datas['generator_tube_material']);
        if($calculation_values['TG2'] == 1){
            $my_template->setValue('generator_tube_value', "Copper");
        }
        else{
            $my_template->setValue('generator_tube_value', "SS316L ERW");
        }
        
       
        $file_name = Auth::user()->id."_L1.docx";

        try{
            $my_template->saveAs(storage_path($file_name));
        }catch (Exception $e){
            //handle exception
        }


        return $file_name;

    }

    public function wordFormatCHS2($user_report_id,$model_code){
        $user_report = UserReport::find($user_report_id);

        $unit_set = UnitSet::find($user_report->unit_set_id);


        $calculation_values = json_decode($user_report->calculation_values,true);
        $date = date('d-M-Y, H:i ', strtotime($user_report->created_at));

        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$model_code)
                                        ->where('min_model','<=',(int)$calculation_values['MODEL'])->where('max_model','>=',(int)$calculation_values['MODEL'])->first();

        $chiller_options = $chiller_metallurgy_options->chillerOptions;
        
        $evaporator_option = $chiller_options->where('type', 'eva')->where('value',$calculation_values['TU2'])->first();
        $absorber_option = $chiller_options->where('type', 'abs')->where('value',$calculation_values['TU5'])->first();
        $condenser_option = $chiller_options->where('type', 'con')->where('value',$calculation_values['TV5'])->first();

        $evaporator_name = $evaporator_option->metallurgy->report_name;
        $absorber_name = $absorber_option->metallurgy->report_name;
        $condenser_name = $condenser_option->metallurgy->report_name;

        $vam_base = new VamBaseController();
        $language_datas = $vam_base->getLanguageDatas();
        $units_data = $vam_base->getUnitsData();

        $calculation_values['client_name'] = $user_report->name;
        $calculation_values['enquiry_name'] = $user_report->phone;
        $calculation_values['project_name'] = $user_report->project;
        $calculation_values['date_time'] = date('d-M-Y, H:i ', strtotime($user_report->created_at));
        $calculation_values['evaporator_name'] = $evaporator_name;
        $calculation_values['absorber_name'] = $absorber_name;
        $calculation_values['condenser_name'] = $condenser_name;


        if($calculation_values['region_type'] ==2){
            $my_template = new \PhpOffice\PhpWord\TemplateProcessor(storage_path('report_template/CHS2_USA.docx'));
        }
        else{
            $my_template = new \PhpOffice\PhpWord\TemplateProcessor(storage_path('report_template/CHS2.docx'));
        }
        


        $my_template = $this->commonTemplate($my_template, $language_datas, $calculation_values, $units_data, $unit_set);


        // Hot Water Circuit
        $my_template->setValue('hot_water_circuit', $language_datas['hot_water_circuit']);

        $my_template->setValue('heat_duty', $language_datas['heat_duty']);
        $my_template->setValue('heat_duty_unit', $units_data[$unit_set->HeatUnit]);
        $my_template->setValue('heat_duty_value', round($calculation_values['HEATCAP'],1));

        $my_template->setValue('hot_water_flow', $language_datas['hot_water_flow']);
        $my_template->setValue('hot_water_flow_unit', $units_data[$unit_set->FlowRateUnit]);
        $my_template->setValue('hot_water_flow_value', round($calculation_values['HotWaterFlow'],1));

        $my_template->setValue('hot_water_in_temp', $language_datas['hot_water_in_temp']);
        $my_template->setValue('hot_water_in_temp_unit', $units_data[$unit_set->TemperatureUnit]);
        $my_template->setValue('hot_water_in_temp_value', round($calculation_values['THW1'],1));

        $my_template->setValue('hot_water_out_temp', $language_datas['hot_water_out_temp']);
        $my_template->setValue('hot_water_out_temp_unit', $units_data[$unit_set->TemperatureUnit]);
        $my_template->setValue('hot_water_out_temp_value', ceil($calculation_values['THW2']));

        $my_template->setValue('side_arm_passes', $language_datas['side_arm_passes']);
        $my_template->setValue('side_arm_passes_value', round($calculation_values['TGP'],1));

        $my_template->setValue('hot_water_pressure_loss', $language_datas['hot_water_pressure_loss']);
        $my_template->setValue('hot_water_pressure_loss_unit', $units_data[$unit_set->PressureDropUnit]);
        $my_template->setValue('hot_water_pressure_loss_value', round($calculation_values['HotWaterFrictionLoss'],1));

        $my_template->setValue('hot_water_conn_dia', $language_datas['hot_water_connection_dia']);
        $my_template->setValue('hot_water_conn_dia_unit', $units_data[$unit_set->NozzleDiameterUnit]);
        $my_template->setValue('hot_water_conn_dia_value', round($calculation_values['HotWaterConnectionDiameter'],1));

        $my_template->setValue('hw_max_working_pressure', $language_datas['max_working_pressure']);
        $my_template->setValue('hw_max_working_pressure_unit', $units_data[$unit_set->WorkPressureUnit]);
        $my_template->setValue('hw_max_working_pressure_value', round($calculation_values['m_maxHWWorkPressure'],1));

        // Steam
        $my_template->setValue('steam_circuit', $language_datas['steam_circuit']);

        $my_template->setValue('heat_input', $language_datas['heat_input']);
        $my_template->setValue('heat_input_unit', $units_data[$unit_set->HeatUnit]);
        $my_template->setValue('heat_input_value', round($calculation_values['HeatInput'],1));

        $my_template->setValue('steam_pressure', $language_datas['steam_pressure']);
        $my_template->setValue('steam_pressure_unit', $units_data[$unit_set->PressureUnit]);
        $my_template->setValue('steam_pressure_value', round($calculation_values['PST1'],1));

        $my_template->setValue('steam_consumption', $language_datas['steam_consumption']);
        $my_template->setValue('steam_consumption_unit', $units_data[$unit_set->SteamConsumptionUnit]);
        $my_template->setValue('steam_consumption_value', round($calculation_values['SteamConsumption'],1));

        $my_template->setValue('condensate_drain_temperature', $language_datas['condensate_drain_temperature']);
        $my_template->setValue('condensate_drain_temperature_unit', $units_data[$unit_set->TemperatureUnit]);
        $my_template->setValue('condensate_drain_temperature_min_value', round($calculation_values['m_dMinCondensateDrainTemperature'],1));
        $my_template->setValue('condensate_drain_temperature_max_value', round($calculation_values['m_dMaxCondensateDrainTemperature'],1));

        $my_template->setValue('condensate_drain_pressure', $language_datas['condensate_drain_pressure']);
        $my_template->setValue('condensate_drain_pressure_unit', $units_data[$unit_set->PressureUnit]);
        $my_template->setValue('condensate_drain_pressure_value', round($calculation_values['m_dCondensateDrainPressure'],1));


        $my_template->setValue('connection_inlet_diameter', $language_datas['connection_inlet_dia']);
        $my_template->setValue('connection_inlet_diameter_unit', $units_data[$unit_set->NozzleDiameterUnit]);
        $my_template->setValue('connection_inlet_diameter_value', round($calculation_values['SteamConnectionDiameter'],1));

        $my_template->setValue('connection_drain_diameter', $language_datas['connection_drain_dia']);
        $my_template->setValue('connection_drain_diameter_unit', $units_data[$unit_set->NozzleDiameterUnit]);
        $my_template->setValue('connection_drain_diameter_value', round($calculation_values['SteamDrainDiameter'],1));

        $my_template->setValue('design_pressure', $language_datas['design_pressure']);
        $my_template->setValue('design_pressure_unit', $units_data[$unit_set->PressureUnit]);
        $my_template->setValue('design_pressure_value', round($calculation_values['m_DesignPressure'],1));

        $file_name = Auth::user()->id."_CHS2.docx";

        try{
            $my_template->saveAs(storage_path($file_name));
        }catch (Exception $e){
            //handle exception
        }

        // /* Set the PDF Engine Renderer Path */
        // $domPdfPath = base_path('vendor/dompdf/dompdf');
        // \PhpOffice\PhpWord\Settings::setPdfRendererPath($domPdfPath);
        // \PhpOffice\PhpWord\Settings::setPdfRendererName('DomPDF');
         
        // //Load word file
        // $Content = \PhpOffice\PhpWord\IOFactory::load(storage_path($file_name)); 
    
        // //Save it into PDF
        // $PDFWriter = \PhpOffice\PhpWord\IOFactory::createWriter($Content,'PDF');
        // $PDFWriter->save(public_path('new-result.pdf'));


        return $file_name;

    }

    public function wordFormatS1($user_report_id,$model_code){

        $user_report = UserReport::find($user_report_id);

        $unit_set = UnitSet::find($user_report->unit_set_id);


        $calculation_values = json_decode($user_report->calculation_values,true);
        $date = date('d-M-Y, H:i ', strtotime($user_report->created_at));

        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$model_code)
                                        ->where('min_model','<=',(int)$calculation_values['MODEL'])->where('max_model','>=',(int)$calculation_values['MODEL'])->first();

        $chiller_options = $chiller_metallurgy_options->chillerOptions;
        
        $evaporator_option = $chiller_options->where('type', 'eva')->where('value',$calculation_values['TU2'])->first();
        $absorber_option = $chiller_options->where('type', 'abs')->where('value',$calculation_values['TU5'])->first();
        $condenser_option = $chiller_options->where('type', 'con')->where('value',$calculation_values['TV5'])->first();

        $evaporator_name = $evaporator_option->metallurgy->report_name;
        $absorber_name = $absorber_option->metallurgy->report_name;
        $condenser_name = $condenser_option->metallurgy->report_name;

        $vam_base = new VamBaseController();
        $language_datas = $vam_base->getLanguageDatas();
        $units_data = $vam_base->getUnitsData();

        $calculation_values['client_name'] = $user_report->name;
        $calculation_values['enquiry_name'] = $user_report->phone;
        $calculation_values['project_name'] = $user_report->project;
        $calculation_values['date_time'] = date('d-M-Y, H:i ', strtotime($user_report->created_at));
        $calculation_values['evaporator_name'] = $evaporator_name;
        $calculation_values['absorber_name'] = $absorber_name;
        $calculation_values['condenser_name'] = $condenser_name;


        if($calculation_values['region_type'] ==2){
            $my_template = new \PhpOffice\PhpWord\TemplateProcessor(storage_path('report_template/S1_USA.docx'));
        }
        else{
            $my_template = new \PhpOffice\PhpWord\TemplateProcessor(storage_path('report_template/S1.docx'));
        }
        


        $my_template = $this->commonTemplate($my_template, $language_datas, $calculation_values, $units_data, $unit_set);


        // Steam
        $my_template->setValue('steam_circuit', $language_datas['steam_circuit']);

        $my_template->setValue('heat_input', $language_datas['heat_input']);
        $my_template->setValue('heat_input_unit', $units_data[$unit_set->HeatUnit]);
        $my_template->setValue('heat_input_value', round($calculation_values['HeatInput'],1));

        $my_template->setValue('steam_pressure', $language_datas['steam_pressure']);
        $my_template->setValue('steam_pressure_unit', $units_data[$unit_set->PressureUnit]);
        $my_template->setValue('steam_pressure_value', round($calculation_values['PST1'],1));

        $my_template->setValue('steam_consumption', $language_datas['steam_consumption']);
        $my_template->setValue('steam_consumption_unit', $units_data[$unit_set->SteamConsumptionUnit]);
        $my_template->setValue('steam_consumption_value', round($calculation_values['SteamConsumption'],1));

        $my_template->setValue('condensate_drain_temperature', $language_datas['condensate_drain_temperature']);
        $my_template->setValue('condensate_drain_temperature_unit', $units_data[$unit_set->TemperatureUnit]);
        $my_template->setValue('condensate_drain_temperature_min_value', round($calculation_values['m_dMinCondensateDrainTemperature'],1));
        $my_template->setValue('condensate_drain_temperature_max_value', round($calculation_values['m_dMaxCondensateDrainTemperature'],1));

        $my_template->setValue('condensate_drain_pressure', $language_datas['condensate_drain_pressure']);
        $my_template->setValue('condensate_drain_pressure_unit', $units_data[$unit_set->PressureUnit]);
        $my_template->setValue('condensate_drain_pressure_value', round($calculation_values['m_dCondensateDrainPressure'],1));


        $my_template->setValue('connection_inlet_diameter', $language_datas['connection_inlet_dia']);
        $my_template->setValue('connection_inlet_diameter_unit', $units_data[$unit_set->NozzleDiameterUnit]);
        $my_template->setValue('connection_inlet_diameter_value', round($calculation_values['SteamConnectionDiameter'],1));

        $my_template->setValue('connection_drain_diameter', $language_datas['connection_drain_dia']);
        $my_template->setValue('connection_drain_diameter_unit', $units_data[$unit_set->NozzleDiameterUnit]);
        $my_template->setValue('connection_drain_diameter_value', round($calculation_values['SteamDrainDiameter'],1));

        $my_template->setValue('design_pressure', $language_datas['design_pressure']);
        $my_template->setValue('design_pressure_unit', $units_data[$unit_set->PressureUnit]);
        $my_template->setValue('design_pressure_value', round($calculation_values['m_DesignPressure'],1));

        $file_name = Auth::user()->id."_s1.docx";

        try{
            $my_template->saveAs(storage_path($file_name));
        }catch (Exception $e){
            //handle exception
        }



        return $file_name;
    }

    public function wordFormatS2($user_report_id,$model_code){
        $user_report = UserReport::find($user_report_id);

        $unit_set = UnitSet::find($user_report->unit_set_id);


        $calculation_values = json_decode($user_report->calculation_values,true);
        $date = date('d-M-Y, H:i ', strtotime($user_report->created_at));

        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$model_code)
                                        ->where('min_model','<=',(int)$calculation_values['MODEL'])->where('max_model','>=',(int)$calculation_values['MODEL'])->first();

        $chiller_options = $chiller_metallurgy_options->chillerOptions;
        
        $evaporator_option = $chiller_options->where('type', 'eva')->where('value',$calculation_values['TU2'])->first();
        $absorber_option = $chiller_options->where('type', 'abs')->where('value',$calculation_values['TU5'])->first();
        $condenser_option = $chiller_options->where('type', 'con')->where('value',$calculation_values['TV5'])->first();

        $evaporator_name = $evaporator_option->metallurgy->report_name;
        $absorber_name = $absorber_option->metallurgy->report_name;
        $condenser_name = $condenser_option->metallurgy->report_name;

        $vam_base = new VamBaseController();
        $language_datas = $vam_base->getLanguageDatas();
        $units_data = $vam_base->getUnitsData();

        $calculation_values['client_name'] = $user_report->name;
        $calculation_values['enquiry_name'] = $user_report->phone;
        $calculation_values['project_name'] = $user_report->project;
        $calculation_values['date_time'] = date('d-M-Y, H:i ', strtotime($user_report->created_at));
        $calculation_values['evaporator_name'] = $evaporator_name;
        $calculation_values['absorber_name'] = $absorber_name;
        $calculation_values['condenser_name'] = $condenser_name;


        if($calculation_values['region_type'] ==2){
            $my_template = new \PhpOffice\PhpWord\TemplateProcessor(storage_path('report_template/S2_USA.docx'));
        }
        else{
            $my_template = new \PhpOffice\PhpWord\TemplateProcessor(storage_path('report_template/S2.docx'));
        }
        


        $my_template = $this->commonTemplate($my_template, $language_datas, $calculation_values, $units_data, $unit_set);


        // Steam
        $my_template->setValue('steam_circuit', $language_datas['steam_circuit']);

        $my_template->setValue('heat_input', $language_datas['heat_input']);
        $my_template->setValue('heat_input_unit', $units_data[$unit_set->HeatUnit]);
        $my_template->setValue('heat_input_value', round($calculation_values['HeatInput'],1));

        $my_template->setValue('steam_pressure', $language_datas['steam_pressure']);
        $my_template->setValue('steam_pressure_unit', $units_data[$unit_set->PressureUnit]);
        $my_template->setValue('steam_pressure_value', round($calculation_values['PST1'],1));

        $my_template->setValue('steam_consumption', $language_datas['steam_consumption']);
        $my_template->setValue('steam_consumption_unit', $units_data[$unit_set->SteamConsumptionUnit]);
        $my_template->setValue('steam_consumption_value', round($calculation_values['SteamConsumption'],1));

        $my_template->setValue('condensate_drain_temperature', $language_datas['condensate_drain_temperature']);
        $my_template->setValue('condensate_drain_temperature_unit', $units_data[$unit_set->TemperatureUnit]);
        $my_template->setValue('condensate_drain_temperature_min_value', round($calculation_values['m_dMinCondensateDrainTemperature'],1));
        $my_template->setValue('condensate_drain_temperature_max_value', round($calculation_values['m_dMaxCondensateDrainTemperature'],1));

        $my_template->setValue('condensate_drain_pressure', $language_datas['condensate_drain_pressure']);
        $my_template->setValue('condensate_drain_pressure_unit', $units_data[$unit_set->PressureUnit]);
        $my_template->setValue('condensate_drain_pressure_value', round($calculation_values['m_dCondensateDrainPressure'],1));


        $my_template->setValue('connection_inlet_diameter', $language_datas['connection_inlet_dia']);
        $my_template->setValue('connection_inlet_diameter_unit', $units_data[$unit_set->NozzleDiameterUnit]);
        $my_template->setValue('connection_inlet_diameter_value', round($calculation_values['SteamConnectionDiameter'],1));

        $my_template->setValue('connection_drain_diameter', $language_datas['connection_drain_dia']);
        $my_template->setValue('connection_drain_diameter_unit', $units_data[$unit_set->NozzleDiameterUnit]);
        $my_template->setValue('connection_drain_diameter_value', round($calculation_values['SteamDrainDiameter'],1));

        $my_template->setValue('design_pressure', $language_datas['design_pressure']);
        $my_template->setValue('design_pressure_unit', $units_data[$unit_set->PressureUnit]);
        $my_template->setValue('design_pressure_value', round($calculation_values['m_DesignPressure'],1));

        $file_name = Auth::user()->id."_s2.docx";

        try{
            $my_template->saveAs(storage_path($file_name));
        }catch (Exception $e){
            //handle exception
        }

        return $file_name;

    }


   public function wordFormatE2($user_report_id,$model_code){
        
        $user_report = UserReport::find($user_report_id);

        $unit_set = UnitSet::find($user_report->unit_set_id);


        $calculation_values = json_decode($user_report->calculation_values,true);
        $date = date('d-M-Y, H:i ', strtotime($user_report->created_at));

        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$model_code)
                                        ->where('min_model','<=',(int)$calculation_values['MODEL'])->where('max_model','>=',(int)$calculation_values['MODEL'])->first();

        $chiller_options = $chiller_metallurgy_options->chillerOptions;
        
        $evaporator_option = $chiller_options->where('type', 'eva')->where('value',$calculation_values['TU2'])->first();
        $absorber_option = $chiller_options->where('type', 'abs')->where('value',$calculation_values['TU5'])->first();
        $condenser_option = $chiller_options->where('type', 'con')->where('value',$calculation_values['TV5'])->first();

        $evaporator_name = $evaporator_option->metallurgy->report_name;
        $absorber_name = $absorber_option->metallurgy->report_name;
        $condenser_name = $condenser_option->metallurgy->report_name;

        $vam_base = new VamBaseController();
        $language_datas = $vam_base->getLanguageDatas();
        $units_data = $vam_base->getUnitsData();

        $phpWord = new \PhpOffice\PhpWord\PhpWord();


        $section = $phpWord->addSection();

        
        
        // $section->addImage(asset('assets/images/pic.png'),array('align' => 'center'));
        $section->addTextBreak(1);
        $description = "Technical Specification : Vapour Absorption Chiller";

        // $section->addImage(asset('assets/images/pic.png'),array('marginLeft' => 5));
        $title = array('size' => 12, 'bold' => true,'align' => 'center');

        
        $section->addTextRun($title)->addText($description,$title);
        //$section->addTextBreak(1);
        $cellRowSpan = array('bgColor' => 'e5e5e5');

        $table_style = new \PhpOffice\PhpWord\Style\Table;
        $table_style->setBorderColor('cccccc');
        $table_style->setBorderSize(10);
        $table_style->setUnit(\PhpOffice\PhpWord\Style\Table::WIDTH_PERCENT);
        $table_style->setWidth(100 * 50);

        $alignment = array('bold' => true, 'align' => 'center');


        $header = array('size' => 10, 'bold' => true);

        $header_table = $section->addTable($table_style);
        $header_table->addRow();
        $header_table->addCell(1050,$cellRowSpan)->addText(htmlspecialchars($language_datas['client']),$header);
        $header_table->addCell(2550,$cellRowSpan)->addText(htmlspecialchars($user_report->name),$header);
        $header_table->addCell(1550,$cellRowSpan)->addText(htmlspecialchars($language_datas['version']),$header);
        $header_table->addCell(2000,$cellRowSpan)->addText(htmlspecialchars($calculation_values['version']." Dt : ".$calculation_values['version_date']),$header);

        $header_table->addRow();
        $header_table->addCell(1050,$cellRowSpan)->addText(htmlspecialchars($language_datas['enquiry']),$header);
        $header_table->addCell(2550,$cellRowSpan)->addText(htmlspecialchars($user_report->phone),$header);
        $header_table->addCell(1550,$cellRowSpan)->addText(htmlspecialchars($language_datas['date']),$header);
        $header_table->addCell(2000,$cellRowSpan)->addText(htmlspecialchars($date),$header);

        $header_table->addRow();
        $header_table->addCell(1050,$cellRowSpan)->addText(htmlspecialchars($language_datas['project']),$header);
        $header_table->addCell(2550,$cellRowSpan)->addText(htmlspecialchars($user_report->project),$header);
        $header_table->addCell(1550,$cellRowSpan)->addText(htmlspecialchars($language_datas['model']),$header);
        $header_table->addCell(2000,$cellRowSpan)->addText(htmlspecialchars($calculation_values['model_name']),$header);

        $section->addTextBreak(1);

        $description_table = $section->addTable($table_style);
        $description_table->addRow();
        $description_table->addCell(700,$cellRowSpan)->addText(htmlspecialchars(""),$header);
        $description_table->addCell(2850,$cellRowSpan)->addText(htmlspecialchars($language_datas['description']),$header);
        $description_table->addCell(1750,$cellRowSpan)->addTextRun($alignment)->addText(htmlspecialchars($language_datas['unit']),$header);
        $description_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars(""),$header);

        $description_table->addRow();
        $description_table->addCell(700,$cellRowSpan)->addText(htmlspecialchars(""),$header);
        $description_table->addCell(2850,$cellRowSpan)->addText(htmlspecialchars($language_datas['capacity']."(+/-3%)"),$header);
        $description_table->addCell(1750,$cellRowSpan)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->CapacityUnit]),$header);
        $description_table->addCell(1750,$cellRowSpan)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['TON'],1)),$header);

        $section->addTextBreak(1);

        $chilled_table = $section->addTable($table_style);
        $chilled_table->addRow();
        $chilled_table->addCell(700,$cellRowSpan)->addText(htmlspecialchars("A"),$header);
        $chilled_table->addCell(2850,$cellRowSpan)->addText(htmlspecialchars($language_datas['chilled_water_circuit']),$header);
        $chilled_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars(""),$header);
        $chilled_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars(""),$header);

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("1."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['chilled_water_flow']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->FlowRateUnit]));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['ChilledWaterFlow'],1)));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("2."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['chilled_inlet_temp']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->TemperatureUnit]));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['TCHW11'],1)));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("3."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['chilled_outlet_temp']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->TemperatureUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['TCHW12'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("4."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['evaporate_pass']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "No" ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $calculation_values['EvaporatorPasses'] ));


        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("5."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['chilled_pressure_loss']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->PressureDropUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['ChilledFrictionLoss'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("6."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['chilled_connection_diameter']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->NozzleDiameterUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['ChilledConnectionDiameter'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("7."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['glycol_type']));
        $chilled_table->addCell(1750)->addText(htmlspecialchars( "" ));
        if(empty($calculation_values['CHGLY']) || $calculation_values['GLL'] == 1)
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "NA" ));
        else if($calculation_values['GLL'] == 2)
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "Ethylene" ));
        else
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "Proplylene" ));


        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("8."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['chilled_gylcol']. "%"));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(" ( % )"));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['CHGLY'],1)));
       
        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("9."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['chilled_fouling_factor']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->FoulingFactorUnit] ));
        if($calculation_values['TUU'] == "standard")
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $calculation_values['TUU'] ));
        else
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $calculation_values['FFCHW1'] ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("10."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['max_working_pressure']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->WorkPressureUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( ceil($calculation_values['m_maxCHWWorkPressure']) ));

        $section->addTextBreak(1);

        $chilled_table = $section->addTable($table_style);
        $chilled_table->addRow();
        $chilled_table->addCell(700,$cellRowSpan)->addText(htmlspecialchars("B"),$header);
        $chilled_table->addCell(2850,$cellRowSpan)->addText(htmlspecialchars($language_datas['cooling_water_circuit']),$header);
        $chilled_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars(""),$header);
        $chilled_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars(""),$header);

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("1."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['heat_rejected']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->HeatUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['HeatRejected'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("2."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['cooling_water_flow']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->FlowRateUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['GCW'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("3."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['cooling_inlet_temp']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->TemperatureUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['TCW11'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("4."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['cooling_outlet_temp']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->TemperatureUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['CoolingWaterOutTemperature'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("5."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['absorber_condenser_pass']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "No" ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $calculation_values['AbsorberPasses']."/".$calculation_values['CondenserPasses'] ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("6."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['cooling_bypass_flow']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->FlowRateUnit] ));
        if(empty($calculation_values['BypassFlow']))
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "-" ));
        else
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['BypassFlow'],1) ));


        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("7."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['cooling_pressure_loss']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->PressureDropUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['CoolingFrictionLoss'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("8."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['cooling_connection_diameter']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->NozzleDiameterUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['CoolingConnectionDiameter'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("9."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['glycol_type']));
        $chilled_table->addCell(1750)->addText(htmlspecialchars( "" ));
        if(empty($calculation_values['COGLY']) || $calculation_values['GLL'] == 1)
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "NA" ));
        else if($calculation_values['GLL'] == 2)
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "Ethylene" ));
        else
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "Proplylene" ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("10."));
        $chilled_table->addCell(2550)->addText(htmlspecialchars($language_datas['cooling_gylcol']." ( % )"));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "%" ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['COGLY'],1) ));


        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("11."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['cooling_fouling_factor']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->FoulingFactorUnit] ));
        if($calculation_values['TUU'] == "standard")
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $calculation_values['TUU'] ));
        else
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $calculation_values['FFCOW1'] ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("12."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['max_working_pressure']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->WorkPressureUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( ceil($calculation_values['m_maxCOWWorkPressure']) ));

        $section->addTextBreak(1);

        $chilled_table = $section->addTable($table_style);
        $chilled_table->addRow();
        $chilled_table->addCell(700,$cellRowSpan)->addText(htmlspecialchars("C"),$header);
        $chilled_table->addCell(2850,$cellRowSpan)->addText(htmlspecialchars($language_datas['exhaust_gas_circuit']),$header);
        $chilled_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars(""),$header);
        $chilled_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars(""),$header);

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("1."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['heat_input']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->HeatUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['HeatInput'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("2."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['engine_type']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars("-"));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($calculation_values['engine_type']));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("3."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['exhaust_gas_flow']." (+/-3%)"));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->ExhaustGasFlowUnit]));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['GEXHAUST'],1)));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("4."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['exhaust_gas_in_temp']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->TemperatureUnit]));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['TEXH1'],1)));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("5."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['exhaust_gas_out_temp']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->TemperatureUnit]));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['ActExhaustGasTempOut'],1)));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("6."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['exhaust_connection_diameter']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->NozzleDiameterUnit]));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['ExhaustConnectionDiameter'],1)));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("7."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['exhaust_gas_sp_heat_capacity']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->HeatCapacityUnit]));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['AvgExhGasCp'],1)));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("8."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['exhaust_gas_flow']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->ExhaustGasFlowUnit]));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['ExhaustGasFlowRate'],1)));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("9."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['percentage_engine_load_considered']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars("%"));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['LOAD'],1)));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("10."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['pressure_drop']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->FurnacePressureDropUnit]));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['FURNPRDROP'],1)));


        $section->addTextBreak(1);

        $chilled_table = $section->addTable($table_style);
        $chilled_table->addRow();
        $chilled_table->addCell(700,$cellRowSpan)->addText(htmlspecialchars("D"),$header);
        $chilled_table->addCell(2850,$cellRowSpan)->addText(htmlspecialchars($language_datas['electrical_data']),$header);
        $chilled_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars(""),$header);
        $chilled_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars(""),$header);

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("1."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['power_supply']));
        $chilled_table->addCell(1750)->addText(htmlspecialchars( "" ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $calculation_values['PowerSupply'] ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("2."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['power_consumption']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "kVA" ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['TotalPowerConsumption'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("3."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['absorbent_pump_rating']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "kW (A)" ));
        if($calculation_values['region_type'] ==2){
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['USA_AbsorbentPumpMotorKW'],2) ."( ". round($calculation_values['USA_AbsorbentPumpMotorAmp'],2)." )" ));
        }
        else{
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['AbsorbentPumpMotorKW'],2) ."( ". round($calculation_values['AbsorbentPumpMotorAmp'],2)." )" ));
        }
        

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("4."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['refrigerant_pump_rating']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "kW (A)" ));
        if($calculation_values['region_type'] ==2){
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['USA_RefrigerantPumpMotorKW'],2) ."( ". round($calculation_values['USA_RefrigerantPumpMotorAmp'],2)." )" ));
        }
        else{
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['RefrigerantPumpMotorKW'],2) ."( ". round($calculation_values['RefrigerantPumpMotorAmp'],2)." )" ));
        }
        

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("5."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['vaccum_pump_rating']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "kW (A)" ));
        if($calculation_values['region_type'] ==2){
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['USA_PurgePumpMotorKW'],2) ."( ". round($calculation_values['USA_PurgePumpMotorAmp'],2)." )" ));
        }
        else{
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['PurgePumpMotorKW'],2) ."( ". round($calculation_values['PurgePumpMotorAmp'],2)." )" ));
        }
        
        if($calculation_values['region_type'] ==2)
        {

            $chilled_table->addRow();
            $chilled_table->addCell(700)->addText(htmlspecialchars("6."));
            $chilled_table->addCell(2850)->addText(htmlspecialchars("MOP"));
            $chilled_table->addCell(1750)->addText(htmlspecialchars( "" ));
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['MOP'],2)));

            $chilled_table->addRow();
            $chilled_table->addCell(700)->addText(htmlspecialchars("7."));
            $chilled_table->addCell(2850)->addText(htmlspecialchars("MCA"));
            $chilled_table->addCell(1750)->addText(htmlspecialchars( "" ));
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['MCA'],2) ));
        }


        $section->addTextBreak(1);

        $chilled_table = $section->addTable($table_style);
        $chilled_table->addRow();
        $chilled_table->addCell(700,$cellRowSpan)->addText(htmlspecialchars("F"),$header);
        $chilled_table->addCell(2850,$cellRowSpan)->addText(htmlspecialchars($language_datas['tube_metallurgy']),$header);
        $chilled_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars(""),$header);
        $chilled_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars(""),$header);

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("1."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['evaporator_tube_material']));
        $chilled_table->addCell(1750)->addText(htmlspecialchars(""));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($evaporator_name));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("2."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['absorber_tube_material']));
        $chilled_table->addCell(1750)->addText(htmlspecialchars(""));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($absorber_name));


        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("3."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['condenser_tube_material']));
        $chilled_table->addCell(1750)->addText(htmlspecialchars(""));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($condenser_name));

        if(!$calculation_values['isStandard'] || $calculation_values['isStandard'] != 'true'){
            $chilled_table->addRow();
            $chilled_table->addCell(700)->addText(htmlspecialchars("4."));
            $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['evaporator_tube_thickness']));
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->LengthUnit]));
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['TU3'],1)));

            $chilled_table->addRow();
            $chilled_table->addCell(700)->addText(htmlspecialchars("5."));
            $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['absorber_tube_thickness']));
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->LengthUnit]));
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['TU6'],1)));

            $chilled_table->addRow();
            $chilled_table->addCell(700)->addText(htmlspecialchars("6."));
            $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['condenser_tube_thickness']));
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->LengthUnit]));
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['TV6'],1)));
        }

        $section->addTextBreak(1);

        $chilled_table = $section->addTable($table_style);
        $chilled_table->addRow();
        $chilled_table->addCell(700,$cellRowSpan)->addText(htmlspecialchars("G"),$header);
        $chilled_table->addCell(2850,$cellRowSpan)->addText(htmlspecialchars($language_datas['low_temp_heat_exchange']),$header);
        $chilled_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars(""),$header);
        $chilled_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars($calculation_values['HHType']),$header);
        
        $section->addTextBreak(1);
        $chilled_table = $section->addTable($table_style);
        $chilled_table->addRow();
        $chilled_table->addCell(700,$cellRowSpan)->addText(htmlspecialchars($language_datas['caption_notes']." : "),$header);

        foreach ($calculation_values['notes'] as $key => $note) {
            $section->addText(($key + 1).". ".$note);
        }

        $file_name = "E2-Series-".Auth::user()->id.".docx";
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        try {
            $objWriter->save(storage_path($file_name));
        } catch (Exception $e) {
            Log::info($e);
        }

        return $file_name;

    }

     
    

    

    public function saveCalculationReport($model_values,$calculation_values,$user_datas,$model_code){


        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$model_code)
                                        ->where('min_model','<=',(int)$calculation_values['MODEL'])->where('max_model','>',(int)$calculation_values['MODEL'])->first();

        $chiller_options = $chiller_metallurgy_options->chillerOptions;
        
        $evaporator_option = $chiller_options->where('type', 'eva')->where('value',$calculation_values['TU2'])->first();
        $absorber_option = $chiller_options->where('type', 'abs')->where('value',$calculation_values['TU5'])->first();
        $condenser_option = $chiller_options->where('type', 'con')->where('value',$calculation_values['TV5'])->first();

        $evaporator_name = $evaporator_option->metallurgy->display_name;
        $absorber_name = $absorber_option->metallurgy->display_name;
        $condenser_name = $condenser_option->metallurgy->display_name;

        $calculation_report = new CalculatorReport;
        $calculation_report->version = isset($calculation_values['version']) ?  $calculation_values['version'] : "";
        $calculation_report->user_mail = isset($user_datas['user_mail']) ?  $user_datas['user_mail'] : "";
        $calculation_report->ip_address = isset($user_datas['ip_address']) ?  $user_datas['ip_address'] : "";
        $calculation_report->customer_name = isset($user_datas['customer_name']) ?  $user_datas['customer_name'] : "";
        $calculation_report->project_name = isset($user_datas['project_name']) ?  $user_datas['project_name'] : "";
        $calculation_report->opportunity_number = isset($user_datas['opportunity_number']) ?  $user_datas['opportunity_number'] : "";
        $calculation_report->unit_set = isset($user_datas['unit_set']) ?  $user_datas['unit_set'] : "";

        $calculation_report->model_name = isset($calculation_values['model_name']) ?  $calculation_values['model_name'] : "";
        $calculation_report->model_number = isset($model_values['model_number']) ?  $model_values['model_number'] : "";
        $calculation_report->capacity = isset($model_values['capacity']) ?  $model_values['capacity'] : "";

        // Chilled and Cooling
        $calculation_report->chilled_water_in = isset($model_values['chilled_water_in']) ?  $model_values['chilled_water_in'] : "";
        $calculation_report->chilled_water_out = isset($model_values['chilled_water_out']) ?  $model_values['chilled_water_out'] : "";
        $calculation_report->cooling_water_in = isset($model_values['cooling_water_in']) ?  $model_values['cooling_water_in'] : "";
        $calculation_report->cooling_water_flow = isset($model_values['cooling_water_flow']) ?  $model_values['cooling_water_flow'] : "";

        // Glycol
        if(empty($model_values['glycol_chilled_water']) || $model_values['glycol_selected'] == 1){
            $calculation_report->glycol_selected = "NA";
        }
        elseif ($model_values['glycol_selected'] == 2) {
            $calculation_report->glycol_selected = "Ethylene";
        }
        else{
            $calculation_report->glycol_selected = "Proplylene";
        }

        $calculation_report->glycol_chilled_water = isset($model_values['glycol_chilled_water']) ?  $model_values['glycol_chilled_water'] : "";
        $calculation_report->glycol_cooling_water = isset($model_values['glycol_cooling_water']) ?  $model_values['glycol_cooling_water'] : "";
        $calculation_report->metallurgy_standard = isset($model_values['metallurgy_standard']) ?  $model_values['metallurgy_standard'] : "";

        // Metallurgy
        $calculation_report->evaporator_material_value = isset($evaporator_name) ?  $evaporator_name : "";
        $calculation_report->evaporator_thickness = isset($calculation_values['TU3']) ?  $calculation_values['TU3'] : "";
        $calculation_report->absorber_material_value = isset($absorber_name) ?  $absorber_name : "";
        $calculation_report->absorber_thickness = isset($calculation_values['TU6']) ?  $calculation_values['TU6'] : "";
        $calculation_report->condenser_material_value = isset($condenser_name) ?  $condenser_name : "";
        $calculation_report->condenser_thickness = isset($calculation_values['TV6']) ?  $calculation_values['TV6'] : "";

        // Fouling Factor
        $calculation_report->fouling_factor = isset($model_values['fouling_factor']) ?  $model_values['fouling_factor'] : "";
        $calculation_report->fouling_chilled_water_value = isset($model_values['fouling_chilled_water_value']) ?  $model_values['fouling_chilled_water_value'] : "";
        $calculation_report->fouling_cooling_water_value = isset($model_values['fouling_cooling_water_value']) ?  $model_values['fouling_cooling_water_value'] : "";

        // Region
        if($model_values['region_type'] == 1){
            $calculation_report->region_type = "Domestic";
        }
        elseif ($model_values['region_type'] == 1) {
            $calculation_report->region_type = "USA";
        }
        else{
            $calculation_report->region_type = "Europe";
        }

        // Heat Source
        $calculation_report->steam_pressure = isset($model_values['steam_pressure']) ?  $model_values['steam_pressure'] : "";
        $calculation_report->fuel_type = isset($model_values['fuel_type']) ?  $model_values['fuel_type'] : "";
        $calculation_report->fuel_value_type = isset($model_values['fuel_value_type']) ?  $model_values['fuel_value_type'] : "";
        $calculation_report->calorific_value = isset($model_values['calorific_value']) ?  $model_values['calorific_value'] : "";
        if($model_code == "CH_G2" || $model_code == "CH_S2"){
            $calculation_report->heated_water_in = isset($model_values['hot_water_in']) ?  $model_values['hot_water_in'] : "";
            $calculation_report->heated_water_out = isset($model_values['hot_water_out']) ?  $model_values['hot_water_out'] : "";
        }
        else{
            $calculation_report->hot_water_in = isset($model_values['hot_water_in']) ?  $model_values['hot_water_in'] : "";
            $calculation_report->hot_water_out = isset($model_values['hot_water_out']) ?  $model_values['hot_water_out'] : "";
        }
       
        $calculation_report->all_work_pr_hw = isset($model_values['all_work_pr_hw']) ?  $model_values['all_work_pr_hw'] : "";
        $calculation_report->exhaust_gas_in = isset($model_values['exhaust_gas_in']) ?  $model_values['exhaust_gas_in'] : "";
        $calculation_report->exhaust_gas_out = isset($model_values['exhaust_gas_out']) ?  $model_values['exhaust_gas_out'] : "";
        $calculation_report->gas_flow = isset($model_values['gas_flow']) ?  $model_values['gas_flow'] : "";
        $calculation_report->gas_flow_load = isset($model_values['gas_flow_load']) ?  $model_values['gas_flow_load'] : "";
        $calculation_report->design_load = isset($model_values['design_load']) ?  $model_values['design_load'] : "";
        $calculation_report->pressure_drop = isset($model_values['pressure_drop']) ?  $model_values['pressure_drop'] : "";
        $calculation_report->engine_type = isset($model_values['engine_type']) ?  $model_values['engine_type'] : "";
        $calculation_report->economizer = isset($model_values['economizer']) ?  $model_values['economizer'] : "";
        $calculation_report->glycol_hot_water = isset($model_values['glycol_hot_water']) ?  $model_values['glycol_hot_water'] : "";
        $calculation_report->fouling_hot_water_value = isset($model_values['fouling_hot_water_value']) ?  $model_values['fouling_hot_water_value'] : "";
        $calculation_report->hot_water_flow = isset($model_values['hot_water_flow']) ?  $model_values['hot_water_flow'] : "";
        $calculation_report->generator_tube_value = isset($model_values['generator_tube_name']) ?  $model_values['generator_tube_name'] : "";
        $calculation_report->heat_duty = isset($model_values['heat_duty']) ?  $model_values['heat_duty'] : "";


        // Result
        $calculation_report->result = isset($calculation_values['Result']) ?  $calculation_values['Result'] : "";

        $calculation_report->save();

    }
}
