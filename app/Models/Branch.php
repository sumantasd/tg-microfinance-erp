<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'manager_id',
        'email',
        'phone',
        'alt_phone',
        'address',
        'address_line_2',
        'city',
        'district',
        'state',
        'country',
        'pincode',
        'gstin',
        'vault_cash_limit',
        'current_vault_balance',
        'is_active',
        'is_warehouse',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * Accessor for formatted full branch address.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->address_line_2,
            $this->city,
            $this->district,
            $this->state,
            $this->pincode ? "PIN - {$this->pincode}" : null,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_warehouse' => 'boolean',
            'vault_cash_limit' => 'decimal:4',
            'current_vault_balance' => 'decimal:4',
        ];
    }

    /**
     * Retrieve or auto-create the company's Central Warehouse location.
     */
    public static function getCentralWarehouse(?int $companyId = null): Branch
    {
        $companyId = $companyId ?? (auth()->user()?->company_id ?? 1);

        return self::firstOrCreate(
            ['company_id' => $companyId, 'is_warehouse' => true],
            [
                'name' => 'Central Warehouse',
                'code' => 'CWH-001',
                'phone' => '9999999999',
                'address' => 'Central Warehouse Depot',
                'city' => 'Central Depot',
                'state' => 'Main State',
                'pincode' => '700001',
                'is_active' => true,
            ]
        );
    }

    public function inventoryStocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
