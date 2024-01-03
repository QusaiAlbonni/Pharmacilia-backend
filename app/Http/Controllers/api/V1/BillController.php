<?php

namespace App\Http\Controllers\api\V1;

use App\Models\Bill;
use App\Models\Order;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BillController extends Controller
{


    public function show(Bill $bill){
        if ($bill->order->user->role != 'admin' && ($bill->order->user->id != auth()->user()->id)) {
            return AppServiceProvider::apiResponse('unauthorized',null,'data', false, 403);
        }
        return $bill;
    }
    public function pay(Order $order){
        if ($order->bill->paid) {
            return AppServiceProvider::apiResponse('bill already paid', null, "data", false, 403);
        }
        else {
            $order->bill->paid = true;
            $order->bill->save();
            $server_fcm_key = env('FCM_SERVER_KEY');
            $fcm_token = User::where('id', $order->user_id)->value('fcm_token');
            $fcm = Http::acceptJson()->withToken($server_fcm_key)->post(
                'https://fcm.googleapis.com/fcm/send',
                [
                    'to' => $fcm_token,
                    'notification' =>
                    [
                        'title' => 'Your Bill was paid',
                        'body' =>"your Bill with a total of {$order->bill->total} was paid",
                    ],
                    'data'=>[
                        'order_id'=>$order->id
                    ]
                ]

            );
            return AppServiceProvider::apiResponse('bill is paid');
        }
    }
}
