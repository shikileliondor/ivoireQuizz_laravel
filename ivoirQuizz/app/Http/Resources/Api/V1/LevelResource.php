<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LevelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id'=>$this->id,'city_id'=>$this->city_id,'name'=>$this->name,'slug'=>$this->slug,'type'=>$this->type,'difficulty'=>$this->difficulty,'order'=>(int)$this->order,'required_xp'=>(int)$this->required_xp,'questions_count'=>(int)$this->questions_count,'passing_score'=>(int)$this->passing_score,'xp_reward'=>(int)$this->xp_reward,'coins_reward'=>(int)$this->coins_reward,'gems_reward'=>(int)$this->gems_reward,'is_boss'=>(bool)$this->is_boss,'is_unlocked'=>(bool)($this->progress?->is_unlocked ?? false),'is_completed'=>(bool)($this->progress?->is_completed ?? false),'stars'=>(int)($this->progress?->stars ?? 0),'progress_percent'=>(float)($this->progress?->progress_percent ?? 0)];
    }
}
