<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDisplayNameMetallurgiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('metallurgies', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('name');
            $table->renameColumn('min_velocity', 'eva_min_velocity');
            $table->renameColumn('max_velocity', 'eva_max_velocity');
            $table->string('abs_min_velocity')->nullable();
            $table->string('abs_max_velocity')->nullable();
            $table->string('con_min_velocity')->nullable();
            $table->string('con_max_velocity')->nullable();
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
            $table->dropColumn(['display_name', 'abs_min_velocity', 'abs_max_velocity', 'con_min_velocity', 'con_max_velocity']);
            $table->renameColumn('eva_min_velocity', 'min_velocity');
            $table->renameColumn('eva_max_velocity', 'max_velocity');
        });
    }
}
