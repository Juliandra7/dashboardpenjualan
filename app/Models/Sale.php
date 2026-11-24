<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $table = 'sales';

    // Tabel tidak memakai created_at / updated_at
    public $timestamps = false;

    protected $fillable = [
        'order_id', 'amount', 'profit', 'quantity', 'category', 'sub_category',
        'payment_mode', 'order_date', 'customer_name', 'state', 'city', 'year_month',
    ];

    protected $casts = [
        'amount'     => 'float',
        'profit'     => 'float',
        'quantity'   => 'integer',
        'order_date' => 'date',
    ];
}
