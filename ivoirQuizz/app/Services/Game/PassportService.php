<?php

namespace App\Services\Game;

use App\Models\Region;
use App\Models\User;
use App\Models\UserPassport;
use Illuminate\Support\Facades\DB;

class PassportService
{
    public function stampRegion(User $user, Region $region): UserPassport
    {
        return DB::transaction(fn (): UserPassport => UserPassport::query()->firstOrCreate(
            ['user_id' => $user->id, 'region_id' => $region->id],
            ['completed_at' => now()]
        ));
    }
}
