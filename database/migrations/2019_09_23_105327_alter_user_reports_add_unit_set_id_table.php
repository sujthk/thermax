<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUserReportsAddUnitSetIdTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_reports', function (Blueprint $table) {
            $table->integer('unit_set_id')->unsigned()->nullable()->after('user_id');

            $table->foreign('unit_set_id')->references('id')->on('unit_sets');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_reports', function (Blueprint $table) {
            $table->dropForeign(['unit_set_id']);
            $table->dropColumn('unit_set_id');
        });
    }
}
