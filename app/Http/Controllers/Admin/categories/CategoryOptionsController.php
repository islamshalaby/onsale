<?php
namespace App\Http\Controllers\Admin\categories;

use App\Http\Controllers\Admin\AdminController;
use JD\Cloudder\Facades\Cloudder;
use Illuminate\Http\Request;
use App\Category_option;

class CategoryOptionsController extends AdminController{
    public function index()
    {
    }

    public function show($id){
        $data = Category_option::where('deleted','0')->where('cat_id',$id)->where('cat_type','category')->get();
        return view('admin.categories.category_options.index',compact('data','id'));
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
        Category_option::create($data);
        session()->flash('success', trans('messages.added_s'));
        return back();
    }
    public function update(Request $request){
        $selected_option = Category_option::where('id',$request->id)->first();
        $data = $this->validate(\request(),
            [
                'title_ar' => 'required',
                'title_en' => 'required',
                'is_required' => 'required'
            ]);

        if($request->image){
            $image_name = $request->file('image')->getRealPath();
            Cloudder::upload($image_name, null);
            $imagereturned = Cloudder::getResult();
            $image_id = $imagereturned['public_id'];
            $image_format = $imagereturned['format'];
            $image_new_name = $image_id.'.'.$image_format;
            $input['image'] = $image_new_name ;
        }
        $input['title_ar'] = $request->title_ar;
        $input['title_en'] = $request->title_en;
        $input['is_required'] = $request->is_required;

        Category_option::where('id',$request->id)->update($input);
        session()->flash('success', trans('messages.updated_s'));
        if($selected_option->cat_type == 'category'){
            return redirect(route('cat_options.show',$selected_option->cat_id));
        }elseif($selected_option->cat_type == 'subcategory'){
            return redirect(route('sub_cat_options.show',$selected_option->cat_id));
        }elseif($selected_option->cat_type == 'subTwoCategory'){
            return redirect(route('sub_cat_two_options.show',$selected_option->cat_id));
        }
    }
    public function edit($id){
        $data = Category_option::where('id',$id)->first();
        return view('admin.categories.category_options.edit' , compact('data'));
    }

    public function destroy($id){
        $data['deleted'] = '1';
        Category_option::where('id',$id)->update($data);
        session()->flash('success', trans('messages.deleted_s'));
        return back();
    }
}
