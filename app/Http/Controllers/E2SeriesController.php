<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\VamBaseController;
use App\Http\Controllers\UnitConversionController;
use App\Http\Controllers\ReportController;
use Illuminate\Http\Request;
use App\ChillerDefaultValue;
use App\ChillerMetallurgyOption;
use App\ChillerCalculationValue;
use App\UserReport;
use App\NotesAndError;
use App\UnitSet;
use App\Region;
use Exception;
use Log;
use PDF;
use DB;

class E2SeriesController extends Controller
{
    



    public function RANGECAL()
    {

        $FMIN1 = 0; 
        $FMAX1 = 0;
        $TAPMAX = 0;
        $FMAX = array();
        $FMIN = array();

        $model_number = (int)$this->model_values['model_number'];
        $chilled_water_out = $this->model_values['chilled_water_out'];
        $capacity = $this->model_values['capacity'];

        $GCWMIN1 = $this->RANGECAL1($model_number,$chilled_water_out,$capacity);

        $this->updateInputs();


        // $chiller_data = $this->getChillerData();

        $IDC = floatval($this->calculation_values['IDC']);
        $IDA = floatval($this->calculation_values['IDA']);
        $TNC = floatval($this->calculation_values['TNC']);
        $TNAA = floatval($this->calculation_values['TNAA']);
        $PODA = floatval($this->calculation_values['PODA']);
        $THPA = floatval($this->calculation_values['THPA']);


        $chiller_metallurgy_options = ChillerMetallurgyOption::with('chillerOptions.metallurgy')->where('code',$this->model_code)
                                        ->where('min_model','<=',$model_number)->where('max_model','>',$model_number)->first();

        $chiller_options = $chiller_metallurgy_options->chillerOptions;

        // $evaporator_option = $chiller_options->where('type', 'eva')->where('value',$this->model_values['evaporator_material_value'])->first();
        $absorber_option = $chiller_options->where('type', 'abs')->where('value',$this->calculation_values['TU5'])->first();
        $condenser_option = $chiller_options->where('type', 'con')->where('value',$this->calculation_values['TV5'])->first();


        if($this->calculation_values['MODEL'] < 300){
            $TCP = 2;
        }
        else{
            $TCP = 1;
        }


        $VAMIN = $absorber_option->metallurgy->abs_min_velocity;          
        $VAMAX = $absorber_option->metallurgy->abs_max_velocity;
        $VCMIN = $condenser_option->metallurgy->con_min_velocity;
        $VCMAX = $condenser_option->metallurgy->con_max_velocity;



        $GCWMIN = 3.141593 / 4 * $IDC * $IDC * $VCMIN * $TNC * 3600 / $TCP;     //min required flow in condenser
        $GCWCMAX = 3.141593 / 4 * $IDC * $IDC * $VCMAX * $TNC * 3600 / 1;

        if ($GCWMIN > $GCWMIN1)
            $GCWMIN2 = $GCWMIN;
        else
            $GCWMIN2 = $GCWMIN1;

        $TAPMAX = 4;

        $FMIN[$TAPMAX] = 3.141593 / 4 * $IDA * $IDA * 3600 * $TNAA / $TAPMAX * $VAMIN;

        if ($FMIN[$TAPMAX] < $GCWMIN2)
            $FMIN[$TAPMAX] = $GCWMIN2;

        $FMAX[$TAPMAX] = 3.141593 / 4 * $IDA * $IDA * 3600 * $TNAA / $TAPMAX * $VAMAX;

        $GCWMAX = 3.141593 / 4 * $IDA * $IDA * 3600 * $TNAA * $VAMAX;
        $INIT = 1;
        if ($FMIN[$TAPMAX] > $GCWMAX)
        {
            return array('status' => false,'msg' => $this->notes['NOTES_COW_MAX_LIM']  );
        }
        else
        {
            $FMIN1 = $FMIN[$TAPMAX];
            $FMAX1 = $FMAX[$TAPMAX];
            for ($TAP = $TAPMAX - 1; $TAP >= 1; $TAP--)
            {
                $FMIN[$TAP] = 3.141593 / 4 * $IDA * $IDA * 3600 * $TNAA / $TAP * $VAMIN;
                $FMAX[$TAP] = 3.141593 / 4 * $IDA * $IDA * 3600 * $TNAA / $TAP * $VAMAX;
                if ($FMIN[$TAP] > $FMAX1 && $FMIN1 < $FMAX1)
                {
                    $FLOWMN[$INIT] = $FMIN1;
                    $FLOWMX[$INIT] = $FMAX1;
                    $INIT++;
                    $FMIN1 = $FMIN[$TAP];
                    $FMAX1 = $FMAX[$TAP];
                }
                else
                {
                    $FMAX1 = $FMAX[$TAP];
                }
            }
        }

        // PR_DROP_DATA();
        $PIDA = ($PODA - (2 * $THPA)) / 1000;
        $APA = 3.141593 * $PIDA * $PIDA / 4;


        if ($MODEL == 130 || $MODEL == 810 || $MODEL == 900)  //change
        {
            $GCWPMAX = $APA * 3.5 * 3600;
        }
        else if ($MODEL == 310 || $MODEL == 350 || $MODEL == 410 || $MODEL == 470 || $MODEL == 530 || $MODEL == 580 || $MODEL == 630 || $MODEL == 710)
        {
            $GCWPMAX = $APA * 3.8 * 3600;
        }
        else
        {
            $GCWPMAX = $APA * 4 * 3600;
        }

        if ($FMAX1 > $GCWPMAX)
        {
            $FMAX1 = $GCWPMAX;
        }
        //if ($MODEL < 350 && $GCWCMAX < $$FMAX1)
        //{
        //    $$FMAX1 = $GCWCMAX;
        //}

        if ($FMIN1 < $FMAX1)
        {
            $FLOWMN[$INIT] = $FMIN1;
            $FLOWMX[$INIT] = $FMAX1;
        }
        else
        {
            return array('status' => false,'msg' => $this->notes['NOTES_COW_MAX_LIM']);
        }

        $range_values = array();
        foreach ($FLOWMN as $key => $min) {
            if(!empty($FLOWMX[$key])){
                $min = round($FLOWMN[$key], 1);
                $max = round($FLOWMX[$key], 1);

                $range_values[] = $min;
                $range_values[] = $max;
                // $range_values .= "(".$min." - ".$max.")<br>";
            }

        }

        // $range_values = array_sort($range_values);


        // for ($i=0; $i < $INIT; $i++) { 
        //  $range_values .= "(".$FMIN[$i]." - ".$FMAX[$i].")<br>";
        // }

        $this->model_values['cooling_water_ranges'] = $range_values;

        return array('status' => true,'msg' => "process run successfully");
    }


