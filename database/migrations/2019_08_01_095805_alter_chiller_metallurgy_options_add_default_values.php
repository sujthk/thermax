<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterChillerMetallurgyOptionsAddDefaultValues extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('chiller_metallurgy_options', function (Blueprint $table) {
            $table->string('eva_default_value')->nullable()->after('model');
            $table->string('abs_default_value')->nullable()->after('eva_default_value');
            $table->string('con_default_value')->nullable()->after('abs_default_value');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('chiller_metallurgy_options', function (Blueprint $table) {
            $table->dropColumn(['eva_default_value', 'abs_default_value', 'con_default_value']);
        });
    }
}
