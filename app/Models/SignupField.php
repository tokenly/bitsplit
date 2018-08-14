<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SignupField extends Model
{

    public function options()
    {
        return $this->hasMany('App\Models\SignupFieldOption', 'field_id');
    }

    public function condition()
    {
        return $this->hasOne('App\Models\SignupFieldCondition', 'field_id');
    }

    function checkCondition($value) {

    }
}
