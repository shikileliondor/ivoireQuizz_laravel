<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FriendshipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $userId = $request->user()?->id;

        return [
            'id' => $this->id,
            'status' => $this->status,
            // Saves the client from comparing ids to know which way it points.
            'direction' => $this->requester_id === $userId ? 'sent' : 'received',
            'requester' => new FriendResource($this->whenLoaded('requester')),
            'receiver' => new FriendResource($this->whenLoaded('receiver')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
