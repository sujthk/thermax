<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableNameLanguagesNotesTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename("languages", "languages_old");
        Schema::rename("notes_and_errors", "notes_and_errors_old");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::rename("languages_old", "languages");
        Schema::rename("notes_and_errors_old", "notes_and_errors");
    }
}
