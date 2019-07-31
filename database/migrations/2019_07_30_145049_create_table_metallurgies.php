<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableMetallurgies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('metallurgies', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('default_thickness');
            $table->string('min_thickness');
            $table->string('max_thickness');
            $table->string('min_velocity');
            $table->string('max_velocity');
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
        Schema::dropIfExists('metallurgies');
    }
}
