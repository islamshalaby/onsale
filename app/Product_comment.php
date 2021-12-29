<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Product_comment extends Model
{
    protected $fillable = ['comment', 'user_id', 'product_id','status'];
//    protected $attributes = ['user_name'];
    public function user() {
        return $this->belongsTo('App\User', 'user_id')->select('id','name','image');
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::createFromFormat('Y-m-d H:i:s', $value)->format('Y-m-d');
    }
}
