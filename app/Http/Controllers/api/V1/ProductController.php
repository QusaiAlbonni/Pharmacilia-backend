<?php

namespace App\Http\Controllers\api\V1;

use App\Models\product;
use App\Http\Requests\StoreproductRequest;
use App\Http\Requests\UpdateproductRequest;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Providers\AppServiceProvider;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $products = product::all();
            return AppServiceProvider::apiResponse(
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
                    'data'=>null
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
        //
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
            }
            else {
                return AppServiceProvider::apiResponse('failed to delete', null, 'data', false, 500);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
