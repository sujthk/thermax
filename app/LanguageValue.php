<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LanguageValue extends Model
{
    public function language()
    {
        return $this->belongsTo('App\Language');
    }

    public function language_key()
    {
        return $this->belongsTo('App\LanguageKey');
    }
}
