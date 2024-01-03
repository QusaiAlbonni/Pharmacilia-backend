<?php

namespace App\Http\Controllers\api\V1;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Bill;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use App\Models\product;
use Illuminate\Http\Request;
use app\Providers\AppServiceProvider as AppSP;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\UnauthorizedException;

class OrderController extends Controller
{



    public function filterbystatus()
    {
        $validator = Validator::make(request()->only('status', 'paid', 'start', 'limit'), [
            'status' => 'string|in:pending,sent,received',
            'paid' => 'boolean',
            'start' => 'required|integer|min:0',
            'limit' => 'required|integer|min:1'
        ]);
        if ($validator->fails()) {
            return AppSP::apiResponse('validation error', $validator->errors(), 'errors', false, 422);
        }
        $orders = Order::with(['user' => function ($query) {
            $query->select('id', 'name', 'phone');
        }, 'products' => function ($query) {
            $query->select('products.id', 'products.brand_name', 'products.brand_name_ar', 'quantity', 'order_product.price');
            $query->withTrashed();
        }, 'bill' => function ($query) {
            $query->select('id', 'total', 'paid', 'order_id');
        }]);
        if (isset(request()->status))
            $orders = $orders->where('status', request()->status);
        if (isset(request()->paid)) {
            $isPaid = request()->paid;
            $orders = $orders->whereHas('bill', function ($billQuery) use ($isPaid) {
                $billQuery->where('paid', $isPaid);
            });
        }
        if (auth()->user()->role == 'user') {
            $orders = $orders->where('user_id', auth()->user()->id)->get();
        } else
            $orders = $orders->get();
        return AppSP::apiResponse('retrieved orders', $orders, 'orders');
    }




    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $orders = Order::with(['user' => function ($query) {
                $query->select('id', 'name', 'phone');
            }, 'products' => function ($query) {
                $query->select('products.id', 'products.brand_name', 'products.brand_name_ar', 'quantity', 'order_product.price');
                $query->withTrashed();
            }, 'bill' => function ($query) {
                $query->select('id', 'total', 'paid', 'order_id');
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
            foreach ($products as $product) {
                $order->products()->attach($product['product_id'], [
                    'created_at' => now(), 'updated_at' => now(),
                    'product_id' => $product['product_id'],
                    'quantity' => $product['quantity'],
                    'price' => product::find($product['product_id'])->price
                ]);
            }

            foreach ($order->products as $product) {
                $product->decrement('stock', $product->pivot->quantity);
            }

            $products = $order->products;
            $total = 0.0;
            foreach ($products as $product) {
                $total += $product->pivot->quantity * $product->pivot->price;
            }
            $bill = new Bill();
            $bill->total = $total;
            $order->bill()->save($bill);

            $server_fcm_key = env('FCM_SERVER_KEY');
            $fcm_tokens = User::where('role', 'admin')->pluck('fcm_token');
            $fcm = Http::acceptJson()->withToken($server_fcm_key)->post(
                'https://fcm.googleapis.com/fcm/send',
                [
                    'registration_ids' => $fcm_tokens,
                    'notification' =>
                    [
                        'title' => 'New order!',
                        'body' =>"A new Order was added by {$order->user->name}",
                    ],
                    'data'=>[
                        'order_id'=>$order->id
                    ]
                ]

            );

            return AppSP::apiResponse('order added', $order, 'order');
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
                $query->select('products.id', 'products.brand_name', 'products.brand_name_ar', 'quantity', 'order_product.price');
                $query->withTrashed();
            }, 'bill' => function ($query) {
                $query->select('id', 'total', 'paid', 'order_id');
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
    public function send(Order $order)
    {
        if ($order->status != 'pending') {
            return AppSP::apiResponse('order is not pending', null, 'data', false, 403);
        }
        $order->status = 'sent';
        $order->save();
        $server_fcm_key = env('FCM_SERVER_KEY');
        $fcm_token = User::where('id', $order->user_id)->value('fcm_token');
        $fcm = Http::acceptJson()->withToken($server_fcm_key)->post(
            'https://fcm.googleapis.com/fcm/send',
            [
                'to' => $fcm_token,
                'notification' =>
                [
                    'title' => 'Your Order is on the way!',
                    'body' =>"your Order has been sent and is on its way",
                ],
                'data'=>[
                    'order_id'=>$order->id
                ]
            ]

        );
        return AppSP::apiResponse('order sent', $order, 'order');
    }
    public function receive(Order $order)
    {
        if ($order->status != 'sent') {
            return AppSP::apiResponse('order is not sent', null, 'data', false, 403);
        }
        $order->status = 'received';
        $order->save();
        $server_fcm_key = env('FCM_SERVER_KEY');
        $fcm_token = User::where('id', $order->user_id)->value('fcm_token');
        $fcm = Http::acceptJson()->withToken($server_fcm_key)->post(
            'https://fcm.googleapis.com/fcm/send',
            [
                'to' => $fcm_token,
                'notification' =>
                [
                    'title' => 'Your Order has been delivered',
                    'body' =>"your Order has been succesfully delivered and received if you think its a mistake contact support",
                ],
                'data'=>[
                    'order_id'=>$order->id
                ]
            ]

        );
        foreach ($order->products as $product) {
            $product->increment('sales', $product->pivot->quantity);
        }
        return AppSP::apiResponse('order received', $order, 'order');
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
        if ($order->status !== 'pending') {
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
