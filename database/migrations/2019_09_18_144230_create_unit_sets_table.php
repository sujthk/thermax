<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUnitSetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('unit_sets', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('TemperatureUnit');
            $table->string('LengthUnit');
            $table->string('WeightUnit');
            $table->string('PressureUnit');
            $table->string('PressureDropUnit');
            $table->string('FurnacePressureDropUnit');
            $table->string('WorkPressureUnit');
            $table->string('AreaUnit');
            $table->string('VolumeUnit');
            $table->string('FlowRateUnit');
            $table->string('NozzleDiameterUnit');
            $table->string('CapacityUnit');
            $table->string('FoulingFactorUnit');
            $table->string('SteamConsumptionUnit');
            $table->string('ExhaustGasFlowUnit');
            $table->string('FuelConsumptionOilUnit');
            $table->string('FuelConsumptionGasUnit');
            $table->string('HeatUnit');
            $table->string('CalorificValueGasUnit');
            $table->string('CalorificValueOilUnit');
            $table->string('AllWorkPrHWUnit');
            $table->string('HeatCapacityUnit');
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
        Schema::dropIfExists('unit_sets');
    }
}
