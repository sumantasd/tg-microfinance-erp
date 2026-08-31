<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CashBookEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'cash_book_id',
        'entry_type',
        'category_code',
        'particulars',
        'entry_date',
        'cash_amount',
        'product_amount',
        'bank_amount',
        'reference_type',
        'reference_id',
        'sort_order',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'cash_amount' => 'decimal:2',
        'product_amount' => 'decimal:2',
        'bank_amount' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function cashBook(): BelongsTo
    {
        return $this->belongsTo(CashBook::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
