<?php

namespace Database\Seeders;

use App\Providers\AppServiceProvider;
use App\Providers\GlobalVariablesServiceProvider;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cats = GlobalVariablesServiceProvider::categories();
        for ($i=0; $i < 7; $i++) {
            \App\Models\Category::create([
                'category_name'=>$cats[$i],
                'category_name_ar'=>$cats[6 - $i]
            ]);
        }
    }
}
