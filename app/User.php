<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];



    public function getLastLoggedInAttribute($value)
    {
        if(empty($value))
            return "New User";
        else
            return date('d-m-Y h:i A', strtotime($value));
    }

    // public function getUserTypeAttribute($value)
    // {

    //     return str_replace("_"," ",$value);
    // }

    public function unitSet()
    {
        return $this->belongsTo('App\UnitSet');
    }
    public function region()
    {
        return $this->belongsTo('App\Region');
    }
     public function calculators()
    {
        return $this->belongsToMany('App\Calculator','user_calculators','user_id','calculator_id')->withTimestamps();
    }
    public function groupCalculator()
    {
        return $this->belongsTo('App\GroupCalculator');
    }
     public function userCalculators()
    {
        return $this->hasMany('App\UserCalculator');
    }

    public function language()
    {
        return $this->belongsTo('App\Language');
    }
}
