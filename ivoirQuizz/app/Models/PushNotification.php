<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Notification push envoyée (ou programmée) depuis le back-office, envoyée par lots en file d'attente.
 */
class PushNotification extends Model
{
    public const STATUSES = ['scheduled', 'queued', 'sending', 'sent', 'failed', 'canceled'];

    /** Statuts dans lesquels l'envoi peut encore être annulé. */
    public const CANCELABLE = ['scheduled', 'queued', 'sending'];

    protected $guarded = [];

    protected $casts = [
        'filters' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'devices_count' => 'integer',
        'sent_count' => 'integer',
        'failed_count' => 'integer',
        'removed_count' => 'integer',
        'batches_total' => 'integer',
        'batches_done' => 'integer',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function isCancelable(): bool
    {
        return in_array($this->status, self::CANCELABLE, true);
    }

    /**
     * Ajoute le résultat d'un lot ; le dernier lot termine l'envoi.
     *
     * @param  array{sent: int, failed: int, removed: int}  $result
     */
    public function recordBatch(int $devices, array $result, ?string $error = null): void
    {
        static::query()->whereKey($this->id)->incrementEach([
            'devices_count' => $devices,
            'sent_count' => $result['sent'],
            'failed_count' => $result['failed'],
            'removed_count' => $result['removed'],
            'batches_done' => 1,
        ], $error === null ? [] : ['error' => Str::limit($error, 490)]);

        $fresh = $this->fresh();
        if ($fresh->status === 'sending' && $fresh->batches_done >= $fresh->batches_total) {
            $fresh->update([
                'status' => $fresh->error !== null && $fresh->sent_count === 0 ? 'failed' : 'sent',
                'finished_at' => now(),
            ]);
        }
    }
}
