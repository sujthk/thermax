<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateChillerEvaporatorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chiller_options', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('chiller_metallurgy_option_id')->unsigned();
            $table->integer('metallurgy_id')->unsigned();
            $table->string('value');
            $table->string('type');
            $table->timestamps();
            $table->foreign('chiller_metallurgy_option_id')->references('id')->on('chiller_metallurgy_options');
            $table->foreign('metallurgy_id')->references('id')->on('metallurgies');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chiller_evaporators');
    }
}
