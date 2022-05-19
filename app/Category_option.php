<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category_option extends Model
{
    protected $fillable = ['title_ar','image', 'title_en', 'cat_id','cat_type','deleted','is_required', 'parent_id'];

    public function Values() {
        return $this->hasMany('App\Category_option_value', 'option_id');
    }

    public function parent() {
        return $this->belongsTo('App\Category_option', 'parent_id');
    }

    public function sub_options() {
        return $this->hasMany('App\Category_option', 'parent_id')->where('deleted', '0');
    }
}
