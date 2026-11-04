<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Historique des notifications push envoyées depuis le back-office.
 */
class PushNotification extends Model
{
    protected $guarded = [];

    protected $casts = [
        'filters' => 'array',
        'devices_count' => 'integer',
        'sent_count' => 'integer',
        'failed_count' => 'integer',
        'removed_count' => 'integer',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
