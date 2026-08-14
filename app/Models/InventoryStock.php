<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'product_id',
        'current_stock',
        'reserved_stock',
        'reorder_level',
        'last_restocked_at',
    ];

    protected $casts = [
        'current_stock' => 'integer',
        'reserved_stock' => 'integer',
        'reorder_level' => 'integer',
        'last_restocked_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getAvailableStockAttribute(): int
    {
        return max(0, $this->current_stock - $this->reserved_stock);
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->current_stock <= 0) {
            return 'out_of_stock';
        }
        if ($this->current_stock <= $this->reorder_level) {
            return 'low_stock';
        }
        return 'in_stock';
    }
}
