<?php

namespace App\Http\Controllers;

use App\Participant;
use Illuminate\Support\Facades\Validator;
use App\Category_option_value;
use Illuminate\Http\Request;
use App\Helpers\APIHelpers;
use App\SubThreeCategory;
use App\SubFiveCategory;
use App\Category_option;
use App\SubFourCategory;
use App\SubTwoCategory;
use App\Product_view;
use App\SubCategory;
use App\Favorite;
use App\Category;
use App\Product;


class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['getSubTwoCategoryOptions', 'getSubOptions', 'getSubCategoryOptions', 'show_six_cat', 'getCategoryOptions', 'show_five_cat', 'show_four_cat', 'show_third_cat', 'show_second_cat', 'show_first_cat', 'getcategories', 'getAdSubCategories', 'get_sub_categories_level2', 'get_sub_categories_level3', 'get_sub_categories_level4', 'get_sub_categories_level5', 'getproducts', 'getMainCategoryOptions', 'get_category_products', 'getSubOptionsByMainOptions']]);
    }

    // get cats - sub cats with next level
    public function getCatsSubCats($model, $lang, $show=true, $cat_id=0, $all=false, $whereIn=[]) {
        $categories = $model::has('Products', '>', 0)
        ->where('deleted', 0);
        if ($model == '\App\SubCategory' && $cat_id != 0) {
            $categories = $categories->where('category_id', $cat_id);
        }elseif ($model != '\App\Category' && $cat_id != 0) {
            $categories = $categories->where('sub_category_id', $cat_id);
        }
        if (count($whereIn) > 0) {
            $categories = $categories->whereIn('sub_category_id', $whereIn);
        }
        
        $categories = $categories->select('id', 'title_' . $lang . ' as title', 'image')->orderBy('sort', 'asc')->get()->makeHidden(['ViewSubCategories', 'products'])
        ->map(function ($row) use ($show, $model) {
            if ($show) {
                $row->products_count = count($row->products);
            }
            $row->next_level = false;
            $subCategories = $row->ViewSubCategories;
            
            if ($subCategories && count($subCategories) > 0) {
                $hasProducts = false;
                for ($n = 0; $n < count($subCategories); $n++) {
                    if ($model != '\App\SubFourCategory' || $model != '\App\SubFiveCategory') {
                        if ($subCategories[$n]->subCatsHasProducts != null && count($subCategories[$n]->subCatsHasProducts) > 0) {
                            $hasProducts = true;
                        }
                    }
                }
                if ($hasProducts) {
                    $row->next_level = true;
                }
            }
            $row->selected = false;
            return $row;
        })->toArray();

        if ($all) {
            $title = 'All';
            if ($lang == 'ar') {
                $title = 'الكل';
            }
            $all = [
                'id' => 0,
                'image' => "",
                'title' => $title,
                'next_level' => false,
                'selected' => false
            ];
            
            array_unshift($categories, $all);
        }

        return $categories;
    }

    public function getcategories(Request $request)
    {
        $lang = $request->lang;
        $category = '\App\Category';
        $subCat = '\App\SubCategory';
        $categories = $this->getCatsSubCats($category, $lang, false);

        // $data = Categories_ad::select('image','ad_type','content as link')->where('type','category')->inRandomOrder()->take(1)->get();
        $response = APIHelpers::createApiResponse(false, 200, '', '', array('categories' => $categories), $request->lang);
        return response()->json($response, 200);
    }

    // get ad subcategories
    public function getAdSubCategories(Request $request)
    {
        $lang = $request->lang;
        
        $data['sub_categories'] = $this->getCatsSubCats('\App\SubCategory', $lang, false, $request->category_id, false);

        $data['sub_category_array'] = $this->getCatsSubCats('\App\SubCategory', $lang, false, $request->category_id, false);

        $data['category'] = Category::select('id', 'title_en as title')->find($request->category_id);

        $subCatsIds = [];
        for ($i = 0; $i < count($data['sub_categories']); $i++) {
            if ($data['sub_categories'][$i]['id'] != 0) {
                array_push($subCatsIds, $data['sub_categories'][$i]['id']);
            }
        }
        $data['sub_next_categories'] = $this->getCatsSubCats('\App\SubTwoCategory', $lang, false, 0, true, $subCatsIds);
        // if (count($data['sub_categories']) == 0) {
        //     $data['sub_categories'] = $this->getCatsSubCats('\App\SubCategory', '\App\SubTwoCategory', $lang, false, 0, true);

        //     $data['sub_category_array'] = $this->getCatsSubCats('\App\SubCategory', '\App\SubTwoCategory', $lang, false, 0, true);
        // }
        

        $lang = $request->lang;
        $products = Product::where('status', 1)->where('publish', 'Y')->where('deleted', 0)->where('category_id', $request->category_id)->select('id', 'title', 'price', 'main_image as image', 'created_at', 'pin')->orderBy('pin', 'DESC')->orderBy('created_at', 'desc')->simplePaginate(12);
        for ($i = 0; $i < count($products); $i++) {
            $products[$i]['price'] = (string)$products[$i]['price'];
            $views = Product_view::where('product_id', $products[$i]['id'])->get()->count();
            $products[$i]['views'] = $views;
            $user = auth()->user();
            if ($user) {
                $favorite = Favorite::where('user_id', $user->id)->where('product_id', $products[$i]['id'])->first();
                if ($favorite) {
                    $products[$i]['favorite'] = true;
                } else {
                    $products[$i]['favorite'] = false;
                }

                $conversation = Participant::where('ad_product_id', $products[$i]['id'])->where('user_id', $user->id)->first();
                if ($conversation == null) {
                    $products[$i]['conversation_id'] = 0;
                } else {
                    $products[$i]['conversation_id'] = $conversation->conversation_id;
                }
            } else {
                $products[$i]['favorite'] = false;
                $products[$i]['conversation_id'] = 0;
            }
            $products[$i]['time'] = $products[$i]['created_at']->format('Y-m-d');
        }

        $data['products'] = $products;
        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }

    public function get_sub_categories_level2(Request $request)
    {
        $lang = $request->lang;
        $validator = Validator::make($request->all(), [
            'category_id' => 'required'
        ]);

        if ($validator->fails()) {
            $response = APIHelpers::createApiResponse(true, 406, 'بعض الحقول مفقودة', 'بعض الحقول مفقودة', null, $request->lang);
            return response()->json($response, 406);
        }
        if ($request->sub_category_id != 0) {
            $pluckSubCats = SubCategory::where('category_id', $request->category_id)->has('subCatsHasProducts', '>', 0)->where('deleted', 0)->pluck('id')->toArray();
            $data['sub_categories'] = $this->getCatsSubCats('\App\SubTwoCategory', $lang, false, $request->sub_category_id, false);
            $data['sub_category_level1'] = SubCategory::where('id', $request->sub_category_id)->select('id', 'title_' . $lang . ' as title', 'category_id')->first();
            $data['sub_category_array'] = $this->getCatsSubCats('\App\SubTwoCategory', $lang, false, $request->sub_category_id, false);
            
            if (count($data['sub_categories']) == 0) {
                $data['sub_categories'] = $this->getCatsSubCats('\App\SubCategory', $lang, false, $request->category_id, false);
                $data['sub_category_array'] = $this->getCatsSubCats('\App\SubCategory', $lang, false, $request->category_id, false);
            }

            $data['category'] = Category::where('id', $data['sub_category_level1']['category_id'])->select('id', 'title_' . $lang . ' as title')->first();

        } else {
            $pluckSubCats = SubCategory::where('category_id', $request->category_id)->where('deleted', 0)->pluck('id')->toArray();
            
            $data['sub_categories'] = $this->getCatsSubCats('\App\SubTwoCategory', $lang, false, 0, false, $pluckSubCats);
            $data['sub_category_level1'] = (object)[
                "id" => 0,
                "title" => "All",
                "category_id" => $request->category_id
            ];
            $data['sub_category_array'] = $this->getCatsSubCats('\App\SubTwoCategory',  $lang, false, 0, false, $pluckSubCats);

            if (count($data['sub_categories']) == 0) {
                $data['sub_categories'] = $this->getCatsSubCats('\App\SubCategory', $lang, false, $request->category_id, false);
                $data['sub_category_array'] = $this->getCatsSubCats('\App\SubCategory', $lang, false, $request->category_id, false);
            }
            $data['category'] = Category::where('id', $request->category_id)->select('id', 'title_' . $lang . ' as title')->first();
        }


        $data['sub_next_categories'] = [];
        $secondIds = [];
        if (count($data['sub_categories']) > 0) {
            for ($i = 0; $i < count($data['sub_categories']); $i++) {
                if ($data['sub_categories'][$i]['id'] != 0) {
                    array_push($secondIds, $data['sub_categories'][$i]['id']);
                }
            }
        }
        $data['sub_next_categories'] = $this->getCatsSubCats('\App\SubThreeCategory', $lang, false, 0, true, $secondIds);
        
        $lang = $request->lang;


        if ($request->sub_category_id == 0) {
            $sub_category_ids = SubCategory::where('deleted', 0)->where('category_id',$request->category_id)->select('id', 'image', 'title_' . $lang . ' as title')
                ->orderBy('sort', 'asc')->pluck('id')->toArray();

            $products = Product::where('status', 1)->where('publish', 'Y')->where('deleted', 0)->whereIn('sub_category_id', $sub_category_ids)->select('id', 'title', 'price', 'main_image as image', 'created_at', 'pin')->orderBy('pin', 'DESC')->orderBy('created_at', 'desc')->simplePaginate(12);
        } else {
            $products = Product::where('status', 1)->where('publish', 'Y')->where('deleted', 0)->where('sub_category_id', $request->sub_category_id)->select('id', 'title', 'price', 'main_image as image', 'created_at', 'pin')->orderBy('pin', 'DESC')->orderBy('created_at', 'desc')->simplePaginate(12);
        }
        for ($i = 0; $i < count($products); $i++) {
            $products[$i]['price'] = (string)$products[$i]['price'];
            $views = Product_view::where('product_id', $products[$i]['id'])->get()->count();
            $products[$i]['views'] = $views;
            $user = auth()->user();
            if ($user) {
                $favorite = Favorite::where('user_id', $user->id)->where('product_id', $products[$i]['id'])->first();
                if ($favorite) {
                    $products[$i]['favorite'] = true;
                } else {
                    $products[$i]['favorite'] = false;
                }

                $conversation = Participant::where('ad_product_id', $products[$i]['id'])->where('user_id', $user->id)->first();
                if ($conversation == null) {
                    $products[$i]['conversation_id'] = 0;
                } else {
                    $products[$i]['conversation_id'] = $conversation->conversation_id;
                }
            } else {
                $products[$i]['favorite'] = false;
                $products[$i]['conversation_id'] = 0;
            }
            $products[$i]['time'] = $products[$i]['created_at']->format('Y-m-d');
        }

        $data['products'] = $products;
        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }

    public function get_sub_categories_level3(Request $request)
    {
        $lang = $request->lang;
        $validator = Validator::make($request->all(), [
            'category_id' => 'required'
        ]);

        if ($validator->fails() && !isset($request->sub_category_level1_id)) {
            $response = APIHelpers::createApiResponse(true, 406, 'بعض الحقول مفقودة', 'بعض الحقول مفقودة', null, $request->lang);
            return response()->json($response, 406);
        }

        $subCategories = SubCategory::where('category_id', $request->category_id)->pluck('id')->toArray();
        $subCategoriesTwo = SubTwoCategory::where('deleted', 0)->whereIn('sub_category_id', $subCategories)->pluck('id')->toArray();

        if ($request->sub_category_id != 0) {
            // $secondIds = SubTwoCategory::where('sub_category_id', $request->sub_category_id)->where('deleted', 0)->pluck('id')->toArray();
            
            $data['sub_category_array'] = $this->getCatsSubCats('\App\SubThreeCategory', $lang, false, $request->sub_category_id, false);
            $data['sub_categories'] = $this->getCatsSubCats('\App\SubThreeCategory', $lang, false, $request->sub_category_id, false);
            
            $data['sub_category_level2'] = SubTwoCategory::where('id', $request->sub_category_id)->select('id', 'title_' . $lang . ' as title', 'sub_category_id')->first();
            // if ($request->sub_category_level1_id != 0) {
            //     $data['sub_category_array'] = $this->getCatsSubCats('\App\SubThreeCategory', $lang, false, $request->sub_category_id, false);
            // } else {
            //     $data['sub_category_array'] = SubThreeCategory::where(function ($q) {
            //         $q->has('SubCategories', '>', 0)->orWhere(function ($qq) {
            //             $qq->has('Products', '>', 0);
            //         });
            //     })->whereIn('sub_category_id', $request->sub_category_id)->where('deleted', 0)->select('id', 'title_' . $lang . ' as title', 'sub_category_id')->orderBy('sort', 'asc')->get();
            // }
            $data['category'] = Category::where('id', $request->category_id)->select('id', 'title_' . $lang . ' as title')->first();
        } else {

            $data['sub_category_level2'] = (object)[
                "id" => 0,
                "title" => "All",
                "sub_category_id" => $request->sub_category_level1_id
            ];
            $secondIds = SubTwoCategory::where('sub_category_id', $request->sub_category_level1_id)->where('deleted', 0)->pluck('id')->toArray();
            
            $data['sub_category_array'] = $this->getCatsSubCats('\App\SubThreeCategory', $lang, false, 0, false, $secondIds);
            $data['sub_categories'] = $this->getCatsSubCats('\App\SubThreeCategory', $lang, false, 0, false, $secondIds);

            $data['category'] = Category::where('id', $request->category_id)->select('id', 'title_' . $lang . ' as title')->first();
        }


        for ($i = 0; $i < count($data['sub_categories']); $i++) {
            $cat_ids[$i] = $data['sub_categories'][$i]['id'];
        }
        // $data['ad_image'] = Categories_ad::select('image','ad_type','content as link')->wherein('cat_id',$cat_ids)->where('type','sub_three_category')->inRandomOrder()->take(1)->get();


        $All_sub_cat = false;
        $data['sub_next_categories'] = [];
        $thirdIds = [];
        if (count($data['sub_categories']) > 0) {
            for ($i = 0; $i < count($data['sub_categories']); $i++) {
                if ($data['sub_categories'][$i]['id'] != 0) {
                    array_push($thirdIds, $data['sub_categories'][$i]['id']);
                }
                
                if ($All_sub_cat == false) {
                    if ($data['sub_categories'][$i]['next_level'] == false) {
                        $All_sub_cat = false;
                    } else {
                        $All_sub_cat = true;
                    }
                }

            }
        }
        $data['sub_next_categories'] = $this->getCatsSubCats('\App\SubFourCategory', $lang, false, 0, true, $thirdIds);
        
        if (count($data['sub_categories']) == 0) {
            $data['sub_categories'] = $this->getCatsSubCats('\App\SubTwoCategory', $lang, false, $request->sub_category_level1_id, false);
            $data['sub_category_array'] = $this->getCatsSubCats('\App\SubTwoCategory', $lang, false, $request->sub_category_level1_id, false);
        }

        $products = Product::where('status', 1)->where('deleted', 0)->where('publish', 'Y')
            ->where('category_id', $request->category_id)->select('id', 'title', 'price', 'main_image as image', 'pin', 'created_at');
        if ($request->sub_category_id != 0) {
            $products = $products->where('sub_category_two_id', $request->sub_category_id);
        }

        if ($request->sub_category_level1_id != 0) {
            $products = $products->where('sub_category_id', $request->sub_category_level1_id);
        }

        $products = $products->orderBy('pin', 'DESC')->orderBy('created_at', 'desc')->simplePaginate(12);

        for ($i = 0; $i < count($products); $i++) {
            $products[$i]['price'] = (string)$products[$i]['price'];

            $views = Product_view::where('product_id', $products[$i]['id'])->get()->count();
            $products[$i]['views'] = $views;
            $user = auth()->user();
            if ($user) {
                $favorite = Favorite::where('user_id', $user->id)->where('product_id', $products[$i]['id'])->first();
                if ($favorite) {
                    $products[$i]['favorite'] = true;
                } else {
                    $products[$i]['favorite'] = false;
                }
                $conversation = Participant::where('ad_product_id', $products[$i]['id'])->where('user_id', $user->id)->first();
                if ($conversation == null) {
                    $products[$i]['conversation_id'] = 0;
                } else {
                    $products[$i]['conversation_id'] = $conversation->conversation_id;
                }
            } else {
                $products[$i]['favorite'] = false;
                $products[$i]['conversation_id'] = 0;
            }
            $products[$i]['time'] = $products[$i]['created_at']->format('Y-m-d');
        }
        $data['products'] = $products;


        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }

    public function get_sub_categories_level4(Request $request)
    {
        $lang = $request->lang;
        $validator = Validator::make($request->all(), [
            'category_id' => 'required'
        ]);

        if ($validator->fails() && !isset($request->sub_category_level2_id) && !isset($request->sub_category_level1_id)) {
            $response = APIHelpers::createApiResponse(true, 406, 'بعض الحقول مفقودة', 'بعض الحقول مفقودة', null, $request->lang);
            return response()->json($response, 406);
        }


        if ($request->sub_category_level1_id == 0) {
            $subCategories = SubCategory::where('deleted', 0)->where('category_id', $request->category_id)->pluck('id')->toArray();
            $subCategoriesTwo = SubTwoCategory::where('deleted', 0)->whereIn('sub_category_id', $subCategories)->pluck('id')->toArray();
        } else {
            $subCategoriesTwo = SubTwoCategory::where('deleted', 0)->where('sub_category_id', $request->sub_category_level1_id)->pluck('id')->toArray();
        }

        if ($request->sub_category_level2_id == 0) {
            $subCategoriesThree = SubThreeCategory::where('deleted', 0)->whereIn('sub_category_id', $subCategoriesTwo)->pluck('id')->toArray();
        } else {
            $subCategoriesThree = SubThreeCategory::where('deleted', 0)->where('sub_category_id', $request->sub_category_level2_id)->pluck('id')->toArray();
        }

        if ($request->sub_category_id != 0) {
            $data['sub_categories'] = $this->getCatsSubCats('\App\SubFourCategory', $lang, false, $request->sub_category_id, false);
            $data['sub_category_level3'] = SubThreeCategory::where('id', $request->sub_category_id)->select('id', 'title_' . $lang . ' as title', 'sub_category_id')->first();
            $data['sub_category_array'] = $this->getCatsSubCats('\App\SubFourCategory', $lang, false, $request->sub_category_id, false);
            // if ($request->sub_category_level2_id == 0) {
            //     $data['sub_category_array'] = SubFourCategory::where(function ($q) {
            //         $q->has('SubCategories', '>', 0)->orWhere(function ($qq) {
            //             $qq->has('Products', '>', 0);
            //         });
            //     })->where('deleted', 0)->whereIn('sub_category_id', $request->sub_category_id)->select('id', 'title_' . $lang . ' as title', 'sub_category_id')->orderBy('sort', 'asc')->get();
            // } else {
            //     $data['sub_category_array'] = SubFourCategory::where(function ($q) {
            //         $q->has('SubCategories', '>', 0)->orWhere(function ($qq) {
            //             $qq->has('Products', '>', 0);
            //         });
            //     })->where('deleted', 0)->where('sub_category_id', $request->sub_category_id)->select('id', 'title_' . $lang . ' as title', 'sub_category_id')->orderBy('sort', 'asc')->get();
            // }

            $data['category'] = Category::where('id', $request->category_id)->select('id', 'title_' . $lang . ' as title')->first();
        } else {
            $data['sub_categories'] = $this->getCatsSubCats('\App\SubFourCategory', $lang, false, 0, false, $subCategoriesThree);

            $data['sub_category_level3'] = (object)[
                "id" => 0,
                "title" => "All",
                "sub_category_id" => $request->sub_category_level2_id
            ];
            $data['sub_category_array'] = $this->getCatsSubCats('\App\SubFourCategory', $lang, false, 0, false, $subCategoriesThree);
            
            // dd($data['sub_category_array']);
            // if ($request->sub_category_level2_id == 0) {
            //     $data['sub_category_array'] = SubFourCategory::where(function ($q) {
            //         $q->has('SubCategories', '>', 0)->orWhere(function ($qq) {
            //             $qq->has('Products', '>', 0);
            //         });
            //     })->where('deleted', 0)->whereIn('sub_category_id', $request->sub_category_id)->select('id', 'title_' . $lang . ' as title', 'sub_category_id')->orderBy('sort', 'asc')->get();
            //     $three_ids = SubFourCategory::where(function ($q) {
            //         $q->has('SubCategories', '>', 0)->orWhere(function ($qq) {
            //             $qq->has('Products', '>', 0);
            //         });
            //     })->where('deleted', 0)->whereIn('sub_category_id', $request->sub_category_id)->select('id', 'title_' . $lang . ' as title', 'sub_category_id')->orderBy('sort', 'asc')->get()->pluck('id')->toArray();

            // } else {
            //     $data['sub_category_array'] = SubFourCategory::where(function ($q) {
            //         $q->has('SubCategories', '>', 0)->orWhere(function ($qq) {
            //             $qq->has('Products', '>', 0);
            //         });
            //     })->where('deleted', 0)->where('sub_category_id', $request->sub_category_id)->select('id', 'title_' . $lang . ' as title', 'sub_category_id')->orderBy('sort', 'asc')->get();
            //     $three_ids = SubFourCategory::where(function ($q) {
            //         $q->has('SubCategories', '>', 0)->orWhere(function ($qq) {
            //             $qq->has('Products', '>', 0);
            //         });
            //     })->where('deleted', 0)->where('sub_category_id', $request->sub_category_id)->select('id', 'title_' . $lang . ' as title', 'sub_category_id')->orderBy('sort', 'asc')->get()->pluck('id')->toArray();

            // }
            $data['category'] = Category::where('id', $request->category_id)->select('id', 'title_' . $lang . ' as title')->first();
        }


        $All_sub_cat = false;
        $data['sub_next_categories'] = [];
        $fourthIds = [];
        for ($i = 0; $i < count($data['sub_categories']); $i++) {
            if ($data['sub_categories'][$i]['id'] != 0) {
                array_push($fourthIds, $data['sub_categories'][$i]['id']);
            }
        }
        $data['sub_next_categories'] = $this->getCatsSubCats('\App\SubFiveCategory', $lang, false, 0, true, $fourthIds); 

        if (count($data['sub_categories']) == 0) {
            $data['sub_categories'] = $this->getCatsSubCats('\App\SubThreeCategory', $lang, false, $request->sub_category_level2_id, false);
            $data['sub_category_array'] = $this->getCatsSubCats('\App\SubThreeCategory', $lang, false, $request->sub_category_level2_id, false);
        }

        $products = Product::where('status', 1)->where('deleted', 0)->where('publish', 'Y');
        if ($request->sub_category_id != 0) {
            $products = $products->where('sub_category_three_id', $request->sub_category_id);

        } else {
            $products = $products->whereIn('sub_category_three_id', $subCategoriesThree);
        }

        if ($request->sub_category_level2_id != 0) {
            $products = $products->where('sub_category_two_id', $request->sub_category_level2_id);
        }

        if ($request->sub_category_level1_id != 0) {
            $products = $products->where('sub_category_id', $request->sub_category_level1_id);
        }

        $products = $products->select('id', 'title', 'price', 'main_image as image', 'pin', 'created_at')->orderBy('pin', 'DESC')->orderBy('created_at', 'desc')->simplePaginate(12);
        for ($i = 0; $i < count($products); $i++) {
            $products[$i]['price'] = (string)$products[$i]['price'];
            $views = Product_view::where('product_id', $products[$i]['id'])->get()->count();
            $products[$i]['views'] = $views;
            $user = auth()->user();
            if ($user) {
                $favorite = Favorite::where('user_id', $user->id)->where('product_id', $products[$i]['id'])->first();
                if ($favorite) {
                    $products[$i]['favorite'] = true;
                } else {
                    $products[$i]['favorite'] = false;
                }
                $conversation = Participant::where('ad_product_id', $products[$i]['id'])->where('user_id', $user->id)->first();
                if ($conversation == null) {
                    $products[$i]['conversation_id'] = 0;
                } else {
                    $products[$i]['conversation_id'] = $conversation->conversation_id;
                }
            } else {
                $products[$i]['favorite'] = false;
                $products[$i]['conversation_id'] = 0;
            }
            $products[$i]['time'] = $products[$i]['created_at']->format('Y-m-d');
        }
        $data['products'] = $products;


        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }

    public function get_sub_categories_level5(Request $request)
    {
        $lang = $request->lang;
        $validator = Validator::make($request->all(), [
            'category_id' => 'required'
        ]);
        if ($validator->fails() && !isset($request->sub_category_level2_id) && !isset($request->sub_category_level1_id)) {
            $response = APIHelpers::createApiResponse(true, 406, 'بعض الحقول مفقودة', 'بعض الحقول مفقودة', null, $request->lang);
            return response()->json($response, 406);
        }
        if ($request->sub_category_level1_id == 0) {
            $subCategories = SubCategory::where('deleted', 0)->where('category_id', $request->category_id)->pluck('id')->toArray();
            $subCategoriesTwo = SubTwoCategory::where('deleted', 0)->whereIn('sub_category_id', $subCategories)->pluck('id')->toArray();
        } else {
            $subCategoriesTwo = SubTwoCategory::where('deleted', 0)->where('sub_category_id', $request->sub_category_level1_id)->pluck('id')->toArray();
        }
        if ($request->sub_category_level2_id == 0) {
            $subCategoriesThree = SubThreeCategory::where('deleted', 0)->whereIn('sub_category_id', $subCategoriesTwo)->pluck('id')->toArray();
        } else {
            $subCategoriesThree = SubThreeCategory::where('deleted', 0)->where('sub_category_id', $request->sub_category_level2_id)->pluck('id')->toArray();
        }
        if ($request->sub_category_level3_id == 0) {
            $subCategoriesFour = SubFourCategory::where('deleted', 0)->whereIn('sub_category_id', $subCategoriesThree)->pluck('id')->toArray();
        } else {
            $subCategoriesFour = SubFourCategory::where('deleted', 0)->where('sub_category_id', $request->sub_category_level3_id)->pluck('id')->toArray();
        }
        if ($request->sub_category_id != 0) {
            $data['sub_categories'] = $this->getCatsSubCats('\App\SubFiveCategory', $lang, false, $request->sub_category_id, false);
            $data['sub_category_array'] = $this->getCatsSubCats('\App\SubFiveCategory', $lang, false, $request->sub_category_id, false);
            $data['sub_category_level4'] = SubFourCategory::where('deleted', 0)->where('sub_category_id', $request->sub_category_id)->select('id', 'image', 'title_' . $lang . ' as title')->first();
            // if ($request->sub_category_level3_id == 0) {
            //     $data['sub_category_array'] = SubFiveCategory::where(function ($q) {
            //         $q->has('Products', '>', 0);
            //     })->where('deleted', '0')->whereIn('sub_category_id', $request->sub_category_id)->select('id', 'title_' . $lang . ' as title', 'sub_category_id')->orderBy('sort', 'asc')->get();
            // } else {
            //     $data['sub_category_array'] = SubFiveCategory::where(function ($q) {
            //         $q->has('Products', '>', 0);
            //     })->where('deleted', '0')->where('sub_category_id', $request->sub_category_id)->select('id', 'title_' . $lang . ' as title', 'sub_category_id')->orderBy('sort', 'asc')->get();
            // }
            $data['category'] = Category::where('id', $request->category_id)->select('id', 'title_' . $lang . ' as title')->first();
        } else {
            $data['sub_categories'] = $this->getCatsSubCats('\App\SubFiveCategory', $lang, false, 0, false, $subCategoriesFour);
            $data['sub_category_array'] = $this->getCatsSubCats('\App\SubFiveCategory', $lang, false, 0, false, $subCategoriesFour);
            $data['sub_category_level3'] = (object)[
                "id" => 0,
                "title" => "All",
                "sub_category_id" => $request->sub_category_level2_id
            ];
            // if ($request->sub_category_level3_id == 0) {
            //     $data['sub_category_array'] = SubFiveCategory::where(function ($q) {
            //         $q->has('Products', '>', 0);
            //     })->where('deleted', '0')->whereIn('sub_category_id', $request->sub_category_id)->select('id', 'title_' . $lang . ' as title', 'sub_category_id')->orderBy('sort', 'asc')->get();
            // } else {
            //     $data['sub_category_array'] = SubFiveCategory::where(function ($q) {
            //         $q->has('Products', '>', 0);
            //     })->where('deleted', '0')->where('sub_category_id', $request->sub_category_id)->select('id', 'title_' . $lang . ' as title', 'sub_category_id')->orderBy('sort', 'asc')->get();
            // }
            $data['category'] = Category::where('id', $request->category_id)->select('id', 'title_' . $lang . ' as title')->first();
        }
        
        if (count($data['sub_categories']) == 0) {
            $data['sub_categories'] = $this->getCatsSubCats('\App\SubFourCategory', $lang, false, $request->sub_category_level3_id, false);
            $data['sub_category_array'] = $this->getCatsSubCats('\App\SubFourCategory', $lang, false, $request->sub_category_level3_id, false);
        }
        // for ($n = 0; $n < count($data['sub_category_array']); $n++) {
        //     if ($data['sub_category_array'][$n]['id'] == $request->sub_category_id) {
        //         $data['sub_category_array'][$n]['selected'] = true;
        //     } else {
        //         $data['sub_category_array'][$n]['selected'] = false;
        //     }
        // }

        $products = Product::where('status', 1)->where('publish', 'Y')->where('deleted', 0);
        if ($request->sub_category_id != 0) {
            $products = $products->where('sub_category_four_id', $request->sub_category_id);
        }else {
            $products = $products->whereIn('sub_category_four_id', $subCategoriesFour);
        }
        if ($request->sub_category_level3_id != 0) {
            $products = $products->where('sub_category_three_id', $request->sub_category_level3_id);
        }
        if ($request->sub_category_level2_id != 0) {
            $products = $products->where('sub_category_two_id', $request->sub_category_level2_id);
        }
        if ($request->sub_category_level1_id != 0) {
            $products = $products->where('sub_category_id', $request->sub_category_level1_id);
        }
        $products = $products->select('id', 'title', 'price', 'main_image as image', 'pin', 'created_at')->orderBy('pin', 'DESC')->orderBy('created_at', 'desc')->simplePaginate(12);
        for ($i = 0; $i < count($products); $i++) {
            $products[$i]['price'] = (string)$products[$i]['price'];
            $views = Product_view::where('product_id', $products[$i]['id'])->get()->count();
            $products[$i]['views'] = $views;
            $user = auth()->user();
            if ($user) {
                $favorite = Favorite::where('user_id', $user->id)->where('product_id', $products[$i]['id'])->first();
                if ($favorite) {
                    $products[$i]['favorite'] = true;
                } else {
                    $products[$i]['favorite'] = false;
                }
                $conversation = Participant::where('ad_product_id', $products[$i]['id'])->where('user_id', $user->id)->first();
                if ($conversation == null) {
                    $products[$i]['conversation_id'] = 0;
                } else {
                    $products[$i]['conversation_id'] = $conversation->conversation_id;
                }
            } else {
                $products[$i]['favorite'] = false;
                $products[$i]['conversation_id'] = 0;
            }
            $products[$i]['time'] = $products[$i]['created_at']->format('Y-m-d');
        }
        $data['products'] = $products;


        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }

    public function getproducts(Request $request)
    {
        $lang = $request->lang;
        $validator = Validator::make($request->all(), [
            'sub_category_level1_id' => 'required',
            'sub_category_level2_id' => 'required',
            'sub_category_level3_id' => 'required',
            'sub_category_level4_id' => 'required',
            'category_id' => 'required'
        ]);

        if ($validator->fails()) {
            $response = APIHelpers::createApiResponse(true, 406, 'بعض الحقول مفقودة', 'بعض الحقول مفقودة', null, $request->lang);
            return response()->json($response, 406);
        }

        if ($request->sub_category_level1_id == 0) {
            $subCategories = SubCategory::where('deleted', 0)->where('category_id', $request->category_id)->pluck('id')->toArray();
            $subCategoriesTwo = SubTwoCategory::where('deleted', 0)->whereIn('sub_category_id', $subCategories)->pluck('id')->toArray();
        } else {
            $subCategoriesTwo = SubTwoCategory::where('deleted', 0)->where('sub_category_id', $request->sub_category_level1_id)->pluck('id')->toArray();
        }

        if ($request->sub_category_level2_id == 0) {
            $subCategoriesThree = SubThreeCategory::where('deleted', 0)->whereIn('sub_category_id', $subCategoriesTwo)->pluck('id')->toArray();
        } else {
            $subCategoriesThree = SubThreeCategory::where('deleted', 0)->where('sub_category_id', $request->sub_category_level2_id)->pluck('id')->toArray();
        }
        if ($request->sub_category_level3_id == 0) {
            $subCategoriesFour = SubFourCategory::where('deleted', 0)->whereIn('sub_category_id', $subCategoriesThree)->pluck('id')->toArray();
        } else {
            $subCategoriesFour = SubFourCategory::where('deleted', 0)->where('sub_category_id', $request->sub_category_level3_id)->pluck('id')->toArray();
        }

        if ($request->sub_category_level4_id != 0) {
            $data['sub_categories'] = SubFiveCategory::where('deleted', '0')->where('sub_category_id', $request->sub_category_level4_id)->select('id', 'image', 'title_' . $lang . ' as title')->orderBy('sort', 'asc')->get()->toArray();
        } else {
            $data['sub_categories'] = SubFiveCategory::where('deleted', '0')->whereIn('sub_category_id', $subCategoriesFour)->select('id', 'image', 'title_' . $lang . ' as title')->orderBy('sort', 'asc')->get()->toArray();
        }
        if ($request->sub_category_level3_id != 0) {
            $data['sub_category_array'] = SubFiveCategory::where('deleted', '0')->where('sub_category_id', $request->sub_category_level4_id)->select('id', 'image', 'title_' . $lang . ' as title')->orderBy('sort', 'asc')->get()->toArray();
        } else {
            $data['sub_category_array'] = SubFiveCategory::where('deleted', '0')->whereIn('sub_category_id', $subCategoriesFour)->select('id', 'image', 'title_' . $lang . ' as title')->orderBy('sort', 'asc')->get()->toArray();
        }
        if (count($data['sub_category_array']) == 0) {
            $data['sub_category_array'] = SubFiveCategory::where(function ($q) {
                $q->has('Products', '>', 0);
            })->where('deleted', '0')->where('sub_category_id', $request->sub_category_level4_id)->select('id', 'image', 'title_' . $lang . ' as title')->orderBy('sort', 'asc')->get()->toArray();
        }

        $products = Product::where('status', 1)->where('publish', 'Y')->where('deleted', 0);
        if ($request->sub_category_level1_id != 0) {
            $products = $products->where('sub_category_id', $request->sub_category_level1_id);
        }
        if ($request->sub_category_level2_id != 0) {
            $products = $products->where('sub_category_two_id', $request->sub_category_level2_id);
        }
        if ($request->sub_category_level3_id != 0) {
            $products = $products->where('sub_category_three_id', $request->sub_category_level3_id);
        }
        if ($request->sub_category_level4_id != 0) {
            $products = $products->where('sub_category_four_id', $request->sub_category_level4_id);
        }
        $products = $products->where('sub_category_five_id', $request->sub_category_id)->select('id', 'title', 'price', 'main_image as image', 'pin', 'created_at')->where('publish', 'Y')->orderBy('pin', 'DESC')->orderBy('created_at', 'desc')->simplePaginate(12);
        for ($i = 0; $i < count($products); $i++) {
            $products[$i]['price'] = (string)$products[$i]['price'];
            $views = Product_view::where('product_id', $products[$i]['id'])->get()->count();
            $products[$i]['views'] = $views;
            $user = auth()->user();
            if ($user) {
                $favorite = Favorite::where('user_id', $user->id)->where('product_id', $products[$i]['id'])->first();
                if ($favorite) {
                    $products[$i]['favorite'] = true;
                } else {
                    $products[$i]['favorite'] = false;
                }
                $conversation = Participant::where('ad_product_id', $products[$i]['id'])->where('user_id', $user->id)->first();
                if ($conversation == null) {
                    $products[$i]['conversation_id'] = 0;
                } else {
                    $products[$i]['conversation_id'] = $conversation->conversation_id;
                }
            } else {
                $products[$i]['favorite'] = false;
                $products[$i]['conversation_id'] = 0;
            }
            $month = $products[$i]['created_at']->format('F');
            $products[$i]['time'] = $products[$i]['created_at']->format('Y-m-d');
        }
        $data['products'] = $products;
        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }

    public function get_category_products(Request $request) {
        $lang = $request->lang;
        $validator = Validator::make($request->all(), [
            'category_id' => 'required'
        ]);

        if ($validator->fails()) {
            $response = APIHelpers::createApiResponse(true, 406, 'بعض الحقول مفقودة', 'بعض الحقول مفقودة', null, $request->lang);
            return response()->json($response, 406);
        }

        $products = Product::where('status', 1)->where('publish', 'Y')->where('deleted', 0);

        if ($request->category_id && $request->category_id != 0) {
            $products = $products->where('category_id', $request->category_id);
        }

        if ($request->options && count($request->options) > 0) {
            $products->whereHas('Features', function ($q) use ($request) {
                $q->whereIn('target_id', $request->options);
            });
        }

        if($request->has('price_from') && $request->has('price_to')){
            if ($request->price_from == 0 && $request->price_to == 0) {

            }else {
                $products->whereBetween('price', [$request->price_from, $request->price_to]);
            }
        }

        $products = $products->select('id', 'title', 'price', 'main_image as image', 'pin', 'created_at')->where('publish', 'Y')->orderBy('pin', 'DESC')->orderBy('created_at', 'desc')->simplePaginate(12);

        for ($i = 0; $i < count($products); $i++) {
            $products[$i]['price'] = (string)$products[$i]['price'];
            $views = Product_view::where('product_id', $products[$i]['id'])->get()->count();
            $products[$i]['views'] = $views;
            $user = auth()->user();
            if ($user) {
                $favorite = Favorite::where('user_id', $user->id)->where('product_id', $products[$i]['id'])->first();
                if ($favorite) {
                    $products[$i]['favorite'] = true;
                } else {
                    $products[$i]['favorite'] = false;
                }
                $conversation = Participant::where('ad_product_id', $products[$i]['id'])->where('user_id', $user->id)->first();
                if ($conversation == null) {
                    $products[$i]['conversation_id'] = 0;
                } else {
                    $products[$i]['conversation_id'] = $conversation->conversation_id;
                }
            } else {
                $products[$i]['favorite'] = false;
                $products[$i]['conversation_id'] = 0;
            }
            $month = $products[$i]['created_at']->format('F');
            $products[$i]['time'] = $products[$i]['created_at']->format('Y-m-d');
        }
        $data['products'] = $products;

        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }
    //nasser code
    // get ad categories for create ads
    public function show_first_cat(Request $request)
    {
        if ($request->lang == 'en') {
            $data['categories'] = Category::where('deleted', 0)->select('id', 'title_en as title', 'image')->orderBy('sort', 'asc')->get();
        } else {
            $data['categories'] = Category::where('deleted', 0)->select('id', 'title_ar as title', 'image')->orderBy('sort', 'asc')->get();
        }
        if (count($data['categories']) > 0) {
            for ($i = 0; $i < count($data['categories']); $i++) {
                $subThreeCats = SubCategory::where('category_id', $data['categories'][$i]['id'])->where('deleted', 0)->select('id')->first();
                $data['categories'][$i]['next_level'] = false;
                if (isset($subThreeCats['id'])) {
                    $data['categories'][$i]['next_level'] = true;
                }
            }
        }
        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }

    public function show_second_cat(Request $request, $cat_id)
    {
        if ($request->lang == 'en') {
            $data['categories'] = SubCategory::where('category_id', $cat_id)->where('deleted', 0)->select('id', 'title_en as title', 'image')->orderBy('sort', 'asc')->get();
        } else {
            $data['categories'] = SubCategory::where('category_id', $cat_id)->where('deleted', 0)->select('id', 'title_ar as title', 'image')->orderBy('sort', 'asc')->get();
        }
        if (count($data['categories']) > 0) {
            for ($i = 0; $i < count($data['categories']); $i++) {
                $subThreeCats = SubTwoCategory::where('sub_category_id', $data['categories'][$i]['id'])->where('deleted', 0)->where('deleted', 0)->select('id')->first();
                $data['categories'][$i]['next_level'] = false;
                if (isset($subThreeCats['id'])) {
                    $data['categories'][$i]['next_level'] = true;
                }
            }
        }
        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }

    public function show_third_cat(Request $request, $sub_cat_id)
    {
        if ($request->lang == 'en') {
            $data['categories'] = SubTwoCategory::where('sub_category_id', $sub_cat_id)->where('deleted', 0)->select('id', 'title_en as title', 'image')->orderBy('sort', 'asc')->get();
        } else {
            $data['categories'] = SubTwoCategory::where('sub_category_id', $sub_cat_id)->where('deleted', 0)->select('id', 'title_ar as title', 'image')->orderBy('sort', 'asc')->get();
        }
        if (count($data['categories']) > 0) {
            for ($i = 0; $i < count($data['categories']); $i++) {
                $subThreeCats = SubThreeCategory::where('sub_category_id', $data['categories'][$i]['id'])->where('deleted', 0)->select('id')->first();
                $data['categories'][$i]['next_level'] = false;
                if (isset($subThreeCats['id'])) {
                    $data['categories'][$i]['next_level'] = true;
                }
            }
        }
        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }

    public function show_four_cat(Request $request, $sub_sub_cat_id)
    {
        if ($request->lang == 'en') {
            $data['categories'] = SubThreeCategory::where('sub_category_id', $sub_sub_cat_id)->where('deleted', 0)->select('id', 'title_en as title', 'image')->orderBy('sort', 'asc')->get();
        } else {
            $data['categories'] = SubThreeCategory::where('sub_category_id', $sub_sub_cat_id)->where('deleted', 0)->select('id', 'title_ar as title', 'image')->orderBy('sort', 'asc')->get();
        }
        if (count($data['categories']) > 0) {
            for ($i = 0; $i < count($data['categories']); $i++) {
                $subThreeCats = SubFourCategory::where('sub_category_id', $data['categories'][$i]['id'])->where('deleted', 0)->select('id')->first();
                $data['categories'][$i]['next_level'] = false;
                if (isset($subThreeCats['id'])) {
                    $data['categories'][$i]['next_level'] = true;
                }
            }
        }
        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }

    public function show_five_cat(Request $request, $sub_sub_cat_id)
    {
        if ($request->lang == 'en') {
            $data['categories'] = SubFourCategory::where('sub_category_id', $sub_sub_cat_id)->where('deleted', 0)->select('id', 'title_en as title', 'image')->orderBy('sort', 'asc')->get();
        } else {
            $data['categories'] = SubFourCategory::where('sub_category_id', $sub_sub_cat_id)->where('deleted', 0)->select('id', 'title_ar as title', 'image')->orderBy('sort', 'asc')->get();
        }
        if (count($data['categories']) > 0) {
            for ($i = 0; $i < count($data['categories']); $i++) {
                $subThreeCats = SubFiveCategory::where('sub_category_id', $data['categories'][$i]['id'])->where('deleted', '0')->select('id')->first();
                $data['categories'][$i]['next_level'] = false;
                if (isset($subThreeCats['id'])) {
                    $data['categories'][$i]['next_level'] = true;
                }
            }
        }
        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }

    public function show_six_cat(Request $request, $sub_sub_cat_id)
    {
        if ($request->lang == 'en') {
            $data['categories'] = SubFiveCategory::where('sub_category_id', $sub_sub_cat_id)->where('deleted', '0')->select('id', 'title_en as title', 'image')->orderBy('sort', 'asc')->get();
        } else {
            $data['categories'] = SubFiveCategory::where('sub_category_id', $sub_sub_cat_id)->where('deleted', '0')->select('id', 'title_ar as title', 'image')->orderBy('sort', 'asc')->get();
        }
        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }


    // get category options
    public function getCategoryOptions(Request $request, Category $category)
    {
        if ($request->lang == 'en') {
            $data['options'] = Category_option::where('cat_id', $category['id'])->where('cat_type', 'category')->where('deleted', '0')->select('id as option_id', 'title_en as title', 'is_required')->get();

            if (count($data['options']) > 0) {
                for ($i = 0; $i < count($data['options']); $i++) {
                    $data['options'][$i]['type'] = 'input';
                    $optionValues = Category_option_value::where('option_id', $data['options'][$i]['option_id'])->where('deleted', '0')->select('id as value_id', 'value_en as value')->get();
                    if (count($optionValues) > 0) {

                        $data['options'][$i]['type'] = 'select';
                        $data['options'][$i]['values'] = $optionValues;
                    }
                }
            }
        } else {
            $data['options'] = Category_option::where('cat_id', $category['id'])->where('cat_type', 'category')->where('deleted', '0')->select('id as option_id', 'title_ar as title', 'is_required')->get();
            if (count($data['options']) > 0) {
                for ($i = 0; $i < count($data['options']); $i++) {
                    $data['options'][$i]['type'] = 'input';
                    $optionValues = Category_option_value::where('option_id', $data['options'][$i]['option_id'])->where('deleted', '0')->select('id as value_id', 'value_ar as value')->get();
                    if (count($optionValues) > 0) {
                        $data['options'][$i]['type'] = 'select';
                        $data['options'][$i]['values'] = $optionValues;
                    }
                }
            }
        }
        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }

    // get category options
    public function getMainCategoryOptions(Request $request, Category $category)
    {
        $data['options'] = Category_option::where('cat_id', $category['id'])->where('cat_type', 'category')->where('deleted', '0')->where('parent_id', 0)->select('id as option_id', "title_$request->lang as title", 'is_required', 'parent_id')->get();
            
        if (count($data['options']) > 0) {
            for ($i = 0; $i < count($data['options']); $i++) {
                $data['options'][$i]['type'] = 'input';
                $optionValues = Category_option_value::where('option_id', $data['options'][$i]['option_id'])->where('deleted', '0')->select('id as value_id', "value_$request->lang as value")->get();
                if (count($optionValues) > 0) {

                    $data['options'][$i]['type'] = 'select';
                    $data['options'][$i]['values'] = $optionValues;
                }
            }
        }
        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }

    // get sub options
    public function getSubOptions(Request $request)
    {
        $data['options'] = Category_option::where('parent_id', $request->option_id)->where('cat_type', 'category')->where('deleted', '0')->select('id as option_id', "title_$request->lang as title", 'is_required', 'parent_id')->get();
        
        if (count($data['options']) > 0) {
            for ($i = 0; $i < count($data['options']); $i++) {
                $data['options'][$i]['type'] = 'input';
                $optionValues = Category_option_value::where('option_id', $data['options'][$i]['option_id'])->where('parent_id', $request->value_id)->where('deleted', '0')->select('id as value_id', "value_$request->lang as value", 'parent_id')->get();
                if (count($optionValues) > 0) {
                    $data['options'][$i]['type'] = 'select';
                    $data['options'][$i]['values'] = $optionValues;
                }
            }
        }
        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }

    // get sub options by main options
    public function getSubOptionsByMainOptions(Request $request)
    {
        $lang = $request->lang;
        $validator = Validator::make($request->all(), [
            'options' => 'required|array',
            'values' => 'required|array'
        ]);

        if ($validator->fails()) {
            $response = APIHelpers::createApiResponse(true, 406, 'بعض الحقول مفقودة', 'بعض الحقول مفقودة', null, $request->lang);
            return response()->json($response, 406);
        }

        $data['options'] = Category_option::whereIn('parent_id', $request->options)->where('deleted', '0')->select('id as option_id', "title_$request->lang as title", 'is_required', 'parent_id')->get();
        // dd($data['options']);
        if (count($data['options']) > 0) {
            for ($i = 0; $i < count($data['options']); $i++) {
                $data['options'][$i]['type'] = 'input';
                $optionValues = Category_option_value::where('option_id', $data['options'][$i]['option_id'])->whereIn('parent_id', $request->values)->where('deleted', '0')->select('id as value_id', "value_$request->lang as value", 'parent_id')->get();
                if (count($optionValues) > 0) {
                    $data['options'][$i]['type'] = 'select';
                    $data['options'][$i]['values'] = $optionValues;
                }
            }
        }
        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }

    // get sub category options
    public function getSubCategoryOptions(Request $request, Category $category, SubCategory $sub_category)
    {
        if ($request->lang == 'en') {
            $data['options'] = Category_option::where('cat_id', $sub_category['id'])->where('cat_type', 'subcategory')->where('deleted', '0')->select('id as option_id', 'title_en as title', 'is_required')->get();
            if (count($data['options']) > 0) {
                for ($i = 0; $i < count($data['options']); $i++) {
                    $data['options'][$i]['type'] = 'input';
                    $optionValues = Category_option_value::where('option_id', $data['options'][$i]['option_id'])->where('deleted', '0')->select('id as value_id', 'value_en as value')->get();
                    if (count($optionValues) > 0) {

                        $data['options'][$i]['type'] = 'select';
                        $data['options'][$i]['values'] = $optionValues;
                    }
                }
            }
        } else {
            $data['options'] = Category_option::where('cat_id', $sub_category['id'])->where('cat_type', 'subcategory')->where('deleted', '0')->select('id as option_id', 'title_ar as title', 'is_required')->get();
            if (count($data['options']) > 0) {
                for ($i = 0; $i < count($data['options']); $i++) {
                    $data['options'][$i]['type'] = 'input';
                    $optionValues = Category_option_value::where('option_id', $data['options'][$i]['option_id'])->where('deleted', '0')->select('id as value_id', 'value_ar as value')->get();
                    if (count($optionValues) > 0) {
                        $data['options'][$i]['type'] = 'select';
                        $data['options'][$i]['values'] = $optionValues;
                    }
                }
            }
        }

        if (count($data['options']) == 0) {
            if ($request->lang == 'en') {
                $data['options'] = Category_option::where('cat_id', $category['id'])->where('cat_type', 'category')->where('deleted', '0')->select('id as option_id', 'title_en as title', 'is_required')->get();

                if (count($data['options']) > 0) {
                    for ($i = 0; $i < count($data['options']); $i++) {
                        $data['options'][$i]['type'] = 'input';
                        $optionValues = Category_option_value::where('option_id', $data['options'][$i]['option_id'])->where('deleted', '0')->select('id as value_id', 'value_en as value')->get();
                        if (count($optionValues) > 0) {

                            $data['options'][$i]['type'] = 'select';
                            $data['options'][$i]['values'] = $optionValues;
                        }
                    }
                }
            } else {
                $data['options'] = Category_option::where('cat_id', $category['id'])->where('cat_type', 'category')->where('deleted', '0')->select('id as option_id', 'title_ar as title', 'is_required')->get();
                if (count($data['options']) > 0) {
                    for ($i = 0; $i < count($data['options']); $i++) {
                        $data['options'][$i]['type'] = 'input';
                        $optionValues = Category_option_value::where('option_id', $data['options'][$i]['option_id'])->where('deleted', '0')->select('id as value_id', 'value_ar as value')->get();
                        if (count($optionValues) > 0) {
                            $data['options'][$i]['type'] = 'select';
                            $data['options'][$i]['values'] = $optionValues;
                        }
                    }
                }
            }
        }
        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }

    public function getSubTwoCategoryOptions(Request $request, $category, $sub_category, $sub_two_category)
    {
        $lang = $request->lang;
        $data['options'] = [];
        if ($sub_two_category != 0) {
            $data['options'] = Category_option::where('cat_id', $sub_two_category)->where('cat_type', 'subTwoCategory')->where('deleted', '0')->select('id as option_id', 'title_' . $lang . ' as title', 'is_required')->get();
            if (count($data['options']) > 0) {
                for ($i = 0; $i < count($data['options']); $i++) {
                    $data['options'][$i]['type'] = 'input';
                    $optionValues = Category_option_value::where('option_id', $data['options'][$i]['option_id'])->where('deleted', '0')->select('id as value_id', 'value_' . $lang . ' as value')->get();
                    if (count($optionValues) > 0) {
                        $data['options'][$i]['type'] = 'select';
                        $data['options'][$i]['values'] = $optionValues;
                    }
                }
            }
        }

        if ($sub_category != 0) {
            if (count($data['options']) == 0) {
                $data['options'] = Category_option::where('cat_id', $sub_category)->where('cat_type', 'subcategory')->where('deleted', '0')->select('id as option_id', 'title_' . $lang . ' as title', 'is_required')->get();
                if (count($data['options']) > 0) {
                    for ($i = 0; $i < count($data['options']); $i++) {
                        $data['options'][$i]['type'] = 'input';
                        $optionValues = Category_option_value::where('option_id', $data['options'][$i]['option_id'])->where('deleted', '0')->select('id as value_id', 'value_' . $lang . ' as value')->get();
                        if (count($optionValues) > 0) {

                            $data['options'][$i]['type'] = 'select';
                            $data['options'][$i]['values'] = $optionValues;
                        }
                    }
                }
            }
        }


        if ($category != 0) {
            if (count($data['options']) == 0) {
                $data['options'] = Category_option::where('cat_id', $category)->where('cat_type', 'category')->where('deleted', '0')->select('id as option_id', 'title_' . $lang . ' as title', 'is_required')->get();
                if (count($data['options']) > 0) {
                    for ($i = 0; $i < count($data['options']); $i++) {
                        $data['options'][$i]['type'] = 'input';
                        $optionValues = Category_option_value::where('option_id', $data['options'][$i]['option_id'])->where('deleted', '0')->select('id as value_id', 'value_' . $lang . ' as value')->get();
                        if (count($optionValues) > 0) {
                            $data['options'][$i]['type'] = 'select';
                            $data['options'][$i]['values'] = $optionValues;
                        }
                    }
                }
            }
        }
        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }

}
