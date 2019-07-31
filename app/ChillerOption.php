<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ChillerOption extends Model
{
    public function metallurgy()
    {
        return $this->belongsTo('App\Metallurgy');
    }
}
