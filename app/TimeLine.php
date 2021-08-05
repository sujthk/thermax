<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TimeLine extends Model
{
    protected $appends = ['image_path'];

    public function getImagePathAttribute() { 
        if(empty($this->image) || $this->image == "")
                return asset('banner-images/default.jpg');
        else
            return asset('banner-images').'/'.$this->image;               
           
    }
}
