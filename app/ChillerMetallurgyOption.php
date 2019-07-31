<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ChillerMetallurgyOption extends Model
{
    public function chillerOptions()
    {
        return $this->hasMany('App\ChillerOption');
    }
}
