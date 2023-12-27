<?php

namespace App\Http\Controllers\api\V1;

use App\Models\product;
use App\Http\Requests\StoreproductRequest;
use App\Http\Requests\UpdateproductRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Providers\AppServiceProvider as AppSP;
use app\Providers\GlobalVariablesServiceProvider as GlobalVariables;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use PhpParser\Node\Expr\PostDec;
use PhpParser\Node\Stmt\TryCatch;
use Psy\Command\WhereamiCommand;

class ProductController extends Controller
{



    /**
     * Search
     * This functionality allows pharmacists or warehouse owners to search for medications
     * Based on drug_name,manufacturer or classification
     */
    public function search(Request $request)
    {
        $validator = Validator::make($request->only('search_text', 'category', 'start', 'limit'), [
            'category' => 'string|in:' . implode(',', GlobalVariables::categories()),
            'search_text' => 'string',
            'start' => 'required|integer|min:0',
            'limit' => 'required|integer|min:1'
        ]);
        if ($validator->fails()) {
            return AppSP::apiResponse('validation error', $validator->errors(), 'errors', false, 422);
        }
        try {
            $products = product::latest()->filter(request(['search_text', 'category']))
                ->offset($request->start)
                ->limit($request->limit)
                ->get();
            if (count($products) != 0) {
                return AppSP::apiResponse(
                    'Item recieved',
                    $products,
                    'products'
                );
            } else
                return AppSP::apiResponse('no results found', null, 'data', false, 404);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $products = product::latest()->when(
                auth()->user()->role == 'user',
                function ($query) {
                    $query->where('expiration_date', '>', now());
                }
            )->get();
            return AppSP::apiResponse(
                'products retrieved',
                $products,
                'products'
            );
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function common(){
        try {
            $products=product::orderBy('sales','desc')->where('expiration_date', '>', now())->get();

                return AppSP::apiResponse(
                    'Item recieved depending on most sales',
                    $products,
                    'products'
                );
         } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
                'data' => null
            ], 500);
     }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreproductRequest $request)
    {
        $data = $request->validated();
        if (isset($data['image'])) {
            try {
                $imagePath = $data['image']->store('ProductImages', 'public');
                $image = Image::make(public_path("storage/{$imagePath}"))->fit(600, 600);
                $image->save();
            } catch (\Throwable $th) {
                log::info('error', $th->getTrace());
                return response()->json([
                    'status' => false,
                    'message' => $th->getMessage(),
                    'data' => null
                ], 500);
            }
            unset($data['image']);
        }
        $data['image'] = isset($imagePath) ? $imagePath : null;
        $product = product::create($data);
        return response()->json([
            'status' => true,
            'message' => 'Product added',
            'product' => $product
        ]);
    }

    /**
     * choose a specific drug to view.
     *  detailed information.
     */
    public function show(product $product)
    {

        if (auth()->user()->role == 'user') {
            if (Carbon::parse($product->expiration_date)->lessThan(Carbon::now())) {
                return AppSP::apiResponse('not found', null, "data", false, 404);
            }
        }
        return response()->json([
            'status' => true,
            'message' => 'Item retrieved',
            'product' => $product
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateproductRequest $request, product $product)
    {
        $data = $request->validated();
        if (isset($data['image'])) {
            try {
                $imagePath = $data['image']->store('ProductImages', 'public');
                $image = Image::make(public_path("storage/{$imagePath}"))->fit(600, 600);
                $image->save();
            } catch (\Throwable $th) {
                log::info('error', $th->getTrace());
                return response()->json([
                    'status' => false,
                    'message' => $th->getMessage(),
                    'data' => null
                ], 500);
            }
            unset($data['image']);
        }
        $data['image'] = isset($imagePath) ? $imagePath : null;
        $status = $product->update($data);
        return response()->json([
            'status' => $status,
            'message' => 'Product updated',
            'product' => $product
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(product $product)
    {
        if ($product->orders()->whereIn('status',['sent', 'pending'])->count()) {
            return AppSP::apiResponse('there are pending or sending orders placed for this product',null, 'data', false, 403);
        }
        try {
            $prodData = $product->toArray();
            $status = $product->delete();
            if ($status) {
                return response()->json([
                    'status' => true,
                    'message' => 'Product deleted',
                    'product' => $prodData
                ]);
            } else {
                return AppSP::apiResponse('failed to delete', null, 'data', false, 500);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
