<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashBookDenomination extends Model
{
    use HasFactory;

    protected $fillable = [
        'cash_book_id',
        'denomination',
        'count',
        'amount',
    ];

    protected $casts = [
        'denomination' => 'integer',
        'count' => 'integer',
        'amount' => 'decimal:2',
    ];

    public function cashBook(): BelongsTo
    {
        return $this->belongsTo(CashBook::class);
    }
}
