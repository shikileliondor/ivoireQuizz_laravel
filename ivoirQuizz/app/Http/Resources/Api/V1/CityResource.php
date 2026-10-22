<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $campaignScore = $this->relationLoaded('levels')
            ? $this->levels->sum(fn ($level): int => (int) ($level->progress?->best_score ?? 0))
            : 0;

        return ['id' => $this->id, 'region_id' => $this->region_id, 'name' => $this->name, 'slug' => $this->slug, 'description' => $this->description, 'image_url' => $this->imageUrl(), 'order' => (int) $this->order, 'required_xp' => (int) $this->required_xp, 'campaign_score' => (int) $campaignScore, 'is_unlocked' => (bool) ($this->progress?->is_unlocked ?? false), 'is_completed' => (bool) ($this->progress?->is_completed ?? false), 'stars' => (int) ($this->progress?->stars ?? 0), 'progress_percent' => (float) ($this->progress?->progress_percent ?? 0), 'levels' => LevelResource::collection($this->whenLoaded('levels'))];
    }

    private function imageUrl(): ?string
    {
        $image = trim((string) $this->image);
        if ($image === '') {
            $image = 'cities/'.$this->slug.'.jpg';
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($image)) {
            return null;
        }

        $parts = parse_url($disk->url($image));
        $host = $parts['host'] ?? parse_url((string) config('app.url'), PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return null;
        }

        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = $parts['path'] ?? '/'.ltrim($image, '/');
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        return 'https://'.$host.$port.$path.$query.$fragment;
    }
}