    public function RANGECAL1($model_number,$chilled_water_out,$capacity)
    {
        $TCHW12 = $chilled_water_out;
        $TON = $capacity;

        if ($model_number < 750.0)
        {
            if ($TCHW12 < 6.699 && $TCHW12 > 4.99)
                $KM1 = 1.8824 - 0.1765 * $TCHW12;
            else
            {
                if ($TCHW12 <= 4.99 && $TCHW12 > 4.5)
                    $KM1 = 1.0;
                else
                {
                    if ($TCHW12 <= 4.5 && $TCHW12 > 3.49)
                        $KM1 = 1.0 + (4.5 - $TCHW12) * 0.2;
                    else
                    {
                        if ($TCHW12 < 3.5)
                        {
                            $KM1 = 1.2;
                        }
                        else
                        {
                            $KM1 = 0.7;
                        }
                    }
                }
            }
            $GCWMIN1 = $TON * $KM1;
        }
        else
        {
            if ($TCHW12 < 6.699 && $TCHW12 > 4.99)
                $KM1 = 1.8824 - 0.1765 * $TCHW12;
            else
            {
                if ($TCHW12 <= 4.99 && $TCHW12 > 4.5)
                    $KM1 = 1.0;
                else
                {
                    if ($TCHW12 <= 4.5 && $TCHW12 > 3.49)
                        $KM1 = 1.0 + (4.5 - $TCHW12) * 0.2;
                    else
                    {
                        if ($TCHW12 < 3.5)
                        {
                            $KM1 = 1.2;
                        }
                        else
                        {
                            $KM1 = 0.7;
                        }
                    }
                }
            }
            $GCWMIN1 = $TON * $KM1;
        }

        return $GCWMIN1;
    }

