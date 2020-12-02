<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserCalculator extends Model
{
   public function calculator()
    {
        return $this->belongsTo('App\Calculator');
    }
}
