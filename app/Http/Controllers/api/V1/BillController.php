<?php

namespace App\Http\Controllers\api\V1;

use App\Models\Bill;
use App\Models\Order;
use App\Providers\AppServiceProvider;
use Illuminate\Http\Request;

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
            return AppServiceProvider::apiResponse('bill is paid');
        }
    }
}
