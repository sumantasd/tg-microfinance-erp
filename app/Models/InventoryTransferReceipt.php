<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryTransferReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_id',
        'receipt_number',
        'received_by',
        'received_at',
        'total_good_quantity',
        'total_damaged_quantity',
        'total_shortage_quantity',
        'notes',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'total_good_quantity' => 'integer',
        'total_damaged_quantity' => 'integer',
        'total_shortage_quantity' => 'integer',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(InventoryTransfer::class, 'transfer_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryTransferReceiptItem::class, 'receipt_id');
    }
}
