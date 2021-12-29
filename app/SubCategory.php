<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SubCategory extends Model
{
    // protected  $appends = ['next_level'];
    protected  $hidden = ['SubCategories'];
    protected $fillable = ['title_en', 'title_ar', 'image', 'deleted', 'brand_id', 'category_id','sort'];

    public function brand() {
        return $this->belongsTo('App\Brand', 'brand_id');
    }

    public function category() {
        return $this->belongsTo('App\Category', 'category_id');
    }

    public function products() {
        return $this->hasMany('App\Product', 'sub_category_id')->where('status', 1)->where('publish', 'Y')->where('deleted', 0);
    }

    public function Products_custom() {
        return $this->hasMany('App\Product', 'sub_category_id')->where('status', 1)->where('publish', 'Y')->where('deleted', 0);
    }

    public function SubCategories() {
        return $this->hasMany('App\SubTwoCategory', 'sub_category_id')->where('deleted', 0)->where(function ($q) {
                                    $q->has('SubCategories', '>', 0)->Where(function ($qq) {
                                        $qq->has('Products', '>', 0);
                                    });
                                });
    }
    public function ViewSubCategories() {
        return $this->hasMany('App\SubTwoCategory', 'sub_category_id')->where('deleted', 0);
    }

    public function subCatsHasProducts() {
        return $this->hasMany('App\SubTwoCategory', 'sub_category_id')->where('deleted', 0)->has('products', '>', 0);
    }
    // public function getNextLevelAttribute(){
    //     $result = false ;
    //     if(count($this->SubCategories) > 0 ){
    //         foreach ($this->SubCategories as $row){
    //             if(count($row->SubCategories) > 0 ){
    //                 $result = true ;
    //                 break;
    //             }else{
    //                 $result = false ;
    //             }
    //         }
    //     }else{
    //         $result = false ;
    //     }
    //     return $result ;
    // }
}
