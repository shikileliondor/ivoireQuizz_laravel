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
            $slug = Str::slug($name);
            $city = City::withTrashed()->updateOrCreate(
                ['region_id' => $abidjan->id, 'slug' => $slug],
                ['name' => $name, 'order' => $slug === 'yopougon' ? 1 : $index + 1, 'is_active' => $slug === 'yopougon']
            );
            if ($city->trashed()) {
                $city->restore();
            }
        }
        City::query()->where('region_id', $abidjan->id)->where('slug', '<>', 'yopougon')->update(['is_active' => false]);

        $poro = Region::where('slug', 'poro')->firstOrFail();

        $korhogo = City::withTrashed()->updateOrCreate(
            ['region_id' => $poro->id, 'slug' => 'korhogo'],
            ['name' => 'Korhogo', 'order' => 1, 'is_active' => true]
        );
        if ($korhogo->trashed()) {
            $korhogo->restore();
        }
        City::query()->where('region_id', $poro->id)->where('slug', '<>', 'korhogo')->update(['is_active' => false]);

        $sudComoé = Region::where('slug', 'sud-comoe')->firstOrFail();

        $aboisso = City::withTrashed()->updateOrCreate(
            ['region_id' => $sudComoé->id, 'slug' => 'aboisso'],
            ['name' => 'Aboisso', 'order' => 1, 'is_active' => true]
        );
        if ($aboisso->trashed()) {
            $aboisso->restore();
        }
        City::query()->where('region_id', $sudComoé->id)->where('slug', '<>', 'aboisso')->update(['is_active' => false]);
    }
}
