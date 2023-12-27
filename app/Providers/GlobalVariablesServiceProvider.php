<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class GlobalVariablesServiceProvider extends ServiceProvider
{

    protected const categories = [
        'oral_use',
        'external_use',
        'injectable',
        'Intravenous_fluids',
        'vaccines_and_serums',
        'sterilizers',
        'other'
    ];
    protected const orderStatuses = [
        'pending',
        'sent',
        'received'
    ];

    /**
     * @var string $categories[]
     * @return array categories
     */
    public static function categories(): array
    {
        return self::categories;
    }
    public static function orderStatuses(): array
    {
        return self::orderStatuses;
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
