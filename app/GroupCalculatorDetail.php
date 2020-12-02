<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GroupCalculatorDetail extends Model
{
    protected $table = 'group_calculator_details';

    public function calculator()
    {
        return $this->belongsTo('App\Calculator');
    }
}
