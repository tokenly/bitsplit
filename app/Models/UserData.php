<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserData extends Model
{

    protected $table = 'user_data';
    //
    function field() {
        return $this->belongsTo('App\Models\SignupField', 'field_id');
    }
}
