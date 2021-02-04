<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ChillerCalculationValue extends Model
{
    protected $casts = [
        'min_model' => 'float',
    ];
}
