<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransferItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_id',
        'product_id',
        'quantity',
        'received_quantity',
        'damaged_quantity',
        'shortage_quantity',
        'cancelled_quantity',
        'discrepancy_remarks',
        'cancellation_reason',
        'cancelled_by',
        'cancelled_at',
        'unit_price',
        'total_value',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'received_quantity' => 'integer',
        'damaged_quantity' => 'integer',
        'shortage_quantity' => 'integer',
        'cancelled_quantity' => 'integer',
        'cancelled_at' => 'datetime',
        'unit_price' => 'decimal:2',
        'total_value' => 'decimal:2',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(InventoryTransfer::class, 'transfer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function receiptItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InventoryTransferReceiptItem::class, 'transfer_item_id');
    }

    public function cancellationItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InventoryTransferCancellationItem::class, 'transfer_item_id');
    }

    public function getRemainingQuantityAttribute(): int
    {
        return max(0, $this->quantity - (
            (int)$this->received_quantity +
            (int)$this->damaged_quantity +
            (int)$this->shortage_quantity +
            (int)$this->cancelled_quantity
        ));
    }

    public function getPendingQuantityAttribute(): int
    {
        return $this->remaining_quantity;
    }

    public function getIsFullyReceivedAttribute(): bool
    {
        return $this->remaining_quantity === 0 && (int)$this->received_quantity === (int)$this->quantity;
    }

    public function getIsClosedAttribute(): bool
    {
        return $this->remaining_quantity === 0;
    }
}
