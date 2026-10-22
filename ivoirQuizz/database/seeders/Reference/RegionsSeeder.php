<?php

namespace Database\Seeders\Reference;

use App\Models\Region;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RegionsSeeder extends Seeder
{
    public function run(): void
    {
        $visibleSlugs = ['abidjan', 'poro', 'sud-comoe'];

        foreach (['Abidjan', 'Yamoussoukro', 'Bouaké', 'Poro', 'Man', 'San Pedro', 'Sud-Comoé'] as $index => $name) {
            $slug = Str::slug($name);
            $region = Region::withTrashed()->where('slug', $slug)->first();

            // Reuse the former Korhogo region row so existing IDs and progress remain valid.
            if (! $region && $slug === 'poro') {
                $region = Region::withTrashed()->where('slug', 'korhogo')->first();
            }

            if ($region) {
                if ($region->trashed()) {
                    $region->restore();
                }

                $region->fill([
                    'name' => $name,
                    'slug' => $slug,
                    'order' => $index + 1,
                    'is_active' => in_array($slug, $visibleSlugs, true),
                ])->save();

                continue;
            }

            Region::create([
                'name' => $name,
                'slug' => $slug,
                'order' => $index + 1,
                'is_active' => in_array($slug, $visibleSlugs, true),
            ]);
        }

        Region::query()->whereNotIn('slug', $visibleSlugs)->update(['is_active' => false]);
    }
}
