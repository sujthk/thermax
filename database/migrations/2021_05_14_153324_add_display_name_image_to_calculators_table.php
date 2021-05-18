<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDisplayNameImageToCalculatorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('calculators', function (Blueprint $table) {
            $table->string('display_name')->nullable()->default("name");
            $table->string('image')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('calculators', function (Blueprint $table) {
            $table->dropColumn('display_name');
            $table->dropColumn('image');
        });
    }
}
