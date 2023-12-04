<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class product extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $hidden = ['pivot'];

    public function orders()
    {
        return $this->belongsToMany(Order::class)->withPivot('quantity');}

    public function scopeFilter($query, array $filters)
    {



        $query->when($filters['search_text'] ?? false, function ($query, $search) {

            $query
                ->where('brand_name', 'like', $search . "%")
                ->orwhere('brand_name_ar', 'like', $search . "%")
                ->orwhere('manufacturer', 'like', $search . "%")
                ->orwhere('manufacturer_ar', 'like', $search . "%");
        });
        $query->when($filters['category'] ?? false, function ($query, $category) {

            $query
                ->where('category', 'like', $category . "%");
        });
    }
}
