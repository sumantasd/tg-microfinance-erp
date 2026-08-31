<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashBookOnlineCollection extends Model
{
    use HasFactory;

    protected $fillable = [
        'cash_book_id',
        'collection_date',
        'customer_id',
        'customer_name',
        'amount',
        'group_id',
        'group_name',
        'mobile_no',
        'payment_method',
        'transaction_reference',
        'repayment_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'collection_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function cashBook(): BelongsTo
    {
        return $this->belongsTo(CashBook::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class, 'group_id');
    }

    public function repayment(): BelongsTo
    {
        return $this->belongsTo(LoanRepayment::class, 'repayment_id');
    }
}
