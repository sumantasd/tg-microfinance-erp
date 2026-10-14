<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransferReceiptItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_id',
        'transfer_item_id',
        'product_id',
        'good_quantity',
        'damaged_quantity',
        'shortage_quantity',
        'remarks',
    ];

    protected $casts = [
        'good_quantity' => 'integer',
        'damaged_quantity' => 'integer',
        'shortage_quantity' => 'integer',
    ];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(InventoryTransferReceipt::class, 'receipt_id');
    }

    public function transferItem(): BelongsTo
    {
        return $this->belongsTo(InventoryTransferItem::class, 'transfer_item_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
