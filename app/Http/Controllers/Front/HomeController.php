<?php
namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;

class HomeController extends Controller{

    // get all contact us messages
    public function index(){

        return view('front.index' );
    }

}
