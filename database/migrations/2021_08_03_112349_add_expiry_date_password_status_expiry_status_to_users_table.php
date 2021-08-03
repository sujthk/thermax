<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddExpiryDatePasswordStatusExpiryStatusToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('expiry_at')->nullable();
            $table->boolean('expiry_status')->default(1)->comment('1 = Not Expired, 0 = Expired');
            $table->boolean('password_status')->default(1)->comment('1 = Change password, 0 = no change');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
}
