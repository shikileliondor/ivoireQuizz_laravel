<?php

namespace Database\Seeders\Reference;

use App\Models\City;
use App\Models\Region;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CitiesSeeder extends Seeder
{
    public function run(): void
    {
        $abidjan = Region::where('slug', 'abidjan')->firstOrFail();

        foreach (['Cocody', 'Plateau', 'Yopougon', 'Treichville', 'Marcory', 'Abobo', 'Koumassi', 'Port-Bouët'] as $index => $name) {
            $city = City::withTrashed()->updateOrCreate(
                ['region_id' => $abidjan->id, 'slug' => Str::slug($name)],
                ['name' => $name, 'order' => $index + 1, 'is_active' => true]
            );
            if ($city->trashed()) {
                $city->restore();
            }
        }

        $poro = Region::where('slug', 'poro')->firstOrFail();

        $korhogo = City::withTrashed()->updateOrCreate(
            ['region_id' => $poro->id, 'slug' => 'korhogo'],
            ['name' => 'Korhogo', 'order' => 1, 'is_active' => true]
        );
        if ($korhogo->trashed()) {
            $korhogo->restore();
        }

        $sudComoé = Region::where('slug', 'sud-comoe')->firstOrFail();

        $aboisso = City::withTrashed()->updateOrCreate(
            ['region_id' => $sudComoé->id, 'slug' => 'aboisso'],
            ['name' => 'Aboisso', 'order' => 1, 'is_active' => true]
        );
        if ($aboisso->trashed()) {
            $aboisso->restore();
        }
    }
}