    private function DATA()
    {

        if ($this->calculation_values['TCW11'] <= 32.0)
            $this->calculation_values['TCWA'] = $this->calculation_values['TCW11'];
        else
            $this->calculation_values['TCWA'] = 32.0;

        if ($this->calculation_values['TCW11'] < 29.4)
            $this->calculation_values['AT13'] = 99.99;
        else
            $this->calculation_values['AT13'] = ($this->calculation_values['A_AT13'] * $this->calculation_values['TCWA']) + $this->calculation_values['B_AT13'];

        $this->calculation_values['ALTHE'] = $this->calculation_values['ALTHE'] * $this->calculation_values['ALTHE_F'];
        $this->calculation_values['AHTHE'] = $this->calculation_values['AHTHE'] * $this->calculation_values['AHTHE_F'];
        $this->calculation_values['AHR'] = $this->calculation_values['AHR'] * $this->calculation_values['AHR_F'];
       
        if($this->calculation_values['region_type'] == 2 || $this->calculation_values['region_type'] == 3)
        {
            $this->calculation_values['AT13'] =$this->calculation_values['AT13']-$this->calculation_values['EX_AT13'] ;
            $this->calculation_values['KEVA'] =$this->calculation_values['KEVA']*$this->calculation_values['EX_KEVA'] ;
            $this->calculation_values['KABS'] =$this->calculation_values['KABS']*$this->calculation_values['EX_KABS'] ;
        }



        $this->calculation_values['KEVA1'] = 1 / ((1 / $this->calculation_values['KEVA']) - (0.65 / 340000.0));

        if ($this->calculation_values['TU2'] == 2)
            $this->calculation_values['KEVA'] = 1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 340000.0));
        if ($this->calculation_values['TU2'] == 1)
            $this->calculation_values['KEVA'] = 1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 37000.0));
        if ($this->calculation_values['TU2'] == 4)
            $this->calculation_values['KEVA'] = (1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 21000.0))) * 0.93;
        if ($this->calculation_values['TU2'] == 3)
            $this->calculation_values['KEVA'] = 1 / ((1 / $this->calculation_values['KEVA1']) + ($this->calculation_values['TU3'] / 21000.0)) * 0.93;            //Changed to $this->calculation_values['KEVA1'] from 1600 on 06/11/2017 as tube metallurgy is changed
        if ($this->calculation_values['TU2'] == 5)
            $this->calculation_values['KEVA'] = 1 / ((1 / 1600.0) + ($this->calculation_values['TU3'] / 15000.0));
        /********* VARIATION OF $this->calculation_values['KABS'] WITH CON METALLURGY ****/
        if ($this->calculation_values['TU2'] == 6 || $this->calculation_values['TU2'] == 7 || $this->calculation_values['TU2'] == 8)
        {
            if ($this->calculation_values['TV5'] == 1)
                $this->calculation_values['KM5'] = 1;
            else if ($this->calculation_values['TV5'] == 2)
                $this->calculation_values['KM5'] = 1;
            else if ($this->calculation_values['TV5'] == 3)
                $this->calculation_values['KM5'] = 1;
            else if ($this->calculation_values['TV5'] == 4)
                $this->calculation_values['KM5'] = 1;
            else if ($this->calculation_values['TV5'] == 5)
                $this->calculation_values['KM5'] = 1;
            else
                $this->calculation_values['KM5'] = 1;
        }
        else
            $this->calculation_values['KM5'] = 1;
        /********* DETERMINATION OF $this->calculation_values['KABS'] FOR NONSTD. SELECTION****/
        $this->calculation_values['KABS1'] = 1 / ((1 / $this->calculation_values['KABS']) - (0.65 / 340000));
        if ($this->calculation_values['TU5'] == 1)
        {
            $this->calculation_values['KABS'] = 1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 37000));
        }
        else
        {
            if ($this->calculation_values['TU5'] == 2)
                $this->calculation_values['KABS'] = 1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 340000));
            if ($this->calculation_values['TU5'] == 6)
                $this->calculation_values['KABS'] = (1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 21000))) * 0.93;
            else
            {
                $this->calculation_values['KABS1'] = 1240;
                if ($this->calculation_values['TU5'] == 3)
                    $this->calculation_values['KABS'] = 1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 37000));
                if ($this->calculation_values['TU5'] == 4)
                    $this->calculation_values['KABS'] = 1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 340000));
                if ($this->calculation_values['TU5'] == 5)
                    $this->calculation_values['KABS'] = 1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 21000));
                if ($this->calculation_values['TU5'] == 7)
                    $this->calculation_values['KABS'] = 1 / ((1 / $this->calculation_values['KABS1']) + ($this->calculation_values['TU6'] / 15000));
            }
        }
        $this->calculation_values['KABS'] = $this->calculation_values['KABS'] * $this->calculation_values['KM5'];


        /********** DETERMINATION OF $this->calculation_values['KCON'] IN NONSTD. SELECTION*******/
        $this->calculation_values['KCON1'] = 1 / ((1 / $this->calculation_values['KCON']) - (0.65 / 340000));

        if ($this->calculation_values['TV5'] == 1)
        {
            //$this->calculation_values['KCON1'] = 4000;
            $this->calculation_values['KCON'] = 1 / ((1 / $this->calculation_values['KCON1']) + ($this->calculation_values['TV6'] / 37000));
        }
        else if ($this->calculation_values['TV5'] == 2)
            $this->calculation_values['KCON'] = 1 / ((1 / $this->calculation_values['KCON1']) + ($this->calculation_values['TV6'] / 340000));
        else if ($this->calculation_values['TV5'] == 4)
            $this->calculation_values['KCON'] = 1 / ((1 / $this->calculation_values['KCON1']) + ($this->calculation_values['TV6'] / 21000)) * 0.95;
        else
        {
            $this->calculation_values['KCON1'] = 3000;
            if ($this->calculation_values['TV5'] == 3)
                $this->calculation_values['KCON'] = 1 / ((1 / $this->calculation_values['KCON1']) + ($this->calculation_values['TV6'] / 21000));
            if ($this->calculation_values['TV5'] == 5)
                $this->calculation_values['KCON'] = 1 / ((1 / $this->calculation_values['KCON1']) + ($this->calculation_values['TV6'] / 15000));
        }
        //if ($this->calculation_values['TV5'] == 0)
        //{
        //    $this->calculation_values['KCON'] = 3000 * 2;
        //}

        $this->calculation_values['AEVAH'] = $this->calculation_values['AEVA'] / 2;
        $this->calculation_values['AEVAL'] = $this->calculation_values['AEVA'] / 2;
        $this->calculation_values['AABSH'] = $this->calculation_values['AABS'] / 2;
        $this->calculation_values['AABSL'] = $this->calculation_values['AABS'] / 2;
    }



    private function THICKNESS()
    {
        $this->calculation_values['THE'] = 0;
        $this->calculation_values['THA'] = 0;
        $this->calculation_values['THC'] = 0;

        /********** EVA THICKNESS *********/
        if ($this->calculation_values['TU3'] == 0.0)
        {
            //if (MODEL < 750)
            //    $this->calculation_values['THE'] = 0.57;
            //else
                $this->calculation_values['THE'] = 0.65;
        }
        else
        {
            $this->calculation_values['THE'] = $this->calculation_values['TU3'];
        }

        /********** ABS THICKNESS *********/
        if ($this->calculation_values['TU6'] == 0.0)
        {
            $this->calculation_values['THA'] = 0.65;
        }
        else
        {
            $this->calculation_values['THA'] = $this->calculation_values['TU6'];
        }

        /********** COND THICKNESS *********/
        if ($this->calculation_values['TV6'] == 0.0)
        {
            $this->calculation_values['THC'] = .65;
        }
        else
        {
            $this->calculation_values['THC'] = $this->calculation_values['TV6'];
        }

        
        if ($this->calculation_values['TU2'] < 2.1 || $this->calculation_values['TU2'] == 4)
            $this->calculation_values['IDE'] = $this->calculation_values['ODE'] - ((2.0 * ($this->calculation_values['THE'] + 0.1)) / 1000.0);
        else
            $this->calculation_values['IDE'] = $this->calculation_values['ODE'] - ((2.0 * $this->calculation_values['THE']) / 1000.0);

        if ($this->calculation_values['TU5'] < 2.1 || $this->calculation_values['TU5'] == 6)
            $this->calculation_values['IDA'] = $this->calculation_values['ODA'] - ((2.0 * ($this->calculation_values['THA'] + 0.1)) / 1000.0);
        else
            $this->calculation_values['IDA'] = $this->calculation_values['ODA'] - ((2.0 * $this->calculation_values['THA']) / 1000.0);

        if ($this->calculation_values['TV5'] == 1 || $this->calculation_values['TV5'] == 2 || $this->calculation_values['TV5'] == 0 || $this->calculation_values['TV5'] == 4)
            $this->calculation_values['IDC'] = $this->calculation_values['ODC'] - ((2.0 * ($this->calculation_values['THC'] + 0.1)) / 1000.0);
        else
            $this->calculation_values['IDC'] = $this->calculation_values['ODC'] - ((2.0 * $this->calculation_values['THC']) / 1000.0);
        


    }

    public function WATERPROP(){
    {
        $vam_base = new VamBaseController();

        if ($this->calculation_values['GLL'] == 2)
        {
            $this->calculation_values['CHGLY_VIS12'] = $vam_base->EG_VISCOSITY($this->calculation_values['TCHW12'], $this->calculation_values['CHGLY']) / 1000;
            $this->calculation_values['CHGLY_TCON12'] = $vam_base->EG_THERMAL_CONDUCTIVITY($this->calculation_values['TCHW12'], $this->calculation_values['CHGLY']);
            $this->calculation_values['CHGLY_ROW12'] = $vam_base->EG_ROW($this->calculation_values['TCHW12'], $this->calculation_values['CHGLY']);
            $this->calculation_values['CHGLY_SPHT12'] = $vam_base->EG_SPHT($this->calculation_values['TCHW12'], $this->calculation_values['CHGLY']) * 1000;

            $this->calculation_values['COGLY_VISH1'] = $vam_base->EG_VISCOSITY($this->calculation_values['TCW11'], $this->calculation_values['COGLY']) / 1000;
            $this->calculation_values['COGLY_TCONH1'] = $vam_base->EG_THERMAL_CONDUCTIVITY($this->calculation_values['TCW11'], $this->calculation_values['COGLY']);
            $this->calculation_values['COGLY_ROWH1'] = $vam_base->EG_ROW($this->calculation_values['TCW11'], $this->calculation_values['COGLY']);
            $this->calculation_values['COGLY_SPHT1'] = $vam_base->EG_SPHT($this->calculation_values['TCW11'], $this->calculation_values['COGLY']) * 1000;
        }
        else
        {
            $this->calculation_values['CHGLY_VIS12'] = $vam_base->PG_VISCOSITY($this->calculation_values['TCHW12'], $this->calculation_values['CHGLY']) / 1000;
            $this->calculation_values['CHGLY_TCON12'] = $vam_base->PG_THERMAL_CONDUCTIVITY($this->calculation_values['TCHW12'], $this->calculation_values['CHGLY']);
            $this->calculation_values['CHGLY_ROW12'] = $vam_base->PG_ROW($this->calculation_values['TCHW12'], $this->calculation_values['CHGLY']);
            $this->calculation_values['CHGLY_SPHT12'] = $vam_base->PG_SPHT($this->calculation_values['TCHW12'], $this->calculation_values['CHGLY']) * 1000;

            $this->calculation_values['COGLY_VISH1'] = $vam_base->PG_VISCOSITY($this->calculation_values['TCW11'], $this->calculation_values['COGLY']) / 1000;
            $this->calculation_values['COGLY_TCONH1'] = $vam_base->PG_THERMAL_CONDUCTIVITY($this->calculation_values['TCW11'], $this->calculation_values['COGLY']);
            $this->calculation_values['COGLY_ROWH1'] = $vam_base->PG_ROW($this->calculation_values['TCW11'], $this->calculation_values['COGLY']);
            $this->calculation_values['COGLY_SPHT1'] = $vam_base->PG_SPHT($this->calculation_values['TCW11'], $this->calculation_values['COGLY']) * 1000;

        }
        $this->calculation_values['GCHW'] = $this->calculation_values['TON'] * 3024 / (($this->calculation_values['TCHW11'] - $this->calculation_values['TCHW12']) * $this->calculation_values['CHGLY_ROW12'] * $this->calculation_values['CHGLY_SPHT12'] / 4187);
    }



    public function VELOCITY(){
    {
        $this->calculation_values['VELEVA'] = 0;

        $this->calculation_values['TAP'] = 0;
        do
        {
            $this->calculation_values['TAP'] = $this->calculation_values['TAP'] + 1;
            $this->calculation_values['VA'] = $this->calculation_values['GCW'] / (((3600 * 3.141593 * $this->calculation_values['IDA'] * $this->calculation_values['IDA']) / 4.0) * ($this->calculation_values['TNAA'] / $this->calculation_values['TAP']));
        } while ($this->calculation_values['VA'] < $this->calculation_values['VAMAX']);


        if ($this->calculation_values['VA'] > ($this->calculation_values['VAMAX'] + 0.01) && $this->calculation_values['TAP'] != 1)
        {
            $this->calculation_values['TAP'] = $this->calculation_values['TAP'] - 1;
            $this->calculation_values['VA'] = $this->calculation_values['GCW'] / (((3600 * 3.141593 * $this->calculation_values['IDA'] * $this->calculation_values['IDA']) / 4.0) * ($this->calculation_values['TNAA'] / $this->calculation_values['TAP']));
        }

        if ($this->calculation_values['TAP'] == 1)           //PARAFLOW
        {
            $this->calculation_values['GCWAH'] = 0.5 * $this->calculation_values['GCW'];
            $this->calculation_values['GCWAL'] = 0.5 * $this->calculation_values['GCW'];
        }
        else                //SERIES FLOW
        {
            $this->calculation_values['GCWAH'] = $this->calculation_values['GCW'];
            $this->calculation_values['GCWAL'] = $this->calculation_values['GCW'];
        }

        /**************** CONDENSER VELOCITY ******************/
        $this->calculation_values['TCP'] = 1;
        $this->calculation_values['GCWCMAX'] = 3.141593 / 4 * ($this->calculation_values['IDC'] * $this->calculation_values['IDC']) * $this->calculation_values['TNC'] * $this->calculation_values['VCMAX'] * 3600 / $this->calculation_values['TCP'];
        if ($this->calculation_values['GCW'] > $this->calculation_values['GCWCMAX'])
            $this->calculation_values['GCWC'] = $this->calculation_values['GCWCMAX'];
        else
            $this->calculation_values['GCWC'] = $this->calculation_values['GCW'];

        if ($this->calculation_values['MODEL'] < 300)
        {
            do
            {
                $this->calculation_values['VC'] = ($this->calculation_values['GCWC'] * 4) / (3.141593 * $this->calculation_values['IDC'] * $this->calculation_values['IDC'] * $this->calculation_values['TNC'] * 3600 / $this->calculation_values['TCP']);

                if ($this->calculation_values['VC'] < 1.4)
                {
                    $this->calculation_values['TCP'] = $this->calculation_values['TCP'] + 1;
                }

                if ($this->calculation_values['TCP'] > 2)
                {
                    $this->calculation_values['TCP'] = 2;
                    $this->calculation_values['VC'] = ($this->calculation_values['GCWC'] * 4) / (3.141593 * $this->calculation_values['IDC'] * $this->calculation_values['IDC'] * $this->calculation_values['TNC'] * 3600 / $this->calculation_values['TCP']);
                    break;
                }

            } while ($this->calculation_values['VC'] < 1.4);
        }
        else
        {
            $this->calculation_values['VC'] = ($this->calculation_values['GCWC'] * 4) / (3.141593 * $this->calculation_values['IDC'] * $this->calculation_values['IDC'] * $this->calculation_values['TNC'] * 3600 / $this->calculation_values['TCP']);
        }


        /********************* EVAPORATOR VELOCITY ********************/
        $this->calculation_values['GCHW'] = $this->calculation_values['TON'] * 3024 / (($this->calculation_values['TCHW11'] - $this->calculation_values['TCHW12']) * $this->calculation_values['CHGLY_ROW12'] * $this->calculation_values['CHGLY_SPHT12'] / 4187);
        if ($this->calculation_values['TU2'] == 3 && $this->calculation_values['TCHW12'] < 3.5 && $this->calculation_values['CHGLY'] == 0)
        {
            $this->calculation_values['TP'] = 1;
            do
            {
                $this->calculation_values['VEA'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * (($this->calculation_values['TNEV'] / 2) / $this->calculation_values['TP']));
                if ($this->calculation_values['VEA'] < 0.9)
                    $this->calculation_values['TP'] = $this->calculation_values['TP'] + 1;
            } while ($this->calculation_values['VEA'] < 0.9 && $this->calculation_values['TP'] <= 4);
            if ($this->calculation_values['TP'] > 4)
            {
                $this->calculation_values['TP'] = 4;
                $this->calculation_values['VEA'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * (($this->calculation_values['TNEV'] / 2) / $this->calculation_values['TP']));

                if ($this->calculation_values['VEA'] < 0.80)
                {
                    return  array('status' => false,'msg' => $this->notes['NOTES_CHW_VELO']);
                }
            }
            if ($this->calculation_values['VEA'] > 1.8)                        // 06/11/2017
            {
                if ($this->calculation_values['TP'] == 1)
                {
                    return  array('status' => false,'msg' => $this->notes['NOTES_CHW_VELO_HI']);
                }
                else
                {
                    $this->calculation_values['TP'] = $this->calculation_values['TP'] - 1;
                    $this->calculation_values['VEA'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * (($this->calculation_values['TNEV'] / 2) / $this->calculation_values['TP']));
                }
            }
        }
        else 
        {
            if (($this->calculation_values['TU2'] == 3 && $this->calculation_values['TCHW12'] >= 3.5) || ($this->calculation_values['TU2'] == 3 && $this->calculation_values['TCHW12'] < 3.5 && $this->calculation_values['CHGLY'] != 0))
            {
                $this->calculation_values['TP'] = 1;
                do
                {
                    $this->calculation_values['VEA'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * (($this->calculation_values['TNEV'] / 2) / $this->calculation_values['TP']));
                    if ($this->calculation_values['VEA'] < 0.7)
                        $this->calculation_values['TP'] = $this->calculation_values['TP'] + 1;
                } while ($this->calculation_values['VEA'] < 0.7 && $this->calculation_values['TP'] <= 4);

                if ($this->calculation_values['TP'] > 4)
                {
                    $this->calculation_values['TP'] = 4;
                    $this->calculation_values['VEA'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * (($this->calculation_values['TNEV'] / 2) / $this->calculation_values['TP']));

                    if ($this->calculation_values['VEA'] < 0.60)
                    {
                        return  array('status' => false,'msg' => $this->notes['NOTES_CHW_VELO']);
                    }
                }

                if ($this->calculation_values['VEA'] > 1.8)                         // 14 FEB 2012
                {
                    if ($this->calculation_values['TP'] == 1)
                    {
                        return  array('status' => false,'msg' => $this->notes['NOTES_CHW_VELO_HI']);
                    }
                    else
                    {
                        $this->calculation_values['TP'] = $this->calculation_values['TP'] - 1;
                        $this->calculation_values['VEA'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * (($this->calculation_values['TNEV'] / 2) / $this->calculation_values['TP']));
                    }
                }
            }
            else
            {
                $this->calculation_values['TP'] = 1;
                do
                {
                    $this->calculation_values['VEA'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * (($this->calculation_values['TNEV'] / 2) / $this->calculation_values['TP']));
                    if ($this->calculation_values['VEA'] < 1.5)
                        $this->calculation_values['TP'] = $this->calculation_values['TP'] + 1;
                } while ($this->calculation_values['VEA'] < 1.5 && $this->calculation_values['TP'] <= 4);

                if ($this->calculation_values['TP'] > 4)
                {
                    $this->calculation_values['TP'] = 4;
                    $this->calculation_values['VEA'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * (($this->calculation_values['TNEV'] / 2) / $this->calculation_values['TP']));
                    if ($this->calculation_values['VEA'] < 1.4)
                    {
                        return  array('status' => false,'msg' => $this->notes['NOTES_CHW_VELO']);
                    }
                }
                if ($this->calculation_values['MODEL'] < 1200)
                {
                    if ($this->calculation_values['VEA'] > 2.64)
                    {
                        if ($this->calculation_values['TP'] == 1)
                        {
                            return  array('status' => false,'msg' => $this->notes['NOTES_CHW_VELO_HI']);
                        }
                        else
                        {
                            $this->calculation_values['TP'] = $this->calculation_values['TP'] - 1;
                            $this->calculation_values['VEA'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * (($this->calculation_values['TNEV'] / 2) / $this->calculation_values['TP']));
                        }
                    }
                }
                else
                {
                    if ($this->calculation_values['VEA'] > 2.78)                          // 14 FEB 2012
                    {
                        if ($this->calculation_values['TP'] == 1)
                        {
                            return  array('status' => false,'msg' => $this->notes['NOTES_CHW_VELO_HI']);
                        }
                        else
                        {
                            $this->calculation_values['TP'] = $this->calculation_values['TP'] - 1;
                            $this->calculation_values['VEA'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * (($this->calculation_values['TNEV'] / 2) / $this->calculation_values['TP']));
                        }
                    }
                }
            }
        }

        $vam_base = new VamBaseController();

        $pid_ft1 = $vam_base->PIPE_ID($this->calculation_values['PNB1']);
        $this->calculation_values['PIDE1'] = $pid_ft1['PID'];
        $this->calculation_values['FT1'] = $pid_ft1['FT'];

        $pid_ft2 = $vam_base->PIPE_ID($this->calculation_values['PNB2']);
        $this->calculation_values['PIDE2'] = $pid_ft2['PID'];
        $this->calculation_values['FT2'] = $pid_ft2['FT'];

        $pid_ft = $vam_base->PIPE_ID($this->calculation_values['PNB']);
        $this->calculation_values['PIDA'] = $pid_ft['PID'];
        $this->calculation_values['FT'] = $pid_ft['FT'];

        // PR_DROP_DATA();
        $this->PR_DROP_CHILL();

        if ($this->calculation_values['FLE'] > 12)
        {
            //if ($this->calculation_values['MODEL'] < 750 && $this->calculation_values['TU2'] < 2.1)
            //{
            //    $this->calculation_values['VEMIN'] = 0.45;
            //}
            //else
            {
                $this->calculation_values['VEMIN'] = 1;
            }
            $this->calculation_values['TP'] = 1;
            do
            {
                $this->calculation_values['VEA'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * (($this->calculation_values['TNEV'] / 2) / $this->calculation_values['TP']));
                if ($this->calculation_values['VEA'] < $this->calculation_values['VEMIN'])
                    $this->calculation_values['TP'] = $this->calculation_values['TP'] + 1;
            } while ($this->calculation_values['VEA'] < $this->calculation_values['VEMIN'] && $this->calculation_values['TP'] <= 4);

            if ($this->calculation_values['TP'] > 4)
            {
                $this->calculation_values['TP'] = 4;
                $this->calculation_values['VEA'] = $this->calculation_values['GCHW'] / (((3600 * 3.141593 * $this->calculation_values['IDE'] * $this->calculation_values['IDE']) / 4.0) * (($this->calculation_values['TNEV'] / 2) / $this->calculation_values['TP']));
            }
        }
        return  array('status' => true,'msg' => "chilled water velocity");
    }

    

    public function PR_DROP_CHILL(){

        $vam_base = new VamBaseController();

        $this->calculation_values['CHGLY_ROW22'] = 0;
        $this->calculation_values['CHGLY_VIS22'] = 0;
        $this->calculation_values['FE1'] = 0;
        $this->calculation_values['F'] = 0;

        // $this->calculation_values['PIDE1'] = (PODE1 - (2 * THPE1)) / 1000;
        // $this->calculation_values['PIDE2'] = (PODE2 - (2 * THPE2)) / 1000;

        $this->calculation_values['VPE1'] = ($this->calculation_values['GCHW'] * 4) / (3.141593 * $this->calculation_values['PIDE1'] * $this->calculation_values['PIDE1'] * 3600);

        if ($this->calculation_values['MODEL'] > 300)
        {
            $this->calculation_values['VPE2'] = (0.5 * $this->calculation_values['GCHW'] * 4) / (3.141593 * $this->calculation_values['PIDE2'] * $this->calculation_values['PIDE2'] * 3600);
            $this->calculation_values['VPBR'] = (0.5 * $this->calculation_values['GCHW'] * 4) / (3.141593 * $this->calculation_values['PIDE1'] * $this->calculation_values['PIDE1'] * 3600);
        }
        else
        {
            $this->calculation_values['VPE2'] = 0;
            $this->calculation_values['VPBR'] = 0;
        }

        //PIPE1

        // double $this->calculation_values['VPE1'] = ($this->calculation_values['GCHW'] * 4) / (3.141593 * $this->calculation_values['PIDE1'] * $this->calculation_values['PIDE1'] * 3600);            //VELOCITY IN PIPE1
        $this->calculation_values['TME'] = ($this->calculation_values['TCHW11'] + $this->calculation_values['TCHW12']) / 2.0;

        if ($this->calculation_values['GLL'] == 3)
        {
            $this->calculation_values['CHGLY_ROW22'] = PG_ROW($this->calculation_values['TME'], $this->calculation_values['CHGLY']);
            $this->calculation_values['CHGLY_VIS22'] = PG_VISCOSITY($this->calculation_values['TME'], $this->calculation_values['CHGLY']) / 1000;
        }
        else
        {
            $this->calculation_values['CHGLY_ROW22'] = EG_ROW($this->calculation_values['TME'], $this->calculation_values['CHGLY']);
            $this->calculation_values['CHGLY_VIS22'] = EG_VISCOSITY($this->calculation_values['TME'], $this->calculation_values['CHGLY']) / 1000;
        }

        $this->calculation_values['REPE1'] = ($this->calculation_values['PIDE1'] * $this->calculation_values['VPE1'] * $this->calculation_values['CHGLY_ROW22']) / $this->calculation_values['CHGLY_VIS22'];

        if ($this->calculation_values['MODEL'] > 300)
        {
            $this->calculation_values['REPE2'] = ($this->calculation_values['PIDE2'] * $this->calculation_values['VPE2'] * $this->calculation_values['CHGLY_ROW22']) / $this->calculation_values['CHGLY_VIS22'];
            $this->calculation_values['REBR'] = ($this->calculation_values['PIDE1'] * $this->calculation_values['VPBR'] * $this->calculation_values['CHGLY_ROW22']) / $this->calculation_values['CHGLY_VIS22'];          //REYNOLDS NO IN PIPE1
        }
        else
        {
            $this->calculation_values['REPE2'] = 0;
            $this->calculation_values['REBR'] = 0;
        }

        $this->calculation_values['FF1'] = 1.325 / pow(log((0.0457 / (3.7 * $this->calculation_values['PIDE1'] * 1000)) + (5.74 / pow($this->calculation_values['REPE1'], 0.9))), 2);       //FRICTION FACTOR CAL

        if ($this->calculation_values['MODEL'] > 300)
        {
            $this->calculation_values['FF2'] = 1.325 / pow(log((0.0457 / (3.7 * $this->calculation_values['PIDE2'] * 1000)) + (5.74 / pow($this->calculation_values['REPE2'], 0.9))), 2);
            $this->calculation_values['FF3'] = 1.325 / pow(log((0.0457 / (3.7 * $this->calculation_values['PIDE1'] * 1000)) + (5.74 / pow($this->calculation_values['REBR'], 0.9))), 2);
        }
        else
        {
            $this->calculation_values['FF2'] = 0;
            $this->calculation_values['FF3'] = 0;
        }


        $this->calculation_values['FL1'] = ($this->calculation_values['FF1'] * ($this->calculation_values['SL1'] + $this->calculation_values['SL8']) / $this->calculation_values['PIDE1']) * ($this->calculation_values['VPE1'] * $this->calculation_values['VPE1'] / (2 * 9.81));

        if ($this->calculation_values['MODEL'] > 300)
        {
            $this->calculation_values['FL2'] = ($this->calculation_values['FF2'] * ($this->calculation_values['SL3'] + $this->calculation_values['SL4'] + $this->calculation_values['SL5'] + $this->calculation_values['SL6']) / $this->calculation_values['PIDE2']) * ($this->calculation_values['VPE2'] * $this->calculation_values['VPE2'] / (2 * 9.81));
            $this->calculation_values['FL3'] = ($this->calculation_values['FF3'] * ($this->calculation_values['SL2'] + $this->calculation_values['SL7']) / $this->calculation_values['PIDE1']) * ($this->calculation_values['VPBR'] * $this->calculation_values['VPBR'] / (2 * 9.81));
            $this->calculation_values['FL4'] = (2 * $this->calculation_values['FT1'] * 20 * $this->calculation_values['VPBR'] * $this->calculation_values['VPBR'] / (2 * 9.81)) + (2 * $this->calculation_values['FT2'] * 60 * $this->calculation_values['VPE2'] * $this->calculation_values['VPE2'] / (2 * 9.81)) + (2 * $this->calculation_values['FT2'] * 14 * $this->calculation_values['VPE2'] * $this->calculation_values['VPE2'] / (2 * 9.81));
            $this->calculation_values['FL5'] = ($this->calculation_values['VPE2'] * $this->calculation_values['VPE2'] / (2 * 9.81)) + (0.5 * $this->calculation_values['VPE2'] * $this->calculation_values['VPE2'] / (2 * 9.81));
        }
        else
        {
            $this->calculation_values['FL2'] = $this->calculation_values['FL3'] = $this->calculation_values['FL4'] = 0;
            $this->calculation_values['FL5'] = ($this->calculation_values['VPE1'] * $this->calculation_values['VPE1'] / (2 * 9.81)) + (0.5 * $this->calculation_values['VPE1'] * $this->calculation_values['VPE1'] / (2 * 9.81));
        }

        $this->calculation_values['FLP'] = $this->calculation_values['FL1'] + $this->calculation_values['FL2'] + $this->calculation_values['FL3'] + $this->calculation_values['FL4'] + $this->calculation_values['FL5'];      //EVAPORATOR PIPE LOSS

        $this->calculation_values['RE'] = ($this->calculation_values['VEA'] * $this->calculation_values['IDE'] * $this->calculation_values['CHGLY_ROW22']) / $this->calculation_values['CHGLY_VIS22'];            //REYNOLDS NO IN TUBES

        if (($this->calculation_values['MODEL'] < 1200 && $this->calculation_values['TU2'] == 3))
        {
            $this->calculation_values['F'] = 1.325 / pow(log((1.53 / (3.7 * $this->calculation_values['IDE'] * 1000)) + (5.74 / pow($this->calculation_values['RE'], 0.9))), 2);
            $this->calculation_values['FE1'] = $this->calculation_values['F'] * $this->calculation_values['LE'] * $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (2 * 9.81 * $this->calculation_values['IDE']);
        }
        else if ($this->calculation_values['MODEL'] > 1200 && $this->calculation_values['TU2'] == 3)                                         //06/11/2017   Changed for SS FInned
        {
            $this->calculation_values['F'] = (1.325 / pow(log((1.53 / (3.7 * $this->calculation_values['IDE'] * 1000)) + (5.74 / pow($this->calculation_values['RE'], 0.9))), 2)) * ((-0.0315 * $this->calculation_values['VEA']) + 0.85);
            $this->calculation_values['FE1'] = $this->calculation_values['F'] * $this->calculation_values['LE'] * $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (2 * 9.81 * $this->calculation_values['IDE']);

        }
        else if (($this->calculation_values['MODEL'] < 1200 && ($this->calculation_values['TU2'] == 1 || $this->calculation_values['TU2'] == 2 || $this->calculation_values['TU2'] == 0) || ($this->calculation_values['MODEL'] < 1200 && $this->calculation_values['TU2'] == 4)))                    // 12% AS PER EXPERIMENTATION      
        {
            $this->calculation_values['F'] = (0.0014 + (0.137 / pow($this->calculation_values['RE'], 0.32))) * 1.12;
            $this->calculation_values['FE1'] = 2 * $this->calculation_values['F'] * $this->calculation_values['LE'] * $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (9.81 * $this->calculation_values['IDE']);
        }
        else if ($this->calculation_values['MODEL'] > 1200 && ($this->calculation_values['TU2'] == 1 || $this->calculation_values['TU2'] == 2 || $this->calculation_values['TU2'] == 4 || $this->calculation_values['TU2'] == 0))
        {
            $this->calculation_values['F'] = 0.0014 + (0.137 / pow($this->calculation_values['RE'], 0.32));
            $this->calculation_values['FE1'] = 2 * $this->calculation_values['F'] * $this->calculation_values['LE'] * $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (9.81 * $this->calculation_values['IDE']);
        }
        else
        {
            $this->calculation_values['F'] = 0.0014 + (0.125 / pow($this->calculation_values['RE'], 0.32));
            $this->calculation_values['FE1'] = 2 * $this->calculation_values['F'] * $this->calculation_values['LE'] * $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (9.81 * $this->calculation_values['IDE']);
        }

        $this->calculation_values['FE2'] = $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (4 * 9.81);
        $this->calculation_values['FE3'] = $this->calculation_values['VEA'] * $this->calculation_values['VEA'] / (2 * 9.81);
        $this->calculation_values['FE4'] = (($this->calculation_values['FE1'] + $this->calculation_values['FE2'] + $this->calculation_values['FE3']) * $this->calculation_values['TP']) * 2;      //EVAPORATOR TUBE LOSS FOR DOUBLE ABS
        $this->calculation_values['FLE'] = $this->calculation_values['FLP'] + $this->calculation_values['FE4'];                //TOTAL FRICTION LOSS IN CHILLED WATER CKT
        $this->calculation_values['PDE'] = $this->calculation_values['FLE'] + $this->calculation_values['SHE'];                    //PRESSURE DROP IN CHILLED WATER CKT
    }
}
