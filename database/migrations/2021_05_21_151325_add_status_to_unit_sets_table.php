<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusToUnitSetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('unit_sets', function (Blueprint $table) {
            $table->boolean('status')->default(1)->comment('1 = Active, 0 = Inactive'); 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('unit_sets', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
