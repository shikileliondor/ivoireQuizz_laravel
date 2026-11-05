<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    /** Catégorie du mode Logos, jouable seulement depuis le lancement rapide. */
    public const LOGOS = 'logos';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    /** Identifiant de la catégorie Logos, ou null tant qu'elle n'existe pas. */
    public static function logosId(): ?int
    {
        $id = static::query()->where('slug', self::LOGOS)->value('id');

        return $id === null ? null : (int) $id;
    }
}
