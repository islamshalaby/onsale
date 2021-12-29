<?php
namespace App\Http\Controllers\Admin\categories;

use App\Http\Controllers\Admin\AdminController;
use JD\Cloudder\Facades\Cloudder;
use Illuminate\Http\Request;
use App\Category_option;

class SubCategoryOptionsController extends AdminController{
    public function index()
    {
    }

    public function show($id){
        $data = Category_option::where('deleted','0')->where('cat_id',$id)->where('cat_type','subcategory')->get();
        return view('admin.categories.sub_category.sub_category_options.index',compact('data','id'));
    }

    public function store(Request $request){
        $data = $this->validate(\request(),
            [
                'cat_id' => 'required',
                'image' => 'required',
                'title_ar' => 'required',
                'title_en' => 'required',
                'is_required' => 'required'
            ]);

        $image_name = $request->file('image')->getRealPath();
        Cloudder::upload($image_name, null);
        $imagereturned = Cloudder::getResult();
        $image_id = $imagereturned['public_id'];
        $image_format = $imagereturned['format'];
        $image_new_name = $image_id.'.'.$image_format;
        $data['image'] = $image_new_name ;
        $data['cat_type'] = 'subcategory' ;
        Category_option::create($data);
        session()->flash('success', trans('messages.added_s'));
        return back();
    }

}
