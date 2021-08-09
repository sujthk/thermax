<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCalculatorReportsAllValuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('calculator_reports', function (Blueprint $table) {
            $table->increments('id');
            $table->string('version',30)->nullable();
            $table->string('user_mail',50)->nullable();
            $table->string('ip_address',30)->nullable();
            $table->string('customer_name')->nullable();
            $table->string('project_name')->nullable();
            $table->string('opportunity_number')->nullable();
            $table->string('unit_set',30)->nullable();
            $table->string('model_name',30)->nullable();
            $table->string('model_number',30)->nullable();
            $table->string('capacity',30)->nullable();
            $table->string('chilled_water_in',30)->nullable();
            $table->string('chilled_water_out',30)->nullable();
            $table->string('cooling_water_in',30)->nullable();
            $table->string('cooling_water_flow',30)->nullable();
            $table->string('glycol_selected',30)->nullable();
            $table->string('glycol_chilled_water',30)->nullable();
            $table->string('glycol_cooling_water',30)->nullable();
            $table->string('metallurgy_standard',30)->nullable();
            $table->string('evaporator_material_value',30)->nullable();
            $table->string('evaporator_thickness',30)->nullable();
            $table->string('absorber_material_value',30)->nullable();
            $table->string('absorber_thickness',30)->nullable();
            $table->string('condenser_material_value',30)->nullable();
            $table->string('condenser_thickness',30)->nullable();
            $table->string('fouling_factor',30)->nullable();
            $table->string('fouling_chilled_water_value',30)->nullable();
            $table->string('fouling_cooling_water_value',30)->nullable();
            $table->string('region_type',30)->nullable();
            $table->string('steam_pressure',30)->nullable();
            $table->string('fuel_type',30)->nullable();
            $table->string('fuel_value_type',30)->nullable();
            $table->string('calorific_value',30)->nullable();
            $table->string('hot_water_in',30)->nullable();
            $table->string('hot_water_out',30)->nullable();
            $table->string('all_work_pr_hw',30)->nullable();
            $table->string('exhaust_gas_in',30)->nullable();
            $table->string('exhaust_gas_out',30)->nullable();
            $table->string('gas_flow',30)->nullable();
            $table->string('gas_flow_load',30)->nullable();
            $table->string('design_load',30)->nullable();
            $table->string('pressure_drop',30)->nullable();
            $table->string('engine_type',30)->nullable();
            $table->string('economizer',30)->nullable();
            $table->string('glycol_hot_water',30)->nullable();
            $table->string('fouling_hot_water_value',30)->nullable();
            $table->string('hot_water_flow',30)->nullable();
            $table->string('generator_tube_value',30)->nullable();
            $table->string('heat_duty',30)->nullable();
            $table->string('heated_water_in',30)->nullable();
            $table->string('heated_water_out',30)->nullable();
            $table->string('result',30)->nullable();
            $table->string('extra1',30)->nullable();
            $table->string('extra2',30)->nullable();
            $table->string('extra3',30)->nullable();
            $table->string('extra4',30)->nullable();
            $table->string('extra5',30)->nullable();
            $table->string('extra6',30)->nullable();
            $table->string('extra7',30)->nullable();
            $table->string('extra8',30)->nullable();
            $table->string('extra9',30)->nullable();
            $table->string('extra10',30)->nullable();
            $table->string('extra11',30)->nullable();
            $table->string('extra12',30)->nullable();
            $table->string('extra13',30)->nullable();
            $table->string('extra14',30)->nullable();
            $table->string('extra15',30)->nullable();
            $table->string('extra16',30)->nullable();
            $table->string('extra17',30)->nullable();
            $table->string('extra18',30)->nullable();
            $table->string('extra19',30)->nullable();
            $table->string('extra20',30)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('calculator_reports');
    }
}
