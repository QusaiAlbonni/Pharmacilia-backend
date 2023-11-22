<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class GlobalVariablesServiceProvider extends ServiceProvider
{

    protected static $categories = [
        'oral_use',
        'external_use',
        'injectable',
        'Intravenous_fluids',
        'vaccines_and_serums',
        'sterilizers',
        'other'
    ];

    /**
     * @var string $categories[]
     * @return array $categories
     */
    public static function categories(): array
    {
        return self::$categories;
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
