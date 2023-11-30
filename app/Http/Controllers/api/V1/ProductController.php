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

class ProductController extends Controller
{



    public function getByCategory(Request $request)
    {
        $validator = Validator::make($request->only('category', 'start', 'limit'), [
            'category' => 'required|in:' . implode(',', GlobalVariables::categories()),
            'start' => 'required|integer|min:0',
            'limit' => 'required|integer|min:1'
        ]);
        if ($validator->fails()) {
            return AppSP::apiResponse('validation error', $validator->errors(), 'errors', false, 422);
        }
        try {
            $products = product::where('category', $request->category)
                ->when(auth()->user()->role == 'user', function ($query) {
                    $query->where('expiration_date', '>', now());
                })
                ->offset($request->start)
                ->limit($request->limit)
                ->get();
            if (count($products) != 0) {
                return AppSP::apiResponse(
                    'retrieved items by category',
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
            $products = product::all();
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
     * Display the specified resource.
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
