<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGroupCalculatorDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group_calculator_details', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('group_calculator_id')->unsigned();
            $table->integer('calculator_id')->unsigned();
            $table->timestamps();

            $table->foreign('group_calculator_id')->references('id')->on('group_calculators');
            $table->foreign('calculator_id')->references('id')->on('calculators');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('group_calculator_details');
    }
}
