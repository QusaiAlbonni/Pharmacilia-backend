<?php

namespace App\Http\Controllers\api\V1;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Providers\AppServiceProvider as AppSP;

class ReportsController extends Controller
{
    public function salesByMonth()
    {
        $validator = Validator::make(request()->only('month'), [
            'month' => 'required|date_format:Y-m',
        ]);
        if ($validator->fails()) {
            return AppSP::apiResponse('validation error', $validator->errors(), 'errors', false, 422);
        }
        $selectedMonth = request()->month;

        $totalSales = Order::where('status', 'received')
            ->whereMonth('updated_at', '=', substr($selectedMonth, 5, 2))
            ->whereYear('updated_at', '=', substr($selectedMonth, 0, 4))
            ->with(['products' => function ($query) {
                $query->withTrashed();
            }])
            ->get()
            ->map(function ($order) {
                return $order->products->map(function ($product) use ($order) {
                    return [
                        'product_id' => $product->id,
                        'quantity' => $product->pivot->quantity,
                        'total_sale' => $product->pivot->quantity * $product->pivot->price,
                    ];
                });
            })
            ->flatten(1)
            ->groupBy('product_id')
            ->map(function ($sales) {
                return [
                    'total_quantity' => $sales->sum('quantity'),
                    'total_sales' => $sales->sum('total_sale'),
                ];
            });
        $totalSales = collect($totalSales
            ->map(function ($sales, $product_id) {
                return [
                    'product_id' => $product_id,
                    'total_quantity' => $sales['total_quantity'],
                    'total_sales' => $sales['total_sales'],
                ];
            })
            ->sortByDesc('total_sales')
            ->values()
            ->toArray());

        $totalSalesAllProducts = $totalSales->sum('total_sales');
        $totalQuantityAllProducts = $totalSales->sum('total_quantity');
        $finalData = [
            'total_sales' => $totalSalesAllProducts,
            'total_quantity' => $totalQuantityAllProducts,
            'products' => $totalSales,
        ];
        return AppSP::apiResponse('retrieved report', $finalData);
    }
    public function userByMonth()
    {

        $lastTwelveMonthsData = [];

        for ($i = 0; $i < 12; $i++) {
            $currentMonth = now()->subMonths($i);

            $totalSales = Order::where('user_id', auth()->user()->id)
                ->whereMonth('created_at', '=', $currentMonth->format('m'))
                ->whereYear('created_at', '=', $currentMonth->format('Y'))
                ->with(['products' => function ($query) {
                    $query->withTrashed();
                }])
                ->get()
                ->map(function ($order) {
                    return $order->products->map(function ($product) use ($order) {
                        return [
                            'product_id' => $product->id,
                            'quantity' => $product->pivot->quantity,
                            'total_sale' => $product->pivot->quantity * $product->pivot->price,
                        ];
                    });
                })
                ->flatten(1)
                ->groupBy('product_id')
                ->map(function ($sales) {
                    return [
                        'total_quantity' => $sales->sum('quantity'),
                        'total_sales' => $sales->sum('total_sale'),
                    ];
                });

            $totalSales = collect($totalSales
                ->map(function ($sales, $product_id) {
                    return [
                        'product_id' => $product_id,
                        'total_quantity' => $sales['total_quantity'],
                        'total_sales' => $sales['total_sales'],
                    ];
                })
                ->sortByDesc('total_sales')
                ->values()
                ->toArray());

            $lastTwelveMonthsData[] = [
                'month' => $currentMonth->format('Y-m'),
                'total_sales' => $totalSales->sum('total_sales'),
                'total_quantity' => $totalSales->sum('total_quantity'),
                'products' => $totalSales,
            ];
        }

        return AppSP::apiResponse('retrieved report', $lastTwelveMonthsData);
    }
}
