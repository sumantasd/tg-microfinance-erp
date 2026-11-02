<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Data Management / Deletion Password Secret Hash
    |--------------------------------------------------------------------------
    |
    | A bcrypt or argon2id hash of the deletion password. Set this in your
    | .env file as DATA_DELETION_PASSWORD_HASH (or DEVELOPER_SECRET_HASH).
    | If null or empty, deletion actions remain strictly locked on the server.
    |
    | Generate using: php artisan tinker --execute="echo password_hash('your-password', PASSWORD_BCRYPT);"
    |
    */
    'secret_hash' => env('DATA_DELETION_PASSWORD_HASH', env('DEVELOPER_SECRET_HASH', null)),

    /*
    |--------------------------------------------------------------------------
    | Session Lifetime (Minutes)
    |--------------------------------------------------------------------------
    |
    | Once authorized, the developer session remains active for this duration
    | unless explicitly revoked or after a cleanup operation executes.
    |
    */
    'session_lifetime_minutes' => (int) env('DEVELOPER_SESSION_LIFETIME_MINUTES', 15),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Maximum allowed failed passphrase attempts per window before locking out.
    |
    */
    'rate_limit_max_attempts' => (int) env('DEVELOPER_RATE_LIMIT_ATTEMPTS', 5),
    'rate_limit_decay_seconds' => (int) env('DEVELOPER_RATE_LIMIT_DECAY', 300), // 5 minutes

    /*
    |--------------------------------------------------------------------------
    | Confirmation Phrase
    |--------------------------------------------------------------------------
    |
    | Exact string the developer must type to confirm data cleanup.
    |
    */
    'confirmation_phrase' => 'CLEAN DEMO DATA',
];

