<?php

use Illuminate\Database\Seeder;
use App\ChillerCalculationValue;

class ChillerCalculationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $calculation_values = array('TCWA' => '32','AT13' => '101','LE' => '2.072','TNEV' => '304','TNAA' => '276','TNC' => '140','AEVA' => '31.0','AABS' => '28.2','ACON' => '14.3','ALTG' => '12.7','AHTG' => '10.9','ALTHE' => '13.806','AHTHE' => '10.6384','ADHE' => '2.57','AHR' => '3.73','MODEL1' => '100','KEVA' => '2790.72','KABS' => '1525.39387','SFACTOR' => '0.891','KCON' => '4200','ULTHE' => '450','ULTG' => '1850','ODE' => '0.016','ODA' => '0.016','ODC' => '0.016','AEVAH' => '15.5','AEVAL' => '15.5','AABSH' => '14.1','AABSL' => '14.1','UHTHE' => '1400','UDHE' => '400','UHR' => '700','UHTG' => '1750','IDE' => '0.01486','IDA' => '0.0145','IDC' => '0.0145','PNB' => '150','PODA' => '168.3','THPA' => '7.11','PNB1' => '125','PNB2' => '100','SL1' => '0.49','SL2' => '0.82','SL3' => '0.348','SL4' => '0.204','SL5' => '0.204','SL6' => '0.123','SL7' => '0.82','SL8' => '0.49','SHE' => '1.525','PNB' => '150','PSLI' => '0.660','PSLO' => '0.568','PSL2' => '0.481','SHA' => '1.946');


        $chiller_calculation_value = new ChillerCalculationValue;
        $chiller_calculation_value->name = "Double Effect : Steam S2 Series 130-2560NTR";
        $chiller_calculation_value->code = "D_S2";
        $chiller_calculation_value->min_model = 130;
        $chiller_calculation_value->calculation_values = json_encode($calculation_values);
        $chiller_calculation_value->save();
    }
}
