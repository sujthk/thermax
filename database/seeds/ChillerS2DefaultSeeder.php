<?php

use Illuminate\Database\Seeder;
use App\ChillerDefaultValue;
class ChillerS2DefaultSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $cooling_water_ranges = array(87.5,217.4,218.2,234.9);
        $s2_c2_130_default =  array('model_number' => 130,'model_name' => "TAC S2 C3",'capacity' => 114,'chilled_water_in' => 12,'chilled_water_out' => 7,'min_chilled_water_out' => 0,'cooling_water_in' => 32,'cooling_water_flow' => 114,'cooling_water_in_min_range' =>25.0,'cooling_water_in_max_range' => 36.0,'cooling_water_ranges' => $cooling_water_ranges,'evaporator_material_value' => 2,'evaporator_thickness' => 0.5700,'evaporator_thickness_min_range' => 0.57,'evaporator_thickness_max_range' => 1.0,'absorber_material_value' => 2,'absorber_thickness' => 0.6500,'absorber_thickness_min_range' => 0.65,'absorber_thickness_max_range' => 1,'condenser_material_value' => 2,'condenser_thickness' => 0.6500,'condenser_thickness_min_range' => 0.65,'condenser_thickness_max_range' => 1,'glycol_selected' => 1,'glycol_none' => false,'metallurgy_standard' => true,'glycol_chilled_water' => 0.0,'glycol_cooling_water' => 0.0,'steam_pressure_min_range' => 3.5,'steam_pressure_max_range' => 10.0,'steam_pressure' => 8.0,'fouling_factor' => "standard",'fouling_non_chilled' => 0.00001,'fouling_non_cooling' => 0.00001,'fouling_ari_chilled' => 0.00002,'fouling_ari_cooling' => 0.00005,'calculate_option' => true);



        $chiller_default_value = new ChillerDefaultValue;
        $chiller_default_value->name = "Double Effect : Steam S2 Series 130-2560NTR";
        $chiller_default_value->code = "D_S2";
        $chiller_default_value->model = 130;
        $chiller_default_value->default_values = json_encode($s2_c2_130_default);
        $chiller_default_value->save();



    }
}
