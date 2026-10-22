<?php

namespace Database\Seeders\Reference;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategoriesSeeder extends Seeder
{
    private const CATEGORIES = [
        'histoire' => ['name' => 'Histoire'],
        'geographie' => ['name' => "G\u{00E9}ographie", 'legacy_slug' => 'gographie'],
        'gastronomie' => ['name' => 'Gastronomie'],
        'sport' => ['name' => 'Sport'],
        'culture' => ['name' => 'Culture'],
        'monuments' => ['name' => 'Monuments'],
        'personnalites' => ['name' => "Personnalit\u{00E9}s", 'legacy_slug' => 'personnalits'],
        'agriculture' => ['name' => 'Agriculture'],
        'economie' => ['name' => "\u{00C9}conomie"],
        'energie' => ['name' => "\u{00C9}nergie"],
        'artisanat' => ['name' => 'Artisanat'],
        'education' => ['name' => "\u{00C9}ducation"],
        'musique' => ['name' => 'Musique'],
        'patrimoine' => ['name' => 'Patrimoine'],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $slug => $definition) {
            $category = Category::query()->where('slug', $slug)->first();

            if (! $category && isset($definition['legacy_slug'])) {
                $category = Category::query()->where('slug', $definition['legacy_slug'])->first();
            }

            if ($category) {
                $category->fill([
                    'slug' => $slug,
                    'name' => $definition['name'],
                    'is_active' => true,
                ])->save();

                continue;
            }

            Category::create([
                'slug' => $slug,
                'name' => $definition['name'],
                'is_active' => true,
            ]);
        }
    }
}
