<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    use HasFactory;
    protected $guarded = ['*'];
    protected $casts = ['total'=>'float'];

    protected $hidden = ['pivot'];


    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
