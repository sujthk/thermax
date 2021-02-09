<?php

use Illuminate\Database\Seeder;

class UserLanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')
            ->update(['language_id' => 1]);
    }
}
