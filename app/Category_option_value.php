<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category_option_value extends Model
{
    protected $fillable = ['image','value_ar', 'value_en', 'option_id','deleted', 'parent_id'];

    public function parent() {
        return $this->belongsTo('App\Category_option_value', 'parent_id');
    }

    public function option() {
        return $this->belongsTo('App\Category_option', 'option_id');
    }
}
