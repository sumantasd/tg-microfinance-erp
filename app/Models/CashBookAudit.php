<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashBookAudit extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'cash_book_id',
        'user_id',
        'action',
        'changes',
        'ip_address',
        'notes',
        'created_at',
    ];

    protected $casts = [
        'changes' => 'array',
        'created_at' => 'datetime',
    ];

    public function cashBook(): BelongsTo
    {
        return $this->belongsTo(CashBook::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
