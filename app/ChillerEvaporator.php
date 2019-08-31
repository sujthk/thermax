<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ChillerEvaporator extends Model
{
    public function metallurgy()
    {
        return $this->belongsTo('App\Metallurgy');
    }
}
