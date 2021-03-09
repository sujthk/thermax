<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\UserReport;
use App\UnitSet;
use App\ChillerMetallurgyOption;
use App\Http\Controllers\VamBaseController;

class ReportController extends Controller
{
    public function wordFormatS2($user_report_id,$model_code){
        
        $user_report = UserReport::find($user_report_id);

        $unit_set = UnitSet::find($user_report->unit_set_id);


        $calculation_values = json_decode($user_report->calculation_values,true);
        $date = date('m/d/Y, h:i A', strtotime($user_report->created_at));

        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$model_code)
                                        ->where('min_model','<=',$calculation_values['MODEL'])->where('max_model','>',$calculation_values['MODEL'])->first();

        $chiller_options = $chiller_metallurgy_options->chillerOptions;
        
        $evaporator_option = $chiller_options->where('type', 'eva')->where('value',$calculation_values['TU2'])->first();
        $absorber_option = $chiller_options->where('type', 'abs')->where('value',$calculation_values['TU5'])->first();
        $condenser_option = $chiller_options->where('type', 'con')->where('value',$calculation_values['TV5'])->first();

        $evaporator_name = $evaporator_option->metallurgy->display_name;
        $absorber_name = $absorber_option->metallurgy->display_name;
        $condenser_name = $condenser_option->metallurgy->display_name;

        $vam_base = new VamBaseController();
        $language_datas = $vam_base->getLanguageDatas();
        $units_data = $vam_base->getUnitsData();

        $phpWord = new \PhpOffice\PhpWord\PhpWord();


        $section = $phpWord->addSection();

        
        
