<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category_id',
        'type',
        'question_text',
        'explanation',
        'difficulty',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'difficulty' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the category of the question.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get all options for the question.
     */
    public function options(): HasMany
    {
        return $this->hasMany(Option::class);
    }

    /**
     * Scope a query to only active questions.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query by category ID.
     */
    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Get the correct option for the question.
     */
    public function getCorrectOption(): ?Option
    {
        return $this->options()->where('is_correct', true)->first();
    }

    /**
     * Scope a query to return random questions.
     */
    public function scopeRandom(Builder $query, int $limit = 10): Builder
    {
        return $query->inRandomOrder()->limit($limit);
    }
}
