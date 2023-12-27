<?php

namespace App\Http\Controllers\api\V1;

use App\Models\product;
use App\Providers\AppServiceProvider;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function toggleFavorite(product $product){
        $isFavorite = auth()->user()->favorites()->toggle($product->id);
        return response()->json(['is_favorite' => $isFavorite]);
    }
    public function index(){
        $favs = auth()->user()->favorites()->get();
        return AppServiceProvider::apiResponse('retrieved favorite products', $favs, 'products');
    }
    public function isFavorite(product $product){
        $isfav = auth()->user()->favorites->contains($product->id);
        return AppServiceProvider::apiResponse('checked succesfully', $isfav, 'isfavorite');
    }
}
