<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'product_id',
        'product_sku_snapshot',
        'product_name_snapshot',
        'quantity',
        'unit_purchase_cost',
        'mrp_snapshot',
        'discount',
        'tax_rate',
        'tax_amount',
        'line_subtotal',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_purchase_cost' => 'decimal:2',
        'mrp_snapshot' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'line_subtotal' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(ProductPurchase::class, 'purchase_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
