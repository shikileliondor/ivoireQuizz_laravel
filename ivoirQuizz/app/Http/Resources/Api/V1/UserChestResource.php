<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserChestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id'=>$this->id,'status'=>$this->status,'opened_at'=>$this->opened_at?->toISOString(),'chest'=>$this->whenLoaded('chest', fn()=>['id'=>$this->chest->id,'name'=>$this->chest->name,'type'=>$this->chest->type,'image'=>$this->chest->image])];
    }
}
