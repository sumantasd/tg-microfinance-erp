<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiIdempotencyKey extends Model
{
    protected $table = 'api_idempotency_keys';

    protected $fillable = [
        'idempotency_key',
        'endpoint',
        'user_id',
        'company_id',
        'branch_id',
        'request_hash',
        'status',
        'response_code',
        'response_body',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
