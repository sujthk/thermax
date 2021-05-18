<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Calculator extends Model
{
    protected $appends = ['image_path'];

    public function getImagePathAttribute() { 
     		if(empty($this->image) || $this->image == "")
            		return asset('calculators/default.png');
       		else
           		return asset('calculators').'/'.$this->image;               
           
    }
}
