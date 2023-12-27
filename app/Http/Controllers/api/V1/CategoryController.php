<?php

namespace App\Http\Controllers\api\V1;

use App\Models\Category;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Providers\AppServiceProvider as AppSP;
use Intervention\Image\Facades\Image;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {

            $Categories = Category::all();
            return AppSP::apiResponse(
                'items retrieved',
                $Categories,
                'categories',
            );
        } catch (\Throwable $th) {
            return AppSP::apiResponse(
                $th->getMessage(),
                null,
                'data',
                false,
                500
            );
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request)
    {
        $data = $request->validated();
        if (isset($data['image'])) {
            try {
                $imagePath = $data['image']->store('Categories', 'public');
                $image = Image::make(public_path("storage/{$imagePath}"))->fit(50, 50);
                $image->save();
            } catch (\Throwable $th) {
                return response()->json([
                    'status' => false,
                    'message' => $th->getMessage(),
                    'data' => null
                ], 500);
            }
            unset($data['image']);
        }
        $data['image'] = isset($imagePath) ? $imagePath : null;
        $category = Category::create($data);
        return response()->json([
            'status' => true,
            'message' => 'category added',
            'category' => $category
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        return AppSP::apiResponse(
            'data retrieved',
            $category,
            'category',
        );
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        try {
            $status = $category->delete();
            return AppSP::apiResponse(
                'category deleted');
        } catch (\Throwable $th) {
            return AppSP::apiResponse(
                $th->getMessage(),
                null,
                'data',
                false,
                500
            );
        }
    }
}
