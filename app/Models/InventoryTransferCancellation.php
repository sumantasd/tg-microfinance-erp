<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryTransferCancellation extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_id',
        'cancelled_by',
        'cancelled_at',
        'scope',
        'category_id',
        'total_cancelled_quantity',
        'reason',
    ];

    protected $casts = [
        'cancelled_at' => 'datetime',
        'total_cancelled_quantity' => 'integer',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(InventoryTransfer::class, 'transfer_id');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryTransferCancellationItem::class, 'cancellation_id');
    }
}
