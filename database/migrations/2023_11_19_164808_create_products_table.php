<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Providers\GlobalVariablesServiceProvider as GlobalVariables;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->string('scientific_name')->nullable();
            $table->string('scientific_name_ar')->nullable();
            $table->string('brand_name');
            $table->string('brand_name_ar');
            $table->enum('category', GlobalVariables::categories())->default('other');
            $table->string('manufacturer');
            $table->string('manufacturer_ar');
            $table->unsignedInteger('stock');
            $table->unsignedInteger('sales')->default(0);
            $table->unsignedFloat('price');
            $table->timestamp('expiration_date');
            $table->text('description')->nullable();
            $table->text('description_ar')->nullable();
            $table->string('image')->nullable();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
