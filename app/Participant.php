<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    protected $fillable = ['user_id', 'conversation_id', 'ad_product_id','other_user_id'];

    public function Ad_product()
    {
        return $this->belongsTo('App\Product', 'ad_product_id');
    }
    public function Product_data()
    {
        return $this->belongsTo('App\Product', 'ad_product_id')->select('id','title');
    }
    public function User()
    {
        return $this->belongsTo('App\User', 'user_id');
    }
    public function Conversation()
    {
        return $this->belongsTo('App\Conversation', 'conversation_id');
    }
}
