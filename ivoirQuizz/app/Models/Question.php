<?php

namespace App\Models;

use App\Support\PublicImage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use HasFactory, SoftDeletes;

    /** Énoncé proposé par défaut pour une question de la catégorie Logos. */
    public const LOGO_QUESTION_TEXT = 'Quelle entreprise utilise ce logo ?';

    protected $guarded = [];

    /** La source d'une image ne sert qu'à l'admin. */
    protected $hidden = ['image_source'];

    protected $casts = [
        'points' => 'integer',
        'source_version' => 'integer',
        'xp_reward' => 'integer',
        'time_limit' => 'integer',
        'is_active' => 'boolean',
    ];

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    public function gameSessionAnswers(): HasMany
    {
        return $this->hasMany(GameSessionAnswer::class);
    }

    public function questionReports(): HasMany
    {
        return $this->hasMany(QuestionReport::class);
    }

    /** Une question de type image sans image ne peut pas être jouée. */
    public function scopeDisplayable(Builder $query): Builder
    {
        return $query->where(fn (Builder $query) => $query->where('type', '!=', 'image')->orWhere(
            fn (Builder $query) => $query->whereNotNull('image')->where('image', '!=', ''),
        ));
    }

    /** Les logos restent dans leur catégorie, où le joueur voit la mention légale. */
    public function scopeWithoutLogos(Builder $query): Builder
    {
        return $query->whereDoesntHave('category', fn (Builder $category) => $category->where('slug', Category::LOGOS));
    }

    public function imageUrl(): ?string
    {
        return PublicImage::url($this->image);
    }

    public function isDisplayable(): bool
    {
        return $this->type !== 'image' || $this->imageUrl() !== null;
    }
}
