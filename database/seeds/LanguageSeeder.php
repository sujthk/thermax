<?php

use Illuminate\Database\Seeder;
use App\Language;
use App\LanguageKey;
use App\LanguageValue;
use App\NotesError;
class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $language = new Language;
        $language->name = 'english';
        $language->status = 1;
        $language->save();

        $old_language_values = DB::table('languages_old')->get();

        foreach ($old_language_values as $old_language_value) {
            $language_key = new LanguageKey;
            $language_key->name = $old_language_value->name;
            $language_key->type = "FORM_VALUES";
            $language_key->save();

            $language_value = new LanguageValue;
            $language_value->language_id = $language->id;
            $language_value->language_key_id = $language_key->id;
            $language_value->value = $old_language_value->english;
            $language_value->save();

        }

        $old_notes_and_errors = DB::table('notes_and_errors_old')->get();

        foreach ($old_notes_and_errors as $old_notes_and_error) {
            $language_key = new LanguageKey;
            $language_key->name = $old_notes_and_error->name;
            $language_key->type = "NOTES_ERRORS";
            $language_key->save();

            $notes_error = new NotesError;
            $notes_error->language_id = $language->id;
            $notes_error->language_key_id = $language_key->id;
            $notes_error->value = $old_notes_and_error->value;
            $notes_error->save();

        }
    }
}