        $section->addImage(asset('assets/images/pic.png'),array('align' => 'center'));
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
        $header_table->addCell(2000,$cellRowSpan)->addText(htmlspecialchars("5.1.2.0"),$header);

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
        $description_table->addCell(1750,$cellRowSpan)->addTextRun($alignment)->addText(htmlspecialchars($calculation_values['TON']),$header);

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
        if($calculation_values['GL'] == 1)
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "NA" ));
        else if($calculation_values['GL'] == 2)
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
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['cooling_water_flow']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->FlowRateUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['GCW'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("2."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['cooling_inlet_temp']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->TemperatureUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['TCW11'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("3."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['cooling_outlet_temp']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->TemperatureUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['CoolingWaterOutTemperature'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("4."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['absorber_condenser_pass']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "No" ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $calculation_values['AbsorberPasses']."/".$calculation_values['CondenserPasses'] ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("5."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['cooling_bypass_flow']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->FlowRateUnit] ));
        if(empty($calculation_values['BypassFlow']))
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "-" ));
        else
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['BypassFlow'],1) ));


        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("6."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['cooling_pressure_loss']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->PressureDropUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['CoolingFrictionLoss'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("7."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['cooling_connection_diameter']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->NozzleDiameterUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['CoolingConnectionDiameter'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("8."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['glycol_type']));
        $chilled_table->addCell(1750)->addText(htmlspecialchars( "" ));
        if($calculation_values['GL'] == 1)
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "NA" ));
        else if($calculation_values['GL'] == 2)
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "Ethylene" ));
        else
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "Proplylene" ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("9."));
        $chilled_table->addCell(2550)->addText(htmlspecialchars($language_datas['cooling_gylcol']." ( % )"));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "%" ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['COGLY'],1) ));


        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("10."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['cooling_fouling_factor']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->FoulingFactorUnit] ));
        if($calculation_values['TUU'] == "standard")
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $calculation_values['TUU'] ));
        else
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $calculation_values['FFCOW1'] ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("11."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['max_working_pressure']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->WorkPressureUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( ceil($calculation_values['m_maxCOWWorkPressure']) ));

        $section->addTextBreak(1);

        $chilled_table = $section->addTable($table_style);
        $chilled_table->addRow();
        $chilled_table->addCell(700,$cellRowSpan)->addText(htmlspecialchars("C"),$header);
        $chilled_table->addCell(2850,$cellRowSpan)->addText(htmlspecialchars($language_datas['steam_circuit']),$header);
        $chilled_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars(""),$header);
        $chilled_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars(""),$header);

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("1."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['steam_pressure']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->PressureUnit]));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['PST1'],1)));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("2."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['steam_consumption']." (+/-3%)"));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->SteamConsumptionUnit]));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['SteamConsumption'],1)));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("3."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['condensate_drain_temperature']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->TemperatureUnit]));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(ceil($calculation_values['m_dMinCondensateDrainTemperature']) ." - ".ceil($calculation_values['m_dMaxCondensateDrainTemperature']) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("4."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['condensate_drain_pressure']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->PressureUnit]));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['m_dCondensateDrainPressure'],1)));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("5."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['connection_inlet_dia']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->NozzleDiameterUnit]));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['SteamConnectionDiameter'],1)));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("6."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['connection_drain_dia']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->NozzleDiameterUnit]));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['SteamDrainDiameter'],1)));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("7."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['design_pressure']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->PressureUnit]));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['m_DesignPressure'],1)));


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
        $chilled_table->addCell(700,$cellRowSpan)->addText(htmlspecialchars("E"),$header);
        $chilled_table->addCell(2850,$cellRowSpan)->addText(htmlspecialchars($language_datas['physical_data']),$header);
        $chilled_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars(""),$header);
        $chilled_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars(""),$header);

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("1."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['length']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->LengthUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( ceil($calculation_values['Length']) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("2."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['width']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->LengthUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( ceil($calculation_values['Width']) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("3."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['height']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->LengthUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( ceil($calculation_values['Height'])));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("4."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['operating_weight']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->WeightUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['OperatingWeight'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("5."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['shipping_weight']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->WeightUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['MaxShippingWeight'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("6."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['flooded_weight']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->WeightUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['FloodedWeight'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("7."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['dry_weight']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->WeightUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['DryWeight'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("8."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['tube_clearing_space']." (".$language_datas['one_side_length_wise'].")"));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->LengthUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['ClearanceForTubeRemoval'],1) ));

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

        $file_name = "S2-Steam-Fired-Series-".Auth::user()->id.".docx";
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        try {
            $objWriter->save(storage_path($file_name));
        } catch (Exception $e) {
            Log::info($e);
        }

    }

    public function wordFormatL5($user_report_id,$model_code){
        
        $user_report = UserReport::find($user_report_id);

        $unit_set = UnitSet::find($user_report->unit_set_id);


        $calculation_values = json_decode($user_report->calculation_values,true);
        $date = date('m/d/Y, h:i A', strtotime($user_report->created_at));

        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$model_code)
                                        ->where('min_model','<=',$calculation_values['MODEL'])->where('max_model','>=',$calculation_values['MODEL'])->first();

        $chiller_options = $chiller_metallurgy_options->chillerOptions;
        
        $evaporator_option = $chiller_options->where('type', 'eva')->where('value',$calculation_values['TU2'])->first();
        $absorber_option = $chiller_options->where('type', 'abs')->where('value',$calculation_values['TU5'])->first();
        $condenser_option = $chiller_options->where('type', 'con')->where('value',$calculation_values['TV5'])->first();

        $evaporator_name = $evaporator_option->metallurgy->display_name;
        $absorber_name = $absorber_option->metallurgy->display_name;
        $condenser_name = $condenser_option->metallurgy->display_name;

        $vam_base = new VamBaseController();
        $language_datas = $vam_base->getLanguageDatas();
        $units_data = $vam_base->getUnitsData();

        $phpWord = new \PhpOffice\PhpWord\PhpWord();


        $section = $phpWord->addSection();

        
        
        $section->addImage(asset('assets/images/pic.png'),array('align' => 'center'));
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
        $header_table->addCell(2000,$cellRowSpan)->addText(htmlspecialchars("5.1.2.0"),$header);

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
        $description_table->addCell(1750,$cellRowSpan)->addTextRun($alignment)->addText(htmlspecialchars($calculation_values['TON']),$header);

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
        if($calculation_values['GL'] == 1)
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "NA" ));
        else if($calculation_values['GL'] == 2)
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
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['cooling_water_flow']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->FlowRateUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['GCW'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("2."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['cooling_inlet_temp']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->TemperatureUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['TCW11'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("3."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['cooling_outlet_temp']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->TemperatureUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['CoolingWaterOutTemperature'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("4."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['absorber_condenser_pass']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "No" ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $calculation_values['AbsorberPasses']."/".$calculation_values['CondenserPasses'] ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("5."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['cooling_bypass_flow']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->FlowRateUnit] ));
        if(empty($calculation_values['BypassFlow']))
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "-" ));
        else
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['BypassFlow'],1) ));


        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("6."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['cooling_pressure_loss']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->PressureDropUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['CoolingFrictionLoss'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("7."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['cooling_connection_diameter']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->NozzleDiameterUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['CoolingConnectionDiameter'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("8."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['glycol_type']));
        $chilled_table->addCell(1750)->addText(htmlspecialchars( "" ));
        if($calculation_values['GL'] == 1)
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "NA" ));
        else if($calculation_values['GL'] == 2)
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "Ethylene" ));
        else
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "Proplylene" ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("9."));
        $chilled_table->addCell(2550)->addText(htmlspecialchars($language_datas['cooling_gylcol']." ( % )"));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "%" ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['COGLY'],1) ));


        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("10."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['cooling_fouling_factor']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->FoulingFactorUnit] ));
        if($calculation_values['TUU'] == "standard")
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $calculation_values['TUU'] ));
        else
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $calculation_values['FFCOW1'] ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("11."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['max_working_pressure']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->WorkPressureUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( ceil($calculation_values['m_maxCOWWorkPressure']) ));

        $section->addTextBreak(1);

        $chilled_table = $section->addTable($table_style);
        $chilled_table->addRow();
        $chilled_table->addCell(700,$cellRowSpan)->addText(htmlspecialchars("C"),$header);
        $chilled_table->addCell(2850,$cellRowSpan)->addText(htmlspecialchars($language_datas['hot_water_circuit']),$header);
        $chilled_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars(""),$header);
        $chilled_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars(""),$header);

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("1."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['hot_water_flow']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->FlowRateUnit]));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['GHOT'],1)));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("2."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['hot_water_inlet_temp']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->TemperatureUnit]));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['THW1'],1)));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("3."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['hot_water_outlet_temp']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->TemperatureUnit]));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['THW4'],1)));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("4."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['generator_passes']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "No" ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $calculation_values['TGP']." + ".$calculation_values['TGP'] ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("5."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['hot_water_circuit_pressure_loss']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->PressureDropUnit]));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['HotWaterFrictionLoss'],1)));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("6."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['hot_water_connection_diameter']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->NozzleDiameterUnit]));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['GENNB'],1)));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("7."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['glycol_type']));
        $chilled_table->addCell(1750)->addText(htmlspecialchars( "" ));
        if($calculation_values['GL'] == 1)
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "NA" ));
        else if($calculation_values['GL'] == 2)
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "Ethylene" ));
        else
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "Proplylene" ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("8."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['hot_water_glycol']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "%"));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['HWGLY'],1)));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("9."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['max_working_pressure']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->WorkPressureUnit]));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['m_maxHWWorkPressure'],1)));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("10."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['hot_water_fouling_factor']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->FoulingFactorUnit] ));
        if($calculation_values['TUU'] == "standard")
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $calculation_values['TUU'] ));
        else
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $calculation_values['FFHOW1'] ));

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
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['hp_absorbent_pump_rating']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "kW (A)" ));
        if($calculation_values['region_type'] ==2){
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['USA_HPAbsorbentPumpMotorKW'],2) ."( ". round($calculation_values['USA_HPAbsorbentPumpMotorAmp'],2)." )" ));
        }
        else{
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['HPAbsorbentPumpMotorKW'],2) ."( ". round($calculation_values['HPAbsorbentPumpMotorAmp'],2)." )" ));
        }
        

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("4."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['lp_absorbent_pump_rating']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "kW (A)" ));
        if($calculation_values['region_type'] ==2){
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['USA_LPRefrigerantPumpMotorKW'],2) ."( ". round($calculation_values['USA_LPRefrigerantPumpMotorAmp'],2)." )" ));
        }
        else{
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['LPAbsorbentPumpMotorKW'],2) ."( ". round($calculation_values['LPAbsorbentPumpMotorAmp'],2)." )" ));
        }       

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("5."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['refrigerant_pump_rating']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "kW (A)" ));
        if($calculation_values['region_type'] ==2){
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['USA_RefrigerantPumpMotorKW'],2) ."( ". round($calculation_values['USA_RefrigerantPumpMotorAmp'],2)." )" ));
        }
        else{
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['RefrigerantPumpMotorKW'],2) ."( ". round($calculation_values['RefrigerantPumpMotorAmp'],2)." )" ));
        }
        

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("6."));
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
        $chilled_table->addCell(700,$cellRowSpan)->addText(htmlspecialchars("E"),$header);
        $chilled_table->addCell(2850,$cellRowSpan)->addText(htmlspecialchars($language_datas['physical_data']),$header);
        $chilled_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars(""),$header);
        $chilled_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars(""),$header);

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("1."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['length']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->LengthUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( ceil($calculation_values['Length']) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("2."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['width']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->LengthUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( ceil($calculation_values['Width']) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("3."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['height']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->LengthUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( ceil($calculation_values['Height'])));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("4."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['operating_weight']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->WeightUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['OperatingWeight'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("5."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['shipping_weight']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->WeightUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['MaxShippingWeight'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("6."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['flooded_weight']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->WeightUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['FloodedWeight'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("7."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['dry_weight']));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->WeightUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['DryWeight'],1) ));

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("8."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['tube_clearing_space']." (".$language_datas['one_side_length_wise'].")"));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->LengthUnit] ));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['ClearanceForTubeRemoval'],1) ));

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

        $chilled_table->addRow();
        $chilled_table->addCell(700)->addText(htmlspecialchars("4."));
        $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['generator_tube_material']));
        $chilled_table->addCell(1750)->addText(htmlspecialchars(""));
        $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($calculation_values['generator_tube_name']));

        if(!$calculation_values['isStandard'] || $calculation_values['isStandard'] != 'true'){
            $chilled_table->addRow();
            $chilled_table->addCell(700)->addText(htmlspecialchars("5."));
            $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['evaporator_tube_thickness']));
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->LengthUnit]));
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['TU3'],1)));

            $chilled_table->addRow();
            $chilled_table->addCell(700)->addText(htmlspecialchars("6."));
            $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['absorber_tube_thickness']));
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->LengthUnit]));
            $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['TU6'],1)));

            $chilled_table->addRow();
            $chilled_table->addCell(700)->addText(htmlspecialchars("7."));
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

        $file_name = "L5-Series-".Auth::user()->id.".docx";
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        try {
            $objWriter->save(storage_path($file_name));
        } catch (Exception $e) {
            Log::info($e);
        }

    }

    public function wordFormatH2($user_report_id,$model_code){
           
       $user_report = UserReport::find($user_report_id);

       $unit_set = UnitSet::find($user_report->unit_set_id);

       $vam_base = new VamBaseController();
       $language_datas = $vam_base->getLanguageDatas();
       $units_data = $vam_base->getUnitsData();
       

       $calculation_values = json_decode($user_report->calculation_values,true);
       $date = date('m/d/Y, h:i A', strtotime($user_report->created_at));

       $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$model_code)
                                       ->where('min_model','<',$calculation_values['MODEL'])->where('max_model','>',$calculation_values['MODEL'])->first();

       $chiller_options = $chiller_metallurgy_options->chillerOptions;
       
       $evaporator_option = $chiller_options->where('type', 'eva')->where('value',$calculation_values['TU2'])->first();
       $absorber_option = $chiller_options->where('type', 'abs')->where('value',$calculation_values['TU5'])->first();
       $condenser_option = $chiller_options->where('type', 'con')->where('value',$calculation_values['TV5'])->first();

       $evaporator_name = $evaporator_option->metallurgy->display_name;
       $absorber_name = $absorber_option->metallurgy->display_name;
       $condenser_name = $condenser_option->metallurgy->display_name;

       $vam_base = new VamBaseController();
       $language_datas = $vam_base->getLanguageDatas();


       $phpWord = new \PhpOffice\PhpWord\PhpWord();


       $section = $phpWord->addSection();


       $section->addImage(asset('assets/images/pic.png'),array('align' => 'center'));
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
       $header_table->addCell(2000,$cellRowSpan)->addText(htmlspecialchars("5.1.2.0"),$header);

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
       $description_table->addCell(1750,$cellRowSpan)->addTextRun($alignment)->addText(htmlspecialchars($calculation_values['TON']),$header);

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
       if($calculation_values['GL'] == 1)
           $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "NA" ));
       else if($calculation_values['GL'] == 2)
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
       $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['cooling_water_flow']));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->FlowRateUnit] ));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['GCW'],1) ));

       $chilled_table->addRow();
       $chilled_table->addCell(700)->addText(htmlspecialchars("2."));
       $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['cooling_inlet_temp']));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->TemperatureUnit] ));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['TCW11'],1) ));

       $chilled_table->addRow();
       $chilled_table->addCell(700)->addText(htmlspecialchars("3."));
       $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['cooling_outlet_temp']));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->TemperatureUnit] ));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['CoolingWaterOutTemperature'],1) ));

       $chilled_table->addRow();
       $chilled_table->addCell(700)->addText(htmlspecialchars("4."));
       $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['absorber_condenser_pass']));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "No" ));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $calculation_values['AbsorberPasses']."/".$calculation_values['CondenserPasses'] ));

       $chilled_table->addRow();
       $chilled_table->addCell(700)->addText(htmlspecialchars("5."));
       $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['cooling_bypass_flow']));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->FlowRateUnit] ));
       if(empty($calculation_values['BypassFlow']))
           $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "-" ));
       else
           $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['BypassFlow'],1) ));


       $chilled_table->addRow();
       $chilled_table->addCell(700)->addText(htmlspecialchars("6."));
       $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['cooling_pressure_loss']));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->PressureDropUnit] ));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['CoolingFrictionLoss'],1) ));

       $chilled_table->addRow();
       $chilled_table->addCell(700)->addText(htmlspecialchars("7."));
       $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['cooling_connection_diameter']));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->NozzleDiameterUnit] ));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['CoolingConnectionDiameter'],1) ));

       $chilled_table->addRow();
       $chilled_table->addCell(700)->addText(htmlspecialchars("8."));
       $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['glycol_type']));
       $chilled_table->addCell(1750)->addText(htmlspecialchars( "" ));
       if($calculation_values['GL'] == 1)
           $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "NA" ));
       else if($calculation_values['GL'] == 2)
           $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "Ethylene" ));
       else
           $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "Proplylene" ));

       $chilled_table->addRow();
       $chilled_table->addCell(700)->addText(htmlspecialchars("9."));
       $chilled_table->addCell(2550)->addText(htmlspecialchars($language_datas['cooling_gylcol']." ( % )"));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "%" ));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['COGLY'],1) ));


       $chilled_table->addRow();
       $chilled_table->addCell(700)->addText(htmlspecialchars("10."));
       $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['cooling_fouling_factor']));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->FoulingFactorUnit] ));
       if($calculation_values['TUU'] == "standard")
           $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $calculation_values['TUU'] ));
       else
           $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $calculation_values['FFCOW1'] ));

       $chilled_table->addRow();
       $chilled_table->addCell(700)->addText(htmlspecialchars("11."));
       $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['max_working_pressure']));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->WorkPressureUnit] ));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( ceil($calculation_values['m_maxCOWWorkPressure']) ));

       $section->addTextBreak(1);

       $chilled_table = $section->addTable($table_style);
       $chilled_table->addRow();
       $chilled_table->addCell(700,$cellRowSpan)->addText(htmlspecialchars("C"),$header);
       $chilled_table->addCell(2850,$cellRowSpan)->addText(htmlspecialchars($language_datas['hot_water_circuit']),$header);
       $chilled_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars(""),$header);
       $chilled_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars(""),$header);

       $chilled_table->addRow();
       $chilled_table->addCell(700)->addText(htmlspecialchars("1."));
       $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['hot_water_flow']."(+/- 3%)"));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->FlowRateUnit]));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['HotWaterFlow'],1)));

       $chilled_table->addRow();
       $chilled_table->addCell(700)->addText(htmlspecialchars("2."));
       $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['hot_water_in_temp']));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->TemperatureUnit]));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['hot_water_in'],1)));

       $chilled_table->addRow();
       $chilled_table->addCell(700)->addText(htmlspecialchars("3."));
       $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['hot_water_out_temp']));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->TemperatureUnit]));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(ceil($calculation_values['hot_water_out'])  ));

       $chilled_table->addRow();
       $chilled_table->addCell(700)->addText(htmlspecialchars("4."));
       $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['generator_passes']));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->PressureUnit]));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['GeneratorPasses'],1)));

       $chilled_table->addRow();
       $chilled_table->addCell(700)->addText(htmlspecialchars("5."));
       $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['hot_water_pressure_loss']));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->PressureDropUnit]));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['HotWaterFrictionLoss'],1)));

       $chilled_table->addRow();
       $chilled_table->addCell(700)->addText(htmlspecialchars("6."));
       $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['hot_water_connection_dia']));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->NozzleDiameterUnit]));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['HotWaterConnectionDiameter'],1)));

       $chilled_table->addRow();
       $chilled_table->addCell(700)->addText(htmlspecialchars("7."));
       $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['max_working_pressure']));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars($units_data[$unit_set->WorkPressureUnit]));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars(round($calculation_values['all_work_pr_hw'],1)));



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
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['AbsorbentPumpMotorKW'],2) ."( ". round($calculation_values['AbsorbentPumpMotorAmp'],2)." )" ));

       $chilled_table->addRow();
       $chilled_table->addCell(700)->addText(htmlspecialchars("4."));
       $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['refrigerant_pump_rating']));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "kW (A)" ));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['RefrigerantPumpMotorKW'],2) ."( ". round($calculation_values['RefrigerantPumpMotorAmp'],2)." )" ));

       $chilled_table->addRow();
       $chilled_table->addCell(700)->addText(htmlspecialchars("5."));
       $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['vaccum_pump_rating']));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( "kW (A)" ));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['PurgePumpMotorKW'],2) ."( ". round($calculation_values['PurgePumpMotorAmp'],2)." )" ));
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
       $chilled_table->addCell(700,$cellRowSpan)->addText(htmlspecialchars("E"),$header);
       $chilled_table->addCell(2850,$cellRowSpan)->addText(htmlspecialchars($language_datas['physical_data']),$header);
       $chilled_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars(""),$header);
       $chilled_table->addCell(1750,$cellRowSpan)->addText(htmlspecialchars(""),$header);

       $chilled_table->addRow();
       $chilled_table->addCell(700)->addText(htmlspecialchars("1."));
       $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['length']));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->LengthUnit] ));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( ceil($calculation_values['Length']) ));

       $chilled_table->addRow();
       $chilled_table->addCell(700)->addText(htmlspecialchars("2."));
       $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['width']));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->LengthUnit] ));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( ceil($calculation_values['Width']) ));

       $chilled_table->addRow();
       $chilled_table->addCell(700)->addText(htmlspecialchars("3."));
       $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['height']));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->LengthUnit] ));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( ceil($calculation_values['Height'])));

       $chilled_table->addRow();
       $chilled_table->addCell(700)->addText(htmlspecialchars("4."));
       $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['operating_weight']));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->WeightUnit] ));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['OperatingWeight'],1) ));

       $chilled_table->addRow();
       $chilled_table->addCell(700)->addText(htmlspecialchars("5."));
       $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['shipping_weight']));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->WeightUnit] ));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['MaxShippingWeight'],1) ));

       $chilled_table->addRow();
       $chilled_table->addCell(700)->addText(htmlspecialchars("6."));
       $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['flooded_weight']));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->WeightUnit] ));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['FloodedWeight'],1) ));

       $chilled_table->addRow();
       $chilled_table->addCell(700)->addText(htmlspecialchars("7."));
       $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['dry_weight']));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->WeightUnit] ));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['DryWeight'],1) ));

       $chilled_table->addRow();
       $chilled_table->addCell(700)->addText(htmlspecialchars("8."));
       $chilled_table->addCell(2850)->addText(htmlspecialchars($language_datas['tube_clearing_space']." (".$language_datas['one_side_length_wise'].")"));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( $units_data[$unit_set->LengthUnit] ));
       $chilled_table->addCell(1750)->addTextRun($alignment)->addText(htmlspecialchars( round($calculation_values['ClearanceForTubeRemoval'],1) ));

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

       $file_name = "H2-Steam-Fired-Series".Auth::user()->id.".docx";
       $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
       try {
           $objWriter->save(storage_path($file_name));
       } catch (Exception $e) {
           Log::info($e);
       }
   }
}
