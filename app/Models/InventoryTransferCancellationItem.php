<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransferCancellationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cancellation_id',
        'transfer_item_id',
        'product_id',
        'cancelled_quantity',
        'reason',
    ];

    protected $casts = [
        'cancelled_quantity' => 'integer',
    ];

    public function cancellation(): BelongsTo
    {
        return $this->belongsTo(InventoryTransferCancellation::class, 'cancellation_id');
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
