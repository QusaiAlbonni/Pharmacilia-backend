<?php

namespace App\Http\Controllers\api\V1;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\product;
use Illuminate\Http\Request;
use app\Providers\AppServiceProvider as AppSP;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\UnauthorizedException;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $orders = Order::with(['user' => function ($query) {
                $query->select('id', 'name', 'phone');
            }, 'products' => function ($query) {
                $query->select('products.id', 'products.brand_name', 'products.brand_name_ar', 'quantity');
            }]);
            if (auth()->user()->role == 'admin') {
                $orders = $orders->get();
            } else
                $orders = $orders->where('user_id', auth()->user()->id)->get();
            return AppSP::apiResponse('items retrieved', $orders, 'orders');
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
    public function store(StoreOrderRequest $request)
    {
        $products = $request->products;
        try {
            $order = auth()->user()->orders()->create();
            $order->products()->attach($products, ['created_at' => now(), 'updated_at' => now()]);
            foreach ($order->products as $product) {
                $product->decrement('stock', $product->pivot->quantity);
            }
            return AppSP::apiResponse('order added');
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
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        if (auth()->user()->role == 'user')
            $this->authorize('access', $order);
        try {
            $order = $order->load(['products' => function ($query) {
                $query->select('products.id', 'products.brand_name', 'products.brand_name_ar', 'quantity');
            }]);
            return AppSP::apiResponse('item retreived', $order, 'order');
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
     * Show the form for editing the specified resource.
     */
    public function edit(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *  THIS IS UNTESTED
     */
    public function destroy(Order $order)
    {
        if ($order->status !== 'bending') {
            return AppSP::apiResponse(
                'the order has been already proccessed cannot cancel',
                null,
                'data',
                false,
                403
            );
        }
        $this->authorize('access', $order);
        try {
            foreach ($order->products as $product) {
                $product->increment('stock', $product->pivot->quantity);
            }
            $order->delete();
            return AppSP::apiResponse('order deleted');
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
