<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReportNameToMetallurgiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('metallurgies', function (Blueprint $table) {
            $table->string('report_name')->nullable()->default("name");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('metallurgies', function (Blueprint $table) {
            $table->dropColumn('ode');
        });
    }
}
