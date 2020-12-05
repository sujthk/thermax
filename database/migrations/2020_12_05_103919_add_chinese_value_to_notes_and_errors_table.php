<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddChineseValueToNotesAndErrorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notes_and_errors', function (Blueprint $table) {
            $table->string('chinese_value')->nullable()->after('value')->default('默认值');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notes_and_errors', function (Blueprint $table) {
            $table->dropColumn(['chinese_value']);
        });
    }
}
