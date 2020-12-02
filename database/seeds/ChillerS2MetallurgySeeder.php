<?php

use Illuminate\Database\Seeder;
use App\ChillerMetallurgyOption;

class ChillerS2MetallurgySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $eva_options = array();
        $eva_options[] = array('name' => 'CuNi (90:10,95:5) Finned','value' => '1');
        $eva_options[] = array('name' => 'Cu Finned','value' => '2');
        $eva_options[] = array('name' => 'SS Finned','value' => '3');
        $eva_options[] = array('name' => 'SS Mini Finned','value' => '4');
        $eva_options[] = array('name' => 'Titanium Plain','value' => '5');

        $abs_options = array();
		$abs_options[] = array('name' => 'CuNi (90:10,95:5) Mini Finned','value' => '1');
		$abs_options[] = array('name' => 'Cu Mini Finned','value' => '2');
		$abs_options[] = array('name' => 'SS Plain ERW','value' => '5');
		$abs_options[] = array('name' => 'SS Mini finned','value' => '6');
		$abs_options[] = array('name' => 'Titanium Plain','value' => '7');

		$con_options = array();
		$con_options[] = array('name' => 'CuNi (90:10,95:5) Mini Finned','value' => '1');
		$con_options[] = array('name' => 'Cu Mini Finned','value' => '2');
		$con_options[] = array('name' => 'SS Plain ERW','value' => '3');
		$con_options[] = array('name' => 'SS Mini finned','value' => '4');
		$con_options[] = array('name' => 'Titanium Plain','value' => '5');


		$chiller_metallurgy_option = new ChillerMetallurgyOption;
		$chiller_metallurgy_option->name = "Double Effect : Steam S2 Series 130-2560NTR";
        $chiller_metallurgy_option->code = "D_S2";
        $chiller_metallurgy_option->model = 130;
        $chiller_metallurgy_option->evaporator_options = json_encode($eva_options);
        $chiller_metallurgy_option->absorber_options = json_encode($abs_options);
        $chiller_metallurgy_option->condenser_options = json_encode($con_options);
        $chiller_metallurgy_option->save();

    }
}
