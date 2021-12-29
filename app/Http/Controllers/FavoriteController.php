<?php

namespace App\Http\Controllers;

use App\Product_view;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Helpers\APIHelpers;
use App\Favorite;
use App\Product;


class FavoriteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api' , ['except' => []]);
    }

    public function addtofavorites(Request $request){
        $user = auth()->user();
        if($user->active == 0){
            $response = APIHelpers::createApiResponse(true , 406 ,  'تم حظر حسابك', 'تم حظر حسابك' , null, $request->lang );
            return response()->json($response , 406);
        }
        $validator = Validator::make($request->all() , [
            'product_id' => 'required',
        ]);
        if($validator->fails()) {
            $response = APIHelpers::createApiResponse(true , 406 ,  'بعض الحقول مفقودة', 'بعض الحقول مفقودة' , null, $request->lang );
            return response()->json($response , 406);
        }
        $favorite = Favorite::where('product_id' , $request->product_id)->where('user_id' , $user->id)->first();
        if($favorite){
            $response = APIHelpers::createApiResponse(true , 406 ,  'تم إضافه هذا المنتج للمفضله من قبل', 'تم إضافه هذا المنتج للمفضله من قبل' , null, $request->lang );
            return response()->json($response , 406);
        }else{
            $favorite = new Favorite();
            $favorite->user_id = $user->id;
            $favorite->product_id = $request->product_id;
            $favorite->save();
            $response = APIHelpers::createApiResponse(false , 200 ,  '', '' , $favorite, $request->lang);
            return response()->json($response , 200);
        }
    }

    public function removefromfavorites(Request $request){
        $user = auth()->user();
        if($user->active == 0){
            $response = APIHelpers::createApiResponse(true , 406 ,  'تم حظر حسابك', 'تم حظر حسابك' , (object)[], $request->lang );
            return response()->json($response , 406);
        }

        $validator = Validator::make($request->all() , [
            'product_id' => 'required',
        ]);

        if($validator->fails()) {
            $response = APIHelpers::createApiResponse(true , 406 ,  'بعض الحقول مفقودة', 'بعض الحقول مفقودة' , null, $request->lang );
            return response()->json($response , 406);
        }

        $favorite = Favorite::where('product_id' , $request->product_id)->where('user_id',$user->id)->first();
        if($favorite){
            $favorite->delete();
            $response = APIHelpers::createApiResponse(false , 200 ,  'Deteted ', 'تم الحذف' , (object)[], $request->lang);
            return response()->json($response , 200);
        }else{
            $response = APIHelpers::createApiResponse(true , 406 ,  'هذا المنتج غير موجود بالمفضله', 'هذا المنتج غير موجود بالمفضله' , null, $request->lang );
            return response()->json($response , 406);
        }
    }

    public function getfavorites(Request $request){
        $user = auth()->user();
        if($user->active == 0){
            $response = APIHelpers::createApiResponse(true , 406 ,  'تم حظر حسابك', 'تم حظر حسابك' , null, $request->lang );
            return response()->json($response , 406);
        }else {
            $favorites = Favorite::select('id','product_id','user_id')
                                 ->with('Product')
                                 ->where('user_id', $user->id)
                                 ->orderBy('id','desc')
                                 ->get();

            $inc = 0;
            $data = null;
            foreach ($favorites as $key => $row){
                $product = Product::where('id',$row->product_id)->first();
                if($product != null){
                    if($product->status == 1 && $product->deleted == 0 && $product->publish == 'Y'){
                        $data[$inc]['id'] = $product->id;
                        $data[$inc]['title'] = $product->title;
                        $data[$inc]['image'] = $product->main_image;
                        $data[$inc]['price'] = (string) $product->price ;
                        $data[$inc]['description'] = $product->description;
                        $data[$inc]['created_at'] = $product->created_at;
                        $data[$inc]['time'] = $product->created_at->format('Y-m-d');
                        $data[$inc]['views'] = Product_view::where('product_id', $product->id )->get()->count();
                        $inc =$inc + 1;
                    }
                }
            }

            if(count($favorites) > 0) {
                $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
            }else{
                $response = APIHelpers::createApiResponse(false, 200, 'no item favorite to show', 'لا يوجد عناصر للعرض', null, $request->lang);
            }
            return response()->json($response, 200);
        }
    }
}
