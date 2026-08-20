<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPaymentAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'supplier_id',
        'supplier_payment_id',
        'product_purchase_id',
        'allocated_amount',
        'created_by',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(SupplierPayment::class, 'supplier_payment_id');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(ProductPurchase::class, 'product_purchase_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
