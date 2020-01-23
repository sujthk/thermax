<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GroupCalculator extends Model
{
    public function calculators()
    {
        return $this->belongsToMany('App\Calculator','group_calculator_details','group_calculator_id','calculator_id')->withTimestamps();
    }
    public function groupCalculatorDetails()
    {
        return $this->hasMany('App\GroupCalculatorDetail');
    }
}
